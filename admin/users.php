<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$user = getCurrentUser();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetId = (int)$_POST['user_id'];
    $action   = sanitize($_POST['action'] ?? '');

    if ($targetId && $targetId !== $user['id']) {
        switch ($action) {
            case 'approve':
                $conn->query("UPDATE users SET status='active' WHERE id=$targetId AND role='artist'");
                setFlash('success', 'Artist approved!');
                break;
            case 'block':
                $conn->query("UPDATE users SET status='blocked' WHERE id=$targetId");
                setFlash('success', 'User blocked.');
                break;
            case 'unblock':
                $conn->query("UPDATE users SET status='active' WHERE id=$targetId");
                setFlash('success', 'User unblocked.');
                break;
            case 'delete':
                // Delete dependent records first (FK columns are NOT NULL, cannot be nullified)
                $conn->query("DELETE FROM transactions WHERE user_id=$targetId");
                $conn->query("DELETE FROM withdrawal_requests WHERE artist_id=$targetId");
                $conn->query("DELETE FROM orders WHERE customer_id=$targetId OR artist_id=$targetId");
                $conn->query("DELETE FROM reviews WHERE customer_id=$targetId");
                $conn->query("DELETE FROM likes WHERE user_id=$targetId");
                $conn->query("DELETE FROM wishlist WHERE user_id=$targetId");
                $conn->query("DELETE FROM cart WHERE user_id=$targetId");
                $conn->query("DELETE FROM comments WHERE user_id=$targetId");
                $conn->query("DELETE FROM artworks WHERE artist_id=$targetId");
                $conn->query("DELETE FROM users WHERE id=$targetId AND role != 'admin'");
                if ($conn->affected_rows > 0) {
                    setFlash('success', 'User deleted.');
                } else {
                    setFlash('error', 'Delete failed - user not found or is an admin.');
                }
                break;
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$filter = sanitize($_GET['filter'] ?? 'all');
$search = sanitize($_GET['search'] ?? '');

$where = ["u.role != 'admin'"];
if ($filter === 'artist')   $where[] = "u.role='artist'";
if ($filter === 'customer') $where[] = "u.role='customer'";
if ($filter === 'pending')  $where[] = "u.status='pending'";
if ($filter === 'blocked')  $where[] = "u.status='blocked'";
if ($search) $where[] = "(u.name LIKE '%$search%' OR u.email LIKE '%$search%')";

$whereStr = "WHERE " . implode(" AND ", $where);
$users = $conn->query("SELECT u.*, 
    (SELECT COUNT(*) FROM artworks a WHERE a.artist_id=u.id) as artwork_count,
    (SELECT COUNT(*) FROM orders o WHERE o.customer_id=u.id) as order_count
    FROM users u $whereStr ORDER BY u.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Manage Users';
require_once __DIR__ . '/../includes/header.php';
showFlash();
?>

<div class="dash-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="dash-main">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 style="font-family:var(--font-display);font-size:1.6rem;font-weight:400;margin-bottom:0">
                Manage Users <span style="font-size:1rem;color:var(--ink-muted)">(<?= count($users) ?>)</span>
            </h2>
            <form method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm" value="<?= $search ?>" placeholder="Search users…">
                <input type="hidden" name="filter" value="<?= $filter ?>">
                <button type="submit" class="btn btn-dark btn-sm">Search</button>
            </form>
        </div>

        <!-- Filter tabs -->
        <div class="d-flex gap-2 mb-4 flex-wrap">
            <?php $tabs = ['all'=>'All','artist'=>'Artists','customer'=>'Customers','pending'=>'Pending','blocked'=>'Blocked'];
            foreach ($tabs as $k=>$v): ?>
            <a href="?filter=<?= $k ?>" class="category-pill <?= $filter===$k?'active':'' ?>"><?= $v ?></a>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <?php if (empty($users)): ?>
                <div class="text-center py-5 text-muted">No users found.</div>
                <?php else: ?>
                <table class="table table-hover mb-0">
                    <thead><tr>
                        <th>User</th><th>Role</th><th>Status</th><th>Artworks/Orders</th><th>Joined</th><th>Actions</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= profileImage($u['profile_image']) ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover;" alt="">
                                <div>
                                    <div style="font-weight:500;font-size:.875rem"><?= sanitize($u['name']) ?></div>
                                    <div style="font-size:.75rem;color:var(--ink-muted)"><?= sanitize($u['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark" style="font-size:.7rem"><?= ucfirst($u['role']) ?></span></td>
                        <td><span class="badge status-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span></td>
                        <td style="font-size:.85rem">
                            <?php if ($u['role']==='artist'): ?>
                                <i class="bi bi-images me-1 text-muted"></i><?= $u['artwork_count'] ?> artworks
                            <?php else: ?>
                                <i class="bi bi-bag me-1 text-muted"></i><?= $u['order_count'] ?> orders
                            <?php endif; ?>
                        </td>
                        <td style="font-size:.8rem;color:var(--ink-muted)"><?= timeAgo($u['created_at']) ?></td>
                        <td>
                            <form method="POST" class="d-flex gap-1">
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <?php if ($u['status']==='pending'): ?>
                                <button name="action" value="approve" class="btn btn-sm" style="background:var(--success);color:white;padding:.25rem .6rem;font-size:.75rem;">Approve</button>
                                <?php endif; ?>
                                <?php if ($u['status']==='blocked'): ?>
                                <button name="action" value="unblock" class="btn btn-sm btn-outline-success" style="padding:.25rem .6rem;font-size:.75rem;">Unblock</button>
                                <?php elseif ($u['status']==='active'): ?>
                                <button name="action" value="block" class="btn btn-sm btn-outline-warning" style="padding:.25rem .6rem;font-size:.75rem;">Block</button>
                                <?php endif; ?>
                                <button name="action" value="delete" class="btn btn-sm btn-outline-danger" style="padding:.25rem .6rem;font-size:.75rem;"
                                        onclick="return confirm('Delete <?= sanitize($u['name']) ?>?')">Del</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
