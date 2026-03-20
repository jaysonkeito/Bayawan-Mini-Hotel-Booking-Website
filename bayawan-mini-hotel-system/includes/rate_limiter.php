<?php
// bayawan-mini-hotel-system/includes/rate_limiter.php
//
// Provides two strategies:
//   1. SESSION-based  — for login, OTP, registration (per browser session)
//   2. DATABASE-based — for contact form (per IP, persists across sessions)
//
// Usage:
//   session_rate_limit('user_login');          // check + increment
//   session_rate_reset('user_login');           // reset on success
//   db_rate_limit($conn, 'contact_form', $ip); // check + increment
//   db_rate_reset($conn, 'contact_form', $ip); // reset on success

define('RATE_MAX_ATTEMPTS', 5);
define('RATE_LOCKOUT_SECONDS', 15 * 60); // 15 minutes


// ─────────────────────────────────────────────────────────────
//  SESSION-BASED RATE LIMITER
//  Used for: user login, admin login, OTP, registration
// ─────────────────────────────────────────────────────────────

/**
 * Check and increment session-based rate limit.
 *
 * @param string $action  e.g. 'user_login', 'admin_login', 'otp', 'register'
 * @return array [
 *   'allowed'        => bool,
 *   'attempts_left'  => int,
 *   'retry_after'    => int (seconds until lockout expires, 0 if not locked)
 * ]
 */
function session_rate_limit(string $action): array {
    if (session_status() === PHP_SESSION_NONE) session_start();

    $key      = 'rl_' . $action;
    $now      = time();

    // Initialise if missing
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['attempts' => 0, 'locked_until' => 0];
    }

    $data = &$_SESSION[$key];

    // Check if currently locked
    if ($data['locked_until'] > $now) {
        return [
            'allowed'       => false,
            'attempts_left' => 0,
            'retry_after'   => $data['locked_until'] - $now,
        ];
    }

    // If lockout just expired, reset
    if ($data['locked_until'] > 0 && $data['locked_until'] <= $now) {
        $data['attempts']     = 0;
        $data['locked_until'] = 0;
    }

    // Increment attempts
    $data['attempts']++;

    // Lock if over limit
    if ($data['attempts'] >= RATE_MAX_ATTEMPTS) {
        $data['locked_until'] = $now + RATE_LOCKOUT_SECONDS;
        return [
            'allowed'       => false,
            'attempts_left' => 0,
            'retry_after'   => RATE_LOCKOUT_SECONDS,
        ];
    }

    return [
        'allowed'       => true,
        'attempts_left' => RATE_MAX_ATTEMPTS - $data['attempts'],
        'retry_after'   => 0,
    ];
}

/**
 * Reset session-based rate limit on successful action.
 */
function session_rate_reset(string $action): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $key = 'rl_' . $action;
    $_SESSION[$key] = ['attempts' => 0, 'locked_until' => 0];
}

/**
 * Format retry_after seconds into a human-readable string.
 */
function format_retry_after(int $seconds): string {
    if ($seconds >= 60) {
        $mins = ceil($seconds / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '');
    }
    return $seconds . ' second' . ($seconds > 1 ? 's' : '');
}


// ─────────────────────────────────────────────────────────────
//  DATABASE-BASED RATE LIMITER
//  Used for: contact form (IP-based, persists across sessions)
// ─────────────────────────────────────────────────────────────

/**
 * Get client IP address.
 */
function get_client_ip(): string {
    $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

/**
 * Check and increment DB-based rate limit.
 *
 * @param mysqli $conn
 * @param string $action  e.g. 'contact_form'
 * @param string $ip
 * @return array [allowed, attempts_left, retry_after]
 */
function db_rate_limit(mysqli $conn, string $action, string $ip): array {
    $now     = date('Y-m-d H:i:s');
    $lockout = date('Y-m-d H:i:s', time() + RATE_LOCKOUT_SECONDS);

    // Fetch existing record
    $stmt = mysqli_prepare($conn, "SELECT * FROM `rate_limit` WHERE `ip`=? AND `action`=?");
    mysqli_stmt_bind_param($stmt, 'ss', $ip, $action);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$row) {
        // First attempt — insert
        $stmt = mysqli_prepare($conn,
            "INSERT INTO `rate_limit`(`ip`,`action`,`attempts`,`last_attempt`) VALUES (?,?,1,?)");
        mysqli_stmt_bind_param($stmt, 'sss', $ip, $action, $now);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return [
            'allowed'       => true,
            'attempts_left' => RATE_MAX_ATTEMPTS - 1,
            'retry_after'   => 0,
        ];
    }

    // Check if locked
    if ($row['locked_until'] && strtotime($row['locked_until']) > time()) {
        $retry = strtotime($row['locked_until']) - time();
        return [
            'allowed'       => false,
            'attempts_left' => 0,
            'retry_after'   => $retry,
        ];
    }

    // If lockout expired, reset
    if ($row['locked_until'] && strtotime($row['locked_until']) <= time()) {
        $stmt = mysqli_prepare($conn,
            "UPDATE `rate_limit` SET `attempts`=1, `locked_until`=NULL, `last_attempt`=? WHERE `ip`=? AND `action`=?");
        mysqli_stmt_bind_param($stmt, 'sss', $now, $ip, $action);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return [
            'allowed'       => true,
            'attempts_left' => RATE_MAX_ATTEMPTS - 1,
            'retry_after'   => 0,
        ];
    }

    // Increment attempts
    $new_attempts = $row['attempts'] + 1;

    if ($new_attempts >= RATE_MAX_ATTEMPTS) {
        // Lock
        $stmt = mysqli_prepare($conn,
            "UPDATE `rate_limit` SET `attempts`=?, `locked_until`=?, `last_attempt`=? WHERE `ip`=? AND `action`=?");
        mysqli_stmt_bind_param($stmt, 'issss', $new_attempts, $lockout, $now, $ip, $action);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return [
            'allowed'       => false,
            'attempts_left' => 0,
            'retry_after'   => RATE_LOCKOUT_SECONDS,
        ];
    }

    // Update count
    $stmt = mysqli_prepare($conn,
        "UPDATE `rate_limit` SET `attempts`=?, `last_attempt`=? WHERE `ip`=? AND `action`=?");
    mysqli_stmt_bind_param($stmt, 'isss', $new_attempts, $now, $ip, $action);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return [
        'allowed'       => true,
        'attempts_left' => RATE_MAX_ATTEMPTS - $new_attempts,
        'retry_after'   => 0,
    ];
}

/**
 * Reset DB-based rate limit on successful action.
 */
function db_rate_reset(mysqli $conn, string $action, string $ip): void {
    $stmt = mysqli_prepare($conn,
        "DELETE FROM `rate_limit` WHERE `ip`=? AND `action`=?");
    mysqli_stmt_bind_param($stmt, 'ss', $ip, $action);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}