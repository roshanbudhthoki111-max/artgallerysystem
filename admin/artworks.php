<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$user = getCurrentUser();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $artId  = (int)$_POST['artwork_id'];
    $action = sanitize($_POST['action'] ?? '');

    switch ($action) {
        case 'approve':
            $conn->query("UPDATE artworks SET is_approved=1 WHERE id=$artId");
            setFlash('success', 'Artwork approved and is now live!');
            break;
        case 'reject':
            $conn->query("UPDATE artworks SET is_approved=0 WHERE id=$artId");
            setFlash('success', 'Artwork rejected.');
            break;
        case 'feature':
            $conn->query("UPDATE artworks SET is_featured=1 WHERE id=$artId");
            setFlash('success', 'Artwork featured on homepage!');
            break;
        case 'unfeature':
            $conn->query("UPDATE artworks SET is_featured=0 WHERE id=$artId");
            setFlash('success', 'Artwork removed from featured.');
            break;
        case 'delete':
            $art = $conn->query("SELECT image FROM artworks WHERE id=$artId")->fetch_assoc();
            if ($art) {
                // Delete dependent records first (artwork_id is NOT NULL in these tables)
                $conn->query("DELETE FROM orders WHERE artwork_id=$artId");
                $conn->query("DELETE FROM offers WHERE artwork_id=$artId");
                $conn->query("DELETE FROM reviews WHERE artwork_id=$artId");
                $conn->query("DELETE FROM likes WHERE artwork_id=$artId");
                $conn->query("DELETE FROM wishlist WHERE artwork_id=$artId");
                $conn->query("DELETE FROM cart WHERE artwork_id=$artId");
                $conn->query("DELETE FROM artwork_views WHERE artwork_id=$artId");
                $conn->query("DELETE FROM comments WHERE artwork_id=$artId");
                $conn->query("DELETE FROM artworks WHERE id=$artId");
                if ($conn->affected_rows > 0) {
                    @unlink(__DIR__ . '/../uploads/artworks/' . $art['image']);
                    setFlash('success', 'Artwork deleted.');
                } else {
                    setFlash('error', 'Delete failed — artwork not found.');
                }
            } else {
                setFlash('error', 'Artwork not found.');
            }
            break;
    }
    header("Location: /art-gallery/admin/artworks.php");
    exit;
}

$filter = sanitize($_GET['filter'] ?? 'all');
$search = sanitize($_GET['search'] ?? '');

$where = ["1=1"];
if ($filter === 'pending')  $where[] = "a.is_approved=0";
if ($filter === 'approved') $where[] = "a.is_approved=1";
if ($filter === 'featured') $where[] = "a.is_featured=1";
if ($search) $where[] = "(a.title LIKE '%$search%' OR u.name LIKE '%$search%')";

$whereStr = "WHERE " . implode(" AND ", $where);

$artworks = $conn->query("SELECT a.*, u.name as artist_name, c.name as category_name
    FROM artworks a LEFT JOIN users u ON a.artist_id=u.id LEFT JOIN categories c ON a.category_id=c.id
    $whereStr ORDER BY a.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Moderate Artworks';
require_once __DIR__ . '/../includes/header.php';
showFlash();
?>

<div class="dash-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="dash-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 style="font-family:var(--font-display);font-size:1.6rem;font-weight:400;margin-bottom:0">
                Artworks <span style="font-size:1rem;color:var(--ink-muted)">(<?= count($artworks) ?>)</span>
            </h2>
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm" value="<?= $search ?>" placeholder="Search artworks…">
                <input type="hidden" name="filter" value="<?= $filter ?>">
                <button class="btn btn-dark btn-sm">Search</button>
            </form>
        </div>

        <div class="d-flex gap-2 mb-4 flex-wrap">
            <?php foreach (['all'=>'All','pending'=>'Pending','approved'=>'Approved','featured'=>'Featured'] as $k=>$v): ?>
            <a href="?filter=<?= $k ?>" class="category-pill <?= $filter===$k?'active':'' ?>"><?= $v ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($artworks)): ?>
        <div class="card"><div class="card-body text-center py-5 text-muted">No artworks found.</div></div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($artworks as $art): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div style="position:relative;aspect-ratio:4/3;overflow:hidden;background:var(--canvas-alt);">
                        <img src="<?= artworkImage($art['image']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                        <div style="position:absolute;top:.5rem;left:.5rem;display:flex;gap:.3rem;flex-wrap:wrap;">
                            <?php if (!$art['is_approved']): ?>
                            <span class="badge status-pending">Pending</span>
                            <?php else: ?>
                            <span class="badge status-active">Live</span>
                            <?php endif; ?>
                            <?php if ($art['is_featured']): ?>
                            <span class="badge" style="background:var(--gold);color:white">Featured</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="font-weight:600;font-size:.9rem;margin-bottom:.25rem"><?= sanitize($art['title']) ?></div>
                        <div style="font-size:.8rem;color:var(--ink-muted)">by <?= sanitize($art['artist_name']) ?></div>
                        <div style="font-size:.8rem;color:var(--ink-muted)"><?= sanitize($art['category_name'] ?? '—') ?></div>
                        <div style="font-family:var(--font-display);font-size:1.1rem;font-weight:600;margin-top:.5rem"><?= formatPrice($art['price']) ?></div>
                    </div>
                    <div class="card-body border-top pt-2 d-flex flex-wrap gap-1">
                        <form method="POST" class="d-contents">
                            <input type="hidden" name="artwork_id" value="<?= $art['id'] ?>">
                            <?php if (!$art['is_approved']): ?>
                            <button name="action" value="approve" class="btn btn-sm" style="background:var(--success);color:white;font-size:.75rem;padding:.25rem .6rem;">Approve</button>
                            <?php else: ?>
                            <button name="action" value="reject" class="btn btn-sm btn-outline-warning" style="font-size:.75rem;padding:.25rem .6rem;">Unpublish</button>
                            <?php endif; ?>
                            <?php if (!$art['is_featured']): ?>
                            <button name="action" value="feature" class="btn btn-sm" style="background:var(--gold);color:white;font-size:.75rem;padding:.25rem .6rem;">Feature</button>
                            <?php else: ?>
                            <button name="action" value="unfeature" class="btn btn-sm btn-outline-secondary" style="font-size:.75rem;padding:.25rem .6rem;">Unfeature</button>
                            <?php endif; ?>
                            <a href="/art-gallery/artwork.php?id=<?= $art['id'] ?>" class="btn btn-sm btn-icon" title="View"><i class="bi bi-eye"></i></a>
                        </form>
                        <form method="POST" onsubmit="return confirm('Delete this artwork permanently? This cannot be undone.')">
                            <input type="hidden" name="artwork_id" value="<?= $art['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn btn-sm btn-icon" style="color:var(--danger)" title="Delete"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
