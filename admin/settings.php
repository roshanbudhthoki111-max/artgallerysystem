<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = ['site_name','site_tagline','commission_percentage','currency','artist_auto_approve','payment_esewa_id','payment_esewa_secret','payment_khalti_key','payment_khalti_secret'];
    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            $val = sanitize($_POST[$key]);
            $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?");
            $stmt->bind_param("sss", $key, $val, $val);
            $stmt->execute();
        }
    }
    setFlash('success', 'Settings saved!');
    header("Location: /art-gallery/admin/settings.php");
    exit;
}

$settings = [];
$result = $conn->query("SELECT * FROM settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$pageTitle = 'System Settings';
require_once __DIR__ . '/../includes/header.php';
showFlash();
?>

<div class="dash-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="dash-main">
        <h2 style="font-family:var(--font-display);font-size:1.6rem;font-weight:400;margin-bottom:1.5rem">System Settings</h2>

        <form method="POST">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card mb-3">
                        <div class="card-header">General Settings</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Site Name</label>
                                <input type="text" name="site_name" class="form-control" value="<?= sanitize($settings['site_name'] ?? 'ArtVault') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tagline</label>
                                <input type="text" name="site_tagline" class="form-control" value="<?= sanitize($settings['site_tagline'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Default Currency</label>
                                <select name="currency" class="form-select">
                                    <option value="NPR" <?= ($settings['currency']??'NPR')==='NPR'?'selected':'' ?>>NPR — Nepali Rupee</option>
                                    <option value="USD" <?= ($settings['currency']??'')==='USD'?'selected':'' ?>>USD — US Dollar</option>
                                    <option value="INR" <?= ($settings['currency']??'')==='INR'?'selected':'' ?>>INR — Indian Rupee</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">Commission & Payouts</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Platform Commission (%)</label>
                                <div class="input-group">
                                    <input type="number" name="commission_percentage" class="form-control"
                                           value="<?= $settings['commission_percentage'] ?? 10 ?>" min="0" max="50" step="0.5">
                                    <span class="input-group-text">%</span>
                                </div>
                                <div class="form-text">Artists receive (100 - commission)% of each sale.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Auto-Approve Artists</label>
                                <select name="artist_auto_approve" class="form-select">
                                    <option value="0" <?= ($settings['artist_auto_approve']??'0')==='0'?'selected':'' ?>>No — Manual review required</option>
                                    <option value="1" <?= ($settings['artist_auto_approve']??'')==='1'?'selected':'' ?>>Yes — Approve automatically</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card mb-3">
                        <div class="card-header">Payment Gateway Settings</div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-phone me-1"></i>eSewa Merchant ID</label>
                                <input type="text" name="payment_esewa_id" class="form-control"
                                       value="<?= sanitize($settings['payment_esewa_id'] ?? '') ?>" placeholder="EPAYTEST or your merchant ID">
                                <div class="form-text">Merchant code from your eSewa dashboard. Use <code>EPAYTEST</code> for sandbox.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">eSewa Secret Key</label>
                                <input type="password" name="payment_esewa_secret" class="form-control"
                                       value="<?= sanitize($settings['payment_esewa_secret'] ?? '') ?>" placeholder="eSewa HMAC secret key">
                                <div class="form-text">HMAC secret from eSewa dashboard. Sandbox default: <code>8gBm/:&amp;EnhH.1/q</code></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-phone-fill me-1"></i>Khalti Public Key</label>
                                <input type="text" name="payment_khalti_key" class="form-control"
                                       value="<?= sanitize($settings['payment_khalti_key'] ?? '') ?>" placeholder="test_public_key_…">
                                <div class="form-text">Public key from your Khalti dashboard (used for display).</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Khalti Secret Key</label>
                                <input type="password" name="payment_khalti_secret" class="form-control"
                                       value="<?= sanitize($settings['payment_khalti_secret'] ?? '') ?>" placeholder="test_secret_key_…">
                                <div class="form-text">Secret key from your Khalti dashboard — used for server-side payment initiation &amp; verification.</div>
                            </div>
                            <div class="alert alert-info mb-0" style="font-size:.8rem">
                                <i class="bi bi-info-circle me-1"></i>
                                Payment gateway integration is simulated in this demo. Add real credentials for production.
                            </div>
                        </div>
                    </div>

                    <!-- System info -->
                    <div class="card">
                        <div class="card-header">System Information</div>
                        <div class="card-body">
                            <table class="table table-sm mb-0">
                                <tr><th style="font-size:.8rem;width:40%">PHP Version</th><td style="font-size:.85rem"><?= PHP_VERSION ?></td></tr>
                                <tr><th style="font-size:.8rem">Database</th><td style="font-size:.85rem">MySQL (art_gallery)</td></tr>
                                <tr><th style="font-size:.8rem">Upload Limit</th><td style="font-size:.85rem"><?= ini_get('upload_max_filesize') ?></td></tr>
                                <tr><th style="font-size:.8rem">Server</th><td style="font-size:.85rem"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'XAMPP' ?></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-dark px-5">Save All Settings</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
