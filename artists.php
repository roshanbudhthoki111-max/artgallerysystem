<?php
$pageTitle = 'Artists';
require_once __DIR__ . '/includes/header.php';

$search = sanitize($_GET['search'] ?? '');
$where  = "u.role='artist' AND u.status='active'";
if ($search) $where .= " AND u.name LIKE '%$search%'";

$artists = $conn->query("SELECT u.*, 
    COUNT(a.id) as artwork_count,
    COALESCE(SUM(a.likes_count),0) as total_likes,
    COALESCE(SUM(a.views),0) as total_views
    FROM users u
    LEFT JOIN artworks a ON a.artist_id=u.id AND a.is_approved=1
    WHERE $where
    GROUP BY u.id ORDER BY artwork_count DESC")->fetch_all(MYSQLI_ASSOC);
?>

<div class="page-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <div class="section-label">Creators</div>
                <h1 class="page-header-title">Meet the <em style="font-family:var(--font-display);font-style:italic;color:var(--gold)">Artists</em></h1>
            </div>
            <div class="col-lg-5 ms-auto">
                <form method="GET" class="d-flex gap-2">
                    <input type="text" name="search" class="form-control" value="<?= $search ?>" placeholder="Search artists…">
                    <button type="submit" class="btn btn-dark">Search</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <?php if (empty($artists)): ?>
    <div class="text-center py-5">
        <p class="text-muted">No artists found.</p>
    </div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($artists as $artist): ?>
        <div class="col-sm-6 col-lg-4 col-xl-3">
            <a href="/art-gallery/artist-profile.php?id=<?= $artist['id'] ?>" class="text-decoration-none">
                <div class="card text-center p-4 h-100 artwork-card">
                    <img src="<?= profileImage($artist['profile_image']) ?>"
                         style="width:88px;height:88px;border-radius:50%;object-fit:cover;border:3px solid var(--border);margin:0 auto 1rem;" alt="">
                    <h5 style="font-family:var(--font-display);font-size:1.2rem;font-weight:400;margin-bottom:.25rem;color:var(--ink)">
                        <?= sanitize($artist['name']) ?>
                    </h5>
                    <?php if ($artist['bio']): ?>
                    <p class="text-muted mb-3" style="font-size:.8rem;line-height:1.5;"><?= truncate(sanitize($artist['bio']), 80) ?></p>
                    <?php endif; ?>
                    <div class="d-flex justify-content-center gap-3 mt-auto">
                        <div class="text-center">
                            <div style="font-family:var(--font-display);font-size:1.2rem;font-weight:600;color:var(--ink)"><?= $artist['artwork_count'] ?></div>
                            <div style="font-size:.7rem;color:var(--ink-muted)">Artworks</div>
                        </div>
                        <div class="text-center">
                            <div style="font-family:var(--font-display);font-size:1.2rem;font-weight:600;color:var(--ink)"><?= number_format($artist['total_likes']) ?></div>
                            <div style="font-size:.7rem;color:var(--ink-muted)">Likes</div>
                        </div>
                    </div>
                    <?php if ($artist['social_instagram'] || $artist['social_twitter']): ?>
                    <div class="d-flex justify-content-center gap-2 mt-3">
                        <?php if ($artist['social_instagram']): ?>
                        <a href="https://instagram.com/<?= sanitize($artist['social_instagram']) ?>" onclick="event.stopPropagation()" target="_blank" class="footer-social" style="color:var(--ink-muted);border-color:var(--border);">
                            <i class="bi bi-instagram"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($artist['social_twitter']): ?>
                        <a href="https://twitter.com/<?= sanitize($artist['social_twitter']) ?>" onclick="event.stopPropagation()" target="_blank" class="footer-social" style="color:var(--ink-muted);border-color:var(--border);">
                            <i class="bi bi-twitter-x"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
