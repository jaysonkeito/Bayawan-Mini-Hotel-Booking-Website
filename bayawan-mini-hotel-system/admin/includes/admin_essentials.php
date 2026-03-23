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
    // FIX: Use finfo to check actual file magic bytes rather than trusting
    // $_FILES['type'], which is provided by the browser and can be spoofed.
    // A malicious user could upload a PHP file with type "image/jpeg" set
    // in the request. finfo_file() reads the actual file content on disk.
    $finfo       = new finfo(FILEINFO_MIME_TYPE);
    $actual_mime = $finfo->file($image['tmp_name']);
    $valid_mime  = ['image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($actual_mime, $valid_mime))  return 'inv_img';
    if (($image['size'] / (1024 * 1024)) > 2)  return 'inv_size';

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
    // FIX: SVG files can contain embedded <script> tags and on* event handler
    // attributes, making them a well-known XSS vector when served from the same
    // origin. Previously this function only checked MIME type and called
    // move_uploaded_file() directly.
    //
    // Now we:
    // 1. Still check MIME type as a first gate.
    // 2. Read the SVG content and strip all <script> blocks.
    // 3. Strip all on* event handler attributes (onclick, onload, onerror, etc.).
    // 4. Write the sanitized content ourselves instead of using move_uploaded_file().
    if ($image['type'] !== 'image/svg+xml')    return 'inv_img';
    if (($image['size'] / (1024 * 1024)) > 1)  return 'inv_size';

    $svg_content = file_get_contents($image['tmp_name']);
    if ($svg_content === false) return 'upd_failed';

    // Strip <script>...</script> blocks (case-insensitive, handles multiline)
    $svg_content = preg_replace('/<script[\s\S]*?<\/script>/i', '', $svg_content);

    // Neutralise on* event handler attributes (onclick, onload, onerror, onmouseover, etc.)
    $svg_content = preg_replace('/\bon\w+\s*=/i', 'data-removed=', $svg_content);

    // Strip javascript: href/xlink:href values
    $svg_content = preg_replace('/\b(href|xlink:href)\s*=\s*["\']?\s*javascript:/i', 'data-removed=', $svg_content);

    $ext   = pathinfo($image['name'], PATHINFO_EXTENSION);
    $rname = 'IMG_' . random_int(11111, 99999) . '.' . $ext;
    $path  = UPLOAD_IMAGE_PATH . $folder . $rname;

    return (file_put_contents($path, $svg_content) !== false) ? $rname : 'upd_failed';
}

function uploadUserImage(array $image): string {
    // FIX: Use finfo for actual MIME detection (matches uploadImage fix above).
    // Additionally, the GD re-encode below already acts as a strong second layer —
    // if the file isn't a real image, imagecreatefrom*() will return false.
    $finfo       = new finfo(FILEINFO_MIME_TYPE);
    $actual_mime = $finfo->file($image['tmp_name']);
    $valid_mime  = ['image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($actual_mime, $valid_mime)) return 'inv_img';

    $ext   = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
    $rname = 'IMG_' . random_int(11111, 99999) . '.jpeg';
    $path  = UPLOAD_IMAGE_PATH . USERS_FOLDER . $rname;

    $img = match ($ext) {
        'png'  => imagecreatefrompng($image['tmp_name']),
        'webp' => imagecreatefromwebp($image['tmp_name']),
        default => imagecreatefromjpeg($image['tmp_name']),
    };

    if (!$img) return 'inv_img';

    return imagejpeg($img, $path, 75) ? $rname : 'upd_failed';
}
