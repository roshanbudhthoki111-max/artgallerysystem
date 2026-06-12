<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('customer');
$user = getCurrentUser();
$userId = $user['id'];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['post_action'] ?? $_POST['add_action'] ?? '';

    if ($action === 'add_to_cart' || isset($_POST['add_artwork_id'])) {
        $artworkId = (int)($_POST['add_artwork_id'] ?? $_POST['artwork_id'] ?? 0);
        if ($artworkId) {
            $stmt = $conn->prepare("INSERT INTO cart (user_id, artwork_id) VALUES (?,?) ON DUPLICATE KEY UPDATE quantity=1");
            $stmt->bind_param("ii", $userId, $artworkId);
            $stmt->execute();
            setFlash('success', 'Added to cart!');
        }
    }

    if ($action === 'remove') {
        $artworkId = (int)$_POST['artwork_id'];
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id=? AND artwork_id=?");
        $stmt->bind_param("ii", $userId, $artworkId);
        $stmt->execute();
        setFlash('success', 'Removed from cart.');
    }

    if ($action === 'clear') {
        $conn->query("DELETE FROM cart WHERE user_id=$userId");
        setFlash('success', 'Cart cleared.');
    }

    header("Location: /art-gallery/customer/cart.php");
    exit;
}

// Get cart items
$cartItems = $conn->query("SELECT c.*, a.title, a.price, a.image, a.availability, a.artist_id,
    u.name as artist_name FROM cart c JOIN artworks a ON c.artwork_id=a.id JOIN users u ON a.artist_id=u.id
    WHERE c.user_id=$userId")->fetch_all(MYSQLI_ASSOC);

$total = array_sum(array_column($cartItems, 'price'));

$pageTitle = 'My Cart';
require_once __DIR__ . '/../includes/header.php';
showFlash();
?>

<div class="dash-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="dash-main">
        <h2 style="font-family:var(--font-display);font-size:1.6rem;font-weight:400;margin-bottom:1.5rem">
            Shopping Cart <span style="font-size:1rem;color:var(--ink-muted)">(<?= count($cartItems) ?> items)</span>
        </h2>

        <?php if (empty($cartItems)): ?>
        <div class="card"><div class="card-body text-center py-5">
            <i class="bi bi-bag" style="font-size:3rem;color:var(--border)"></i>
            <h5 class="mt-3" style="font-family:var(--font-display);font-weight:400">Your cart is empty</h5>
            <p class="text-muted">Discover artworks you'll love.</p>
            <a href="/art-gallery/gallery.php" class="btn btn-dark">Browse Gallery</a>
        </div></div>
        <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body p-0">
                        <?php foreach ($cartItems as $item): ?>
                        <div class="d-flex align-items-center gap-3 p-3 border-bottom">
                            <a href="/art-gallery/artwork.php?id=<?= $item['artwork_id'] ?>">
                                <img src="<?= artworkImage($item['image']) ?>"
                                     style="width:72px;height:72px;object-fit:cover;border-radius:var(--radius);flex-shrink:0;" alt="">
                            </a>
                            <div class="flex-grow-1">
                                <a href="/art-gallery/artwork.php?id=<?= $item['artwork_id'] ?>" class="text-decoration-none">
                                    <div style="font-weight:500;color:var(--ink)"><?= sanitize($item['title']) ?></div>
                                </a>
                                <div style="font-size:.8rem;color:var(--ink-muted)">by <?= sanitize($item['artist_name']) ?></div>
                                <?php if ($item['availability'] !== 'available'): ?>
                                <span class="badge bg-danger" style="font-size:.65rem">No longer available</span>
                                <?php endif; ?>
                            </div>
                            <div style="font-family:var(--font-display);font-size:1.1rem;font-weight:600;white-space:nowrap">
                                <?= formatPrice($item['price']) ?>
                            </div>
                            <form method="POST">
                                <input type="hidden" name="post_action" value="remove">
                                <input type="hidden" name="artwork_id" value="<?= $item['artwork_id'] ?>">
                                <button type="submit" class="btn btn-icon" style="color:var(--danger)" title="Remove">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-body d-flex justify-content-between">
                        <form method="POST">
                            <input type="hidden" name="post_action" value="clear">
                            <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Clear cart?')">
                                <i class="bi bi-trash me-1"></i>Clear Cart
                            </button>
                        </form>
                        <a href="/art-gallery/gallery.php" class="btn btn-outline-dark btn-sm">
                            <i class="bi bi-arrow-left me-1"></i>Continue Shopping
                        </a>
                    </div>
                </div>
            </div>

            <!-- Order summary -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">Order Summary</div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2" style="font-size:.875rem">
                            <span>Subtotal (<?= count($cartItems) ?> items)</span>
                            <span><?= formatPrice($total) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2" style="font-size:.875rem">
                            <span>Shipping</span>
                            <span class="text-muted">Calculated at checkout</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <strong>Total</strong>
                            <strong style="font-family:var(--font-display);font-size:1.3rem"><?= formatPrice($total) ?></strong>
                        </div>
                        <a href="/art-gallery/customer/checkout.php" class="btn btn-dark w-100 py-2 mb-2">
                            <i class="bi bi-lock me-1"></i>Proceed to Checkout
                        </a>
                        <div class="text-center text-muted" style="font-size:.75rem">
                            <i class="bi bi-shield-check me-1"></i>Secure checkout · Buyer protection
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
