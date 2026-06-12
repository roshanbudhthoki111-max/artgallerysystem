<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('customer');
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = sanitize($_POST['name'] ?? '');
    $profileImage = $user['profile_image'];

    if (!empty($_FILES['profile_image']['name'])) {
        $upload = uploadFile($_FILES['profile_image'], 'profiles');
        if (isset($upload['success'])) {
            if ($profileImage && $profileImage !== 'default-avatar.png') {
                @unlink(__DIR__ . '/../uploads/profiles/' . $profileImage);
            }
            $profileImage = $upload['filename'];
        }
    }

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';

    if (!empty($newPassword)) {
        if (!password_verify($currentPassword, $user['password'])) {
            setFlash('error', 'Current password incorrect.');
            header("Location: /art-gallery/customer/profile.php"); exit;
        }
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param("si", $hash, $user['id']);
        $stmt->execute();
    }

    $stmt = $conn->prepare("UPDATE users SET name=?, profile_image=? WHERE id=?");
    $stmt->bind_param("ssi", $name, $profileImage, $user['id']);
    $stmt->execute();
    $_SESSION['user_name'] = $name;

    setFlash('success', 'Profile updated!');
    header("Location: /art-gallery/customer/profile.php"); exit;
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/../includes/header.php';
showFlash();
?>

<div class="dash-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="dash-main">
        <h2 style="font-family:var(--font-display);font-size:1.6rem;font-weight:400;margin-bottom:1.5rem">My Profile</h2>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card text-center">
                    <div class="card-body py-4">
                        <img src="<?= profileImage($user['profile_image']) ?>" id="avatar-preview"
                             style="width:96px;height:96px;border-radius:50%;object-fit:cover;border:3px solid var(--border);margin-bottom:1rem;" alt="">
                        <h5 style="font-family:var(--font-display);font-size:1.2rem;font-weight:400"><?= sanitize($user['name']) ?></h5>
                        <div class="text-muted" style="font-size:.8rem"><?= sanitize($user['email']) ?></div>
                        <div class="text-muted" style="font-size:.75rem">Member since <?= date('M Y', strtotime($user['created_at'])) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <form method="POST" enctype="multipart/form-data">
                    <div class="card mb-3">
                        <div class="card-header">Personal Information</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Profile Photo</label>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?= profileImage($user['profile_image']) ?>" id="avatar-preview2"
                                         style="width:48px;height:48px;border-radius:50%;object-fit:cover;" alt="">
                                    <label class="btn btn-outline-dark btn-sm">
                                        <i class="bi bi-camera me-1"></i>Change Photo
                                        <input type="file" name="profile_image" accept="image/*" class="d-none"
                                               onchange="previewImg(this,'avatar-preview','avatar-preview2')">
                                    </label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="name" class="form-control" value="<?= sanitize($user['name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?= sanitize($user['email']) ?>" disabled>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-3">
                        <div class="card-header">Change Password</div>
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" placeholder="Current password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-control" placeholder="Min. 8 characters">
                                </div>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-dark px-4">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function previewImg(input, ...ids) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => ids.forEach(id => { const el = document.getElementById(id); if(el) el.src = e.target.result; });
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
