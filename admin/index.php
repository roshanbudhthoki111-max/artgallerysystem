<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$user = getCurrentUser();

// Stats
$totalUsers      = $conn->query("SELECT COUNT(*) as c FROM users WHERE role != 'admin'")->fetch_assoc()['c'];
$totalArtists    = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='artist' AND status='active'")->fetch_assoc()['c'];
$totalCustomers  = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='customer'")->fetch_assoc()['c'];
$pendingArtists  = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='artist' AND status='pending'")->fetch_assoc()['c'];
$totalArtworks   = $conn->query("SELECT COUNT(*) as c FROM artworks")->fetch_assoc()['c'];
$pendingArtworks = $conn->query("SELECT COUNT(*) as c FROM artworks WHERE is_approved=0")->fetch_assoc()['c'];
$totalOrders     = $conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'];
$totalRevenue    = (float)$conn->query("SELECT COALESCE(SUM(commission_amount),0) as r FROM orders WHERE status='delivered'")->fetch_assoc()['r'];
$pendingWithdrawals = $conn->query("SELECT COUNT(*) as c FROM withdrawal_requests WHERE status='pending'")->fetch_assoc()['c'];

// Monthly revenue for chart
$monthlyRevenue = $conn->query("SELECT DATE_FORMAT(created_at,'%b %Y') as month,
    YEAR(created_at) as yr, MONTH(created_at) as mo,
    SUM(commission_amount) as revenue, SUM(total_price) as gmv, COUNT(*) as orders
    FROM orders WHERE status='delivered' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at), DATE_FORMAT(created_at,'%b %Y')
    ORDER BY yr, mo")->fetch_all(MYSQLI_ASSOC);

// Recent users
$recentUsers = $conn->query("SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Recent orders
$recentOrders = $conn->query("SELECT o.*, a.title as artwork_title, u.name as buyer_name, ar.name as artist_name
    FROM orders o JOIN artworks a ON o.artwork_id=a.id JOIN users u ON o.customer_id=u.id JOIN users ar ON o.artist_id=ar.id
    ORDER BY o.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
showFlash();
?>

<div class="dash-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="dash-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 style="font-family:var(--font-display);font-size:1.8rem;font-weight:400;margin-bottom:.15rem">Admin Dashboard</h2>
                <p class="text-muted mb-0" style="font-size:.875rem">Platform overview — <?= date('l, F j, Y') ?></p>
            </div>
            <?php if ($pendingArtists > 0 || $pendingArtworks > 0): ?>
            <div class="d-flex gap-2">
                <?php if ($pendingArtists > 0): ?>
                <a href="/art-gallery/admin/users.php?filter=pending" class="btn btn-sm btn-warning">
                    <i class="bi bi-person-check me-1"></i><?= $pendingArtists ?> Pending Artists
                </a>
                <?php endif; ?>
                <?php if ($pendingArtworks > 0): ?>
                <a href="/art-gallery/admin/artworks.php?filter=pending" class="btn btn-sm btn-warning">
                    <i class="bi bi-image me-1"></i><?= $pendingArtworks ?> Pending Artworks
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="bi bi-currency-rupee"></i></div>
                    <div class="stat-value" style="font-size:1.4rem">NPR <?= number_format($totalRevenue) ?></div>
                    <div class="stat-label">Platform Revenue</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="bi bi-bag-check"></i></div>
                    <div class="stat-value"><?= $totalOrders ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="bi bi-people"></i></div>
                    <div class="stat-value"><?= $totalUsers ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon red"><i class="bi bi-images"></i></div>
                    <div class="stat-value"><?= $totalArtworks ?></div>
                    <div class="stat-label">Total Artworks</div>
                </div>
            </div>
        </div>

        <!-- Sub-stats -->
        <div class="row g-3 mb-4">
            <?php $substats = [
                ['label'=>'Artists', 'value'=>$totalArtists, 'sub'=>$pendingArtists.' pending', 'icon'=>'bi-palette'],
                ['label'=>'Customers', 'value'=>$totalCustomers, 'sub'=>'registered buyers', 'icon'=>'bi-person'],
                ['label'=>'Pending Artworks', 'value'=>$pendingArtworks, 'sub'=>'awaiting approval', 'icon'=>'bi-hourglass'],
                ['label'=>'Pending Withdrawals', 'value'=>$pendingWithdrawals, 'sub'=>'to process', 'icon'=>'bi-arrow-up-circle'],
            ]; foreach ($substats as $s): ?>
            <div class="col-6 col-lg-3">
                <div class="card p-3">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi <?= $s['icon'] ?>" style="font-size:1.5rem;color:var(--gold)"></i>
                        <div>
                            <div style="font-size:1.5rem;font-family:var(--font-display);font-weight:600;line-height:1"><?= $s['value'] ?></div>
                            <div style="font-size:.75rem;color:var(--ink-muted)"><?= $s['label'] ?></div>
                            <div style="font-size:.7rem;color:var(--ink-muted)"><?= $s['sub'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-4 mb-4">
            <!-- Revenue chart -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">Revenue Overview (Last 6 months)</div>
                    <div class="card-body">
                        <canvas id="revenueChart" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Quick links -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">Quick Actions</div>
                    <div class="card-body d-flex flex-column gap-2">
                        <a href="/art-gallery/admin/users.php" class="btn btn-outline-dark text-start">
                            <i class="bi bi-people me-2"></i>Manage Users
                        </a>
                        <a href="/art-gallery/admin/artworks.php" class="btn btn-outline-dark text-start">
                            <i class="bi bi-images me-2"></i>Moderate Artworks
                        </a>
                        <a href="/art-gallery/admin/orders.php" class="btn btn-outline-dark text-start">
                            <i class="bi bi-box-seam me-2"></i>View All Orders
                        </a>
                        <a href="/art-gallery/admin/withdrawals.php" class="btn btn-outline-dark text-start d-flex justify-content-between">
                            <span><i class="bi bi-wallet2 me-2"></i>Process Withdrawals</span>
                            <?php if ($pendingWithdrawals): ?><span class="badge bg-danger"><?= $pendingWithdrawals ?></span><?php endif; ?>
                        </a>
                        <a href="/art-gallery/admin/categories.php" class="btn btn-outline-dark text-start">
                            <i class="bi bi-tags me-2"></i>Manage Categories
                        </a>
                        <a href="/art-gallery/admin/settings.php" class="btn btn-outline-dark text-start">
                            <i class="bi bi-gear me-2"></i>System Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Users -->
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Recent Users</span>
                        <a href="/art-gallery/admin/users.php" class="btn btn-sm btn-outline-dark">All Users</a>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>User</th><th>Role</th><th>Status</th><th>Joined</th></tr></thead>
                            <tbody>
                            <?php foreach ($recentUsers as $u): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?= profileImage($u['profile_image']) ?>" style="width:30px;height:30px;border-radius:50%;object-fit:cover;" alt="">
                                        <span style="font-size:.875rem"><?= sanitize($u['name']) ?></span>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark" style="font-size:.7rem"><?= ucfirst($u['role']) ?></span></td>
                                <td><span class="badge status-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span></td>
                                <td style="font-size:.8rem;color:var(--ink-muted)"><?= timeAgo($u['created_at']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Recent Orders</span>
                        <a href="/art-gallery/admin/orders.php" class="btn btn-sm btn-outline-dark">All Orders</a>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Artwork</th><th>Buyer</th><th>Amount</th><th>Status</th></tr></thead>
                            <tbody>
                            <?php foreach ($recentOrders as $o): ?>
                            <tr>
                                <td style="font-size:.875rem"><?= truncate(sanitize($o['artwork_title']),20) ?></td>
                                <td style="font-size:.875rem"><?= sanitize($o['buyer_name']) ?></td>
                                <td style="font-size:.875rem;font-weight:500"><?= formatPrice($o['total_price']) ?></td>
                                <td><span class="badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('revenueChart').getContext('2d');
const months = <?= json_encode(array_column($monthlyRevenue, 'month')) ?: '[]' ?>;
const revenue = <?= json_encode(array_map(fn($m)=>(float)$m['revenue'], $monthlyRevenue)) ?: '[]' ?>;
const gmv     = <?= json_encode(array_map(fn($m)=>(float)$m['gmv'], $monthlyRevenue)) ?: '[]' ?>;
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: months.length ? months : ['Jan','Feb','Mar','Apr','May','Jun'],
        datasets: [
            { label: 'Platform Revenue', data: revenue, backgroundColor: 'rgba(184,144,74,.85)', borderRadius: 4 },
            { label: 'Total GMV', data: gmv, backgroundColor: 'rgba(13,13,13,.08)', borderRadius: 4 }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top', labels: { font: { family: 'DM Sans', size: 12 } } } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.04)' } },
            x: { grid: { display: false } }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
