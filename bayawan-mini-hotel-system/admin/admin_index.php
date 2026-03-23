<?php
// bayawan-mini-hotel-system/admin/admin_index.php
require('includes/admin_essentials.php');
require('includes/admin_configuration.php');
require_once('../includes/rate_limiter.php');
require_once('../includes/vendor/autoload.php');

use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\BaconQrCodeProvider;

if (session_status() === PHP_SESSION_NONE) session_start();
if (isset($_SESSION['adminLogin']) && $_SESSION['adminLogin'] == true) {
    redirect('admin_dashboard.php');
}

$show_2fa = isset($_SESSION['admin_2fa_pending']) && $_SESSION['admin_2fa_pending'] === true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login Panel</title>
  <?php require('includes/admin_links.php'); ?>
  <style>
    div.login-form {
      position:absolute; top:50%; left:50%;
      transform:translate(-50%,-50%);
      width:400px;
    }
    .otp-input {
      letter-spacing:10px;
      font-size:1.6rem;
      text-align:center;
    }
  </style>
</head>
<body class="bg-light">

<div class="login-form rounded bg-white shadow overflow-hidden">

  <?php if (!$show_2fa): ?>
  <!-- ── Step 1: Username + Password ── -->
  <form method="POST">
    <h4 class="bg-dark text-white py-3 text-center mb-0">ADMIN LOGIN PANEL</h4>
    <div class="p-4">
      <div class="mb-3">
        <input name="admin_name" required type="text"
               class="form-control shadow-none text-center" placeholder="Username" autocomplete="username">
      </div>
      <div class="mb-4">
        <input name="admin_pass" required type="password"
               class="form-control shadow-none text-center" placeholder="Password" autocomplete="current-password">
      </div>
      <button name="login" type="submit" class="btn text-white custom-bg shadow-none w-100">LOGIN</button>
    </div>
  </form>

  <?php else: ?>
  <!-- ── Step 2: TOTP Code ── -->
  <form method="POST">
    <h4 class="bg-dark text-white py-3 text-center mb-0">TWO-FACTOR AUTH</h4>
    <div class="p-4 text-center">
      <i class="bi bi-shield-lock-fill text-success" style="font-size:2.5rem;"></i>
      <p class="text-muted mt-2 mb-4 small">
        Enter the 6-digit code from your authenticator app.
      </p>
      <input name="totp_code" required type="text" inputmode="numeric"
             pattern="[0-9]{6}" maxlength="6" autofocus
             class="form-control shadow-none otp-input mb-4"
             placeholder="000000" autocomplete="one-time-code">
      <button name="verify_2fa" type="submit"
              class="btn text-white custom-bg shadow-none w-100 mb-2">VERIFY</button>
      <a href="admin_index.php?cancel_2fa=1" class="btn btn-outline-secondary shadow-none w-100 btn-sm">
        Cancel &amp; go back
      </a>
    </div>
  </form>
  <?php endif; ?>

</div>

<?php

// ── Cancel 2FA step ───────────────────────────────────────────
if (isset($_GET['cancel_2fa'])) {
    unset($_SESSION['admin_2fa_pending'], $_SESSION['admin_2fa_id']);
    redirect('admin_index.php');
}

// ── Step 1: Password login ────────────────────────────────────
if (isset($_POST['login'])) {
    $rl = session_rate_limit('admin_login');

    if (!$rl['allowed']) {
        $wait = format_retry_after($rl['retry_after']);
        alert('error', "Too many failed attempts. Please wait {$wait} before trying again.");
    } else {
        $frm_data = filteration($_POST);

        $query = "SELECT * FROM `admin_cred` WHERE `admin_name`=?";
        $res   = select($query, [$frm_data['admin_name']], 's');

        if ($res->num_rows == 1) {
            $row = mysqli_fetch_assoc($res);

            if (password_verify($frm_data['admin_pass'], $row['admin_pass'])) {
                session_rate_reset('admin_login');

                // Check if 2FA is enabled for this admin
                if (!empty($row['totp_enabled']) && !empty($row['totp_secret'])) {
                    // Store pending state — don't log in yet
                    $_SESSION['admin_2fa_pending'] = true;
                    $_SESSION['admin_2fa_id']      = $row['sr_no'];
                    redirect('admin_index.php');
                } else {
                    // No 2FA — log in directly
                    $_SESSION['adminLogin'] = true;
                    $_SESSION['adminId']    = $row['sr_no'];
                    $_SESSION['adminName']  = $row['admin_name'];
                    $_SESSION['adminRole']  = $row['admin_role'];
                    redirect('admin_dashboard.php');
                }
            } else {
                $left = $rl['attempts_left'] - 1;
                alert('error', $left > 0
                    ? "Invalid credentials. {$left} attempt(s) remaining."
                    : "Account locked for " . format_retry_after(RATE_LOCKOUT_SECONDS) . "."
                );
            }
        } else {
            $left = $rl['attempts_left'] - 1;
            alert('error', $left > 0
                ? "Invalid credentials. {$left} attempt(s) remaining."
                : "Account locked for " . format_retry_after(RATE_LOCKOUT_SECONDS) . "."
            );
        }
    }
}

// ── Step 2: TOTP verification ─────────────────────────────────
if (isset($_POST['verify_2fa'])) {
    $rl = session_rate_limit('admin_2fa');

    if (!$rl['allowed']) {
        alert('error', "Too many attempts. Please wait " . format_retry_after($rl['retry_after']) . ".");
    } elseif (empty($_SESSION['admin_2fa_id'])) {
        redirect('admin_index.php');
    } else {
        $admin_id = (int)$_SESSION['admin_2fa_id'];
        $code     = trim($_POST['totp_code'] ?? '');

        $row = mysqli_fetch_assoc(
            select("SELECT * FROM `admin_cred` WHERE `sr_no`=? LIMIT 1", [$admin_id], 'i')
        );

        $tfa = new TwoFactorAuth(new BaconQrCodeProvider(), 'Bayawan Mini Hotel');
        $valid = $row && $tfa->verifyCode($row['totp_secret'], $code);

        if ($valid) {
            session_rate_reset('admin_2fa');
            unset($_SESSION['admin_2fa_pending'], $_SESSION['admin_2fa_id']);

            $_SESSION['adminLogin'] = true;
            $_SESSION['adminId']    = $row['sr_no'];
            $_SESSION['adminName']  = $row['admin_name'];
            $_SESSION['adminRole']  = $row['admin_role'];
            redirect('admin_dashboard.php');
        } else {
            $left = $rl['attempts_left'] - 1;
            alert('error', $left > 0
                ? "Invalid code. {$left} attempt(s) remaining."
                : "Too many attempts. Account locked for " . format_retry_after(RATE_LOCKOUT_SECONDS) . "."
            );
        }
    }
}
?>

<?php require('includes/admin_scripts.php') ?>
</body>
</html>