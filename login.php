<?php
require_once __DIR__ . '/includes/functions.php';
startSession();

if (isLoggedIn()) {
    $role = $_SESSION['user_role'];
    header("Location: /art-gallery/" . ($role === 'admin' ? 'admin/' : ($role === 'artist' ? 'artist/' : 'customer/')));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'blocked') {
                $error = 'Your account has been suspended. Contact support.';
            } elseif ($user['status'] === 'pending') {
                $error = 'Your artist account is pending admin approval.';
            } else {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['name'];

                setFlash('success', 'Welcome back, ' . $user['name'] . '!');
                $redirect = $_GET['redirect'] ?? '/art-gallery/' . ($user['role'] === 'admin' ? 'admin/' : ($user['role'] === 'artist' ? 'artist/' : 'customer/'));
                header("Location: $redirect");
                exit;
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
$pageTitle = 'Sign In';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — ArtVault</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/art-gallery/assets/css/main.css" rel="stylesheet">
</head>
<body>
<div class="auth-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-sm-10 col-md-8 col-lg-5">

                <div class="text-center mb-4">
                    <a href="/art-gallery/" style="font-family:var(--font-display);font-size:1.8rem;color:var(--ink);text-decoration:none;">
                        <span style="color:var(--gold)">◈</span> ArtVault
                    </a>
                </div>

                <div class="auth-card">
                    <h1 class="auth-title">Welcome back</h1>
                    <p class="auth-subtitle">Sign in to your account to continue</p>

                    <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?= sanitize($_POST['email'] ?? '') ?>" placeholder="your@email.com" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label d-flex justify-content-between">
                                Password
                                <a href="#" style="font-size:.8rem;color:var(--gold);font-weight:normal;text-transform:none;letter-spacing:0">Forgot?</a>
                            </label>
                            <div class="input-group">
                                <input type="password" name="password" id="pass" class="form-control" placeholder="••••••••" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePass()">
                                    <i class="bi bi-eye" id="pass-icon"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-dark w-100 py-2 mb-3">Sign In</button>
                    </form>

                    <hr class="my-3">
                    <p class="text-center text-muted mb-0" style="font-size:.875rem">
                        No account? <a href="/art-gallery/register.php" style="color:var(--gold)">Create one free</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function togglePass() {
    const p = document.getElementById('pass');
    const i = document.getElementById('pass-icon');
    p.type = p.type === 'password' ? 'text' : 'password';
    i.className = p.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>
</body>
</html>
