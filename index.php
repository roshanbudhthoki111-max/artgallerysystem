<?php
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';

// Featured artworks
$featuredArtworks = getArtworks(['featured' => true], 6);
if (empty($featuredArtworks)) $featuredArtworks = getArtworks([], 6);

// Categories
$catResult = $conn->query("SELECT * FROM categories ORDER BY name LIMIT 8");
$categories = $catResult->fetch_all(MYSQLI_ASSOC);

// Featured artists
$artistResult = $conn->query("SELECT u.*, COUNT(a.id) as artwork_count 
    FROM users u LEFT JOIN artworks a ON u.id = a.artist_id AND a.is_approved = 1
    WHERE u.role = 'artist' AND u.status = 'active'
    GROUP BY u.id ORDER BY artwork_count DESC LIMIT 6");
$artists = $artistResult->fetch_all(MYSQLI_ASSOC);

// Stats
$totalArtworks = $conn->query("SELECT COUNT(*) as c FROM artworks WHERE is_approved=1")->fetch_assoc()['c'];
$totalArtists  = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='artist' AND status='active'")->fetch_assoc()['c'];
$totalSales    = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status='delivered'")->fetch_assoc()['c'];
?>

<!-- HERO -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="hero-content fade-in-up">
                    <div class="hero-tagline">◈ The Art Marketplace</div>
                    <h1 class="hero-title">Discover <em>Art</em> that Speaks to You</h1>
                    <p class="hero-subtitle">Handpicked artworks from Nepal's most talented contemporary artists. Original pieces, limited editions, and commissioned works.</p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="/art-gallery/gallery.php" class="btn btn-gold btn-lg px-4">
                            <i class="bi bi-compass me-2"></i>Explore Gallery
                        </a>
                    </div>
                    <div class="hero-stats">
                        <div>
                            <div class="hero-stat-num"><?= number_format($totalArtworks) ?>+</div>
                            <div class="hero-stat-label">Artworks</div>
                        </div>
                        <div>
                            <div class="hero-stat-num"><?= number_format($totalArtists) ?>+</div>
                            <div class="hero-stat-label">Artists</div>
                        </div>
                        <div>
                            <div class="hero-stat-num"><?= number_format($totalSales) ?>+</div>
                            <div class="hero-stat-label">Sales</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <div class="hero-images">
                    <?php if (!empty($featuredArtworks[0])): ?>
                    <div class="hero-img-main">
                        <img src="<?= artworkImage($featuredArtworks[0]['image']) ?>" alt="<?= sanitize($featuredArtworks[0]['title']) ?>">
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($featuredArtworks[1])): ?>
                    <div class="hero-img-secondary">
                        <img src="<?= artworkImage($featuredArtworks[1]['image']) ?>" alt="<?= sanitize($featuredArtworks[1]['title']) ?>">
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CATEGORIES -->
<section class="py-5 bg-white border-bottom">
    <div class="container">
        <div class="d-flex gap-2 flex-wrap justify-content-center">
            <a href="/art-gallery/gallery.php" class="category-pill active">All Art</a>
            <?php foreach ($categories as $cat): ?>
            <a href="/art-gallery/gallery.php?category=<?= $cat['slug'] ?>" class="category-pill">
                <?= sanitize($cat['name']) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FEATURED ARTWORKS -->
<section class="py-5 py-lg-6">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <div class="section-label">Curated Selection</div>
                <h2 class="section-title">Featured <em>Artworks</em></h2>
            </div>
            <a href="/art-gallery/gallery.php" class="btn btn-outline-dark btn-sm">View All <i class="bi bi-arrow-right ms-1"></i></a>
        </div>

        <div class="row g-4">
            <?php foreach ($featuredArtworks as $art): ?>
            <div class="col-md-6 col-lg-4">
                <div class="artwork-card h-100" onclick="location.href='/art-gallery/artwork.php?id=<?= $art['id'] ?>'">
                    <div class="artwork-card-img">
                        <img src="<?= artworkImage($art['image']) ?>" alt="<?= sanitize($art['title']) ?>" loading="lazy">
                        <?php if ($art['is_featured']): ?>
                            <span class="artwork-badge featured">Featured</span>
                        <?php elseif ($art['availability'] === 'sold'): ?>
                            <span class="artwork-badge sold">Sold</span>
                        <?php endif; ?>
                        <div class="artwork-card-overlay">
                            <div class="artwork-card-actions">
                                <a href="/art-gallery/artwork.php?id=<?= $art['id'] ?>" class="btn btn-icon btn-sm" onclick="event.stopPropagation()" title="View">
                                    <i class="bi bi-eye text-white"></i>
                                </a>
                                <?php if (isLoggedIn() && $_SESSION['user_role'] === 'customer'): ?>
                                <button class="btn btn-icon btn-sm" data-action="toggle_wishlist" data-id="<?= $art['id'] ?>" onclick="event.stopPropagation()" title="Wishlist">
                                    <i class="bi bi-bookmark text-white"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="artwork-card-body">
                        <div class="artwork-title"><?= sanitize($art['title']) ?></div>
                        <div class="artwork-artist">by <?= sanitize($art['artist_name']) ?></div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="artwork-price"><?= formatPrice($art['price']) ?></div>
                            <div class="artwork-meta">
                                <span><i class="bi bi-eye me-1"></i><?= number_format($art['views']) ?></span>
                                <span><i class="bi bi-heart me-1"></i><?= number_format($art['likes_count']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="py-5 bg-white border-top border-bottom">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label">Simple Process</div>
            <h2 class="section-title">How <em>ArtVault</em> Works</h2>
        </div>
        <div class="row g-4 text-center">
            <?php $steps = [
                ['icon'=>'bi-search','num'=>'01','title'=>'Discover','desc'=>'Browse thousands of original artworks from verified Nepali artists.'],
                ['icon'=>'bi-heart','num'=>'02','title'=>'Connect','desc'=>'Like, save to wishlist, and send offers to artists directly.'],
                ['icon'=>'bi-bag-check','num'=>'03','title'=>'Purchase','desc'=>'Secure checkout with eSewa or Khalti. Buyer protection guaranteed.'],
                ['icon'=>'bi-truck','num'=>'04','title'=>'Receive','desc'=>'Track your order and receive your artwork safely at your door.'],
            ]; foreach ($steps as $step): ?>
            <div class="col-md-6 col-lg-3">
                <div class="py-3">
                    <div class="d-inline-flex align-items-center justify-content-center mb-3"
                         style="width:56px;height:56px;border-radius:50%;background:var(--canvas-alt);font-size:1.4rem;">
                        <i class="bi <?= $step['icon'] ?>" style="color:var(--gold)"></i>
                    </div>
                    <div style="font-size:.7rem;letter-spacing:.2em;color:var(--ink-muted);margin-bottom:.25rem"><?= $step['num'] ?></div>
                    <h5 style="font-family:var(--font-display);font-size:1.2rem;font-weight:400"><?= $step['title'] ?></h5>
                    <p class="text-muted small"><?= $step['desc'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FEATURED ARTISTS -->
<?php if (!empty($artists)): ?>
<section class="py-5 py-lg-6">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <div class="section-label">Meet the Creators</div>
                <h2 class="section-title">Featured <em>Artists</em></h2>
            </div>
            <a href="/art-gallery/artists.php" class="btn btn-outline-dark btn-sm">All Artists <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="row g-4">
            <?php foreach ($artists as $artist): ?>
            <div class="col-md-6 col-lg-4 col-xl-2">
                <a href="/art-gallery/artist-profile.php?id=<?= $artist['id'] ?>" class="text-decoration-none">
                    <div class="artist-card">
                        <img src="<?= profileImage($artist['profile_image']) ?>" alt="" class="artist-card-avatar">
                        <div class="artist-card-name"><?= sanitize($artist['name']) ?></div>
                        <div class="text-muted" style="font-size:.8rem"><?= $artist['artwork_count'] ?> artworks</div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
