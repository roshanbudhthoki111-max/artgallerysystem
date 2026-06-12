<?php
/**
 * pay-khalti.php
 * Initiates a Khalti payment using the Khalti Payment Initiation API (v2).
 *
 * Flow:
 *   1. Customer submits checkout → session stores order details → redirect here
 *   2. This page calls Khalti's /epayment/initiate/ endpoint server-side
 *   3. Khalti returns a payment_url → we redirect the customer there
 *   4. Customer pays on Khalti's page
 *   5. Khalti redirects back to payment-callback.php?gateway=khalti with ?pidx=...
 *   6. Callback page calls Khalti's /epayment/lookup/ to verify, then saves orders
 */
require_once __DIR__ . '/../includes/functions.php';
requireRole('customer', '/art-gallery/login.php');

$checkout = $_SESSION['pending_checkout'] ?? null;
if (!$checkout) {
    setFlash('error', 'Session expired. Please try again.');
    header('Location: /art-gallery/customer/checkout.php');
    exit;
}

// ── Credentials ────────────────────────────────────────────────────────────
// TEST secret key: Key taken from your Khalti test dashboard (starts with "test_secret_key_")
// LIVE secret key: from your live Khalti merchant dashboard
$secretKey = getSetting('payment_khalti_secret') ?? 'test_secret_key_YOUR_KEY_HERE';

// Khalti uses PAISA (1 NPR = 100 paisa)
$amountPaisa = (int)round((float)$checkout['total'] * 100);
$ref         = $checkout['ref'];
$returnUrl   = 'https://' . $_SERVER['HTTP_HOST'] . '/art-gallery/customer/payment-callback.php?gateway=khalti';

// ── Initiate payment via Khalti server-side API ────────────────────────────
// TEST:  https://a.khalti.com/api/v2/epayment/initiate/
// LIVE:  https://khalti.com/api/v2/epayment/initiate/
$khaltiInitUrl = 'https://a.khalti.com/api/v2/epayment/initiate/';
// Switch to live: $khaltiInitUrl = 'https://khalti.com/api/v2/epayment/initiate/';

$payload = json_encode([
    'return_url'      => $returnUrl,
    'website_url'     => 'https://' . $_SERVER['HTTP_HOST'] . '/art-gallery/',
    'amount'          => $amountPaisa,
    'purchase_order_id'   => $ref,
    'purchase_order_name' => 'ArtVault Order ' . $ref,
    'customer_info'   => [
        'name'  => $_SESSION['user_name']  ?? 'Customer',
        'email' => $_SESSION['user_email'] ?? '',
    ],
]);

$ch = curl_init($khaltiInitUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Key ' . $secretKey,
        'Content-Type: application/json',
    ],
    CURLOPT_TIMEOUT        => 15,
]);
$response   = curl_exec($ch);
$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError  = curl_error($ch);
curl_close($ch);

if ($curlError || $httpStatus !== 200) {
    $errDetail = $curlError ?: "HTTP $httpStatus: $response";
    setFlash('error', 'Could not connect to Khalti. Please try again. (' . $errDetail . ')');
    header('Location: /art-gallery/customer/checkout.php');
    exit;
}

$data = json_decode($response, true);
if (empty($data['payment_url']) || empty($data['pidx'])) {
    $msg = $data['detail'] ?? $data['error_key'] ?? 'Unexpected response from Khalti.';
    setFlash('error', 'Khalti error: ' . $msg);
    header('Location: /art-gallery/customer/checkout.php');
    exit;
}

// Store pidx so the callback can look it up
$_SESSION['pending_checkout']['khalti_pidx'] = $data['pidx'];

// Redirect customer to Khalti's hosted payment page
header('Location: ' . $data['payment_url']);
exit;
