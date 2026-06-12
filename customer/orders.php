<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('customer');
$user = getCurrentUser();
$userId = $user['id'];

$orders = $conn->query("SELECT o.*, a.title as artwork_title, a.image as artwork_image, u.name as artist_name
    FROM orders o JOIN artworks a ON o.artwork_id=a.id JOIN users u ON a.artist_id=u.id
    WHERE o.customer_id=$userId ORDER BY o.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'My Orders';
require_once __DIR__ . '/../includes/header.php';
showFlash();
?>

<div class="dash-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="dash-main">
        <h2 style="font-family:var(--font-display);font-size:1.6rem;font-weight:400;margin-bottom:1.5rem">My Orders</h2>

        <?php if (empty($orders)): ?>
        <div class="card"><div class="card-body text-center py-5">
            <i class="bi bi-box-seam" style="font-size:3rem;color:var(--border)"></i>
            <h5 class="mt-3" style="font-family:var(--font-display);font-weight:400">No orders yet</h5>
            <p class="text-muted">Start exploring art you love.</p>
            <a href="/art-gallery/gallery.php" class="btn btn-dark">Browse Gallery</a>
        </div></div>
        <?php else: ?>
        <div class="d-flex flex-column gap-3">
            <?php foreach ($orders as $o): ?>
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center g-3">
                        <div class="col-md-1">
                            <img src="<?= artworkImage($o['artwork_image']) ?>"
                                 style="width:60px;height:60px;object-fit:cover;border-radius:var(--radius);" alt="">
                        </div>
                        <div class="col-md-4">
                            <div style="font-weight:600"><?= sanitize($o['artwork_title']) ?></div>
                            <div style="font-size:.8rem;color:var(--ink-muted)">by <?= sanitize($o['artist_name']) ?></div>
                            <div style="font-size:.75rem;color:var(--ink-muted)">Order #<?= $o['id'] ?></div>
                        </div>
                        <div class="col-md-2">
                            <div style="font-family:var(--font-display);font-size:1.15rem;font-weight:600"><?= formatPrice($o['total_price']) ?></div>
                        </div>
                        <div class="col-md-2">
                            <span class="badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span>
                        </div>
                        <div class="col-md-2" style="font-size:.8rem;color:var(--ink-muted)">
                            <?= date('M j, Y', strtotime($o['created_at'])) ?>
                        </div>
                        <div class="col-md-1">
                            <a href="/art-gallery/customer/order-detail.php?id=<?= $o['id'] ?>" class="btn btn-icon btn-sm" title="View Details">
                                <i class="bi bi-eye"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <?php
                    $steps = ['pending'=>0,'confirmed'=>1,'shipped'=>2,'delivered'=>3,'cancelled'=>-1];
                    $step  = $steps[$o['status']] ?? 0;
                    ?>
                    <?php if ($o['status'] !== 'cancelled' && $o['status'] !== 'refunded'): ?>
                    <div class="mt-3 pt-3 border-top">
                        <div class="d-flex justify-content-between mb-1" style="font-size:.7rem;color:var(--ink-muted)">
                            <span>Order Placed</span><span>Confirmed</span><span>Shipped</span><span>Delivered</span>
                        </div>
                        <div class="progress" style="height:4px;border-radius:2px;">
                            <div class="progress-bar" style="width:<?= ($step/3)*100 ?>%;background:var(--gold)"></div>
                        </div>
                    </div>
                    <?php elseif ($o['status'] === 'cancelled'): ?>
                    <div class="mt-2 text-danger" style="font-size:.8rem;"><i class="bi bi-x-circle me-1"></i>Order was cancelled.</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
