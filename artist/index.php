<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('artist', '/art-gallery/login.php');
$user     = getCurrentUser();
$artistId = (int)$user['id'];

// Helper: safely run a query and return one column value, or a default
function safeQueryVal($conn, $sql, $default = 0) {
    $result = $conn->query($sql);
    if (!$result) return $default;
    $row = $result->fetch_assoc();
    return $row ? array_values($row)[0] : $default;
}

// Stats
$totalArtworks = safeQueryVal($conn, "SELECT COUNT(*) FROM artworks WHERE artist_id=$artistId");
$totalSales    = safeQueryVal($conn, "SELECT COUNT(*) FROM orders WHERE artist_id=$artistId AND status='delivered'");
$totalEarnings = safeQueryVal($conn, "SELECT COALESCE(SUM(artist_earning),0) FROM orders WHERE artist_id=$artistId AND status='delivered'");
$pendingOrders = safeQueryVal($conn, "SELECT COUNT(*) FROM orders WHERE artist_id=$artistId AND status='pending'");
$pendingOffers = safeQueryVal($conn, "SELECT COUNT(*) FROM offers o JOIN artworks a ON o.artwork_id=a.id WHERE a.artist_id=$artistId AND o.status='pending'");
$totalViews    = safeQueryVal($conn, "SELECT COALESCE(SUM(views),0) FROM artworks WHERE artist_id=$artistId");
$totalLikes    = safeQueryVal($conn, "SELECT COALESCE(SUM(likes_count),0) FROM artworks WHERE artist_id=$artistId");

// Recent orders
$recentOrders = [];
$rResult = $conn->query("SELECT o.*, a.title as artwork_title, u.name as buyer_name
    FROM orders o JOIN artworks a ON o.artwork_id=a.id JOIN users u ON o.customer_id=u.id
    WHERE o.artist_id=$artistId ORDER BY o.created_at DESC LIMIT 5");
if ($rResult) $recentOrders = $rResult->fetch_all(MYSQLI_ASSOC);

// Recent artworks
$myArtworks = [];
$aResult = $conn->query("SELECT * FROM artworks WHERE artist_id=$artistId ORDER BY created_at DESC LIMIT 6");
if ($aResult) $myArtworks = $aResult->fetch_all(MYSQLI_ASSOC);

// Monthly earnings for chart (last 6 months)
$monthlyData = [];
$mResult = $conn->query("SELECT DATE_FORMAT(created_at,'%b') as month,
    YEAR(created_at) as yr, MONTH(created_at) as mo,
    COALESCE(SUM(artist_earning),0) as total
    FROM orders WHERE artist_id=$artistId AND status='delivered'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at), DATE_FORMAT(created_at,'%b')
    ORDER BY yr, mo");
if ($mResult) $monthlyData = $mResult->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Artist Dashboard';
require_once __DIR__ . '/../includes/header.php';
showFlash();
?>

<div class="dash-layout">
    <!-- Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main -->
    <div class="dash-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 style="font-family:var(--font-display);font-size:1.8rem;font-weight:400;margin-bottom:.15rem">
                    Good <?= date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening') ?>, <?= sanitize(explode(' ', $user['name'])[0]) ?>
                </h2>
                <p class="text-muted mb-0" style="font-size:.875rem">Here's what's happening with your art today.</p>
            </div>
            <a href="/art-gallery/artist/upload-artwork.php" class="btn btn-dark">
                <i class="bi bi-plus-lg me-1"></i>Upload Artwork
            </a>
        </div>

        <!-- Stats -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="bi bi-currency-rupee"></i></div>
                    <div class="stat-value">NPR <?= number_format($totalEarnings) ?></div>
                    <div class="stat-label">Total Earnings</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="bi bi-bag-check"></i></div>
                    <div class="stat-value"><?= $totalSales ?></div>
                    <div class="stat-label">Artworks Sold</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="bi bi-eye"></i></div>
                    <div class="stat-value"><?= number_format($totalViews) ?></div>
                    <div class="stat-label">Total Views</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <div class="stat-icon red"><i class="bi bi-heart"></i></div>
                    <div class="stat-value"><?= number_format($totalLikes) ?></div>
                    <div class="stat-label">Total Likes</div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Chart -->
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Earnings Overview</span>
                        <span class="text-muted" style="font-size:.75rem">Last 6 months</span>
                    </div>
                    <div class="card-body">
                        <canvas id="earningsChart" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Quick stats -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header">Quick Actions</div>
                    <div class="card-body d-flex flex-column gap-2">
                        <a href="/art-gallery/artist/upload-artwork.php" class="btn btn-outline-dark text-start">
                            <i class="bi bi-upload me-2"></i>Upload New Artwork
                        </a>
                        <a href="/art-gallery/artist/orders.php" class="btn btn-outline-dark text-start d-flex justify-content-between">
                            <span><i class="bi bi-box-seam me-2"></i>Pending Orders</span>
                            <?php if ($pendingOrders > 0): ?><span class="badge bg-danger"><?= $pendingOrders ?></span><?php endif; ?>
                        </a>
                        <a href="/art-gallery/artist/offers.php" class="btn btn-outline-dark text-start d-flex justify-content-between">
                            <span><i class="bi bi-chat-text me-2"></i>New Offers</span>
                            <?php if ($pendingOffers > 0): ?><span class="badge bg-warning text-dark"><?= $pendingOffers ?></span><?php endif; ?>
                        </a>
                        <a href="/art-gallery/artist/earnings.php" class="btn btn-outline-dark text-start">
                            <i class="bi bi-wallet2 me-2"></i>Earnings & Withdrawal
                        </a>
                        <a href="/art-gallery/artist/profile.php" class="btn btn-outline-dark text-start">
                            <i class="bi bi-person me-2"></i>Edit Profile
                        </a>
                        <div class="border-top mt-auto pt-2">
                            <div class="text-muted small">Total artworks: <strong><?= $totalArtworks ?></strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Recent Orders</span>
                <a href="/art-gallery/artist/orders.php" class="btn btn-sm btn-outline-dark">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentOrders)): ?>
                <div class="text-center py-4 text-muted">No orders yet.</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr>
                            <th>Artwork</th><th>Buyer</th><th>Amount</th><th>Status</th><th>Date</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($recentOrders as $o): ?>
                        <tr>
                            <td><?= truncate(sanitize($o['artwork_title']), 30) ?></td>
                            <td><?= sanitize($o['buyer_name']) ?></td>
                            <td><?= formatPrice($o['total_price']) ?></td>
                            <td><span class="badge status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
                            <td><?= date('M j', strtotime($o['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- My Artworks Preview -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>My Artworks</span>
                <a href="/art-gallery/artist/artworks.php" class="btn btn-sm btn-outline-dark">Manage All</a>
            </div>
            <div class="card-body">
                <?php if (empty($myArtworks)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-image" style="font-size:3rem;color:var(--border)"></i>
                    <p class="mt-2 text-muted">No artworks yet. <a href="/art-gallery/artist/upload-artwork.php">Upload your first!</a></p>
                </div>
                <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($myArtworks as $art): ?>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div style="position:relative;border-radius:var(--radius);overflow:hidden;aspect-ratio:1;background:var(--canvas-alt);">
                            <img src="<?= artworkImage($art['image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                            <div style="position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,0,0,.7));padding:.5rem;font-size:.7rem;color:white;">
                                <div style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= sanitize($art['title']) ?></div>
                                <div><?= !$art['is_approved'] ? '⏳ Pending' : ($art['availability']==='sold'?'✓ Sold':'✓ Live') ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('earningsChart').getContext('2d');
const months = <?= json_encode(array_column($monthlyData, 'month')) ?>;
const earnings = <?= json_encode(array_map(fn($m) => (float)$m['total'], $monthlyData)) ?>;
new Chart(ctx, {
    type: 'line',
    data: {
        labels: months.length ? months : ['Jan','Feb','Mar','Apr','May','Jun'],
        datasets: [{
            label: 'Earnings (NPR)',
            data: earnings.length ? earnings : [0,0,0,0,0,0],
            borderColor: '#b8904a',
            backgroundColor: 'rgba(184,144,74,.08)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#b8904a',
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.04)' },
                 ticks: { font: { family: 'DM Sans', size: 11 } } },
            x: { grid: { display: false },
                 ticks: { font: { family: 'DM Sans', size: 11 } } }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
