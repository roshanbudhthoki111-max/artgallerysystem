<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('artist');
$user = getCurrentUser();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $orderId   = (int)$_POST['order_id'];
    $newStatus = sanitize($_POST['new_status'] ?? '');
    $allowed   = ['confirmed','shipped','delivered','cancelled'];
    if (in_array($newStatus, $allowed)) {
        $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=? AND artist_id=?");
        $stmt->bind_param("sii", $newStatus, $orderId, $user['id']);
        $stmt->execute();

        // If delivered, calculate earnings
        if ($newStatus === 'delivered') {
            $order = $conn->query("SELECT * FROM orders WHERE id=$orderId")->fetch_assoc();
            $commission = getSetting('commission_percentage') ?? 10;
            $commAmt = $order['total_price'] * $commission / 100;
            $earning = $order['total_price'] - $commAmt;
            $conn->query("UPDATE orders SET commission_amount=$commAmt, artist_earning=$earning WHERE id=$orderId");
            // Add transaction
            $conn->query("INSERT INTO transactions (user_id, order_id, amount, type, status) VALUES ({$user['id']}, $orderId, $earning, 'sale', 'completed')");
        }

        setFlash('success', 'Order status updated to ' . ucfirst($newStatus));
    }
    header("Location: /art-gallery/artist/orders.php");
    exit;
}

$orders = $conn->query("SELECT o.*, a.title as artwork_title, a.image as artwork_image, u.name as buyer_name, u.email as buyer_email
    FROM orders o JOIN artworks a ON o.artwork_id=a.id JOIN users u ON o.customer_id=u.id
    WHERE o.artist_id={$user['id']} ORDER BY o.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Orders';
require_once __DIR__ . '/../includes/header.php';
showFlash();
?>

<div class="dash-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="dash-main">
        <h2 style="font-family:var(--font-display);font-size:1.6rem;font-weight:400;margin-bottom:1.5rem">Orders</h2>

        <?php if (empty($orders)): ?>
        <div class="card"><div class="card-body text-center py-5">
            <i class="bi bi-box-seam" style="font-size:3rem;color:var(--border)"></i>
            <p class="mt-3 text-muted">No orders yet.</p>
        </div></div>
        <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($orders as $o): ?>
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center g-3">
                        <div class="col-md-1">
                            <img src="<?= artworkImage($o['artwork_image']) ?>" style="width:56px;height:56px;object-fit:cover;border-radius:var(--radius-sm);" alt="">
                        </div>
                        <div class="col-md-4">
                            <div style="font-weight:600;font-size:.9rem"><?= sanitize($o['artwork_title']) ?></div>
                            <div style="font-size:.8rem;color:var(--ink-muted)">Buyer: <?= sanitize($o['buyer_name']) ?></div>
                            <div style="font-size:.75rem;color:var(--ink-muted)"><?= $o['buyer_email'] ?></div>
                        </div>
                        <div class="col-md-2">
                            <div style="font-family:var(--font-display);font-size:1.1rem;font-weight:600"><?= formatPrice($o['total_price']) ?></div>
                            <div style="font-size:.75rem;color:var(--ink-muted)"><?= date('M j, Y', strtotime($o['created_at'])) ?></div>
                        </div>
                        <div class="col-md-2">
                            <span class="badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span>
                        </div>
                        <div class="col-md-3">
                            <?php if (in_array($o['status'], ['pending','confirmed','shipped'])): ?>
                            <form method="POST" class="d-flex gap-1">
                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                <select name="new_status" class="form-select form-select-sm" style="font-size:.8rem">
                                    <?php if ($o['status']==='pending'): ?>
                                    <option value="confirmed">Confirm</option>
                                    <option value="cancelled">Cancel</option>
                                    <?php elseif ($o['status']==='confirmed'): ?>
                                    <option value="shipped">Mark Shipped</option>
                                    <option value="cancelled">Cancel</option>
                                    <?php elseif ($o['status']==='shipped'): ?>
                                    <option value="delivered">Mark Delivered</option>
                                    <?php endif; ?>
                                </select>
                                <button type="submit" class="btn btn-dark btn-sm">Update</button>
                            </form>
                            <?php else: ?>
                            <span class="text-muted small">No actions available</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($o['shipping_address']): ?>
                    <div class="mt-2 pt-2 border-top" style="font-size:.8rem;color:var(--ink-muted)">
                        <i class="bi bi-geo-alt me-1"></i><?= sanitize($o['shipping_address']) ?>
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
