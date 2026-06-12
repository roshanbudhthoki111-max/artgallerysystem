<?php
$pageTitle = 'Gallery';
require_once __DIR__ . '/includes/header.php';

// Filters
$search   = sanitize($_GET['search'] ?? '');
$category = sanitize($_GET['category'] ?? '');
$minPrice = (float)($_GET['min_price'] ?? 0);
$maxPrice = (float)($_GET['max_price'] ?? 500000);
$sort     = sanitize($_GET['sort'] ?? 'newest');
$page     = max(1, (int)($_GET['page'] ?? 1));
$limit    = 12;
$offset   = ($page - 1) * $limit;

$filters = [
    'search'    => $search,
    'category'  => $category,
    'min_price' => $minPrice ?: null,
    'max_price' => $maxPrice < 500000 ? $maxPrice : null,
    'sort'      => $sort,
    'availability' => 'available'
];

$artworks = getArtworks($filters, $limit, $offset);

// Count for pagination
$countFilters = $filters;
$whereArr = ["a.is_approved = 1", "a.availability = 'available'"];
if ($search)   $whereArr[] = "(a.title LIKE '%$search%' OR a.description LIKE '%$search%')";
if ($category) $whereArr[] = "c.slug = '$category'";
$whereStr = "WHERE " . implode(" AND ", $whereArr);
$totalResult = $conn->query("SELECT COUNT(*) as c FROM artworks a LEFT JOIN categories c ON a.category_id = c.id $whereStr");
$total = $totalResult->fetch_assoc()['c'];

$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>

<div class="page-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="section-label">Explore</div>
                <h1 class="page-header-title">Art <em style="font-family:var(--font-display);font-style:italic;color:var(--gold)">Gallery</em></h1>
                <p class="text-muted"><?= number_format($total) ?> artworks available</p>
            </div>
            <div class="col-lg-6">
                <form method="GET" class="d-flex gap-2">
                    <div class="flex-grow-1 position-relative">
                        <i class="bi bi-search position-absolute" style="top:50%;left:.85rem;transform:translateY(-50%);color:var(--ink-muted);font-size:.9rem;pointer-events:none;"></i>
                        <input type="text" name="search" class="form-control ps-4" value="<?= $search ?>" placeholder="Search artworks, artists…">
                    </div>
                    <button type="submit" class="btn btn-dark">Search</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="row g-4">
        <!-- Sidebar Filters -->
        <div class="col-lg-3">
            <div class="sidebar">
                <form method="GET" id="filter-form">
                    <?php if ($search): ?><input type="hidden" name="search" value="<?= $search ?>"><<?php endif; ?>

                    <div class="sidebar-heading">Categories</div>
                    <div class="d-flex flex-column gap-1 mb-4">
                        <a href="/art-gallery/gallery.php<?= $search ? '?search='.$search : '' ?>" class="sidebar-nav-link <?= !$category ? 'active' : '' ?>">
                            <i class="bi bi-grid-3x3-gap"></i> All Artworks
                        </a>
                        <?php foreach ($categories as $cat): ?>
                        <a href="?category=<?= $cat['slug'] ?><?= $search ? '&search='.$search : '' ?>" class="sidebar-nav-link <?= $category === $cat['slug'] ? 'active' : '' ?>">
                            <i class="bi bi-tag"></i> <?= sanitize($cat['name']) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>

                    <div class="sidebar-heading">Price Range</div>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between small text-muted mb-2">
                            <span>NPR 0</span>
                            <span id="price-display">NPR <?= number_format($maxPrice) ?></span>
                        </div>
                        <input type="range" name="max_price" id="price_max" class="form-range w-100"
                               min="0" max="500000" step="1000" value="<?= $maxPrice ?>">
                        <input type="hidden" name="min_price" value="0">
                        <?php if ($category): ?><input type="hidden" name="category" value="<?= $category ?>"><<?php endif; ?>
                    </div>

                    <div class="sidebar-heading">Sort By</div>
                    <select name="sort" class="form-select form-select-sm mb-4" onchange="this.form.submit()">
                        <option value="newest"     <?= $sort==='newest'     ?'selected':'' ?>>Newest First</option>
                        <option value="price_asc"  <?= $sort==='price_asc'  ?'selected':'' ?>>Price: Low to High</option>
                        <option value="price_desc" <?= $sort==='price_desc' ?'selected':'' ?>>Price: High to Low</option>
                        <option value="popular"    <?= $sort==='popular'    ?'selected':'' ?>>Most Viewed</option>
                        <option value="liked"      <?= $sort==='liked'      ?'selected':'' ?>>Most Liked</option>
                    </select>

                    <button type="submit" class="btn btn-dark btn-sm w-100">Apply Filters</button>
                    <a href="/art-gallery/gallery.php" class="btn btn-outline-secondary btn-sm w-100 mt-2">Reset</a>
                </form>
            </div>
        </div>

        <!-- Artworks Grid -->
        <div class="col-lg-9">
            <?php if (empty($artworks)): ?>
            <div class="text-center py-5">
                <i class="bi bi-search" style="font-size:3rem;color:var(--border)"></i>
                <h4 class="mt-3" style="font-family:var(--font-display);font-weight:400">No artworks found</h4>
                <p class="text-muted">Try adjusting your filters or search term.</p>
                <a href="/art-gallery/gallery.php" class="btn btn-dark">Browse All</a>
            </div>
            <?php else: ?>
            <div class="row g-4">
                <?php foreach ($artworks as $art): ?>
                <div class="col-sm-6 col-xl-4 fade-in-up">
                    <div class="artwork-card h-100" onclick="location.href='/art-gallery/artwork.php?id=<?= $art['id'] ?>'">
                        <div class="artwork-card-img">
                            <img src="<?= artworkImage($art['image']) ?>" alt="<?= sanitize($art['title']) ?>" loading="lazy">
                            <?php if ($art['is_featured']): ?>
                                <span class="artwork-badge featured">Featured</span>
                            <?php endif; ?>
                            <div class="artwork-card-overlay">
                                <div class="artwork-card-actions">
                                    <a href="/art-gallery/artwork.php?id=<?= $art['id'] ?>" class="btn btn-icon" onclick="event.stopPropagation()">
                                        <i class="bi bi-eye text-white"></i>
                                    </a>
                                    <?php if (isLoggedIn() && $_SESSION['user_role'] === 'customer'): ?>
                                    <button class="btn btn-icon" data-action="toggle_wishlist" data-id="<?= $art['id'] ?>" onclick="event.stopPropagation()">
                                        <i class="bi bi-bookmark text-white"></i>
                                    </button>
                                    <button class="btn btn-icon" data-action="add_to_cart" data-id="<?= $art['id'] ?>" onclick="event.stopPropagation()">
                                        <i class="bi bi-bag-plus text-white"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="artwork-card-body">
                            <div class="artwork-title"><?= sanitize($art['title']) ?></div>
                            <div class="artwork-artist">by <?= sanitize($art['artist_name']) ?></div>
                            <?php if ($art['category_name']): ?>
                            <div class="mb-2"><span class="badge bg-light text-dark" style="font-size:.7rem;"><?= sanitize($art['category_name']) ?></span></div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="artwork-price"><?= formatPrice($art['price']) ?></div>
                                <div class="artwork-meta">
                                    <span><i class="bi bi-eye me-1"></i><?= number_format($art['views']) ?></span>
                                    <span><i class="bi bi-heart me-1"></i><?= $art['likes_count'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <div class="mt-5">
                <?php echo paginate($total, $limit, $page, '/art-gallery/gallery.php'); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
