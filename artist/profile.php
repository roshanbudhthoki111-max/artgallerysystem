<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('artist', '/art-gallery/login.php');
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = sanitize($_POST['name'] ?? '');
    $bio      = sanitize($_POST['bio'] ?? '');
    $instagram = sanitize($_POST['social_instagram'] ?? '');
    $twitter   = sanitize($_POST['social_twitter'] ?? '');
    $website   = sanitize($_POST['social_website'] ?? '');

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

    $newPassword = $_POST['new_password'] ?? '';
    $currentPassword = $_POST['current_password'] ?? '';

    if (!empty($newPassword)) {
        if (!password_verify($currentPassword, $user['password'])) {
            setFlash('error', 'Current password is incorrect.');
            header("Location: /art-gallery/artist/profile.php");
            exit;
        }
        if (strlen($newPassword) < 8) {
            setFlash('error', 'New password must be at least 8 characters.');
            header("Location: /art-gallery/artist/profile.php");
            exit;
        }
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt2 = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt2->bind_param("si", $hash, $user['id']);
        $stmt2->execute();
    }

    $stmt = $conn->prepare("UPDATE users SET name=?, bio=?, profile_image=?, social_instagram=?, social_twitter=?, social_website=? WHERE id=?");
    if (!$stmt) {
        setFlash('error', 'Database error: could not prepare update.');
        header("Location: /art-gallery/artist/profile.php");
        exit;
    }
    $stmt->bind_param("ssssssi", $name, $bio, $profileImage, $instagram, $twitter, $website, $user['id']);
    if (!$stmt->execute()) {
        setFlash('error', 'Could not save profile changes. Please try again.');
        header("Location: /art-gallery/artist/profile.php");
        exit;
    }

    $_SESSION['user_name'] = $name;
    setFlash('success', 'Profile updated successfully!');
    header("Location: /art-gallery/artist/profile.php");
    exit;
}

$pageTitle = 'Edit Profile';
require_once __DIR__ . '/../includes/header.php';
showFlash();
?>

<div class="dash-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="dash-main">
        <h2 style="font-family:var(--font-display);font-size:1.6rem;font-weight:400;margin-bottom:1.5rem">Edit Profile</h2>

        <div class="row g-4">
            <div class="col-lg-4">
                <!-- Avatar -->
                <div class="card text-center">
                    <div class="card-body py-4">
                        <img src="<?= profileImage($user['profile_image']) ?>" id="avatar-preview"
                             style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--border);margin-bottom:1rem;" alt="">
                        <div>
                            <label for="avatar-input" class="btn btn-outline-dark btn-sm">
                                <i class="bi bi-camera me-1"></i>Change Photo
                            </label>
                        </div>
                        <div class="mt-3">
                            <div style="font-family:var(--font-display);font-size:1.2rem"><?= sanitize($user['name']) ?></div>
                            <div style="font-size:.8rem;color:var(--gold)">◈ Verified Artist</div>
                        </div>
                    </div>
                </div>

                <!-- Public profile link -->
                <div class="card mt-3">
                    <div class="card-body">
                        <div class="sidebar-heading">Public Profile</div>
                        <a href="/art-gallery/artist-profile.php?id=<?= $user['id'] ?>" target="_blank" class="btn btn-outline-dark btn-sm w-100">
                            <i class="bi bi-box-arrow-up-right me-1"></i>View Public Profile
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" id="avatar-input" name="profile_image" accept="image/*" class="d-none"
                           onchange="previewAvatar(this)">

                    <div class="card mb-3">
                        <div class="card-header">Personal Information</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="name" class="form-control" value="<?= sanitize($user['name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?= sanitize($user['email']) ?>" disabled>
                                <div class="form-text">Email cannot be changed.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Artist Bio</label>
                                <textarea name="bio" class="form-control" rows="5"
                                          placeholder="Tell collectors about yourself, your style, and your inspiration…"><?= sanitize($user['bio'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">Social Links</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-instagram me-1"></i>Instagram</label>
                                <div class="input-group">
                                    <span class="input-group-text">@</span>
                                    <input type="text" name="social_instagram" class="form-control" value="<?= sanitize($user['social_instagram'] ?? '') ?>" placeholder="username">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-twitter-x me-1"></i>Twitter/X</label>
                                <div class="input-group">
                                    <span class="input-group-text">@</span>
                                    <input type="text" name="social_twitter" class="form-control" value="<?= sanitize($user['social_twitter'] ?? '') ?>" placeholder="username">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-globe me-1"></i>Website</label>
                                <input type="url" name="social_website" class="form-control" value="<?= sanitize($user['social_website'] ?? '') ?>" placeholder="https://yourwebsite.com">
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">Change Password</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control" placeholder="Leave blank to keep current">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" placeholder="Min. 8 characters">
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
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('avatar-preview').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
