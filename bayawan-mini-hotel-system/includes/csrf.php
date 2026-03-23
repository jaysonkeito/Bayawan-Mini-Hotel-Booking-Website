<?php
// bayawan-mini-hotel-system/includes/csrf.php

if (session_status() === PHP_SESSION_NONE) session_start();

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_token_field(): string {
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return "<input type='hidden' name='csrf_token' id='csrf_token' value='{$token}'>";
}

function csrf_verify(): void {
    $expected  = csrf_token();
    $submitted = $_POST['csrf_token']
              ?? $_GET['csrf_token']
              ?? $_SERVER['HTTP_X_CSRF_TOKEN']
              ?? '';

    if (!hash_equals($expected, $submitted)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        exit('CSRF validation failed.');
    }
}