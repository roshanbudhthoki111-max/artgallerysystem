<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('artist');
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['offer_id'])) {
    $offerId = (int)$_POST['offer_id'];
    $action  = sanitize($_POST['offer_action'] ?? '');
    if (in_array($action, ['accepted','rejected'])) {
        $stmt = $conn->prepare("UPDATE offers o JOIN artworks a ON o.artwork_id=a.id SET o.status=? WHERE o.id=? AND a.artist_id=?");
        $stmt->bind_param("sii", $action, $offerId, $user['id']);
        $stmt->execute();
        setFlash('success', 'Offer ' . $action . '.');
    }
    header("Location: /art-gallery/artist/offers.php");
    exit;
}

$offers = $conn->query("SELECT o.*, a.title as artwork_title, a.price as listed_price, a.image,
    u.name as buyer_name, u.email as buyer_email
    FROM offers o JOIN artworks a ON o.artwork_id=a.id JOIN users u ON o.customer_id=u.id
    WHERE a.artist_id={$user['id']} ORDER BY o.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Offers';
require_once __DIR__ . '/../includes/header.php';
showFlash();
?>

<div class="dash-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="dash-main">
        <h2 style="font-family:var(--font-display);font-size:1.6rem;font-weight:400;margin-bottom:1.5rem">Offers Received</h2>

        <?php if (empty($offers)): ?>
        <div class="card"><div class="card-body text-center py-5">
            <i class="bi bi-chat-text" style="font-size:3rem;color:var(--border)"></i>
            <p class="mt-3 text-muted">No offers received yet.</p>
        </div></div>
        <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($offers as $o): ?>
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center g-3">
                        <div class="col-md-1">
                            <img src="<?= artworkImage($o['image']) ?>" style="width:50px;height:50px;object-fit:cover;border-radius:var(--radius-sm);" alt="">
                        </div>
                        <div class="col-md-3">
                            <div style="font-weight:600;font-size:.9rem"><?= sanitize($o['artwork_title']) ?></div>
                            <div style="font-size:.8rem;color:var(--ink-muted)">Listed: <?= formatPrice($o['listed_price']) ?></div>
                        </div>
                        <div class="col-md-3">
                            <div style="font-size:.85rem;color:var(--ink-muted)">From</div>
                            <div style="font-weight:500;font-size:.9rem"><?= sanitize($o['buyer_name']) ?></div>
                            <div style="font-size:.75rem;color:var(--ink-muted)"><?= timeAgo($o['created_at']) ?></div>
                        </div>
                        <div class="col-md-2">
                            <div style="font-family:var(--font-display);font-size:1.2rem;font-weight:600;color:var(--gold)"><?= formatPrice($o['offer_price']) ?></div>
                            <?php $diff = (($o['offer_price'] - $o['listed_price'])/$o['listed_price'])*100; ?>
                            <div style="font-size:.75rem;color:<?= $diff >= 0 ? 'var(--success)' : 'var(--danger)' ?>">
                                <?= $diff >= 0 ? '+' : '' ?><?= round($diff) ?>% of listed
                            </div>
                        </div>
                        <div class="col-md-1">
                            <span class="badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span>
                        </div>
                        <div class="col-md-2">
                            <?php if ($o['status'] === 'pending'): ?>
                            <form method="POST" class="d-flex gap-1">
                                <input type="hidden" name="offer_id" value="<?= $o['id'] ?>">
                                <button name="offer_action" value="accepted" class="btn btn-sm" style="background:var(--success);color:white">Accept</button>
                                <button name="offer_action" value="rejected" class="btn btn-sm btn-outline-danger">Reject</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($o['message']): ?>
                    <div class="mt-2 pt-2 border-top" style="font-size:.85rem;color:var(--ink-light)">
                        <i class="bi bi-chat-quote me-1"></i><em><?= sanitize($o['message']) ?></em>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
