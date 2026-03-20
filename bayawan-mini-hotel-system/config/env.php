<?php
// config/env.php
//
// Central environment loader for Bayawan Mini Hotel System.
// Reads .env from the project root, defines all constants once.
// Every config file requires only this file — nothing else hardcodes a URL or secret.

if (defined('ENV_LOADED')) return;
define('ENV_LOADED', true);

// ── 1. Parse .env ────────────────────────────────────────────────────

$env_file = dirname(__DIR__) . '/.env';

if (!file_exists($env_file)) {
    http_response_code(500);
    die(
        '<h3 style="font-family:sans-serif;color:#c0392b;">Configuration Error</h3>' .
        '<p style="font-family:sans-serif;">The <code>.env</code> file was not found at: ' .
        '<code>' . htmlspecialchars($env_file) . '</code></p>' .
        '<p style="font-family:sans-serif;">Copy <code>.env.example</code> to <code>.env</code> and fill in your values.</p>'
    );
}

foreach (file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) continue;

    $eq = strpos($line, '=');
    if ($eq === false) continue;

    $key   = trim(substr($line, 0, $eq));
    $value = trim(substr($line, $eq + 1));

    // Strip optional surrounding quotes
    if (strlen($value) >= 2) {
        $first = $value[0]; $last = $value[-1];
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            $value = substr($value, 1, -1);
        }
    }

    if (!array_key_exists($key, $_ENV)) {
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

// ── 2. Helper ────────────────────────────────────────────────────────

function env(string $key, string $fallback = ''): string {
    $v = $_ENV[$key] ?? getenv($key);
    return ($v !== false && $v !== '') ? $v : $fallback;
}

// ── 3. Auto-detect APP_URL if not set ────────────────────────────────
// Builds base URL from the HTTP request so zero config is needed on
// a new host. The .env value always overrides this.

if (empty(env('APP_URL'))) {
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $folder  = env('APP_FOLDER', 'bayawan-mini-hotel-system');
    $script  = $_SERVER['SCRIPT_NAME'] ?? '';

    // Walk up the script path until we find the app folder
    $path = dirname($script);
    while ($path && $path !== '/' && $path !== '\\') {
        if (strtolower(basename($path)) === strtolower($folder)) {
            break;
        }
        $path = dirname($path);
    }
    $path = rtrim($path, '/\\');

    $_ENV['APP_URL'] = $scheme . '://' . $host . $path;
    putenv('APP_URL=' . $_ENV['APP_URL']);
}

// ── 4. Define all constants ───────────────────────────────────────────

// Application
if (!defined('APP_URL'))    define('APP_URL',    rtrim(env('APP_URL'), '/'));
if (!defined('APP_NAME'))   define('APP_NAME',   env('APP_NAME',   'Bayawan Mini Hotel'));
if (!defined('APP_ENV'))    define('APP_ENV',    env('APP_ENV',    'production'));
if (!defined('APP_FOLDER')) define('APP_FOLDER', env('APP_FOLDER', 'bayawan-mini-hotel-system'));

// Image display URLs (used in <img src="...">)
if (!defined('SITE_URL'))            define('SITE_URL',            APP_URL . '/');
if (!defined('ABOUT_IMG_PATH'))      define('ABOUT_IMG_PATH',      APP_URL . '/images/about/');
if (!defined('CAROUSEL_IMG_PATH'))   define('CAROUSEL_IMG_PATH',   APP_URL . '/images/carousel/');
if (!defined('FACILITIES_IMG_PATH')) define('FACILITIES_IMG_PATH', APP_URL . '/images/facilities/');
if (!defined('ROOMS_IMG_PATH'))      define('ROOMS_IMG_PATH',      APP_URL . '/images/rooms/');
if (!defined('USERS_IMG_PATH'))      define('USERS_IMG_PATH',      APP_URL . '/images/users/');

// Filesystem upload paths (used in move_uploaded_file / unlink)
// Derived from DOCUMENT_ROOT so they work on any OS path, regardless of port.
$_img_base = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\')
           . '/' . trim(APP_FOLDER, '/') . '/images/';
if (!defined('UPLOAD_IMAGE_PATH')) define('UPLOAD_IMAGE_PATH', $_img_base);
if (!defined('ABOUT_FOLDER'))      define('ABOUT_FOLDER',      'about/');
if (!defined('CAROUSEL_FOLDER'))   define('CAROUSEL_FOLDER',   'carousel/');
if (!defined('FACILITIES_FOLDER')) define('FACILITIES_FOLDER', 'facilities/');
if (!defined('ROOMS_FOLDER'))      define('ROOMS_FOLDER',      'rooms/');
if (!defined('USERS_FOLDER'))      define('USERS_FOLDER',      'users/');

// Database
if (!defined('DB_HOST')) define('DB_HOST', env('DB_HOST', 'localhost'));
if (!defined('DB_NAME')) define('DB_NAME', env('DB_NAME', ''));
if (!defined('DB_USER')) define('DB_USER', env('DB_USER', 'root'));
if (!defined('DB_PASS')) define('DB_PASS', env('DB_PASS', ''));

// SMTP / PHPMailer
if (!defined('SMTP_HOST'))      define('SMTP_HOST',      env('SMTP_HOST',      'smtp.gmail.com'));
if (!defined('SMTP_USER'))      define('SMTP_USER',      env('SMTP_USER',      ''));
if (!defined('SMTP_PASS'))      define('SMTP_PASS',      env('SMTP_PASS',      ''));
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', env('SMTP_FROM_NAME', APP_NAME));

// PayMongo
// Callback URLs are built from APP_URL — no editing needed when deploying
if (!defined('PAYMONGO_ENVIRONMENT')) define('PAYMONGO_ENVIRONMENT', env('PAYMONGO_ENV',        'TEST'));
if (!defined('PAYMONGO_SECRET_KEY'))  define('PAYMONGO_SECRET_KEY',  env('PAYMONGO_SECRET_KEY', ''));
if (!defined('PAYMONGO_PUBLIC_KEY'))  define('PAYMONGO_PUBLIC_KEY',  env('PAYMONGO_PUBLIC_KEY', ''));
if (!defined('PAYMONGO_API_URL'))     define('PAYMONGO_API_URL',     'https://api.paymongo.com/v1');
if (!defined('PAYMONGO_SUCCESS_URL')) define('PAYMONGO_SUCCESS_URL', APP_URL . '/user_pay_response.php?status=success');
if (!defined('PAYMONGO_FAILED_URL'))  define('PAYMONGO_FAILED_URL',  APP_URL . '/user_pay_response.php?status=failed');

// Google OAuth
// Redirect URI is built from APP_URL — no editing needed when deploying
if (!defined('GOOGLE_CLIENT_ID'))     define('GOOGLE_CLIENT_ID',     env('GOOGLE_CLIENT_ID',     ''));
if (!defined('GOOGLE_CLIENT_SECRET')) define('GOOGLE_CLIENT_SECRET', env('GOOGLE_CLIENT_SECRET', ''));
if (!defined('GOOGLE_REDIRECT_URI'))  define('GOOGLE_REDIRECT_URI',  APP_URL . '/ajax/user_google_callback.php');