<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('artist');
$user = getCurrentUser();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];
    $check = $conn->prepare("SELECT id, image FROM artworks WHERE id=? AND artist_id=?");
    $check->bind_param("ii", $deleteId, $user['id']);
    $check->execute();
    $toDelete = $check->get_result()->fetch_assoc();
    if ($toDelete) {
        // Delete dependent records first to avoid FK constraint errors
        $conn->query("DELETE FROM orders WHERE artwork_id=$deleteId");
        $conn->query("DELETE FROM offers WHERE artwork_id=$deleteId");
        $conn->query("DELETE FROM reviews WHERE artwork_id=$deleteId");
        $conn->query("DELETE FROM likes WHERE artwork_id=$deleteId");
        $conn->query("DELETE FROM wishlist WHERE artwork_id=$deleteId");
        $conn->query("DELETE FROM cart WHERE artwork_id=$deleteId");
        $conn->query("DELETE FROM artwork_views WHERE artwork_id=$deleteId");
        $conn->query("DELETE FROM comments WHERE artwork_id=$deleteId");
        $conn->query("DELETE FROM artworks WHERE id=$deleteId");
        @unlink(__DIR__ . '/../uploads/artworks/' . $toDelete['image']);
        setFlash('success', 'Artwork deleted.');
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$myArtworks = $conn->query("SELECT a.*, c.name as category_name,
    (SELECT COUNT(*) FROM orders o WHERE o.artwork_id=a.id AND o.status='delivered') as sales
    FROM artworks a LEFT JOIN categories c ON a.category_id=c.id
    WHERE a.artist_id={$user['id']} ORDER BY a.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'My Artworks';
require_once __DIR__ . '/../includes/header.php';
showFlash();
?>

<div class="dash-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="dash-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 style="font-family:var(--font-display);font-size:1.6rem;font-weight:400;margin-bottom:0">My Artworks</h2>
            <a href="<?= dirname(dirname($_SERVER['PHP_SELF'])) ?>/artist/upload-artwork.php" class="btn btn-dark">
                <i class="bi bi-plus-lg me-1"></i>Upload New
            </a>
        </div>

        <?php if (empty($myArtworks)): ?>
        <div class="card"><div class="card-body text-center py-5">
            <i class="bi bi-image" style="font-size:3rem;color:var(--border)"></i>
            <h5 class="mt-3" style="font-family:var(--font-display);font-weight:400">No artworks yet</h5>
            <p class="text-muted">Upload your first artwork to start selling.</p>
            <a href="<?= dirname(dirname($_SERVER['PHP_SELF'])) ?>/artist/upload-artwork.php" class="btn btn-dark">Upload Artwork</a>
        </div></div>
        <?php else: ?>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr>
                            <th>Artwork</th><th>Category</th><th>Price</th>
                            <th>Status</th><th>Views</th><th>Sales</th><th>Actions</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($myArtworks as $art): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?= artworkImage($art['image']) ?>" style="width:44px;height:44px;object-fit:cover;border-radius:var(--radius-sm);" alt="">
                                    <div>
                                        <div style="font-weight:500;font-size:.875rem"><?= sanitize($art['title']) ?></div>
                                        <div style="font-size:.75rem;color:var(--ink-muted)"><?= timeAgo($art['created_at']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-size:.875rem"><?= sanitize($art['category_name'] ?? '—') ?></td>
                            <td style="font-size:.875rem;font-weight:500"><?= formatPrice($art['price']) ?></td>
                            <td>
                                <?php if (!$art['is_approved']): ?>
                                    <span class="badge status-pending">Pending</span>
                                <?php elseif ($art['availability']==='sold'): ?>
                                    <span class="badge status-cancelled">Sold</span>
                                <?php else: ?>
                                    <span class="badge status-active">Live</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:.875rem"><?= number_format($art['views']) ?></td>
                            <td style="font-size:.875rem"><?= $art['sales'] ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?= dirname(dirname($_SERVER['PHP_SELF'])) ?>/artwork.php?id=<?= $art['id'] ?>" class="btn btn-icon btn-sm" title="View"><i class="bi bi-eye"></i></a>
                                    <a href="<?= dirname(dirname($_SERVER['PHP_SELF'])) ?>/artist/edit-artwork.php?id=<?= $art['id'] ?>" class="btn btn-icon btn-sm" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <form method="POST" onsubmit="return confirm('Delete this artwork? This cannot be undone.')">
                                        <input type="hidden" name="delete_id" value="<?= $art['id'] ?>">
                                        <button type="submit" class="btn btn-icon btn-sm" style="color:var(--danger)" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
