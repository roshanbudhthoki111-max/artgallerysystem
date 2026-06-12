<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('artist');
$user = getCurrentUser();
$artistId = $user['id'];

// Handle withdrawal request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdraw_amount'])) {
    $amount        = (float)$_POST['withdraw_amount'];
    $method        = sanitize($_POST['payment_method'] ?? 'bank');
    $accountDetails = sanitize($_POST['account_details'] ?? '');

    // Available balance
    $earned   = (float)$conn->query("SELECT COALESCE(SUM(artist_earning),0) as e FROM orders WHERE artist_id=$artistId AND status='delivered'")->fetch_assoc()['e'];
    $withdrawn = (float)$conn->query("SELECT COALESCE(SUM(amount),0) as w FROM withdrawal_requests WHERE artist_id=$artistId AND status IN ('approved','completed')")->fetch_assoc()['w'];
    $available = $earned - $withdrawn;

    if ($amount <= 0 || $amount > $available) {
        setFlash('error', 'Invalid withdrawal amount.');
    } elseif (empty($accountDetails)) {
        setFlash('error', 'Please provide account details.');
    } else {
        $stmt = $conn->prepare("INSERT INTO withdrawal_requests (artist_id, amount, payment_method, account_details) VALUES (?,?,?,?)");
        $stmt->bind_param("idss", $artistId, $amount, $method, $accountDetails);
        $stmt->execute();
        setFlash('success', 'Withdrawal request submitted! Admin will process within 2-3 business days.');
    }
    header("Location: /art-gallery/artist/earnings.php");
    exit;
}

// Stats
$totalEarned   = (float)$conn->query("SELECT COALESCE(SUM(artist_earning),0) as e FROM orders WHERE artist_id=$artistId AND status='delivered'")->fetch_assoc()['e'];
$totalWithdrawn = (float)$conn->query("SELECT COALESCE(SUM(amount),0) as w FROM withdrawal_requests WHERE artist_id=$artistId AND status IN ('approved','completed')")->fetch_assoc()['w'];
$pendingWithdrawal = (float)$conn->query("SELECT COALESCE(SUM(amount),0) as p FROM withdrawal_requests WHERE artist_id=$artistId AND status='pending'")->fetch_assoc()['p'];
$available = $totalEarned - $totalWithdrawn - $pendingWithdrawal;

// Transactions
$transactions = $conn->query("SELECT t.*, o.artwork_id, a.title as artwork_title 
    FROM transactions t LEFT JOIN orders o ON t.order_id=o.id LEFT JOIN artworks a ON o.artwork_id=a.id
    WHERE t.user_id=$artistId ORDER BY t.created_at DESC LIMIT 20")->fetch_all(MYSQLI_ASSOC);

// Withdrawal history
$withdrawals = $conn->query("SELECT * FROM withdrawal_requests WHERE artist_id=$artistId ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Earnings';
require_once __DIR__ . '/../includes/header.php';
showFlash();
?>

<div class="dash-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="dash-main">
        <h2 style="font-family:var(--font-display);font-size:1.6rem;font-weight:400;margin-bottom:1.5rem">Earnings & Payments</h2>

        <!-- Balance cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="bi bi-currency-rupee"></i></div>
                    <div class="stat-value" style="font-size:1.5rem">NPR <?= number_format($totalEarned, 2) ?></div>
                    <div class="stat-label">Total Earned</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="bi bi-wallet2"></i></div>
                    <div class="stat-value" style="font-size:1.5rem">NPR <?= number_format($available, 2) ?></div>
                    <div class="stat-label">Available Balance</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="bi bi-arrow-up-circle"></i></div>
                    <div class="stat-value" style="font-size:1.5rem">NPR <?= number_format($totalWithdrawn, 2) ?></div>
                    <div class="stat-label">Total Withdrawn</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon red"><i class="bi bi-hourglass-split"></i></div>
                    <div class="stat-value" style="font-size:1.5rem">NPR <?= number_format($pendingWithdrawal, 2) ?></div>
                    <div class="stat-label">Pending Withdrawal</div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Withdrawal form -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">Request Withdrawal</div>
                    <div class="card-body">
                        <?php if ($available < 100): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Minimum withdrawal is NPR 100. Your available balance is NPR <?= number_format($available, 2) ?>.
                        </div>
                        <?php else: ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Amount (NPR)</label>
                                <div class="input-group">
                                    <span class="input-group-text">NPR</span>
                                    <input type="number" name="withdraw_amount" class="form-control"
                                           min="100" max="<?= $available ?>" step="0.01"
                                           placeholder="<?= number_format($available, 2) ?>">
                                </div>
                                <div class="form-text">Available: NPR <?= number_format($available, 2) ?></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_method" class="form-select">
                                    <option value="esewa">eSewa</option>
                                    <option value="khalti">Khalti</option>
                                    <option value="bank">Bank Transfer</option>
                                    <option value="connectips">ConnectIPS</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Account Details</label>
                                <textarea name="account_details" class="form-control" rows="3"
                                          placeholder="eSewa number / Account number / IBAN…" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-dark w-100">Request Withdrawal</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Withdrawal history -->
                <?php if (!empty($withdrawals)): ?>
                <div class="card mt-3">
                    <div class="card-header">Withdrawal History</div>
                    <div class="card-body p-0">
                        <?php foreach ($withdrawals as $w): ?>
                        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                            <div>
                                <div style="font-size:.875rem;font-weight:500">NPR <?= number_format($w['amount'], 2) ?></div>
                                <div style="font-size:.75rem;color:var(--ink-muted)"><?= ucfirst($w['payment_method']) ?> · <?= date('M j', strtotime($w['created_at'])) ?></div>
                            </div>
                            <span class="badge status-<?= $w['status'] === 'completed' ? 'delivered' : $w['status'] ?>"><?= ucfirst($w['status']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Transaction history -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">Transaction History</div>
                    <?php if (empty($transactions)): ?>
                    <div class="card-body text-center text-muted py-4">No transactions yet.</div>
                    <?php else: ?>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead><tr>
                                <th>Type</th><th>Description</th><th>Amount</th><th>Status</th><th>Date</th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td>
                                    <span class="badge <?= $t['type']==='sale' ? 'bg-success' : ($t['type']==='withdrawal' ? 'bg-primary' : 'bg-secondary') ?> bg-opacity-10 text-dark" style="font-size:.7rem">
                                        <?= ucfirst($t['type']) ?>
                                    </span>
                                </td>
                                <td style="font-size:.875rem">
                                    <?= $t['artwork_title'] ? sanitize('Sale: '.$t['artwork_title']) : sanitize($t['notes'] ?? ucfirst($t['type'])) ?>
                                </td>
                                <td style="font-size:.875rem;font-weight:600;color:<?= $t['type']==='sale'?'var(--success)':'var(--danger)' ?>">
                                    <?= $t['type']==='sale' ? '+' : '-' ?>NPR <?= number_format($t['amount'], 2) ?>
                                </td>
                                <td><span class="badge status-<?= $t['status']==='completed'?'delivered':$t['status'] ?>"><?= ucfirst($t['status']) ?></span></td>
                                <td style="font-size:.8rem;color:var(--ink-muted)"><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
