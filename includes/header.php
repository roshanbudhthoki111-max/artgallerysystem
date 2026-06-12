<?php
// includes/header.php
require_once __DIR__ . '/functions.php';
startSession();
$currentUser = isLoggedIn() ? getCurrentUser() : null;
$cartCount = ($currentUser && $currentUser['role'] === 'customer') ? getCartCount($currentUser['id']) : 0;
$siteName = getSetting('site_name') ?? 'ArtVault';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' — ' : '' ?><?= $siteName ?></title>
    <?php if (isLoggedIn()): ?>
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/art-gallery/assets/css/main.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg site-nav sticky-top">
    <div class="container">
        <a class="navbar-brand" href="/art-gallery/">
            <span class="brand-icon">◈</span>
            <span class="brand-name"><?= $siteName ?></span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <i class="bi bi-list fs-4"></i>
        </button>

        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav mx-auto gap-1">
                <li class="nav-item"><a class="nav-link" href="/art-gallery/">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="/art-gallery/gallery.php">Gallery</a></li>
                <li class="nav-item"><a class="nav-link" href="/art-gallery/artists.php">Artists</a></li>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <?php if ($currentUser): ?>
                    <?php if ($currentUser['role'] === 'customer'): ?>
                        <a href="/art-gallery/customer/cart.php" class="nav-icon-btn position-relative">
                            <i class="bi bi-bag"></i>
                            <?php if ($cartCount > 0): ?>
                                <span class="badge-dot"><?= $cartCount ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="/art-gallery/customer/wishlist.php" class="nav-icon-btn">
                            <i class="bi bi-heart"></i>
                        </a>
                    <?php endif; ?>
                    <div class="dropdown">
                        <button class="user-avatar-btn dropdown-toggle" data-bs-toggle="dropdown">
                            <img src="<?= profileImage($currentUser['profile_image']) ?>" alt="" class="avatar-sm">
                            <span class="d-none d-md-inline ms-2"><?= sanitize(explode(' ', $currentUser['name'])[0]) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                            <?php if ($currentUser['role'] === 'admin'): ?>
                                <li><a class="dropdown-item" href="/art-gallery/admin/"><i class="bi bi-shield-check me-2"></i>Admin Panel</a></li>
                            <?php elseif ($currentUser['role'] === 'artist'): ?>
                                <li><a class="dropdown-item" href="/art-gallery/artist/"><i class="bi bi-palette me-2"></i>Artist Dashboard</a></li>
                                <li><a class="dropdown-item" href="/art-gallery/artist/profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="/art-gallery/customer/"><i class="bi bi-grid me-2"></i>Dashboard</a></li>
                                <li><a class="dropdown-item" href="/art-gallery/customer/orders.php"><i class="bi bi-box-seam me-2"></i>My Orders</a></li>
                                <li><a class="dropdown-item" href="/art-gallery/customer/wishlist.php"><i class="bi bi-heart me-2"></i>Wishlist</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/art-gallery/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="/art-gallery/login.php" class="btn btn-outline-dark btn-sm px-3">Sign In</a>
                    <a href="/art-gallery/register.php" class="btn btn-dark btn-sm px-3">Join Free</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
