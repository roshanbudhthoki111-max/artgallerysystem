<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('artist');
$user = getCurrentUser();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $price       = (float)($_POST['price'] ?? 0);
    $categoryId  = (int)($_POST['category_id'] ?? 0);
    $availability = in_array($_POST['availability'] ?? '', ['available','reserved']) ? $_POST['availability'] : 'available';

    if (empty($title) || $price <= 0) {
        $error = 'Title and price are required.';
    } elseif (empty($_FILES['image']['name'])) {
        $error = 'Please upload an artwork image or video.';
    } else {
        $upload = uploadFile($_FILES['image'], 'artworks');
        if (isset($upload['error'])) {
            $error = $upload['error'];
        } else {
            $filename  = $upload['filename'];
            $mediaType = str_ends_with($filename, '.mp4') || str_ends_with($filename, '.mov') ? 'video' : 'image';
            $autoApprove = getSetting('artist_auto_approve') === '1' ? 1 : 0;

            $stmt = $conn->prepare("INSERT INTO artworks (artist_id, title, description, price, category_id, image, media_type, availability, is_approved) VALUES (?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param("issdisssi", $user['id'], $title, $description, $price, $categoryId, $filename, $mediaType, $availability, $autoApprove);
            if ($stmt->execute()) {
                setFlash('success', 'Artwork uploaded! ' . ($autoApprove ? 'It\'s now live.' : 'Pending admin approval.'));
                header("Location: /art-gallery/artist/artworks.php");
                exit;
            }
            $error = 'Failed to save artwork.';
        }
    }
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$pageTitle = 'Upload Artwork';
require_once __DIR__ . '/../includes/header.php';
showFlash();
?>

<div class="dash-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="dash-main">
        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="/art-gallery/artist/artworks.php" class="btn btn-icon"><i class="bi bi-arrow-left"></i></a>
            <div>
                <h2 style="font-family:var(--font-display);font-size:1.6rem;font-weight:400;margin-bottom:0">Upload Artwork</h2>
            </div>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger mb-4"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row g-4">
                <!-- Upload zone -->
                <div class="col-lg-5">
                    <div class="card h-100">
                        <div class="card-header">Artwork File</div>
                        <div class="card-body">
                            <div class="upload-zone" id="upload-zone">
                                <input type="file" name="image" id="artwork-file" accept="image/*,video/*" class="d-none" required>
                                <div class="upload-placeholder">
                                    <i class="bi bi-cloud-upload" style="font-size:2.5rem;color:var(--border)"></i>
                                    <div class="mt-2" style="font-weight:500">Drop your artwork here</div>
                                    <div class="text-muted small mt-1">JPG, PNG, GIF, WebP, MP4 — Max 20MB</div>
                                    <button type="button" class="btn btn-outline-dark btn-sm mt-3" onclick="document.getElementById('artwork-file').click()">
                                        Browse Files
                                    </button>
                                </div>
                                <div class="upload-preview"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Details -->
                <div class="col-lg-7">
                    <div class="card">
                        <div class="card-header">Artwork Details</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Title *</label>
                                <input type="text" name="title" class="form-control" value="<?= sanitize($_POST['title'] ?? '') ?>" placeholder="Give your artwork a compelling title" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4" placeholder="Describe your artwork — materials, inspiration, dimensions…"><?= sanitize($_POST['description'] ?? '') ?></textarea>
                            </div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Price (NPR) *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">NPR</span>
                                        <input type="number" name="price" class="form-control" value="<?= $_POST['price'] ?? '' ?>" placeholder="0.00" min="1" step="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Category</label>
                                    <select name="category_id" class="form-select">
                                        <option value="">— Select Category —</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= ($_POST['category_id'] ?? '')==$cat['id']?'selected':'' ?>><?= sanitize($cat['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Availability</label>
                                <div class="d-flex gap-3">
                                    <label class="d-flex align-items-center gap-2 cursor-pointer">
                                        <input type="radio" name="availability" value="available" checked>
                                        <span>Available for sale</span>
                                    </label>
                                    <label class="d-flex align-items-center gap-2 cursor-pointer">
                                        <input type="radio" name="availability" value="reserved">
                                        <span>Reserved</span>
                                    </label>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-dark px-4">
                                    <i class="bi bi-upload me-2"></i>Upload Artwork
                                </button>
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
