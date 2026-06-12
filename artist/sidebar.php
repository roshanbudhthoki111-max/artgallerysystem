<?php
// artist/sidebar.php
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
?>
<aside class="dash-sidebar p-3" style="background:var(--white);border-right:1px solid var(--border);">
    <div class="d-flex align-items-center gap-2 mb-4 p-2">
        <img src="<?= profileImage($user['profile_image']) ?>" class="avatar-md" alt="">
        <div>
            <div style="font-weight:600;font-size:.9rem"><?= sanitize(truncate($user['name'],18)) ?></div>
            <div style="font-size:.75rem;color:var(--gold)">◈ Artist</div>
        </div>
    </div>

    <div class="sidebar-heading">Dashboard</div>
    <a href="/art-gallery/artist/" class="sidebar-nav-link <?= $currentPage==='index.php'&&$currentDir==='artist'?'active':'' ?>">
        <i class="bi bi-speedometer2"></i> Overview
    </a>

    <div class="sidebar-heading mt-3">Artworks</div>
    <a href="/art-gallery/artist/artworks.php" class="sidebar-nav-link <?= $currentPage==='artworks.php'?'active':'' ?>">
        <i class="bi bi-images"></i> My Artworks
    </a>
    <a href="/art-gallery/artist/upload-artwork.php" class="sidebar-nav-link <?= $currentPage==='upload-artwork.php'?'active':'' ?>">
        <i class="bi bi-plus-circle"></i> Upload Artwork
    </a>

    <div class="sidebar-heading mt-3">Commerce</div>
    <a href="/art-gallery/artist/orders.php" class="sidebar-nav-link <?= $currentPage==='orders.php'?'active':'' ?>">
        <i class="bi bi-box-seam"></i> Orders
    </a>
    <a href="/art-gallery/artist/offers.php" class="sidebar-nav-link <?= $currentPage==='offers.php'?'active':'' ?>">
        <i class="bi bi-chat-text"></i> Offers
    </a>
    <a href="/art-gallery/artist/earnings.php" class="sidebar-nav-link <?= $currentPage==='earnings.php'?'active':'' ?>">
        <i class="bi bi-wallet2"></i> Earnings
    </a>

    <div class="sidebar-heading mt-3">Account</div>
    <a href="/art-gallery/artist/profile.php" class="sidebar-nav-link <?= $currentPage==='profile.php'?'active':'' ?>">
        <i class="bi bi-person"></i> Profile
    </a>
    <a href="/art-gallery/" class="sidebar-nav-link">
        <i class="bi bi-globe"></i> View Site
    </a>
    <a href="/art-gallery/logout.php" class="sidebar-nav-link text-danger">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>
</aside>
