<?php
// bayawan-mini-hotel-system/admin/admin_2fa_setup.php
require('includes/admin_essentials.php');
require('includes/admin_configuration.php');
require_once('../includes/vendor/autoload.php');

adminLogin();

use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\EndroidQrCodeProvider;

$tfa      = new TwoFactorAuth(new EndroidQrCodeProvider(), 'Cebu Mini Hotel');
$admin_id = $_SESSION['adminId'];

// Fetch current admin record
$admin = mysqli_fetch_assoc(
    select("SELECT * FROM `admin_cred` WHERE `sr_no`=? LIMIT 1", [$admin_id], 'i')
);

$message      = '';
$message_type = '';

// ── Generate new secret ───────────────────────────────────────
if (isset($_POST['generate'])) {
    $secret = $tfa->createSecret();
    update(
        "UPDATE `admin_cred` SET `totp_secret`=?, `totp_enabled`=0 WHERE `sr_no`=?",
        [$secret, $admin_id], 'si'
    );
    // Refresh admin record
    $admin = mysqli_fetch_assoc(
        select("SELECT * FROM `admin_cred` WHERE `sr_no`=? LIMIT 1", [$admin_id], 'i')
    );
    $message      = 'New secret generated. Scan the QR code below with your authenticator app, then verify to enable 2FA.';
    $message_type = 'info';
}

// ── Verify OTP and enable 2FA ─────────────────────────────────
if (isset($_POST['verify_enable'])) {
    $otp    = trim($_POST['otp'] ?? '');
    $secret = $admin['totp_secret'] ?? '';

    if ($secret && $tfa->verifyCode($secret, $otp)) {
        update(
            "UPDATE `admin_cred` SET `totp_enabled`=1 WHERE `sr_no`=?",
            [$admin_id], 'i'
        );
        $message      = '2FA has been successfully enabled on your account.';
        $message_type = 'success';
        // Refresh
        $admin = mysqli_fetch_assoc(
            select("SELECT * FROM `admin_cred` WHERE `sr_no`=? LIMIT 1", [$admin_id], 'i')
        );
    } else {
        $message      = 'Invalid code. Please try again.';
        $message_type = 'danger';
    }
}

// ── Disable 2FA ───────────────────────────────────────────────
if (isset($_POST['disable_2fa'])) {
    $otp    = trim($_POST['otp_disable'] ?? '');
    $secret = $admin['totp_secret'] ?? '';

    if ($secret && $tfa->verifyCode($secret, $otp)) {
        update(
            "UPDATE `admin_cred` SET `totp_enabled`=0, `totp_secret`=NULL WHERE `sr_no`=?",
            [$admin_id], 'i'
        );
        $message      = '2FA has been disabled on your account.';
        $message_type = 'warning';
        $admin = mysqli_fetch_assoc(
            select("SELECT * FROM `admin_cred` WHERE `sr_no`=? LIMIT 1", [$admin_id], 'i')
        );
    } else {
        $message      = 'Invalid code. 2FA was not disabled.';
        $message_type = 'danger';
    }
}

// Generate QR code if secret exists but 2FA not yet enabled
$qr_code_url = '';
if (!empty($admin['totp_secret']) && !$admin['totp_enabled']) {
    $qr_code_url = $tfa->getQRCodeImageAsDataUri(
        $_SESSION['adminName'] . ' (Cebu)',
        $admin['totp_secret']
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel - 2FA Setup</title>
  <?php require('includes/admin_links.php'); ?>
</head>
<body class="bg-light">

<?php require('includes/admin_header.php'); ?>

<div id="main-content">
  <div class="container-fluid">
    <div class="row justify-content-center">
      <div class="col-lg-6 col-md-8 p-4">

        <h3 class="mb-4">TWO-FACTOR AUTHENTICATION</h3>

        <?php if ($message): ?>
          <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <!-- Status Card -->
        <div class="card border-0 shadow-sm mb-4">
          <div class="card-body d-flex align-items-center justify-content-between p-4">
            <div>
              <h5 class="mb-1">2FA Status</h5>
              <p class="mb-0 text-muted small">Logged in as <strong><?= htmlspecialchars($_SESSION['adminName']) ?></strong></p>
            </div>
            <?php if ($admin['totp_enabled']): ?>
              <span class="badge bg-success px-3 py-2 fs-6">
                <i class="bi bi-shield-check me-1"></i> Enabled
              </span>
            <?php else: ?>
              <span class="badge bg-danger px-3 py-2 fs-6">
                <i class="bi bi-shield-x me-1"></i> Disabled
              </span>
            <?php endif; ?>
          </div>
        </div>

        <?php if (!$admin['totp_enabled']): ?>

          <!-- Step 1: Generate Secret -->
          <?php if (empty($admin['totp_secret'])): ?>
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
              <h6 class="fw-bold mb-2"><span class="badge bg-dark me-2">1</span>Generate Your Secret Key</h6>
              <p class="text-muted small mb-3">
                Click the button below to generate a unique secret key for your account.
                You will then scan it with Google Authenticator or any TOTP app.
              </p>
              <form method="POST">
                <button name="generate" type="submit" class="btn btn-dark shadow-none w-100">
                  <i class="bi bi-key me-2"></i>Generate Secret & Show QR Code
                </button>
              </form>
            </div>
          </div>

          <?php else: ?>

          <!-- Step 2: Scan QR -->
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4 text-center">
              <h6 class="fw-bold mb-3"><span class="badge bg-dark me-2">2</span>Scan with Authenticator App</h6>
              <p class="text-muted small mb-3">
                Open <strong>Google Authenticator</strong>, <strong>Authy</strong>, or any TOTP app
                and scan the QR code below.
              </p>
              <?php if ($qr_code_url): ?>
                <img src="<?= $qr_code_url ?>" alt="QR Code" class="border rounded p-2 mb-3" style="max-width:200px;">
              <?php endif; ?>
              <p class="text-muted small mb-0">
                Can't scan? Enter this key manually:<br>
                <code class="user-select-all"><?= htmlspecialchars($admin['totp_secret']) ?></code>
              </p>
            </div>
          </div>

          <!-- Step 3: Verify -->
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
              <h6 class="fw-bold mb-3"><span class="badge bg-dark me-2">3</span>Verify & Enable</h6>
              <p class="text-muted small mb-3">
                Enter the 6-digit code from your authenticator app to confirm setup.
              </p>
              <form method="POST">
                <div class="input-group mb-3">
                  <input type="text" name="otp" class="form-control form-control-lg text-center shadow-none"
                         placeholder="000000" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" required
                         style="letter-spacing:8px; font-size:1.4rem;">
                </div>
                <button name="verify_enable" type="submit" class="btn btn-success shadow-none w-100">
                  <i class="bi bi-check-circle me-2"></i>Verify & Enable 2FA
                </button>
              </form>
              <hr>
              <form method="POST" class="mt-2">
                <button name="generate" type="submit" class="btn btn-outline-secondary btn-sm shadow-none w-100">
                  <i class="bi bi-arrow-clockwise me-1"></i>Regenerate QR Code
                </button>
              </form>
            </div>
          </div>

          <?php endif; ?>

        <?php else: ?>

          <!-- Disable 2FA -->
          <div class="card border-danger border-0 shadow-sm">
            <div class="card-body p-4">
              <h6 class="fw-bold text-danger mb-3">
                <i class="bi bi-exclamation-triangle me-2"></i>Disable Two-Factor Authentication
              </h6>
              <p class="text-muted small mb-3">
                Enter your current 6-digit authenticator code to disable 2FA.
                Your account will be less secure without it.
              </p>
              <form method="POST">
                <div class="input-group mb-3">
                  <input type="text" name="otp_disable"
                         class="form-control form-control-lg text-center shadow-none"
                         placeholder="000000" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" required
                         style="letter-spacing:8px; font-size:1.4rem;">
                </div>
                <button name="disable_2fa" type="submit" class="btn btn-danger shadow-none w-100">
                  <i class="bi bi-shield-x me-2"></i>Disable 2FA
                </button>
              </form>
            </div>
          </div>

        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<?php require('includes/admin_scripts.php'); ?>
</body>
</html>