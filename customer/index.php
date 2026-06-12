<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('customer');
$user = getCurrentUser();
$userId = $user['id'];

$totalOrders   = $conn->query("SELECT COUNT(*) as c FROM orders WHERE customer_id=$userId")->fetch_assoc()['c'];
$totalSpent    = (float)$conn->query("SELECT COALESCE(SUM(total_price),0) as s FROM orders WHERE customer_id=$userId AND status='delivered'")->fetch_assoc()['s'];
$wishlistCount = $conn->query("SELECT COUNT(*) as c FROM wishlist WHERE user_id=$userId")->fetch_assoc()['c'];
$cartCount     = getCartCount($userId);

$recentOrders = $conn->query("SELECT o.*, a.title as artwork_title, a.image as artwork_image, u.name as artist_name
    FROM orders o JOIN artworks a ON o.artwork_id=a.id JOIN users u ON a.artist_id=u.id
    WHERE o.customer_id=$userId ORDER BY o.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'My Dashboard';
require_once __DIR__ . '/../includes/header.php';
showFlash();
?>

<div class="dash-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="dash-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 style="font-family:var(--font-display);font-size:1.8rem;font-weight:400;margin-bottom:.15rem">
                    Welcome, <?= sanitize(explode(' ', $user['name'])[0]) ?>
                </h2>
                <p class="text-muted mb-0" style="font-size:.875rem">Your art collection dashboard</p>
            </div>
            <a href="/art-gallery/gallery.php" class="btn btn-dark">
                <i class="bi bi-compass me-1"></i>Browse Gallery
            </a>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="bi bi-box-seam"></i></div>
                    <div class="stat-value"><?= $totalOrders ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="bi bi-currency-rupee"></i></div>
                    <div class="stat-value" style="font-size:1.4rem">NPR <?= number_format($totalSpent) ?></div>
                    <div class="stat-label">Total Spent</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon red"><i class="bi bi-heart"></i></div>
                    <div class="stat-value"><?= $wishlistCount ?></div>
                    <div class="stat-label">Wishlist Items</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="bi bi-bag"></i></div>
                    <div class="stat-value"><?= $cartCount ?></div>
                    <div class="stat-label">Items in Cart</div>
                </div>
            </div>
        </div>

        <!-- Quick actions -->
        <div class="row g-3 mb-4">
            <?php $actions = [
                ['href'=>'/art-gallery/gallery.php', 'icon'=>'bi-compass', 'label'=>'Browse Art'],
                ['href'=>'/art-gallery/customer/wishlist.php', 'icon'=>'bi-heart', 'label'=>'My Wishlist'],
                ['href'=>'/art-gallery/customer/cart.php', 'icon'=>'bi-bag', 'label'=>'My Cart'],
                ['href'=>'/art-gallery/customer/orders.php', 'icon'=>'bi-box-seam', 'label'=>'My Orders'],
            ]; foreach ($actions as $a): ?>
            <div class="col-6 col-md-3">
                <a href="<?= $a['href'] ?>" class="card text-center text-decoration-none p-3" style="transition:all var(--trans);display:block;">
                    <i class="bi <?= $a['icon'] ?>" style="font-size:1.5rem;color:var(--gold)"></i>
                    <div style="font-size:.85rem;font-weight:500;margin-top:.5rem;color:var(--ink)"><?= $a['label'] ?></div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Recent Orders -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Recent Orders</span>
                <a href="/art-gallery/customer/orders.php" class="btn btn-sm btn-outline-dark">View All</a>
            </div>
            <?php if (empty($recentOrders)): ?>
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-bag" style="font-size:2.5rem;color:var(--border)"></i>
                <p class="mt-2">No orders yet. <a href="/art-gallery/gallery.php" style="color:var(--gold)">Start exploring art!</a></p>
            </div>
            <?php else: ?>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Artwork</th><th>Artist</th><th>Amount</th><th>Status</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($recentOrders as $o): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= artworkImage($o['artwork_image']) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:var(--radius-sm);" alt="">
                                <span style="font-size:.875rem"><?= truncate(sanitize($o['artwork_title']), 25) ?></span>
                            </div>
                        </td>
                        <td style="font-size:.875rem"><?= sanitize($o['artist_name']) ?></td>
                        <td style="font-size:.875rem;font-weight:500"><?= formatPrice($o['total_price']) ?></td>
                        <td><span class="badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                        <td style="font-size:.8rem;color:var(--ink-muted)"><?= date('M j', strtotime($o['created_at'])) ?></td>
                        <td><a href="/art-gallery/customer/order-detail.php?id=<?= $o['id'] ?>" class="btn btn-icon btn-sm"><i class="bi bi-eye"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
