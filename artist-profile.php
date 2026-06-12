<?php
$pageTitle = 'Artist Profile';
require_once __DIR__ . '/includes/header.php';

$artistId = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM users WHERE id=? AND role='artist' AND status='active'");
$stmt->bind_param("i", $artistId);
$stmt->execute();
$artist = $stmt->get_result()->fetch_assoc();

if (!$artist) { header("Location: /art-gallery/artists.php"); exit; }

$artworks   = getArtworks(['artist_id' => $artistId], 12, 0);
$totalLikes = $conn->query("SELECT COALESCE(SUM(likes_count),0) as l FROM artworks WHERE artist_id=$artistId AND is_approved=1")->fetch_assoc()['l'];
$totalSales = $conn->query("SELECT COUNT(*) as c FROM orders WHERE artist_id=$artistId AND status='delivered'")->fetch_assoc()['c'];
$avgRating  = (float)$conn->query("SELECT AVG(r.rating) as a FROM reviews r JOIN artworks a ON r.artwork_id=a.id WHERE a.artist_id=$artistId")->fetch_assoc()['a'];
?>

<div style="background:var(--ink);color:white;padding:4rem 0 3rem;">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-auto">
                <img src="<?= profileImage($artist['profile_image']) ?>"
                     style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:4px solid rgba(255,255,255,.15);" alt="">
            </div>
            <div class="col">
                <div style="font-size:.7rem;letter-spacing:.2em;color:var(--gold);text-transform:uppercase;margin-bottom:.5rem">Artist</div>
                <h1 style="font-family:var(--font-display);font-size:2.5rem;font-weight:300;margin-bottom:.5rem;color:white">
                    <?= sanitize($artist['name']) ?>
                </h1>
                <?php if ($artist['bio']): ?>
                <p style="color:rgba(255,255,255,.6);max-width:580px;font-size:.95rem;line-height:1.7;margin-bottom:1rem">
                    <?= nl2br(sanitize($artist['bio'])) ?>
                </p>
                <?php endif; ?>
                <div class="d-flex gap-3 flex-wrap">
                    <?php if ($artist['social_instagram']): ?>
                    <a href="https://instagram.com/<?= sanitize($artist['social_instagram']) ?>" target="_blank"
                       style="color:rgba(255,255,255,.5);font-size:.85rem;text-decoration:none;">
                        <i class="bi bi-instagram me-1"></i>@<?= sanitize($artist['social_instagram']) ?>
                    </a>
                    <?php endif; ?>
                    <?php if ($artist['social_website']): ?>
                    <a href="<?= sanitize($artist['social_website']) ?>" target="_blank"
                       style="color:rgba(255,255,255,.5);font-size:.85rem;text-decoration:none;">
                        <i class="bi bi-globe me-1"></i>Website
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="d-flex gap-4 justify-content-lg-end">
                    <?php foreach ([['val'=>count($artworks),'l'=>'Artworks'],['val'=>$totalLikes,'l'=>'Likes'],['val'=>$totalSales,'l'=>'Sold']] as $s): ?>
                    <div class="text-center">
                        <div style="font-family:var(--font-display);font-size:2rem;font-weight:600;color:white;line-height:1"><?= $s['val'] ?></div>
                        <div style="font-size:.7rem;color:rgba(255,255,255,.4);letter-spacing:.08em"><?= $s['l'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <?php if (empty($artworks)): ?>
    <div class="text-center py-5">
        <i class="bi bi-image" style="font-size:3rem;color:var(--border)"></i>
        <p class="mt-3 text-muted">No artworks published yet.</p>
    </div>
    <?php else: ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 style="font-family:var(--font-display);font-size:1.6rem;font-weight:400">
            Artworks <span style="color:var(--ink-muted);font-size:1rem">(<?= count($artworks) ?>)</span>
        </h2>
    </div>
    <div class="row g-4">
        <?php foreach ($artworks as $art): ?>
        <div class="col-sm-6 col-lg-4">
            <div class="artwork-card h-100" onclick="location.href='/art-gallery/artwork.php?id=<?= $art['id'] ?>'">
                <div class="artwork-card-img">
                    <img src="<?= artworkImage($art['image']) ?>" alt="<?= sanitize($art['title']) ?>" loading="lazy">
                    <?php if ($art['availability']==='sold'): ?><span class="artwork-badge sold">Sold</span><?php endif; ?>
                    <div class="artwork-card-overlay">
                        <div class="artwork-card-actions">
                            <a href="/art-gallery/artwork.php?id=<?= $art['id'] ?>" class="btn btn-icon" onclick="event.stopPropagation()">
                                <i class="bi bi-eye text-white"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="artwork-card-body">
                    <div class="artwork-title"><?= sanitize($art['title']) ?></div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <div class="artwork-price"><?= formatPrice($art['price']) ?></div>
                        <div class="artwork-meta">
                            <span><i class="bi bi-heart me-1"></i><?= $art['likes_count'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
