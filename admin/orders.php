<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$user = getCurrentUser();

$filter = sanitize($_GET['filter'] ?? 'all');
$where  = $filter !== 'all' ? "WHERE o.status='$filter'" : "";

$orders = $conn->query("SELECT o.*, a.title as artwork_title, 
    ub.name as buyer_name, ua.name as artist_name
    FROM orders o 
    JOIN artworks a ON o.artwork_id=a.id
    JOIN users ub ON o.customer_id=ub.id
    JOIN users ua ON o.artist_id=ua.id
    $where ORDER BY o.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'All Orders';
require_once __DIR__ . '/../includes/header.php';
showFlash();
?>

<div class="dash-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="dash-main">
        <h2 style="font-family:var(--font-display);font-size:1.6rem;font-weight:400;margin-bottom:1rem">
            Orders <span style="font-size:1rem;color:var(--ink-muted)">(<?= count($orders) ?>)</span>
        </h2>

        <div class="d-flex gap-2 mb-4 flex-wrap">
            <?php foreach (['all'=>'All','pending'=>'Pending','confirmed'=>'Confirmed','shipped'=>'Shipped','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $k=>$v): ?>
            <a href="?filter=<?= $k ?>" class="category-pill <?= $filter===$k?'active':'' ?>"><?= $v ?></a>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <?php if (empty($orders)): ?>
                <div class="text-center py-5 text-muted">No orders found.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr>
                            <th>#</th><th>Artwork</th><th>Buyer</th><th>Artist</th>
                            <th>Total</th><th>Commission</th><th>Status</th><th>Date</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($orders as $o): ?>
                        <tr>
                            <td style="font-size:.8rem;color:var(--ink-muted)">#<?= $o['id'] ?></td>
                            <td style="font-size:.875rem;max-width:160px"><?= truncate(sanitize($o['artwork_title']),22) ?></td>
                            <td style="font-size:.875rem"><?= sanitize($o['buyer_name']) ?></td>
                            <td style="font-size:.875rem"><?= sanitize($o['artist_name']) ?></td>
                            <td style="font-size:.875rem;font-weight:600"><?= formatPrice($o['total_price']) ?></td>
                            <td style="font-size:.85rem;color:var(--gold)">+<?= formatPrice($o['commission_amount']) ?></td>
                            <td><span class="badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                            <td style="font-size:.8rem;color:var(--ink-muted)"><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
