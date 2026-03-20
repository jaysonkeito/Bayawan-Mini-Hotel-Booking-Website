<?php
// bayawan-mini-hotel-system/ajax/user_set_lang.php
// ─────────────────────────────────────────────────────────────────────
// Accepts:  POST { lang: 'en' | 'fil' }
// Saves the chosen language to $_SESSION['lang'].
// Returns:  'ok' on success, 'invalid' on bad input.
// ─────────────────────────────────────────────────────────────────────

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: text/plain');

$allowed = ['en', 'fil'];
$lang    = trim($_POST['lang'] ?? '');

if (in_array($lang, $allowed, true)) {
    $_SESSION['lang'] = $lang;
    echo 'ok';
} else {
    http_response_code(400);
    echo 'invalid';
}