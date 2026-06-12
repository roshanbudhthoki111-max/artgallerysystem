<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('customer');
$user = getCurrentUser();

$orderId = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT o.*, a.title as artwork_title, a.image as artwork_image, a.category_id,
    ub.name as buyer_name, ub.email as buyer_email,
    ua.name as artist_name
    FROM orders o
    JOIN artworks a ON o.artwork_id=a.id
    JOIN users ub ON o.customer_id=ub.id
    JOIN users ua ON o.artist_id=ua.id
    WHERE o.id=? AND o.customer_id=?");
$stmt->bind_param("ii", $orderId, $user['id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) { header("Location: /art-gallery/customer/orders.php"); exit; }

$siteName = getSetting('site_name') ?? 'ArtVault';
$currency = getSetting('currency') ?? 'NPR';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?= $orderId ?> — <?= $siteName ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: #f5f4f2; color: #0d0d0d; font-size: 13px; }
        .invoice-wrap { max-width: 750px; margin: 2rem auto; background: white; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,.1); overflow: hidden; }
        .invoice-header { background: #0d0d0d; color: white; padding: 2rem 2.5rem; display: flex; justify-content: space-between; align-items: flex-start; }
        .brand { font-family: 'Cormorant Garamond', serif; font-size: 1.8rem; }
        .brand span { color: #b8904a; }
        .invoice-num { text-align: right; }
        .invoice-num h2 { font-family: 'Cormorant Garamond', serif; font-size: 1.4rem; font-weight: 400; }
        .invoice-num p { color: rgba(255,255,255,.5); font-size: .8rem; }
        .invoice-body { padding: 2.5rem; }
        .invoice-meta { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid #e8e4df; }
        .meta-block h6 { font-size: .65rem; font-weight: 700; letter-spacing: .15em; text-transform: uppercase; color: #888; margin-bottom: .4rem; }
        .meta-block p { font-size: .875rem; line-height: 1.6; }
        .artwork-row { display: flex; gap: 1.5rem; align-items: center; background: #faf9f7; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem; }
        .artwork-row img { width: 80px; height: 80px; object-fit: cover; border-radius: 6px; flex-shrink: 0; }
        .artwork-info h4 { font-family: 'Cormorant Garamond', serif; font-size: 1.2rem; font-weight: 400; }
        .artwork-info p { color: #888; font-size: .8rem; }
        table.totals { width: 100%; max-width: 300px; margin-left: auto; }
        table.totals td { padding: .4rem 0; font-size: .875rem; }
        table.totals td:last-child { text-align: right; font-weight: 500; }
        .total-row td { border-top: 2px solid #0d0d0d; padding-top: .75rem; font-family: 'Cormorant Garamond', serif; font-size: 1.3rem; font-weight: 600; }
        .status-badge { display: inline-block; padding: .25rem .75rem; border-radius: 999px; font-size: .7rem; font-weight: 600; letter-spacing: .05em; }
        .paid { background: rgba(39,174,96,.15); color: #1a7a3e; }
        .invoice-footer { background: #faf9f7; border-top: 1px solid #e8e4df; padding: 1.25rem 2.5rem; display: flex; justify-content: space-between; font-size: .75rem; color: #888; }
        @media print {
            body { background: white; }
            .invoice-wrap { box-shadow: none; margin: 0; border-radius: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
<div style="text-align:center;padding:1rem;background:#f5f4f2;" class="no-print">
    <button onclick="window.print()" style="background:#0d0d0d;color:white;border:none;padding:.6rem 1.5rem;border-radius:6px;cursor:pointer;font-size:.875rem;">
        🖨 Print / Save as PDF
    </button>
    <a href="/art-gallery/customer/order-detail.php?id=<?= $orderId ?>"
       style="margin-left:.75rem;color:#888;font-size:.85rem;text-decoration:none;">← Back to Order</a>
</div>

<div class="invoice-wrap">
    <div class="invoice-header">
        <div class="brand"><span>◈</span> <?= $siteName ?></div>
        <div class="invoice-num">
            <h2>INVOICE</h2>
            <p>#<?= str_pad($orderId, 6, '0', STR_PAD_LEFT) ?></p>
            <p><?= date('F j, Y', strtotime($order['created_at'])) ?></p>
        </div>
    </div>

    <div class="invoice-body">
        <div class="invoice-meta">
            <div class="meta-block">
                <h6>Billed To</h6>
                <p>
                    <strong><?= sanitize($order['buyer_name']) ?></strong><br>
                    <?= sanitize($order['buyer_email']) ?><br>
                    <?= nl2br(sanitize($order['shipping_address'] ?? '')) ?>
                </p>
            </div>
            <div class="meta-block">
                <h6>Artist</h6>
                <p>
                    <strong><?= sanitize($order['artist_name']) ?></strong><br>
                    via <?= $siteName ?>
                </p>
            </div>
            <div class="meta-block">
                <h6>Payment</h6>
                <p>
                    Method: <?= ucfirst($order['payment_method']) ?><br>
                    Status: <span class="status-badge paid"><?= ucfirst($order['payment_status']) ?></span><br>
                    <?php if ($order['payment_ref']): ?>
                    Ref: <?= sanitize($order['payment_ref']) ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="artwork-row">
            <img src="<?= 'http://' . $_SERVER['HTTP_HOST'] . artworkImage($order['artwork_image']) ?>" alt="">
            <div class="artwork-info">
                <h4><?= sanitize($order['artwork_title']) ?></h4>
                <p>Original artwork by <?= sanitize($order['artist_name']) ?></p>
                <p>Order placed: <?= date('M j, Y', strtotime($order['created_at'])) ?></p>
            </div>
            <div style="margin-left:auto;font-family:'Cormorant Garamond',serif;font-size:1.4rem;font-weight:600;white-space:nowrap;">
                <?= $currency ?> <?= number_format($order['total_price'], 2) ?>
            </div>
        </div>

        <table class="totals">
            <tr>
                <td style="color:#888">Subtotal</td>
                <td><?= $currency ?> <?= number_format($order['total_price'], 2) ?></td>
            </tr>
            <tr>
                <td style="color:#888">Shipping</td>
                <td style="color:#888">—</td>
            </tr>
            <tr class="total-row">
                <td>Total</td>
                <td><?= $currency ?> <?= number_format($order['total_price'], 2) ?></td>
            </tr>
        </table>
    </div>

    <div class="invoice-footer">
        <span>Thank you for your purchase!</span>
        <span><?= $siteName ?> · Nepal · <?= date('Y') ?></span>
    </div>
</div>
</body>
</html>
