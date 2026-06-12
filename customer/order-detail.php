<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('customer');
$user = getCurrentUser();

$orderId = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT o.*, a.title as artwork_title, a.image as artwork_image, a.description as artwork_desc,
    ub.name as buyer_name, ub.email as buyer_email,
    ua.name as artist_name, ua.email as artist_email
    FROM orders o
    JOIN artworks a ON o.artwork_id=a.id
    JOIN users ub ON o.customer_id=ub.id
    JOIN users ua ON o.artist_id=ua.id
    WHERE o.id=? AND o.customer_id=?");
$stmt->bind_param("ii", $orderId, $user['id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) { header("Location: /art-gallery/customer/orders.php"); exit; }

$pageTitle = 'Order #' . $orderId;
require_once __DIR__ . '/../includes/header.php';
showFlash();
?>

<div class="dash-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="dash-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="/art-gallery/customer/orders.php" class="btn btn-icon"><i class="bi bi-arrow-left"></i></a>
                <div>
                    <h2 style="font-family:var(--font-display);font-size:1.4rem;font-weight:400;margin-bottom:0">Order #<?= $orderId ?></h2>
                    <div style="font-size:.8rem;color:var(--ink-muted)"><?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])) ?></div>
                </div>
            </div>
            <a href="/art-gallery/customer/invoice.php?id=<?= $orderId ?>" target="_blank" class="btn btn-outline-dark btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i>Download Invoice
            </a>
        </div>

        <div class="row g-4">
            <!-- Order details -->
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header">Artwork Ordered</div>
                    <div class="card-body d-flex gap-4">
                        <img src="<?= artworkImage($order['artwork_image']) ?>"
                             style="width:120px;height:120px;object-fit:cover;border-radius:var(--radius);flex-shrink:0;" alt="">
                        <div>
                            <h5 style="font-family:var(--font-display);font-size:1.3rem;font-weight:400;margin-bottom:.25rem">
                                <?= sanitize($order['artwork_title']) ?>
                            </h5>
                            <div style="font-size:.875rem;color:var(--ink-muted)">by <?= sanitize($order['artist_name']) ?></div>
                            <?php if ($order['artwork_desc']): ?>
                            <p style="font-size:.875rem;margin-top:.75rem;color:var(--ink-light)"><?= truncate(sanitize($order['artwork_desc']), 150) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header">Order Progress</div>
                    <div class="card-body">
                        <?php
                        $steps = [
                            ['key'=>'pending',   'label'=>'Order Placed',    'icon'=>'bi-bag-check'],
                            ['key'=>'confirmed', 'label'=>'Confirmed',       'icon'=>'bi-check-circle'],
                            ['key'=>'shipped',   'label'=>'Shipped',         'icon'=>'bi-truck'],
                            ['key'=>'delivered', 'label'=>'Delivered',       'icon'=>'bi-house-check'],
                        ];
                        $statusOrder = ['pending'=>0,'confirmed'=>1,'shipped'=>2,'delivered'=>3,'cancelled'=>-1];
                        $currentStep = $statusOrder[$order['status']] ?? 0;
                        ?>
                        <?php if ($order['status'] === 'cancelled'): ?>
                        <div class="alert alert-danger"><i class="bi bi-x-circle me-2"></i>This order was cancelled.</div>
                        <?php else: ?>
                        <div class="d-flex align-items-center gap-0">
                            <?php foreach ($steps as $i => $step): ?>
                            <div class="text-center flex-grow-1">
                                <div class="d-flex align-items-center justify-content-center mb-2">
                                    <?php if ($i > 0): ?>
                                    <div style="height:2px;flex:1;background:<?= $i <= $currentStep ? 'var(--gold)' : 'var(--border)' ?>;margin-right:-1px;"></div>
                                    <?php endif; ?>
                                    <div style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;
                                                background:<?= $i <= $currentStep ? 'var(--gold)' : 'var(--border)' ?>;
                                                color:<?= $i <= $currentStep ? 'white' : 'var(--ink-muted)' ?>;font-size:.85rem;flex-shrink:0;z-index:1;">
                                        <i class="bi <?= $step['icon'] ?>"></i>
                                    </div>
                                    <?php if ($i < count($steps)-1): ?>
                                    <div style="height:2px;flex:1;background:<?= $i < $currentStep ? 'var(--gold)' : 'var(--border)' ?>;margin-left:-1px;"></div>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size:.7rem;color:<?= $i <= $currentStep ? 'var(--ink)' : 'var(--ink-muted)' ?>;font-weight:<?= $i <= $currentStep ? '600' : '400' ?>">
                                    <?= $step['label'] ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($order['tracking_number']): ?>
                        <div class="mt-3 pt-3 border-top">
                            <span class="text-muted" style="font-size:.85rem">Tracking Number: </span>
                            <strong><?= sanitize($order['tracking_number']) ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($order['shipping_address']): ?>
                <div class="card">
                    <div class="card-header">Shipping Details</div>
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-geo-alt text-muted mt-1"></i>
                            <div>
                                <div style="font-weight:500"><?= sanitize($order['buyer_name']) ?></div>
                                <div style="color:var(--ink-muted);font-size:.875rem;white-space:pre-line"><?= sanitize($order['shipping_address']) ?></div>
                            </div>
                        </div>
                        <?php if ($order['notes']): ?>
                        <div class="mt-2 pt-2 border-top" style="font-size:.85rem;color:var(--ink-muted)">
                            <i class="bi bi-chat me-1"></i><?= sanitize($order['notes']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Summary -->
            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header">Order Summary</div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2" style="font-size:.875rem">
                            <span>Artwork Price</span><span><?= formatPrice($order['total_price']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2" style="font-size:.875rem">
                            <span>Payment Method</span><span><?= ucfirst($order['payment_method']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2" style="font-size:.875rem">
                            <span>Payment Status</span>
                            <span class="badge status-<?= $order['payment_status']==='paid'?'delivered':'pending' ?>">
                                <?= ucfirst($order['payment_status']) ?>
                            </span>
                        </div>
                        <?php if ($order['payment_ref']): ?>
                        <div class="d-flex justify-content-between mb-2" style="font-size:.8rem">
                            <span class="text-muted">Payment Ref</span>
                            <code style="font-size:.75rem"><?= sanitize($order['payment_ref']) ?></code>
                        </div>
                        <?php endif; ?>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total</strong>
                            <strong style="font-family:var(--font-display);font-size:1.3rem"><?= formatPrice($order['total_price']) ?></strong>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Order Status</div>
                    <div class="card-body">
                        <span class="badge status-<?= $order['status'] ?>" style="font-size:.85rem;padding:.5rem 1rem;">
                            <?= ucfirst($order['status']) ?>
                        </span>
                        <div class="mt-3">
                            <a href="/art-gallery/artwork.php?id=<?= $order['artwork_id'] ?>" class="btn btn-outline-dark btn-sm w-100">
                                <i class="bi bi-eye me-1"></i>View Artwork
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
