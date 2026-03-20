<?php
// bayawan-mini-hotel-system/admin/includes/admin_essentials.php
// All URL constants, filesystem paths, DB credentials, SMTP settings,
// PayMongo keys, and Google OAuth values are now defined in config/env.php.
// This file loads that single source of truth, then defines the helper
// functions that the rest of the admin panel relies on.

require_once __DIR__ . '/../../config/env.php';


// ── Auth helpers ─────────────────────────────────────────────────────

function adminLogin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!(isset($_SESSION['adminLogin']) && $_SESSION['adminLogin'] == true)) {
        echo "<script>window.location.href='admin_index.php';</script>";
        exit;
    }
    // Update last activity timestamp on every authenticated page load
    $_SESSION['last_activity'] = time();
}

function isAdmin(): bool {
    return isset($_SESSION['adminRole']) && $_SESSION['adminRole'] === 'admin';
}

function isReceptionist(): bool {
    return isset($_SESSION['adminRole']) && $_SESSION['adminRole'] === 'receptionist';
}

function adminOnly() {
    adminLogin();
    if (!isAdmin()) {
        echo "<script>window.location.href='admin_dashboard.php';</script>";
        exit;
    }
}

function redirect(string $url) {
    echo "<script>window.location.href='" . addslashes($url) . "';</script>";
    exit;
}

function alert(string $type, string $msg) {
    $bs_class = ($type === 'success') ? 'alert-success' : 'alert-danger';
    echo <<<HTML
      <div class="alert {$bs_class} alert-dismissible fade show custom-alert" role="alert">
        <strong class="me-3">{$msg}</strong>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    HTML;
}


// ── Image upload / delete helpers ────────────────────────────────────

function uploadImage(array $image, string $folder): string {
    $valid_mime = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($image['type'], $valid_mime))        return 'inv_img';
    if (($image['size'] / (1024 * 1024)) > 2)          return 'inv_size';

    $ext   = pathinfo($image['name'], PATHINFO_EXTENSION);
    $rname = 'IMG_' . random_int(11111, 99999) . '.' . $ext;
    $path  = UPLOAD_IMAGE_PATH . $folder . $rname;

    return move_uploaded_file($image['tmp_name'], $path) ? $rname : 'upd_failed';
}

function deleteImage(string $image, string $folder): bool {
    $full = UPLOAD_IMAGE_PATH . $folder . $image;
    return file_exists($full) && unlink($full);
}

function uploadSVGImage(array $image, string $folder): string {
    if ($image['type'] !== 'image/svg+xml')    return 'inv_img';
    if (($image['size'] / (1024 * 1024)) > 1)  return 'inv_size';

    $ext   = pathinfo($image['name'], PATHINFO_EXTENSION);
    $rname = 'IMG_' . random_int(11111, 99999) . '.' . $ext;
    $path  = UPLOAD_IMAGE_PATH . $folder . $rname;

    return move_uploaded_file($image['tmp_name'], $path) ? $rname : 'upd_failed';
}

function uploadUserImage(array $image): string {
    $valid_mime = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($image['type'], $valid_mime)) return 'inv_img';

    $ext   = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
    $rname = 'IMG_' . random_int(11111, 99999) . '.jpeg';
    $path  = UPLOAD_IMAGE_PATH . USERS_FOLDER . $rname;

    $img = match ($ext) {
        'png'  => imagecreatefrompng($image['tmp_name']),
        'webp' => imagecreatefromwebp($image['tmp_name']),
        default => imagecreatefromjpeg($image['tmp_name']),
    };

    return imagejpeg($img, $path, 75) ? $rname : 'upd_failed';
}