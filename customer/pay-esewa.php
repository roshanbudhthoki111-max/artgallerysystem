<?php
/**
 * pay-esewa.php
 * Initiates an eSewa payment using the v2 API (SHA-256 HMAC signature).
 *
 * Flow:
 *   1. Customer submits checkout → session stores order details → redirect here
 *   2. This page builds the signed payload and auto-submits a form to eSewa
 *   3. Customer completes payment on eSewa's site
 *   4. eSewa redirects back to payment-callback.php?gateway=esewa with a base64 response
 */
require_once __DIR__ . '/../includes/functions.php';
requireRole('customer', '/art-gallery/login.php');

$checkout = $_SESSION['pending_checkout'] ?? null;
if (!$checkout) {
    setFlash('error', 'Session expired. Please try again.');
    header('Location: /art-gallery/customer/checkout.php');
    exit;
}

// ── Credentials (set in Admin → Settings) ─────────────────────────────────
// TEST:  product_code = EPAYTEST  |  secret = 8gBm/:&EnhH.1/q
// LIVE:  use the values from your eSewa merchant dashboard
$productCode = getSetting('payment_esewa_id')     ?? 'EPAYTEST';
$secretKey   = getSetting('payment_esewa_secret')  ?? '8gBm/:&EnhH.1/q';

$amount      = number_format((float)$checkout['total'], 2, '.', '');
$taxAmount   = '0';
$serviceCharge = '0';
$deliveryCharge = '0';
$totalAmount = $amount;  // no extra charges on top
$transactionUuid = $checkout['ref'];

$baseUrl     = 'https://' . $_SERVER['HTTP_HOST'] . '/art-gallery';
$successUrl  = $baseUrl . '/customer/payment-callback.php?gateway=esewa';
$failureUrl  = $baseUrl . '/customer/payment-callback.php?gateway=esewa&status=failed';

// ── SHA-256 HMAC signature ─────────────────────────────────────────────────
// eSewa v2 signature covers: total_amount,transaction_uuid,product_code
$signatureString = "total_amount={$totalAmount},transaction_uuid={$transactionUuid},product_code={$productCode}";
$signature = base64_encode(hash_hmac('sha256', $signatureString, $secretKey, true));

// ── Gateway URL ────────────────────────────────────────────────────────────
// TEST:  https://rc-epay.esewa.com.np/api/epay/main/v2/form
// LIVE:  https://epay.esewa.com.np/api/epay/main/v2/form
$esewaUrl = 'https://rc-epay.esewa.com.np/api/epay/main/v2/form';
// Switch to live:
// $esewaUrl = 'https://epay.esewa.com.np/api/epay/main/v2/form';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Redirecting to eSewa…</title>
<style>
  body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f5f5f5}
  .box{text-align:center;background:#fff;border-radius:12px;padding:2.5rem 3rem;box-shadow:0 2px 16px rgba(0,0,0,.08)}
  .spinner{width:40px;height:40px;border:4px solid #e0e0e0;border-top-color:#60BB46;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 1rem}
  @keyframes spin{to{transform:rotate(360deg)}}
  p{color:#555;margin:.5rem 0}
  .logo{font-size:1.4rem;font-weight:700;color:#60BB46;margin-bottom:1rem}
</style>
</head>
<body>
<div class="box">
  <div class="logo">eSewa</div>
  <div class="spinner"></div>
  <p>Redirecting you to eSewa…</p>
  <p style="font-size:.8rem;color:#999">Please do not close this page.</p>

  <!-- eSewa v2 payment form — auto-submitted -->
  <form id="esewaForm" method="POST" action="<?= htmlspecialchars($esewaUrl) ?>">
    <input type="hidden" name="amount"           value="<?= htmlspecialchars($amount) ?>">
    <input type="hidden" name="tax_amount"        value="<?= $taxAmount ?>">
    <input type="hidden" name="total_amount"      value="<?= htmlspecialchars($totalAmount) ?>">
    <input type="hidden" name="transaction_uuid"  value="<?= htmlspecialchars($transactionUuid) ?>">
    <input type="hidden" name="product_code"      value="<?= htmlspecialchars($productCode) ?>">
    <input type="hidden" name="product_service_charge"  value="<?= $serviceCharge ?>">
    <input type="hidden" name="product_delivery_charge" value="<?= $deliveryCharge ?>">
    <input type="hidden" name="success_url"       value="<?= htmlspecialchars($successUrl) ?>">
    <input type="hidden" name="failure_url"       value="<?= htmlspecialchars($failureUrl) ?>">
    <input type="hidden" name="signed_field_names" value="total_amount,transaction_uuid,product_code">
    <input type="hidden" name="signature"         value="<?= htmlspecialchars($signature) ?>">
  </form>
</div>
<script>document.getElementById('esewaForm').submit();</script>
</body>
</html>
