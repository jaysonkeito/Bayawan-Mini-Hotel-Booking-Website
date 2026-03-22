<?php
// bayawan-mini-hotel-system/includes/rate_limiter.php

// Provides two strategies:
//   1. SESSION-based  — for login, OTP, registration (per browser session)
//   2. DATABASE-based — for contact form (per IP, persists across sessions)

define('RATE_MAX_ATTEMPTS', 5);
define('RATE_LOCKOUT_SECONDS', 15 * 60); // 15 minutes

// ─────────────────────────────────────────────────────────────
//  TRUSTED PROXY CONFIGURATION
//
//  FIX (Warning): The original code checked HTTP_X_FORWARDED_FOR before
//  REMOTE_ADDR, which allows any client to spoof their IP by setting
//  that header — bypassing the IP-based rate limiter entirely.
//
//  SOLUTION: Only trust X-Forwarded-For if your server is actually behind
//  a known reverse proxy (nginx, Cloudflare, load balancer, etc.).
//
//  HOW TO USE:
//   - If you are NOT behind a proxy (shared hosting, direct Apache/Nginx):
//       Leave TRUSTED_PROXIES as an empty array. Only REMOTE_ADDR is used.
//   - If you ARE behind a proxy (e.g. Cloudflare, nginx reverse proxy):
//       Add the proxy's outbound IP(s) to the array below.
//       Example: define('TRUSTED_PROXIES', ['103.21.244.0', '172.16.0.1']);
//
//  Cloudflare IP ranges change occasionally — see:
//  https://www.cloudflare.com/ips/
// ─────────────────────────────────────────────────────────────
if (!defined('TRUSTED_PROXIES')) {
    define('TRUSTED_PROXIES', [
        // Add your reverse proxy IPs here if applicable.
        // Leave empty if running directly on Apache/Nginx without a proxy.
    ]);
}


// ─────────────────────────────────────────────────────────────
//  SESSION-BASED RATE LIMITER
// ─────────────────────────────────────────────────────────────

function session_rate_limit(string $action): array {
    if (session_status() === PHP_SESSION_NONE) session_start();

    $key = 'rl_' . $action;
    $now = time();

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['attempts' => 0, 'locked_until' => 0];
    }

    $data = &$_SESSION[$key];

    if ($data['locked_until'] > $now) {
        return [
            'allowed'       => false,
            'attempts_left' => 0,
            'retry_after'   => $data['locked_until'] - $now,
        ];
    }

    if ($data['locked_until'] > 0 && $data['locked_until'] <= $now) {
        $data['attempts']     = 0;
        $data['locked_until'] = 0;
    }

    $data['attempts']++;

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

function session_rate_reset(string $action): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $key = 'rl_' . $action;
    $_SESSION[$key] = ['attempts' => 0, 'locked_until' => 0];
}

function format_retry_after(int $seconds): string {
    if ($seconds >= 60) {
        $mins = ceil($seconds / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '');
    }
    return $seconds . ' second' . ($seconds > 1 ? 's' : '');
}


// ─────────────────────────────────────────────────────────────
//  DATABASE-BASED RATE LIMITER
// ─────────────────────────────────────────────────────────────

/**
 * Get the real client IP address.
 *
 * FIX (Warning): Only reads X-Forwarded-For when REMOTE_ADDR belongs to a
 * known trusted proxy. Otherwise falls back to REMOTE_ADDR directly.
 * This prevents clients from forging their IP by crafting the header.
 */
function get_client_ip(): string {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Only honour X-Forwarded-For when we are actually behind a trusted proxy
    $trusted = defined('TRUSTED_PROXIES') ? (array) TRUSTED_PROXIES : [];

    if (!empty($trusted) && in_array($remote, $trusted, true)) {
        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($forwarded !== '') {
            // The header may contain a chain: "client, proxy1, proxy2"
            // The leftmost IP is the original client.
            $ip = trim(explode(',', $forwarded)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    // Default: trust only the direct connection IP
    return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '0.0.0.0';
}

function db_rate_limit(mysqli $conn, string $action, string $ip): array {
    $now     = date('Y-m-d H:i:s');
    $lockout = date('Y-m-d H:i:s', time() + RATE_LOCKOUT_SECONDS);

    $stmt = mysqli_prepare($conn, "SELECT * FROM `rate_limit` WHERE `ip`=? AND `action`=?");
    mysqli_stmt_bind_param($stmt, 'ss', $ip, $action);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$row) {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO `rate_limit`(`ip`,`action`,`attempts`,`last_attempt`) VALUES (?,?,1,?)");
        mysqli_stmt_bind_param($stmt, 'sss', $ip, $action, $now);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return ['allowed' => true, 'attempts_left' => RATE_MAX_ATTEMPTS - 1, 'retry_after' => 0];
    }

    if ($row['locked_until'] && strtotime($row['locked_until']) > time()) {
        return [
            'allowed'       => false,
            'attempts_left' => 0,
            'retry_after'   => strtotime($row['locked_until']) - time(),
        ];
    }

    if ($row['locked_until'] && strtotime($row['locked_until']) <= time()) {
        $stmt = mysqli_prepare($conn,
            "UPDATE `rate_limit` SET `attempts`=1, `locked_until`=NULL, `last_attempt`=? WHERE `ip`=? AND `action`=?");
        mysqli_stmt_bind_param($stmt, 'sss', $now, $ip, $action);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return ['allowed' => true, 'attempts_left' => RATE_MAX_ATTEMPTS - 1, 'retry_after' => 0];
    }

    $new_attempts = $row['attempts'] + 1;

    if ($new_attempts >= RATE_MAX_ATTEMPTS) {
        $stmt = mysqli_prepare($conn,
            "UPDATE `rate_limit` SET `attempts`=?, `locked_until`=?, `last_attempt`=? WHERE `ip`=? AND `action`=?");
        mysqli_stmt_bind_param($stmt, 'issss', $new_attempts, $lockout, $now, $ip, $action);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        return ['allowed' => false, 'attempts_left' => 0, 'retry_after' => RATE_LOCKOUT_SECONDS];
    }

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

function db_rate_reset(mysqli $conn, string $action, string $ip): void {
    $stmt = mysqli_prepare($conn, "DELETE FROM `rate_limit` WHERE `ip`=? AND `action`=?");
    mysqli_stmt_bind_param($stmt, 'ss', $ip, $action);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
