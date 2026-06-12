<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');

    if ($action === 'add') {
        $name = sanitize($_POST['name'] ?? '');
        $desc = sanitize($_POST['description'] ?? '');
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)));
        if ($name) {
            $stmt = $conn->prepare("INSERT INTO categories (name, slug, description) VALUES (?,?,?) ON DUPLICATE KEY UPDATE description=?");
            $stmt->bind_param("ssss", $name, $slug, $desc, $desc);
            $stmt->execute();
            setFlash('success', 'Category added!');
        }
    }

    if ($action === 'delete') {
        $id = (int)$_POST['cat_id'];
        $conn->query("DELETE FROM categories WHERE id=$id");
        setFlash('success', 'Category deleted.');
    }

    header("Location: /art-gallery/admin/categories.php");
    exit;
}

$categories = $conn->query("SELECT c.*, COUNT(a.id) as artwork_count FROM categories c 
    LEFT JOIN artworks a ON a.category_id=c.id GROUP BY c.id ORDER BY c.name")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Categories';
require_once __DIR__ . '/../includes/header.php';
showFlash();
?>

<div class="dash-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="dash-main">
        <h2 style="font-family:var(--font-display);font-size:1.6rem;font-weight:400;margin-bottom:1.5rem">Manage Categories</h2>

        <div class="row g-4">
            <!-- Add form -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">Add New Category</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="add">
                            <div class="mb-3">
                                <label class="form-label">Category Name *</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Watercolor" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Brief description…"></textarea>
                            </div>
                            <button type="submit" class="btn btn-dark w-100">Add Category</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Category list -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Category</th><th>Slug</th><th>Description</th><th>Artworks</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td style="font-weight:500;font-size:.9rem"><?= sanitize($cat['name']) ?></td>
                                <td><code style="font-size:.75rem;color:var(--gold)"><?= $cat['slug'] ?></code></td>
                                <td style="font-size:.8rem;color:var(--ink-muted);max-width:200px"><?= truncate(sanitize($cat['description'] ?? ''), 50) ?></td>
                                <td style="font-size:.875rem"><?= $cat['artwork_count'] ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Delete this category?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                                        <button type="submit" class="btn btn-icon btn-sm" style="color:var(--danger)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
