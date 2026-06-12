<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('customer');
$user   = getCurrentUser();
$userId = $user['id'];

$cartItems = $conn->query("SELECT c.*, a.title, a.price, a.image, a.availability, a.artist_id, a.id as art_id,
    u.name as artist_name FROM cart c JOIN artworks a ON c.artwork_id=a.id JOIN users u ON a.artist_id=u.id
    WHERE c.user_id=$userId AND a.availability='available'")->fetch_all(MYSQLI_ASSOC);

if (empty($cartItems)) {
    setFlash('error', 'Your cart is empty or items are no longer available.');
    header("Location: " . dirname($_SERVER['PHP_SELF']) . "/cart.php");
    exit;
}

$total      = array_sum(array_column($cartItems, 'price'));
$commission = (float)(getSetting('commission_percentage') ?? 10);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address       = sanitize($_POST['shipping_address'] ?? '');
    $notes         = sanitize($_POST['notes'] ?? '');
    $paymentMethod = sanitize($_POST['payment_method'] ?? 'esewa');

    if (empty($address)) {
        $error = 'Shipping address is required.';
    } else {
        $paymentRef = strtoupper($paymentMethod) . '-DEMO-' . strtoupper(bin2hex(random_bytes(5)));

        foreach ($cartItems as $item) {
            $commAmt       = $item['price'] * $commission / 100;
            $artistEarning = $item['price'] - $commAmt;
            $artworkId     = (int)$item['artwork_id'];
            $artistId      = (int)$item['artist_id'];
            $price         = (float)$item['price'];
            $pStatus       = 'paid';
            $oStatus       = 'confirmed';

            $stmt = $conn->prepare("INSERT INTO orders
                (customer_id, artwork_id, artist_id, status, total_price, commission_amount,
                 artist_earning, payment_method, payment_status, payment_ref, shipping_address, notes)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("iiisdddsssss",
                $userId, $artworkId, $artistId, $oStatus, $price, $commAmt, $artistEarning,
                $paymentMethod, $pStatus, $paymentRef, $address, $notes);
            $stmt->execute();

            $conn->query("UPDATE artworks SET availability='sold' WHERE id=$artworkId");
        }

        $conn->query("DELETE FROM cart WHERE user_id=$userId");
        setFlash('success', "✓ Payment successful via " . ucfirst($paymentMethod) . "! Ref: $paymentRef");
        header("Location: " . dirname($_SERVER['PHP_SELF']) . "/orders.php");
        exit;
    }
}

$pageTitle = 'Checkout';
require_once __DIR__ . '/../includes/header.php';
showFlash();
?>

<div class="dash-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="dash-main">
        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="<?= dirname($_SERVER['PHP_SELF']) ?>/cart.php" class="btn btn-icon"><i class="bi bi-arrow-left"></i></a>
            <h2 style="font-family:var(--font-display);font-size:1.6rem;font-weight:400;margin-bottom:0">Checkout</h2>
        </div>

        <div class="alert mb-4" style="background:#fff8e1;border:1px solid var(--gold);border-radius:var(--radius);font-size:.825rem;">
            <i class="bi bi-info-circle-fill me-2" style="color:var(--gold)"></i>
            <strong>Demo Mode:</strong> All payments are simulated — no real money is charged.
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger mb-4"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="row g-4">
                <!-- Left column -->
                <div class="col-lg-7">
                    <div class="card mb-3">
                        <div class="card-header"><i class="bi bi-geo-alt me-2"></i>Shipping Address</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Full Shipping Address *</label>
                                <textarea name="shipping_address" class="form-control" rows="3"
                                    placeholder="Street address, city, district, postal code…" required><?= sanitize($_POST['shipping_address'] ?? '') ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Order Notes (Optional)</label>
                                <input type="text" name="notes" class="form-control"
                                    placeholder="Any special instructions…"
                                    value="<?= sanitize($_POST['notes'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header"><i class="bi bi-credit-card me-2"></i>Payment Method</div>
                        <div class="card-body">
                            <div class="d-flex flex-column gap-2">
                                <?php $methods = [
                                    ['id'=>'esewa',      'label'=>'eSewa',      'icon'=>'bi-phone',      'color'=>'#60BB46', 'desc'=>'Pay via eSewa digital wallet'],
                                    ['id'=>'khalti',     'label'=>'Khalti',     'icon'=>'bi-phone-fill', 'color'=>'#5C2D91', 'desc'=>'Pay via Khalti digital wallet'],
                                    ['id'=>'connectips', 'label'=>'ConnectIPS', 'icon'=>'bi-bank',       'color'=>'#003087', 'desc'=>'Pay via bank transfer'],
                                ];
                                foreach ($methods as $m): ?>
                                <label class="d-flex align-items-center gap-3 p-3 border rounded payment-option"
                                       style="cursor:pointer;border-radius:var(--radius)!important;transition:border-color .2s;">
                                    <input type="radio" name="payment_method" value="<?= $m['id'] ?>"
                                           <?= $m['id']==='esewa' ? 'checked' : '' ?> style="accent-color:<?= $m['color'] ?>">
                                    <i class="bi <?= $m['icon'] ?>" style="font-size:1.3rem;color:<?= $m['color'] ?>;width:22px;"></i>
                                    <div class="flex-grow-1">
                                        <div style="font-weight:600;font-size:.9rem"><?= $m['label'] ?></div>
                                        <div style="font-size:.75rem;color:var(--ink-muted)"><?= $m['desc'] ?></div>
                                    </div>
                                    <span class="badge" style="background:#f0f0f0;color:#888;font-size:.65rem;font-weight:500;">SIMULATED</span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right column: order summary -->
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-header">Order Summary</div>
                        <div class="card-body p-0">
                            <?php foreach ($cartItems as $item): ?>
                            <div class="d-flex align-items-center gap-2 px-3 py-2 border-bottom">
                                <img src="<?= artworkImage($item['image']) ?>"
                                     style="width:44px;height:44px;object-fit:cover;border-radius:var(--radius-sm);" alt="">
                                <div class="flex-grow-1" style="font-size:.85rem">
                                    <div style="font-weight:500"><?= truncate(sanitize($item['title']), 22) ?></div>
                                    <div style="color:var(--ink-muted);font-size:.75rem">by <?= sanitize($item['artist_name']) ?></div>
                                </div>
                                <div style="font-weight:600;font-size:.9rem;white-space:nowrap"><?= formatPrice($item['price']) ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2" style="font-size:.875rem">
                                <span>Subtotal</span><span><?= formatPrice($total) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2" style="font-size:.875rem">
                                <span>Shipping</span><span class="text-muted">TBD</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-4">
                                <strong>Total</strong>
                                <strong style="font-family:var(--font-display);font-size:1.4rem"><?= formatPrice($total) ?></strong>
                            </div>
                            <button type="submit" class="btn btn-gold w-100 py-2">
                                <i class="bi bi-lock me-2"></i>Place Order &amp; Pay
                            </button>
                            <div class="text-center text-muted mt-2" style="font-size:.75rem">
                                <i class="bi bi-shield-check me-1"></i>Demo mode — order confirmed instantly
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
