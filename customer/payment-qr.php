<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('customer', '/art-gallery/login.php');
$user   = getCurrentUser();
$userId = $user['id'];

$paymentRef    = sanitize($_GET['ref']    ?? '');
$paymentMethod = sanitize($_GET['method'] ?? 'esewa');
$amount        = (float)($_GET['amount']  ?? 0);

if (!$paymentRef || $amount <= 0) {
    setFlash('error', 'Invalid payment session.');
    header('Location: /art-gallery/customer/orders.php');
    exit;
}

// Validate order belongs to this user and is still pending
$stmt = $conn->prepare(
    "SELECT id FROM orders WHERE payment_ref=? AND customer_id=? AND payment_status='pending' LIMIT 1"
);
$stmt->bind_param('si', $paymentRef, $userId);
$stmt->execute();
$orderRow = $stmt->get_result()->fetch_assoc();
if (!$orderRow) {
    setFlash('error', 'Order not found or already paid.');
    header('Location: /art-gallery/customer/orders.php');
    exit;
}

// ── Find personal QR file (any common extension) ──────────────────────────
function findQrFile($method) {
    $dir = __DIR__ . '/../uploads/qr/';
    foreach (['png','jpg','jpeg','PNG','JPG','JPEG'] as $ext) {
        $file = $dir . $method . '_personal.' . $ext;
        if (file_exists($file)) {
            return '/art-gallery/uploads/qr/' . $method . '_personal.' . $ext;
        }
    }
    return null;
}

$personalQrUrl = findQrFile($paymentMethod);
$activeLabel   = $paymentMethod === 'khalti' ? 'Khalti' : 'eSewa';
$activeColor   = $paymentMethod === 'khalti' ? '#5C2D8B' : '#60BB46';

// Fallback: generated QR from Google Charts (used only if no personal image found)
$esewaId      = getSetting('payment_esewa_id') ?? 'EPAYTEST';
$generatedQrData = $paymentMethod === 'khalti'
    ? "khalti://pay?amount=" . (int)round($amount*100) . "&order_id={$paymentRef}&name=ArtVault"
    : "esewa://payment?amt={$amount}&pid={$paymentRef}&scd={$esewaId}";
$generatedQrUrl = 'https://chart.googleapis.com/chart?cht=qr&chs=280x280&chld=M|2&chl='
    . urlencode($generatedQrData);

// ── Handle payment confirmation ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm_paid') {
    // Fetch artwork IDs first
    $artStmt = $conn->prepare(
        "SELECT artwork_id FROM orders WHERE payment_ref=? AND customer_id=?"
    );
    $artStmt->bind_param('si', $paymentRef, $userId);
    $artStmt->execute();
    $artRows = $artStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Mark orders as paid
    $upStmt = $conn->prepare(
        "UPDATE orders SET payment_status='paid', status='pending' WHERE payment_ref=? AND customer_id=?"
    );
    $upStmt->bind_param('si', $paymentRef, $userId);
    $upStmt->execute();

    // Mark artworks as sold
    foreach ($artRows as $row) {
        $aid = (int)$row['artwork_id'];
        $conn->query("UPDATE artworks SET availability='sold' WHERE id=$aid");
    }

    setFlash('success', "Payment confirmed! Your order ref is {$paymentRef}. We'll process it shortly.");
    header('Location: /art-gallery/customer/orders.php');
    exit;
}

$pageTitle = 'Complete Payment';
require_once __DIR__ . '/../includes/header.php';
showFlash();
?>

<div class="dash-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="dash-main">

        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="/art-gallery/customer/orders.php" class="btn btn-icon">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h2 style="font-family:var(--font-display);font-size:1.6rem;font-weight:400;margin-bottom:0">
                Complete Payment
            </h2>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">

                <!-- Method tabs -->
                <div class="card mb-3">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex gap-2">
                            <a href="?ref=<?= urlencode($paymentRef) ?>&method=esewa&amount=<?= $amount ?>"
                               class="btn btn-sm flex-fill <?= $paymentMethod==='esewa' ? 'btn-dark' : 'btn-outline-dark' ?>">
                                <i class="bi bi-phone me-1"></i>eSewa
                            </a>
                            <a href="?ref=<?= urlencode($paymentRef) ?>&method=khalti&amount=<?= $amount ?>"
                               class="btn btn-sm flex-fill <?= $paymentMethod==='khalti' ? 'btn-dark' : 'btn-outline-dark' ?>">
                                <i class="bi bi-phone-fill me-1"></i>Khalti
                            </a>
                        </div>
                    </div>
                </div>

                <!-- QR Card -->
                <div class="card mb-3">
                    <div class="card-header d-flex align-items-center justify-content-center gap-2">
                        <span style="width:10px;height:10px;border-radius:50%;background:<?= $activeColor ?>;display:inline-block;"></span>
                        <strong>Scan with your <?= $activeLabel ?> app</strong>
                    </div>
                    <div class="card-body text-center py-4">

                        <!-- QR image -->
                        <div style="display:inline-block;padding:14px;border:2px solid var(--border);
                                    border-radius:var(--radius);background:#fff;margin-bottom:1rem;">
                            <?php if ($personalQrUrl): ?>
                                <!-- Personal QR uploaded by admin -->
                                <div style="font-size:.7rem;font-weight:700;letter-spacing:.08em;
                                            color:<?= $activeColor ?>;margin-bottom:8px;">
                                    YOUR <?= strtoupper($activeLabel) ?> QR
                                </div>
                                <img src="<?= htmlspecialchars($personalQrUrl) ?>"
                                     alt="<?= $activeLabel ?> Personal QR"
                                     width="240" height="240"
                                     style="display:block;object-fit:contain;border-radius:4px;">
                                <div style="font-size:.72rem;color:#888;margin-top:8px;">
                                    ⚠️ Enter amount manually when paying
                                </div>
                            <?php else: ?>
                                <!-- Generated QR fallback -->
                                <img id="qrImg"
                                     src="<?= htmlspecialchars($generatedQrUrl) ?>"
                                     alt="<?= $activeLabel ?> QR Code"
                                     width="240" height="240"
                                     style="display:block;"
                                     onerror="this.style.display='none';
                                              document.getElementById('qrFallback').style.display='flex'">
                                <div id="qrFallback"
                                     style="display:none;width:240px;height:240px;background:#f5f5f5;
                                            align-items:center;justify-content:center;
                                            border-radius:4px;font-size:.8rem;color:#999;
                                            text-align:center;padding:1rem;">
                                    QR unavailable — use manual details below
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Amount -->
                        <div style="font-family:var(--font-display);font-size:2rem;font-weight:600;
                                    line-height:1;margin-bottom:.25rem;">
                            NPR <?= number_format($amount, 2) ?>
                        </div>
                        <div style="font-size:.8rem;color:var(--ink-muted);">
                            Order ref: <code style="font-size:.8rem;"><?= htmlspecialchars($paymentRef) ?></code>
                        </div>
                    </div>
                </div>

                <!-- Manual details -->
                <div class="card mb-3">
                    <div class="card-header" style="font-size:.82rem;">
                        <i class="bi bi-keyboard me-1"></i>Manual payment details
                    </div>
                    <div class="card-body py-2 px-3" style="font-size:.85rem;">
                        <?php if ($paymentMethod === 'esewa'): ?>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">eSewa ID</span>
                            <strong><?= htmlspecialchars($esewaId) ?></strong>
                        </div>
                        <?php else: ?>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Khalti ID</span>
                            <strong><?= htmlspecialchars(getSetting('payment_khalti_key') ?? '—') ?></strong>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="text-muted">Amount</span>
                            <strong>NPR <?= number_format($amount, 2) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted">Remarks / Note</span>
                            <strong><?= htmlspecialchars($paymentRef) ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Steps -->
                <div class="card mb-4">
                    <div class="card-body py-3 px-3" style="font-size:.82rem;">
                        <?php $steps = [
                            "Open your <strong>{$activeLabel}</strong> app and tap <em>Scan QR</em> or <em>Send Money</em>",
                            "Scan the QR above or enter the ID manually",
                            "Enter amount <strong>NPR " . number_format($amount, 2) . "</strong> and put <strong>{$paymentRef}</strong> in remarks",
                            "Complete the payment with your PIN, then tap the button below",
                        ]; ?>
                        <?php foreach ($steps as $i => $step): ?>
                        <div class="d-flex gap-2 <?= $i < count($steps)-1 ? 'mb-2' : '' ?>">
                            <span style="color:<?= $activeColor ?>;font-weight:700;min-width:16px;"><?= $i+1 ?>.</span>
                            <span><?= $step ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Confirm button -->
                <form method="POST">
                    <input type="hidden" name="action" value="confirm_paid">
                    <button type="submit" class="btn btn-dark w-100 py-2 mb-2" style="font-size:.95rem;">
                        <i class="bi bi-check-circle me-2"></i>I Have Completed the Payment
                    </button>
                </form>
                <a href="/art-gallery/customer/orders.php"
                   class="btn btn-outline-secondary w-100 mb-3">
                    Pay Later — View My Orders
                </a>

                <div class="text-center text-muted" style="font-size:.73rem;">
                    <i class="bi bi-shield-check me-1"></i>
                    Your order is reserved. Tap above once payment is done.
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
