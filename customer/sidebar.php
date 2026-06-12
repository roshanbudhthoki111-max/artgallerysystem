<?php
// customer/sidebar.php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="dash-sidebar p-3" style="background:var(--white);border-right:1px solid var(--border);">
    <div class="d-flex align-items-center gap-2 mb-4 p-2">
        <img src="<?= profileImage($user['profile_image']) ?>" class="avatar-md" alt="">
        <div>
            <div style="font-weight:600;font-size:.9rem"><?= sanitize(truncate($user['name'],18)) ?></div>
            <div style="font-size:.75rem;color:var(--ink-muted)">Collector</div>
        </div>
    </div>

    <div class="sidebar-heading">My Account</div>
    <a href="/art-gallery/customer/" class="sidebar-nav-link <?= $currentPage==='index.php'?'active':'' ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="/art-gallery/customer/profile.php" class="sidebar-nav-link <?= $currentPage==='profile.php'?'active':'' ?>">
        <i class="bi bi-person"></i> My Profile
    </a>

    <div class="sidebar-heading mt-3">Shopping</div>
    <a href="/art-gallery/gallery.php" class="sidebar-nav-link">
        <i class="bi bi-compass"></i> Browse Gallery
    </a>
    <a href="/art-gallery/customer/cart.php" class="sidebar-nav-link <?= $currentPage==='cart.php'?'active':'' ?>">
        <i class="bi bi-bag"></i> My Cart
    </a>
    <a href="/art-gallery/customer/wishlist.php" class="sidebar-nav-link <?= $currentPage==='wishlist.php'?'active':'' ?>">
        <i class="bi bi-heart"></i> Wishlist
    </a>

    <div class="sidebar-heading mt-3">Orders</div>
    <a href="/art-gallery/customer/orders.php" class="sidebar-nav-link <?= $currentPage==='orders.php'?'active':'' ?>">
        <i class="bi bi-box-seam"></i> My Orders
    </a>

    <div class="sidebar-heading mt-3">Account</div>
    <a href="/art-gallery/" class="sidebar-nav-link">
        <i class="bi bi-globe"></i> View Site
    </a>
    <a href="/art-gallery/logout.php" class="sidebar-nav-link text-danger">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>
</aside>
