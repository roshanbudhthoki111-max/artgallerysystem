<?php
// admin/sidebar.php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="dash-sidebar p-3" style="background:var(--ink);color:white;">
    <div class="d-flex align-items-center gap-2 mb-4 p-2">
        <div style="width:40px;height:40px;border-radius:50%;background:var(--gold);display:flex;align-items:center;justify-content:center;font-size:1.2rem;">◈</div>
        <div>
            <div style="font-weight:600;font-size:.9rem;color:white">Admin Panel</div>
            <div style="font-size:.7rem;color:rgba(255,255,255,.4)">ArtVault Control</div>
        </div>
    </div>

    <?php
    $navSections = [
        'Overview' => [
            ['href'=>'/art-gallery/admin/', 'icon'=>'bi-speedometer2', 'label'=>'Dashboard', 'page'=>'index.php'],
        ],
        'Users' => [
            ['href'=>'/art-gallery/admin/users.php', 'icon'=>'bi-people', 'label'=>'All Users', 'page'=>'users.php'],
        ],
        'Content' => [
            ['href'=>'/art-gallery/admin/artworks.php', 'icon'=>'bi-images', 'label'=>'Artworks', 'page'=>'artworks.php'],
            ['href'=>'/art-gallery/admin/categories.php', 'icon'=>'bi-tags', 'label'=>'Categories', 'page'=>'categories.php'],
        ],
        'Commerce' => [
            ['href'=>'/art-gallery/admin/orders.php', 'icon'=>'bi-box-seam', 'label'=>'Orders', 'page'=>'orders.php'],
            ['href'=>'/art-gallery/admin/withdrawals.php', 'icon'=>'bi-wallet2', 'label'=>'Withdrawals', 'page'=>'withdrawals.php'],
        ],
        'System' => [
            ['href'=>'/art-gallery/admin/settings.php', 'icon'=>'bi-gear', 'label'=>'Settings', 'page'=>'settings.php'],
            ['href'=>'/art-gallery/', 'icon'=>'bi-globe', 'label'=>'View Site', 'page'=>''],
            ['href'=>'/art-gallery/logout.php', 'icon'=>'bi-box-arrow-right', 'label'=>'Logout', 'page'=>''],
        ],
    ];
    foreach ($navSections as $section => $links):
    ?>
    <div style="font-size:.65rem;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:rgba(255,255,255,.3);margin-bottom:.5rem;margin-top:1.25rem;padding-left:.5rem;"><?= $section ?></div>
    <?php foreach ($links as $link): ?>
    <a href="<?= $link['href'] ?>" class="sidebar-nav-link <?= $currentPage === $link['page'] ? 'active' : '' ?>"
       style="color:<?= $currentPage === $link['page'] ? 'white' : 'rgba(255,255,255,.6)' ?>;
              background:<?= $currentPage === $link['page'] ? 'var(--gold)' : 'transparent' ?>;">
        <i class="bi <?= $link['icon'] ?>"></i> <?= $link['label'] ?>
    </a>
    <?php endforeach; endforeach; ?>
</aside>
