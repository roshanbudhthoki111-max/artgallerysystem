<?php
require_once __DIR__ . '/includes/functions.php';
startSession();
if (isLoggedIn()) { header("Location: /art-gallery/"); exit; }

$error = '';
$success = '';
$role = $_GET['role'] ?? 'customer';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = sanitize($_POST['name'] ?? '');
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $role     = in_array($_POST['role'], ['artist','customer']) ? $_POST['role'] : 'customer';
    $bio      = sanitize($_POST['bio'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check email exists
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $hash   = password_hash($password, PASSWORD_DEFAULT);
            $status = ($role === 'artist' && getSetting('artist_auto_approve') !== '1') ? 'pending' : 'active';

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, bio, status) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("ssssss", $name, $email, $hash, $role, $bio, $status);

            if ($stmt->execute()) {
                if ($role === 'artist' && $status === 'pending') {
                    $success = 'Artist account created! Please wait for admin approval before signing in.';
                } else {
                    $userId = $conn->insert_id;
                    $_SESSION['user_id']   = $userId;
                    $_SESSION['user_role'] = $role;
                    $_SESSION['user_name'] = $name;
                    setFlash('success', "Welcome to ArtVault, $name!");
                    header("Location: /art-gallery/" . ($role === 'artist' ? 'artist/' : 'customer/'));
                    exit;
                }
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — ArtVault</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/art-gallery/assets/css/main.css" rel="stylesheet">
</head>
<body>
<div class="auth-wrapper py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-sm-10 col-md-8 col-lg-5">
                <div class="text-center mb-4">
                    <a href="/art-gallery/" style="font-family:var(--font-display);font-size:1.8rem;color:var(--ink);text-decoration:none;">
                        <span style="color:var(--gold)">◈</span> ArtVault
                    </a>
                </div>

                <div class="auth-card">
                    <h1 class="auth-title">Create Account</h1>
                    <p class="auth-subtitle">Join ArtVault and start your journey</p>

                    <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                    <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= $success ?></div>
                    <?php endif; ?>

                    <?php if (!$success): ?>
                    <form method="POST" novalidate>
                        <!-- Role toggle -->
                        <div class="mb-4">
                            <div class="d-grid" style="grid-template-columns:1fr 1fr;display:grid;gap:.5rem;">
                                <label style="cursor:pointer;">
                                    <input type="radio" name="role" value="customer" <?= $role === 'customer' ? 'checked' : '' ?> class="d-none" onchange="toggleBio(false)">
                                    <div class="role-tab" id="tab-customer" onclick="selectRole('customer')">
                                        <i class="bi bi-bag fs-5 d-block mb-1"></i>
                                        <div style="font-weight:600;font-size:.85rem">Buyer</div>
                                        <div style="font-size:.75rem;color:var(--ink-muted)">Browse & collect art</div>
                                    </div>
                                </label>
                                <label style="cursor:pointer;">
                                    <input type="radio" name="role" value="artist" <?= $role === 'artist' ? 'checked' : '' ?> class="d-none" onchange="toggleBio(true)">
                                    <div class="role-tab" id="tab-artist" onclick="selectRole('artist')">
                                        <i class="bi bi-palette fs-5 d-block mb-1"></i>
                                        <div style="font-weight:600;font-size:.85rem">Artist</div>
                                        <div style="font-size:.75rem;color:var(--ink-muted)">Share your artworks</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="name" class="form-control" value="<?= sanitize($_POST['name'] ?? '') ?>" placeholder="Your full name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address *</label>
                            <input type="email" name="email" class="form-control" value="<?= sanitize($_POST['email'] ?? '') ?>" placeholder="your@email.com" required>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">Password *</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="reg-pass" class="form-control" placeholder="Min. 8 chars" required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="toggleField('reg-pass','reg-pass-icon')">
                                        <i class="bi bi-eye" id="reg-pass-icon"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Confirm *</label>
                                <div class="input-group">
                                    <input type="password" name="confirm_password" id="reg-confirm" class="form-control" placeholder="Repeat" required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="toggleField('reg-confirm','reg-confirm-icon')">
                                        <i class="bi bi-eye" id="reg-confirm-icon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div id="bio-field" class="mb-3" style="display:<?= $role === 'artist' ? 'block' : 'none' ?>">
                            <label class="form-label">Artist Bio</label>
                            <textarea name="bio" class="form-control" rows="3" placeholder="Tell us about your art and style..."><?= sanitize($_POST['bio'] ?? '') ?></textarea>
                        </div>

                        <div class="form-check mb-4">
                            <input type="checkbox" id="terms" class="form-check-input" required>
                            <label class="form-check-label" for="terms" style="font-size:.85rem;color:var(--ink-muted)">
                                I agree to the <a href="#" style="color:var(--gold)">Terms</a> and <a href="#" style="color:var(--gold)">Privacy Policy</a>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-dark w-100 py-2">Create Account</button>
                    </form>
                    <?php endif; ?>

                    <hr class="my-3">
                    <p class="text-center text-muted mb-0" style="font-size:.875rem">
                        Already have an account? <a href="/art-gallery/login.php" style="color:var(--gold)">Sign in</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.role-tab {
    border: 2px solid var(--border);
    border-radius: var(--radius);
    padding: 1rem;
    text-align: center;
    transition: all var(--trans);
    background: var(--white);
}
.role-tab:hover { border-color: var(--gold); background: rgba(184,144,74,.04); }
.role-tab.selected { border-color: var(--ink); background: var(--ink); color: var(--white); }
.role-tab.selected div { color: rgba(255,255,255,.7) !important; }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleField(fieldId, iconId) {
    const f = document.getElementById(fieldId);
    const i = document.getElementById(iconId);
    f.type = f.type === 'password' ? 'text' : 'password';
    i.className = f.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
function selectRole(role) {
    document.querySelector('[name=role][value='+role+']').checked = true;
    document.getElementById('tab-customer').classList.toggle('selected', role==='customer');
    document.getElementById('tab-artist').classList.toggle('selected', role==='artist');
    document.getElementById('bio-field').style.display = role === 'artist' ? 'block' : 'none';
}
// Init
selectRole('<?= $role ?>');
</script>
</body>
</html>
