<?php
/**
 * payment-callback.php
 * Handles the return redirect from eSewa and Khalti after payment.
 * Verifies the payment server-side before creating any orders.
 *
 * eSewa  → GET ?gateway=esewa&data=<base64_encoded_json>
 * Khalti → GET ?gateway=khalti&pidx=<token>&status=Completed|...
 */
require_once __DIR__ . '/../includes/functions.php';
requireRole('customer', '/art-gallery/login.php');

$gateway  = sanitize($_GET['gateway'] ?? '');
$checkout = $_SESSION['pending_checkout'] ?? null;

// ── Helper: save verified orders to DB ────────────────────────────────────
function saveOrders($conn, $checkout, $userId, $paymentMethod, $paymentRef) {
    $commission = $checkout['commission'];
    foreach ($checkout['cart_items'] as $item) {
        $commAmt       = $item['price'] * $commission / 100;
        $artistEarning = $item['price'] - $commAmt;
        $artworkId     = (int)$item['artwork_id'];
        $artistId      = (int)$item['artist_id'];
        $price         = (float)$item['price'];
        $ps            = 'paid';
        $os            = 'pending';
        $address       = $checkout['address'];
        $notes         = $checkout['notes'];

        $stmt = $conn->prepare("INSERT INTO orders
            (customer_id, artwork_id, artist_id, status, total_price, commission_amount,
             artist_earning, payment_method, payment_status, payment_ref, shipping_address, notes)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("iiisdddsssss",
            $userId, $artworkId, $artistId, $os,
            $price, $commAmt, $artistEarning,
            $paymentMethod, $ps, $paymentRef, $address, $notes);
        $stmt->execute();
        $conn->query("UPDATE artworks SET availability='sold' WHERE id=$artworkId");
    }
    $conn->query("DELETE FROM cart WHERE user_id=$userId");
    unset($_SESSION['pending_checkout']);
}

// ══════════════════════════════════════════════════════════════════════════
// eSEWA CALLBACK
// ══════════════════════════════════════════════════════════════════════════
if ($gateway === 'esewa') {

    // eSewa POSTs back with ?data=<base64(json)>
    $rawData = $_GET['data'] ?? '';

    if (empty($rawData) || !$checkout) {
        setFlash('error', 'Payment verification failed — missing data.');
        header('Location: /art-gallery/customer/checkout.php');
        exit;
    }

    $decoded = json_decode(base64_decode($rawData), true);
    /*
      Decoded JSON contains:
        transaction_code, status, total_amount, transaction_uuid,
        product_code, signed_field_names, signature
    */

    $status          = $decoded['status']           ?? '';
    $transactionUuid = $decoded['transaction_uuid'] ?? '';
    $totalAmount     = $decoded['total_amount']      ?? '';
    $productCode     = $decoded['product_code']      ?? '';
    $returnedSig     = $decoded['signature']          ?? '';

    // 1. Check status
    if (strtoupper($status) !== 'COMPLETE') {
        setFlash('error', 'eSewa payment was not completed (status: ' . $status . ').');
        header('Location: /art-gallery/customer/orders.php');
        exit;
    }

    // 2. Verify transaction UUID matches our order ref
    if ($transactionUuid !== ($checkout['ref'] ?? '')) {
        setFlash('error', 'Payment verification failed — order reference mismatch.');
        header('Location: /art-gallery/customer/checkout.php');
        exit;
    }

    // 3. Verify HMAC signature
    $secretKey       = getSetting('payment_esewa_secret') ?? '8gBm/:&EnhH.1/q';
    $signatureString = "transaction_code={$decoded['transaction_code']},status={$status},total_amount={$totalAmount},transaction_uuid={$transactionUuid},product_code={$productCode},signed_field_names={$decoded['signed_field_names']}";
    $expectedSig     = base64_encode(hash_hmac('sha256', $signatureString, $secretKey, true));

    if (!hash_equals($expectedSig, $returnedSig)) {
        setFlash('error', 'Payment signature verification failed. Please contact support.');
        header('Location: /art-gallery/customer/orders.php');
        exit;
    }

    // 4. All good — save orders
    saveOrders($conn, $checkout, $_SESSION['user_id'], 'esewa', $transactionUuid);
    setFlash('success', "Payment successful via eSewa! Ref: {$transactionUuid}");
    header('Location: /art-gallery/customer/orders.php');
    exit;
}

// ══════════════════════════════════════════════════════════════════════════
// KHALTI CALLBACK
// ══════════════════════════════════════════════════════════════════════════
if ($gateway === 'khalti') {

    $pidx   = sanitize($_GET['pidx']   ?? '');
    $status = sanitize($_GET['status'] ?? '');

    if (empty($pidx) || !$checkout) {
        setFlash('error', 'Payment verification failed — missing token.');
        header('Location: /art-gallery/customer/checkout.php');
        exit;
    }

    // 1. Verify pidx matches what we stored at initiation
    if ($pidx !== ($checkout['khalti_pidx'] ?? '')) {
        setFlash('error', 'Payment token mismatch. Please try again.');
        header('Location: /art-gallery/customer/checkout.php');
        exit;
    }

    // 2. Server-side lookup — NEVER trust the GET status alone
    $secretKey     = getSetting('payment_khalti_secret') ?? 'test_secret_key_YOUR_KEY_HERE';
    $khaltiLookup  = 'https://a.khalti.com/api/v2/epayment/lookup/';
    // Live: $khaltiLookup = 'https://khalti.com/api/v2/epayment/lookup/';

    $ch = curl_init($khaltiLookup);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['pidx' => $pidx]),
        CURLOPT_HTTPHEADER     => [
            'Authorization: Key ' . $secretKey,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 15,
    ]);
    $response   = curl_exec($ch);
    $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpStatus !== 200) {
        setFlash('error', 'Could not verify Khalti payment. Please contact support.');
        header('Location: /art-gallery/customer/orders.php');
        exit;
    }

    $data         = json_decode($response, true);
    $khaltiStatus = $data['status']             ?? '';
    $paidAmount   = (int)($data['total_amount'] ?? 0); // in paisa
    $orderId      = $data['purchase_order_id']  ?? '';

    // 3. Confirm status is Completed and amounts match
    if ($khaltiStatus !== 'Completed') {
        setFlash('error', "Khalti payment not completed (status: {$khaltiStatus}).");
        header('Location: /art-gallery/customer/orders.php');
        exit;
    }

    $expectedPaisa = (int)round((float)$checkout['total'] * 100);
    if ($paidAmount < $expectedPaisa) {
        setFlash('error', 'Payment amount mismatch. Please contact support.');
        header('Location: /art-gallery/customer/orders.php');
        exit;
    }

    if ($orderId !== $checkout['ref']) {
        setFlash('error', 'Order reference mismatch. Please contact support.');
        header('Location: /art-gallery/customer/orders.php');
        exit;
    }

    // 4. All good — save orders
    saveOrders($conn, $checkout, $_SESSION['user_id'], 'khalti', $orderId);
    setFlash('success', "Payment successful via Khalti! Ref: {$orderId}");
    header('Location: /art-gallery/customer/orders.php');
    exit;
}

// Unknown gateway
setFlash('error', 'Unknown payment gateway.');
header('Location: /art-gallery/customer/checkout.php');
exit;
