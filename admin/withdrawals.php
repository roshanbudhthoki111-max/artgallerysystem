<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wId    = (int)$_POST['withdrawal_id'];
    $action = sanitize($_POST['action'] ?? '');
    $note   = sanitize($_POST['admin_note'] ?? '');

    if (in_array($action, ['approved','rejected','completed'])) {
        $stmt = $conn->prepare("UPDATE withdrawal_requests SET status=?, admin_note=? WHERE id=?");
        $stmt->bind_param("ssi", $action, $note, $wId);
        $stmt->execute();

        if ($action === 'completed') {
            // Add transaction
            $w = $conn->query("SELECT * FROM withdrawal_requests WHERE id=$wId")->fetch_assoc();
            $conn->query("INSERT INTO transactions (user_id, amount, type, status, notes) VALUES ({$w['artist_id']}, {$w['amount']}, 'withdrawal', 'completed', 'Withdrawal processed')");
        }

        setFlash('success', 'Withdrawal ' . $action . '!');
    }
    header("Location: /art-gallery/admin/withdrawals.php");
    exit;
}

$withdrawals = $conn->query("SELECT wr.*, u.name as artist_name, u.email as artist_email
    FROM withdrawal_requests wr JOIN users u ON wr.artist_id=u.id
    ORDER BY wr.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Withdrawal Requests';
require_once __DIR__ . '/../includes/header.php';
showFlash();
?>

<div class="dash-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="dash-main">
        <h2 style="font-family:var(--font-display);font-size:1.6rem;font-weight:400;margin-bottom:1.5rem">Withdrawal Requests</h2>

        <?php if (empty($withdrawals)): ?>
        <div class="card"><div class="card-body text-center py-5 text-muted">No withdrawal requests.</div></div>
        <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($withdrawals as $w): ?>
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center g-3">
                        <div class="col-md-3">
                            <div style="font-weight:600"><?= sanitize($w['artist_name']) ?></div>
                            <div style="font-size:.8rem;color:var(--ink-muted)"><?= sanitize($w['artist_email']) ?></div>
                            <div style="font-size:.75rem;color:var(--ink-muted)"><?= date('M j, Y H:i', strtotime($w['created_at'])) ?></div>
                        </div>
                        <div class="col-md-2">
                            <div style="font-family:var(--font-display);font-size:1.3rem;font-weight:600;color:var(--gold)">
                                NPR <?= number_format($w['amount'], 2) ?>
                            </div>
                            <div style="font-size:.8rem;color:var(--ink-muted)"><?= ucfirst($w['payment_method']) ?></div>
                        </div>
                        <div class="col-md-3">
                            <div style="font-size:.8rem;font-weight:600;color:var(--ink-muted);margin-bottom:.25rem">Account Details:</div>
                            <div style="font-size:.85rem;word-break:break-word"><?= sanitize($w['account_details']) ?></div>
                        </div>
                        <div class="col-md-1">
                            <span class="badge status-<?= $w['status']==='completed'?'delivered':$w['status'] ?>"><?= ucfirst($w['status']) ?></span>
                        </div>
                        <div class="col-md-3">
                            <?php if ($w['status'] === 'pending'): ?>
                            <form method="POST" class="d-flex flex-column gap-2">
                                <input type="hidden" name="withdrawal_id" value="<?= $w['id'] ?>">
                                <input type="text" name="admin_note" class="form-control form-control-sm" placeholder="Admin note (optional)">
                                <div class="d-flex gap-1">
                                    <button name="action" value="approved" class="btn btn-sm flex-grow-1" style="background:var(--success);color:white">Approve</button>
                                    <button name="action" value="completed" class="btn btn-sm flex-grow-1" style="background:var(--gold);color:white">Mark Paid</button>
                                    <button name="action" value="rejected" class="btn btn-sm btn-outline-danger">Reject</button>
                                </div>
                            </form>
                            <?php elseif ($w['status'] === 'approved'): ?>
                            <form method="POST">
                                <input type="hidden" name="withdrawal_id" value="<?= $w['id'] ?>">
                                <button name="action" value="completed" class="btn btn-sm w-100" style="background:var(--gold);color:white">
                                    <i class="bi bi-check2 me-1"></i>Mark as Paid
                                </button>
                            </form>
                            <?php endif; ?>
                            <?php if ($w['admin_note']): ?>
                            <div style="font-size:.75rem;color:var(--ink-muted);margin-top:.5rem"><i class="bi bi-chat me-1"></i><?= sanitize($w['admin_note']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
