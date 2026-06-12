<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('customer');
$user = getCurrentUser();
$userId = $user['id'];

// Handle remove
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_artwork_id'])) {
    $artId = (int)$_POST['remove_artwork_id'];
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id=? AND artwork_id=?");
    $stmt->bind_param("ii", $userId, $artId);
    $stmt->execute();
    setFlash('success', 'Removed from wishlist.');
    header("Location: /art-gallery/customer/wishlist.php");
    exit;
}

$wishlist = $conn->query("SELECT w.*, a.title, a.price, a.image, a.availability, a.likes_count, a.views,
    u.name as artist_name FROM wishlist w JOIN artworks a ON w.artwork_id=a.id JOIN users u ON a.artist_id=u.id
    WHERE w.user_id=$userId ORDER BY w.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'My Wishlist';
require_once __DIR__ . '/../includes/header.php';
showFlash();
?>

<div class="dash-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="dash-main">
        <h2 style="font-family:var(--font-display);font-size:1.6rem;font-weight:400;margin-bottom:1.5rem">
            My Wishlist <span style="font-size:1rem;color:var(--ink-muted)">(<?= count($wishlist) ?> items)</span>
        </h2>

        <?php if (empty($wishlist)): ?>
        <div class="card"><div class="card-body text-center py-5">
            <i class="bi bi-heart" style="font-size:3rem;color:var(--border)"></i>
            <h5 class="mt-3" style="font-family:var(--font-display);font-weight:400">Your wishlist is empty</h5>
            <p class="text-muted">Save artworks you love to revisit later.</p>
            <a href="/art-gallery/gallery.php" class="btn btn-dark">Browse Gallery</a>
        </div></div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($wishlist as $item): ?>
            <div class="col-sm-6 col-lg-4">
                <div class="artwork-card h-100">
                    <div class="artwork-card-img" onclick="location.href='/art-gallery/artwork.php?id=<?= $item['artwork_id'] ?>'">
                        <img src="<?= artworkImage($item['image']) ?>" alt="<?= sanitize($item['title']) ?>" loading="lazy">
                        <?php if ($item['availability'] !== 'available'): ?>
                        <span class="artwork-badge sold">Sold</span>
                        <?php endif; ?>
                    </div>
                    <div class="artwork-card-body">
                        <div class="artwork-title"><?= sanitize($item['title']) ?></div>
                        <div class="artwork-artist">by <?= sanitize($item['artist_name']) ?></div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <div class="artwork-price"><?= formatPrice($item['price']) ?></div>
                            <div class="artwork-meta">
                                <span><i class="bi bi-eye me-1"></i><?= $item['views'] ?></span>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <?php if ($item['availability'] === 'available'): ?>
                            <form method="POST" action="/art-gallery/customer/cart.php" class="flex-grow-1">
                                <input type="hidden" name="add_artwork_id" value="<?= $item['artwork_id'] ?>">
                                <input type="hidden" name="post_action" value="add_to_cart">
                                <button type="submit" class="btn btn-dark btn-sm w-100">
                                    <i class="bi bi-bag-plus me-1"></i>Add to Cart
                                </button>
                            </form>
                            <?php else: ?>
                            <span class="btn btn-outline-secondary btn-sm flex-grow-1 disabled">Unavailable</span>
                            <?php endif; ?>
                            <form method="POST">
                                <input type="hidden" name="remove_artwork_id" value="<?= $item['artwork_id'] ?>">
                                <button type="submit" class="btn btn-icon btn-sm" style="color:var(--danger)" title="Remove from wishlist">
                                    <i class="bi bi-heart-fill"></i>
                                </button>
                            </form>
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
