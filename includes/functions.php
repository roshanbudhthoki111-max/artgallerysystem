<?php
// includes/functions.php

require_once __DIR__ . '/db.php';

// ─── Session helpers ────────────────────────────────────────────────
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function noCacheHeaders() {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");
}

function requireLogin($redirect = '/art-gallery/login.php') {
    noCacheHeaders();
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit;
    }
}

function requireRole($role, $redirect = '/art-gallery/index.php') {
    requireLogin();
    if ($_SESSION['user_role'] !== $role) {
        header("Location: $redirect");
        exit;
    }
}

// ─── Security ────────────────────────────────────────────────────────
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function verifyToken($token) {
    startSession();
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function setToken() {
    startSession();
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken();
    }
    return $_SESSION['csrf_token'];
}

// ─── Flash messages ──────────────────────────────────────────────────
function setFlash($type, $message) {
    startSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    startSession();
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function showFlash() {
    $flash = getFlash();
    if ($flash) {
        $type = $flash['type'] === 'success' ? 'success' : ($flash['type'] === 'error' ? 'danger' : 'info');
        echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$flash['message']}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
    }
}

// ─── File upload ──────────────────────────────────────────────────────
function uploadFile($file, $folder = 'artworks') {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'mov'];
    $maxSize = 20 * 1024 * 1024; // 20MB

    if ($file['error'] !== UPLOAD_ERR_OK) return ['error' => 'Upload failed.'];
    if ($file['size'] > $maxSize) return ['error' => 'File too large (max 20MB).'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) return ['error' => 'Invalid file type.'];

    $filename = uniqid('art_', true) . '.' . $ext;
    $uploadDir = __DIR__ . "/../uploads/{$folder}/";

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return ['success' => true, 'filename' => $filename];
    }
    return ['error' => 'Could not save file.'];
}

// ─── Formatting ──────────────────────────────────────────────────────
function formatPrice($amount, $currency = null) {
    if (!$currency) $currency = getSetting('currency') ?? 'NPR';
    return $currency . ' ' . number_format($amount, 2);
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff/60) . 'm ago';
    if ($diff < 86400) return floor($diff/3600) . 'h ago';
    if ($diff < 2592000) return floor($diff/86400) . 'd ago';
    return date('M j, Y', $time);
}

function truncate($text, $length = 100) {
    return strlen($text) > $length ? substr($text, 0, $length) . '…' : $text;
}

function artworkImage($filename, $folder = 'artworks') {
    $path = "/art-gallery/uploads/{$folder}/{$filename}";
    return $filename && file_exists($_SERVER['DOCUMENT_ROOT'] . $path) ? $path : '/art-gallery/assets/img/placeholder.jpg';
}

function profileImage($filename) {
    $path = "/art-gallery/uploads/profiles/{$filename}";
    return $filename && $filename !== 'default-avatar.png' && file_exists($_SERVER['DOCUMENT_ROOT'] . $path)
        ? $path : '/art-gallery/assets/img/default-avatar.png';
}

// ─── Artwork queries ──────────────────────────────────────────────────
function getArtworks($filters = [], $limit = 12, $offset = 0) {
    global $conn;
    $where = ["a.is_approved = 1"];
    $params = [];
    $types = "";

    if (!empty($filters['category'])) {
        $where[] = "c.slug = ?"; $params[] = $filters['category']; $types .= "s";
    }
    if (!empty($filters['search'])) {
        $where[] = "(a.title LIKE ? OR a.description LIKE ?)";
        $params[] = "%{$filters['search']}%"; $params[] = "%{$filters['search']}%"; $types .= "ss";
    }
    if (!empty($filters['min_price'])) {
        $where[] = "a.price >= ?"; $params[] = $filters['min_price']; $types .= "d";
    }
    if (!empty($filters['max_price'])) {
        $where[] = "a.price <= ?"; $params[] = $filters['max_price']; $types .= "d";
    }
    if (!empty($filters['artist_id'])) {
        $where[] = "a.artist_id = ?"; $params[] = $filters['artist_id']; $types .= "i";
    }
    if (!empty($filters['availability'])) {
        $where[] = "a.availability = ?"; $params[] = $filters['availability']; $types .= "s";
    }
    if (isset($filters['featured']) && $filters['featured']) {
        $where[] = "a.is_featured = 1";
    }

    $whereStr = "WHERE " . implode(" AND ", $where);
    $orderBy = !empty($filters['sort']) ? match($filters['sort']) {
        'price_asc' => 'a.price ASC',
        'price_desc' => 'a.price DESC',
        'popular' => 'a.views DESC',
        'liked' => 'a.likes_count DESC',
        default => 'a.created_at DESC'
    } : 'a.created_at DESC';

    $sql = "SELECT a.*, u.name as artist_name, u.profile_image as artist_avatar, 
                   c.name as category_name, c.slug as category_slug
            FROM artworks a
            LEFT JOIN users u ON a.artist_id = u.id
            LEFT JOIN categories c ON a.category_id = c.id
            $whereStr
            ORDER BY $orderBy
            LIMIT ? OFFSET ?";

    $params[] = $limit; $params[] = $offset;
    $types .= "ii";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getArtwork($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT a.*, u.name as artist_name, u.profile_image as artist_avatar, 
                                   u.bio as artist_bio, u.email as artist_email,
                                   c.name as category_name, c.slug as category_slug
                            FROM artworks a
                            LEFT JOIN users u ON a.artist_id = u.id
                            LEFT JOIN categories c ON a.category_id = c.id
                            WHERE a.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function recordView($artworkId, $userId = null) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $conn->prepare("INSERT INTO artwork_views (artwork_id, viewer_ip, viewer_id) VALUES (?,?,?)");
    $stmt->bind_param("isi", $artworkId, $ip, $userId);
    $stmt->execute();
    $conn->query("UPDATE artworks SET views = views + 1 WHERE id = $artworkId");
}

// ─── Cart helpers ──────────────────────────────────────────────────────
function getCartCount($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['cnt'];
}

function isInWishlist($artworkId, $userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE artwork_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $artworkId, $userId);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

function isLiked($artworkId, $userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM likes WHERE artwork_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $artworkId, $userId);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

// ─── Pagination ────────────────────────────────────────────────────────
function paginate($total, $limit, $currentPage, $url) {
    $totalPages = ceil($total / $limit);
    if ($totalPages <= 1) return '';
    $html = '<nav><ul class="pagination justify-content-center gap-1">';
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i === $currentPage ? 'active' : '';
        $html .= "<li class='page-item $active'><a class='page-link' href='{$url}?page=$i'>$i</a></li>";
    }
    $html .= '</ul></nav>';
    return $html;
}
