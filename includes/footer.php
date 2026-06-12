<?php // includes/footer.php ?>
    <footer class="site-footer mt-auto">
        <div class="container">
            <div class="row g-4 py-5">
                <div class="col-lg-4">
                    <div class="footer-brand mb-3">
                        <span class="brand-icon me-2">◈</span>
                        <span class="brand-name"><?= getSetting('site_name') ?? 'ArtVault' ?></span>
                    </div>
                    <p class="text-muted small"><?= getSetting('site_tagline') ?? 'Discover & Collect Extraordinary Art' ?></p>
                    <div class="d-flex gap-3 mt-3">
                        <a href="#" class="footer-social"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="footer-social"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="footer-social"><i class="bi bi-facebook"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-6">
                    <h6 class="footer-heading">Explore</h6>
                    <ul class="list-unstyled footer-links">
                        <li><a href="/art-gallery/gallery.php">All Artworks</a></li>
                        <li><a href="/art-gallery/artists.php">Artists</a></li>
                        <li><a href="/art-gallery/gallery.php?category=painting">Painting</a></li>
                        <li><a href="/art-gallery/gallery.php?category=digital-art">Digital Art</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-6">
                    <h6 class="footer-heading">Account</h6>
                    <ul class="list-unstyled footer-links">
                        <?php if (!isLoggedIn()): ?>
                        <li><a href="/art-gallery/login.php">Sign In</a></li>
                        <li><a href="/art-gallery/register.php">Register</a></li>
                        <?php elseif (isset($currentUser) && $currentUser['role'] === 'artist'): ?>
                        <li><a href="/art-gallery/artist/">Dashboard</a></li>
                        <li><a href="/art-gallery/artist/profile.php">My Profile</a></li>
                        <?php elseif (isset($currentUser) && $currentUser['role'] === 'admin'): ?>
                        <li><a href="/art-gallery/admin/">Admin Panel</a></li>
                        <?php else: ?>
                        <li><a href="/art-gallery/customer/">Dashboard</a></li>
                        <li><a href="/art-gallery/customer/orders.php">My Orders</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h6 class="footer-heading">Stay Updated</h6>
                    <p class="text-muted small">New artworks, artist spotlights and collection drops.</p>
                    <div class="input-group mt-2">
                        <input type="email" class="form-control form-control-sm" placeholder="your@email.com">
                        <button class="btn btn-dark btn-sm">Subscribe</button>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <span class="text-muted small">© <?= date('Y') ?> <?= getSetting('site_name') ?? 'ArtVault' ?>. All rights reserved.</span>
                <span class="text-muted small">Made with <i class="bi bi-heart-fill text-danger"></i> in Nepal</span>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="/art-gallery/assets/js/main.js"></script>
    <?php if (isset($extraJs)) echo $extraJs; ?>
</body>
</html>
