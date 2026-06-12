<?php
require_once __DIR__ . '/includes/functions.php';
startSession();

$id = (int)($_GET['id'] ?? 0);
$art = getArtwork($id);

if (!$art || (!$art['is_approved'] && (!isLoggedIn() || $_SESSION['user_role'] !== 'admin'))) {
    header("Location: /art-gallery/gallery.php");
    exit;
}

// Record view
recordView($id, isLoggedIn() ? $_SESSION['user_id'] : null);

$currentUser = isLoggedIn() ? getCurrentUser() : null;
$isLikedByMe = $currentUser ? isLiked($id, $currentUser['id']) : false;
$isWishlisted = $currentUser ? isInWishlist($id, $currentUser['id']) : false;

// Avg rating
$ratingResult = $conn->query("SELECT AVG(rating) as avg, COUNT(*) as cnt FROM reviews WHERE artwork_id = $id");
$ratingData = $ratingResult->fetch_assoc();
$avgRating = round((float)$ratingData['avg'], 1);
$reviewCount = (int)$ratingData['cnt'];

// Reviews
$reviews = $conn->query("SELECT r.*, u.name as reviewer, u.profile_image FROM reviews r 
                          LEFT JOIN users u ON r.customer_id = u.id 
                          WHERE r.artwork_id = $id ORDER BY r.created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Comments
$comments = $conn->query("SELECT c.*, u.name as commenter, u.profile_image FROM comments c
                           LEFT JOIN users u ON c.user_id = u.id
                           WHERE c.artwork_id = $id ORDER BY c.created_at DESC LIMIT 20")->fetch_all(MYSQLI_ASSOC);

// Related artworks
$related = getArtworks(['category' => $art['category_slug'], 'artist_id' => null], 4, 0);
$related = array_filter($related, fn($a) => $a['id'] !== $id);

// Handle POST (review, comment, offer)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isLoggedIn()) {
    $action = $_POST['post_action'] ?? '';

    if ($action === 'review' && $currentUser['role'] === 'customer') {
        $rating  = min(5, max(1, (int)$_POST['rating']));
        $comment = sanitize($_POST['comment'] ?? '');
        $stmt = $conn->prepare("INSERT INTO reviews (artwork_id, customer_id, rating, comment) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE rating=?, comment=?");
        $stmt->bind_param("iiissi", $id, $currentUser['id'], $rating, $comment, $rating, $comment);
        $stmt->execute();
        setFlash('success', 'Review submitted!');
        header("Location: /art-gallery/artwork.php?id=$id#reviews");
        exit;
    }

    if ($action === 'comment') {
        $text = sanitize($_POST['comment_text'] ?? '');
        if ($text) {
            $stmt = $conn->prepare("INSERT INTO comments (artwork_id, user_id, comment) VALUES (?,?,?)");
            $stmt->bind_param("iis", $id, $currentUser['id'], $text);
            $stmt->execute();
            setFlash('success', 'Comment added!');
            header("Location: /art-gallery/artwork.php?id=$id#comments");
            exit;
        }
    }

    if ($action === 'offer' && $currentUser['role'] === 'customer') {
        $price = (float)$_POST['offer_price'];
        if ($price > 0 && $price < $art['price'] * 2) {
            $msg = sanitize($_POST['offer_message'] ?? '');
            $stmt = $conn->prepare("INSERT INTO offers (artwork_id, customer_id, offer_price, message) VALUES (?,?,?,?)");
            $stmt->bind_param("iids", $id, $currentUser['id'], $price, $msg);
            $stmt->execute();
            setFlash('success', 'Offer sent to the artist!');
        }
        header("Location: /art-gallery/artwork.php?id=$id");
        exit;
    }
}

$pageTitle = $art['title'];
require_once __DIR__ . '/includes/header.php';
showFlash();
?>

<div class="container py-5">
    <!-- Breadcrumb -->
    <nav class="mb-4">
        <ol class="breadcrumb" style="font-size:.8rem;">
            <li class="breadcrumb-item"><a href="/art-gallery/">Home</a></li>
            <li class="breadcrumb-item"><a href="/art-gallery/gallery.php">Gallery</a></li>
            <?php if ($art['category_name']): ?>
            <li class="breadcrumb-item"><a href="/art-gallery/gallery.php?category=<?= $art['category_slug'] ?>"><?= sanitize($art['category_name']) ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active"><?= sanitize($art['title']) ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        <!-- Image -->
        <div class="col-lg-7">
            <img src="<?= artworkImage($art['image']) ?>" alt="<?= sanitize($art['title']) ?>"
                 class="artwork-detail-img artwork-zoom" style="width:100%;">

            <?php if ($art['availability'] === 'sold'): ?>
            <div class="text-center mt-3">
                <span class="badge bg-dark px-4 py-2" style="font-size:.85rem;">SOLD</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Details -->
        <div class="col-lg-5">
            <div class="d-flex align-items-center gap-2 mb-3">
                <?php if ($art['category_name']): ?>
                <span class="badge bg-light text-dark"><?= sanitize($art['category_name']) ?></span>
                <?php endif; ?>
                <?php if ($art['is_featured']): ?>
                <span class="badge" style="background:var(--gold);color:white">Featured</span>
                <?php endif; ?>
            </div>

            <h1 style="font-family:var(--font-display);font-size:2.2rem;font-weight:400;line-height:1.15;margin-bottom:.5rem">
                <?= sanitize($art['title']) ?>
            </h1>

            <!-- Artist -->
            <a href="/art-gallery/artist-profile.php?id=<?= $art['artist_id'] ?>" class="d-flex align-items-center gap-2 mb-4 text-decoration-none">
                <img src="<?= profileImage($art['artist_avatar']) ?>" class="avatar-sm" alt="">
                <div>
                    <div style="font-size:.85rem;font-weight:500;color:var(--ink)"><?= sanitize($art['artist_name']) ?></div>
                    <div style="font-size:.75rem;color:var(--ink-muted)">Artist</div>
                </div>
            </a>

            <!-- Rating -->
            <?php if ($reviewCount > 0): ?>
            <div class="d-flex align-items-center gap-2 mb-3">
                <div class="stars">
                    <?php for ($i=1;$i<=5;$i++): ?>
                    <i class="bi bi-star<?= $i <= round($avgRating) ? '-fill' : ($i - $avgRating < 1 ? '-half' : '') ?>"></i>
                    <?php endfor; ?>
                </div>
                <span style="font-size:.85rem;color:var(--ink-muted)"><?= $avgRating ?> (<?= $reviewCount ?> reviews)</span>
            </div>
            <?php endif; ?>

            <div class="artwork-detail-price mb-1"><?= formatPrice($art['price']) ?></div>
            <div class="text-muted mb-4" style="font-size:.8rem"><i class="bi bi-eye me-1"></i><?= number_format($art['views']) ?> views &nbsp;·&nbsp; <i class="bi bi-heart me-1"></i><?= $art['likes_count'] ?> likes</div>

            <?php if ($art['description']): ?>
            <p class="text-muted mb-4" style="line-height:1.8;"><?= nl2br(sanitize($art['description'])) ?></p>
            <?php endif; ?>

            <!-- Actions -->
            <?php if ($art['availability'] === 'available'): ?>
            <div class="d-flex flex-column gap-2 mb-4">
                <?php if ($currentUser && $currentUser['role'] === 'customer'): ?>
                <form method="POST" action="/art-gallery/customer/cart.php">
                    <input type="hidden" name="add_artwork_id" value="<?= $id ?>">
                    <button type="submit" name="post_action" value="add_to_cart" class="btn btn-dark w-100 py-2">
                        <i class="bi bi-bag-plus me-2"></i>Add to Cart
                    </button>
                </form>
                <div class="row g-2">
                    <div class="col-6">
                        <button class="btn btn-outline-dark w-100" data-action="toggle_wishlist" data-id="<?= $id ?>" id="wishlist-btn">
                            <i class="bi bi-<?= $isWishlisted ? 'bookmark-fill' : 'bookmark' ?> me-1"></i>
                            <?= $isWishlisted ? 'Saved' : 'Save' ?>
                        </button>
                    </div>
                    <div class="col-6">
                        <button class="btn btn-outline-dark w-100" data-action="toggle_like" data-id="<?= $id ?>" id="like-btn">
                            <i class="bi bi-<?= $isLikedByMe ? 'heart-fill' : 'heart' ?> me-1"></i>
                            <span class="like-count"><?= $art['likes_count'] ?></span>
                        </button>
                    </div>
                </div>
                <button class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#offer-form">
                    <i class="bi bi-chat-text me-1"></i>Make an Offer
                </button>
                <div class="collapse" id="offer-form">
                    <div class="card mt-2">
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="post_action" value="offer">
                                <label class="form-label">Your Offer Price</label>
                                <div class="input-group mb-2">
                                    <span class="input-group-text">NPR</span>
                                    <input type="number" name="offer_price" id="offer_price" class="form-control"
                                           min="1" max="<?= $art['price'] * 2 ?>" placeholder="<?= $art['price'] * 0.8 ?>">
                                    <span class="input-group-text" id="offer-pct"></span>
                                </div>
                                <input type="hidden" id="artwork-price" data-price="<?= $art['price'] ?>">
                                <textarea name="offer_message" class="form-control mb-3" rows="2" placeholder="Optional message to artist…"></textarea>
                                <button type="submit" class="btn btn-gold w-100">Send Offer</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php elseif (!$currentUser): ?>
                <a href="/art-gallery/login.php?redirect=<?= urlencode('/art-gallery/artwork.php?id='.$id) ?>" class="btn btn-dark w-100 py-2">
                    <i class="bi bi-person-circle me-2"></i>Sign In to Purchase
                </a>
                <?php elseif ($currentUser['id'] === $art['artist_id']): ?>
                <a href="/art-gallery/artist/edit-artwork.php?id=<?= $id ?>" class="btn btn-outline-dark w-100">
                    <i class="bi bi-pencil me-2"></i>Edit Artwork
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Share -->
            <div class="d-flex align-items-center gap-2 pt-3 border-top">
                <span style="font-size:.75rem;color:var(--ink-muted);font-weight:600;letter-spacing:.05em">SHARE</span>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('http://localhost/art-gallery/artwork.php?id='.$id) ?>" target="_blank" class="btn-icon"><i class="bi bi-facebook"></i></a>
                <a href="https://twitter.com/intent/tweet?url=<?= urlencode('http://localhost/art-gallery/artwork.php?id='.$id) ?>" target="_blank" class="btn-icon"><i class="bi bi-twitter-x"></i></a>
                <button onclick="navigator.clipboard.writeText(location.href);this.innerHTML='<i class=\'bi bi-check2\'></i>'" class="btn-icon"><i class="bi bi-link-45deg"></i></button>
            </div>
        </div>
    </div>

    <!-- Reviews & Comments -->
    <div class="row g-4 mt-4">
        <!-- Reviews -->
        <div class="col-lg-6" id="reviews">
            <h3 style="font-family:var(--font-display);font-size:1.5rem;font-weight:400;margin-bottom:1.5rem">
                Reviews <span style="font-size:1rem;color:var(--ink-muted)">(<?= $reviewCount ?>)</span>
            </h3>

            <?php if ($currentUser && $currentUser['role'] === 'customer'): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h6 style="font-family:var(--font-display);font-weight:400;margin-bottom:1rem">Write a Review</h6>
                    <form method="POST">
                        <input type="hidden" name="post_action" value="review">
                        <div class="mb-3">
                            <label class="form-label">Rating</label>
                            <div class="d-flex gap-2">
                                <?php for ($i=1;$i<=5;$i++): ?>
                                <label style="cursor:pointer;font-size:1.4rem;color:#f59e0b">
                                    <input type="radio" name="rating" value="<?= $i ?>" class="d-none">
                                    <i class="bi bi-star" id="star-<?= $i ?>" onmouseover="hoverStar(<?= $i ?>)" onmouseout="resetStars()" onclick="selectStar(<?= $i ?>)"></i>
                                </label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <textarea name="comment" class="form-control mb-3" rows="3" placeholder="Share your thoughts about this artwork…"></textarea>
                        <button type="submit" class="btn btn-dark btn-sm">Submit Review</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php foreach ($reviews as $r): ?>
            <div class="d-flex gap-3 mb-4 pb-4 border-bottom">
                <img src="<?= profileImage($r['profile_image']) ?>" class="avatar-sm flex-shrink-0" alt="">
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <strong style="font-size:.875rem"><?= sanitize($r['reviewer']) ?></strong>
                        <span style="font-size:.75rem;color:var(--ink-muted)"><?= timeAgo($r['created_at']) ?></span>
                    </div>
                    <div class="stars mb-1">
                        <?php for ($i=1;$i<=5;$i++): ?>
                        <i class="bi bi-star<?= $i <= $r['rating'] ? '-fill' : '' ?>" style="font-size:.75rem"></i>
                        <?php endfor; ?>
                    </div>
                    <?php if ($r['comment']): ?>
                    <p class="mb-0" style="font-size:.875rem;color:var(--ink-light)"><?= sanitize($r['comment']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Comments -->
        <div class="col-lg-6" id="comments">
            <h3 style="font-family:var(--font-display);font-size:1.5rem;font-weight:400;margin-bottom:1.5rem">
                Comments <span style="font-size:1rem;color:var(--ink-muted)">(<?= count($comments) ?>)</span>
            </h3>

            <?php if (isLoggedIn()): ?>
            <form method="POST" class="mb-4">
                <input type="hidden" name="post_action" value="comment">
                <div class="d-flex gap-2">
                    <img src="<?= profileImage($currentUser['profile_image']) ?>" class="avatar-sm flex-shrink-0" alt="">
                    <div class="flex-grow-1">
                        <textarea name="comment_text" class="form-control mb-2" rows="2" placeholder="Add a comment…"></textarea>
                        <button type="submit" class="btn btn-dark btn-sm">Comment</button>
                    </div>
                </div>
            </form>
            <?php endif; ?>

            <?php foreach ($comments as $c): ?>
            <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
                <img src="<?= profileImage($c['profile_image']) ?>" class="avatar-sm flex-shrink-0" alt="">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <strong style="font-size:.875rem"><?= sanitize($c['commenter']) ?></strong>
                        <span style="font-size:.75rem;color:var(--ink-muted)"><?= timeAgo($c['created_at']) ?></span>
                    </div>
                    <p class="mb-0" style="font-size:.875rem;color:var(--ink-light)"><?= sanitize($c['comment']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Related -->
    <?php if (!empty($related)): ?>
    <div class="mt-5 pt-4 border-top">
        <h3 style="font-family:var(--font-display);font-size:1.5rem;font-weight:400;margin-bottom:1.5rem">More from this Category</h3>
        <div class="row g-4">
            <?php foreach (array_slice(array_values($related), 0, 4) as $r): ?>
            <div class="col-sm-6 col-lg-3">
                <div class="artwork-card" onclick="location.href='/art-gallery/artwork.php?id=<?= $r['id'] ?>'">
                    <div class="artwork-card-img"><img src="<?= artworkImage($r['image']) ?>" alt="" loading="lazy"></div>
                    <div class="artwork-card-body">
                        <div class="artwork-title"><?= sanitize($r['title']) ?></div>
                        <div class="artwork-price"><?= formatPrice($r['price']) ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
let selectedStar = 0;
function hoverStar(n) { for(let i=1;i<=5;i++) document.getElementById('star-'+i).className = i<=n?'bi bi-star-fill':'bi bi-star'; }
function resetStars() { for(let i=1;i<=5;i++) document.getElementById('star-'+i).className = i<=selectedStar?'bi bi-star-fill':'bi bi-star'; }
function selectStar(n) { selectedStar = n; document.querySelector('[name=rating][value="'+n+'"]').checked = true; }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
