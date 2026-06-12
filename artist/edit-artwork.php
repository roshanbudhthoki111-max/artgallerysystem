<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('artist');
$user = getCurrentUser();

$artId = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM artworks WHERE id=? AND artist_id=?");
$stmt->bind_param("ii", $artId, $user['id']);
$stmt->execute();
$art = $stmt->get_result()->fetch_assoc();

if (!$art) { header("Location: /art-gallery/artist/artworks.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $categoryId  = (int)($_POST['category_id'] ?? 0);
    $availability = in_array($_POST['availability'], ['available','sold','reserved']) ? $_POST['availability'] : $art['availability'];

    $image = $art['image'];
    if (!empty($_FILES['image']['name'])) {
        $upload = uploadFile($_FILES['image'], 'artworks');
        if (isset($upload['success'])) {
            @unlink(__DIR__ . '/../uploads/artworks/' . $art['image']);
            $image = $upload['filename'];
        }
    }

    // Note: Re-editing sets is_approved=0, requiring admin to re-approve
    $stmt = $conn->prepare("UPDATE artworks SET title=?, description=?, price=?, category_id=?, image=?, availability=?, is_approved=0 WHERE id=? AND artist_id=?");
    $stmt->bind_param("ssdissii", $title, $description, $price, $categoryId, $image, $availability, $artId, $user['id']);
    $stmt->execute();

    setFlash('success', 'Artwork updated!');
    header("Location: /art-gallery/artist/artworks.php");
    exit;
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$pageTitle = 'Edit Artwork';
require_once __DIR__ . '/../includes/header.php';
showFlash();
?>

<div class="dash-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="dash-main">
        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="/art-gallery/artist/artworks.php" class="btn btn-icon"><i class="bi bi-arrow-left"></i></a>
            <h2 style="font-family:var(--font-display);font-size:1.6rem;font-weight:400;margin-bottom:0">Edit Artwork</h2>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-header">Artwork Image</div>
                        <div class="card-body">
                            <img src="<?= artworkImage($art['image']) ?>" id="current-img"
                                 style="width:100%;border-radius:var(--radius);margin-bottom:1rem;aspect-ratio:4/3;object-fit:cover;" alt="">
                            <div class="upload-zone" style="padding:1.5rem;">
                                <input type="file" name="image" id="artwork-file" accept="image/*,video/*" class="d-none">
                                <div class="upload-placeholder">
                                    <i class="bi bi-cloud-upload" style="font-size:1.5rem;color:var(--border)"></i>
                                    <div class="mt-1" style="font-size:.875rem;font-weight:500">Replace image</div>
                                    <div class="text-muted" style="font-size:.75rem">JPG, PNG, GIF, WebP</div>
                                </div>
                                <div class="upload-preview"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header">Details</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Title *</label>
                                <input type="text" name="title" class="form-control" value="<?= sanitize($art['title']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4"><?= sanitize($art['description'] ?? '') ?></textarea>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Price (NPR) *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">NPR</span>
                                        <input type="number" name="price" class="form-control" value="<?= $art['price'] ?>" min="1" step="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Category</label>
                                    <select name="category_id" class="form-select">
                                        <option value="">— No Category —</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= $art['category_id']==$cat['id']?'selected':'' ?>><?= sanitize($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Availability</label>
                                <select name="availability" class="form-select">
                                    <option value="available" <?= $art['availability']==='available'?'selected':'' ?>>Available</option>
                                    <option value="reserved"  <?= $art['availability']==='reserved'?'selected':'' ?>>Reserved</option>
                                    <option value="sold"      <?= $art['availability']==='sold'?'selected':'' ?>>Sold</option>
                                </select>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-dark px-4">Save Changes</button>
                                <a href="/art-gallery/artist/artworks.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
