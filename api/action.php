<?php
// api/action.php - AJAX actions handler
require_once __DIR__ . '/../includes/functions.php';
startSession();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please sign in.']);
    exit;
}

$data   = json_decode(file_get_contents('php://input'), true);
$action = sanitize($data['action'] ?? '');
$id     = (int)($data['id'] ?? 0);
$userId = $_SESSION['user_id'];

switch ($action) {

    case 'toggle_like':
        $stmt = $conn->prepare("SELECT id FROM likes WHERE artwork_id=? AND user_id=?");
        $stmt->bind_param("ii", $id, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $del = $conn->prepare("DELETE FROM likes WHERE artwork_id=? AND user_id=?");
            $del->bind_param("ii", $id, $userId);
            $del->execute();
            $conn->query("UPDATE artworks SET likes_count = GREATEST(0, likes_count-1) WHERE id=$id");
            $liked = false;
        } else {
            $ins = $conn->prepare("INSERT IGNORE INTO likes (artwork_id, user_id) VALUES (?,?)");
            $ins->bind_param("ii", $id, $userId);
            $ins->execute();
            $conn->query("UPDATE artworks SET likes_count = likes_count+1 WHERE id=$id");
            $liked = true;
        }
        $count = $conn->query("SELECT likes_count FROM artworks WHERE id=$id")->fetch_assoc()['likes_count'];
        echo json_encode(['success' => true, 'liked' => $liked, 'count' => $count, 'message' => $liked ? 'Added to likes' : 'Removed from likes']);
        break;

    case 'toggle_wishlist':
        $stmt = $conn->prepare("SELECT id FROM wishlist WHERE artwork_id=? AND user_id=?");
        $stmt->bind_param("ii", $id, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $del = $conn->prepare("DELETE FROM wishlist WHERE artwork_id=? AND user_id=?");
            $del->bind_param("ii", $id, $userId);
            $del->execute();
            echo json_encode(['success' => true, 'wishlisted' => false, 'message' => 'Removed from wishlist']);
        } else {
            $ins = $conn->prepare("INSERT IGNORE INTO wishlist (user_id, artwork_id) VALUES (?,?)");
            $ins->bind_param("ii", $userId, $id);
            $ins->execute();
            echo json_encode(['success' => true, 'wishlisted' => true, 'message' => 'Saved to wishlist']);
        }
        break;

    case 'add_to_cart':
        if ($_SESSION['user_role'] !== 'customer') {
            echo json_encode(['success' => false, 'message' => 'Only customers can add to cart.']);
            break;
        }
        $ins = $conn->prepare("INSERT IGNORE INTO cart (user_id, artwork_id) VALUES (?,?)");
        $ins->bind_param("ii", $userId, $id);
        $ins->execute();
        echo json_encode(['success' => true, 'message' => 'Added to cart!', 'reload' => false]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
}
