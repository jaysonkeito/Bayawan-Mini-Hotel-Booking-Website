<?php
/* bayawan-mini-hotel-system/includes/csrf.php
//
// Minimal, stateless CSRF protection for all AJAX endpoints.
//
// USAGE — in every AJAX handler file, add near the top (after session_start):
//
//   require_once '../includes/csrf.php';
//   csrf_verify();   // kills the request with 403 if token is missing/wrong
//
// USAGE — in every HTML form/page that fires AJAX, inject the token:
//
//   <?= csrf_token_field() ?>     // renders a hidden <input>
//   OR
//   data.append('csrf_token', '<?= csrf_token() ?>');   // in JS FormData
//
// The token is generated once per session (stored in $_SESSION['csrf_token'])
// and reused for all requests in that session. It is compared with
// hash_equals() to prevent timing attacks.*/

if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Return the current session CSRF token, generating one if needed.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden HTML input carrying the CSRF token.
 * Drop this inside any <form> or read it from JS via getElementById.
 *
 *   <?= csrf_token_field() ?>
 */
function csrf_token_field(): string {
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return "<input type='hidden' name='csrf_token' id='csrf_token' value='{$token}'>";
}

/**
 * Verify the CSRF token submitted with the current request.
 * Terminates with HTTP 403 if the token is absent or incorrect.
 *
 * Checks both POST body and the X-CSRF-Token request header
 * (so both regular FormData POSTs and fetch() with a custom header work).
 */
function csrf_verify(): void {
    $expected  = csrf_token();
    $submitted = $_POST['csrf_token']
              ?? $_SERVER['HTTP_X_CSRF_TOKEN']
              ?? '';

    if (!hash_equals($expected, $submitted)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        exit('CSRF validation failed.');
    }
}
