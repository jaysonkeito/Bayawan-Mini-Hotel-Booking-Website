<?php
// bayawan-mini-hotel-system/admin/includes/admin_essentials.php

require_once __DIR__ . '/../../config/env.php';


// ── Auth helpers ─────────────────────────────────────────────────────

function adminLogin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!(isset($_SESSION['adminLogin']) && $_SESSION['adminLogin'] == true)) {
        echo "<script>window.location.href='admin_index.php';</script>";
        exit;
    }
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

/**
 * Upload a raster image (JPEG/PNG/WebP) for rooms, carousel, facilities, etc.
 *
 * FIX (Bug): Uses finfo_file() to inspect actual file magic bytes instead of
 * trusting $_FILES['type'], which is supplied by the browser and can be spoofed.
 */
function uploadImage(array $image, string $folder): string {
    // Check actual file content, not the browser-supplied MIME type
    $finfo       = new finfo(FILEINFO_MIME_TYPE);
    $actual_mime = $finfo->file($image['tmp_name']);
    $valid_mime  = ['image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($actual_mime, $valid_mime)) return 'inv_img';
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

/**
 * Upload an SVG image (for facility icons).
 *
 * FIX (Warning): After MIME and size checks, the SVG content is sanitized
 * by stripping <script> blocks and inline event handlers (onclick, onload, etc.)
 * before saving — preventing stored XSS via malicious SVG payloads.
 */
function uploadSVGImage(array $image, string $folder): string {
    // Verify actual MIME — do not trust browser-supplied type
    $finfo       = new finfo(FILEINFO_MIME_TYPE);
    $actual_mime = $finfo->file($image['tmp_name']);

    if ($actual_mime !== 'image/svg+xml')      return 'inv_img';
    if (($image['size'] / (1024 * 1024)) > 1)  return 'inv_size';

    // Read SVG content and sanitize before saving
    $svg_content = file_get_contents($image['tmp_name']);
    if ($svg_content === false) return 'upd_failed';

    // Strip <script>...</script> blocks (case-insensitive, handles multi-line)
    $svg_content = preg_replace('/<script[\s\S]*?<\/script>/i', '', $svg_content);

    // Neutralize inline event handlers (onclick, onload, onerror, etc.)
    $svg_content = preg_replace('/\bon\w+\s*=/i', 'data-removed=', $svg_content);

    // Remove javascript: URI schemes in href/xlink:href attributes
    $svg_content = preg_replace('/\bhref\s*=\s*["\']?\s*javascript:/i', 'href="', $svg_content);

    $ext   = pathinfo($image['name'], PATHINFO_EXTENSION);
    $rname = 'IMG_' . random_int(11111, 99999) . '.' . $ext;
    $path  = UPLOAD_IMAGE_PATH . $folder . $rname;

    return (file_put_contents($path, $svg_content) !== false) ? $rname : 'upd_failed';
}

/**
 * Upload and re-encode a user profile picture.
 *
 * FIX (Bug): Uses finfo_file() instead of $_FILES['type'] for MIME detection,
 * then re-encodes via GD — so even a disguised file gets converted to a
 * clean JPEG, eliminating any embedded PHP/HTML in the original file.
 */
function uploadUserImage(array $image): string {
    // Check actual file content
    $finfo       = new finfo(FILEINFO_MIME_TYPE);
    $actual_mime = $finfo->file($image['tmp_name']);
    $valid_mime  = ['image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($actual_mime, $valid_mime)) return 'inv_img';

    $ext   = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
    $rname = 'IMG_' . random_int(11111, 99999) . '.jpeg';
    $path  = UPLOAD_IMAGE_PATH . USERS_FOLDER . $rname;

    // GD re-encoding strips any non-image content from the file
    $img = match ($actual_mime) {
        'image/png'  => imagecreatefrompng($image['tmp_name']),
        'image/webp' => imagecreatefromwebp($image['tmp_name']),
        default      => imagecreatefromjpeg($image['tmp_name']),
    };

    if (!$img) return 'inv_img';

    return imagejpeg($img, $path, 75) ? $rname : 'upd_failed';
}
