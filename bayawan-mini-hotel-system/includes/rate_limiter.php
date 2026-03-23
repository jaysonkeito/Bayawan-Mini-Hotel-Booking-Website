<?php
// bayawan-mini-hotel-system/includes/rate_limiter.php

define('RATE_MAX_ATTEMPTS', 5);
define('RATE_LOCKOUT_SECONDS', 15 * 60);


function session_rate_limit(string $action): array {
    if (session_status() === PHP_SESSION_NONE) session_start();

    $key = 'rl_' . $action;
    $now = time();

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['attempts' => 0, 'locked_until' => 0];
    }

    $data = &$_SESSION[$key];

    if ($data['locked_until'] > $now) {
        return ['allowed' => false, 'attempts_left' => 0, 'retry_after' => $data['locked_until'] - $now];
    }

    if ($data['locked_until'] > 0 && $data['locked_until'] <= $now) {
        $data['attempts']     = 0;
        $data['locked_until'] = 0;
    }

    $data['attempts']++;

    if ($data['attempts'] >= RATE_MAX_ATTEMPTS) {
        $data['locked_until'] = $now + RATE_LOCKOUT_SECONDS;
        return ['allowed' => false, 'attempts_left' => 0, 'retry_after' => RATE_LOCKOUT_SECONDS];
    }

    return ['allowed' => true, 'attempts_left' => RATE_MAX_ATTEMPTS - $data['attempts'], 'retry_after' => 0];
}

function session_rate_reset(string $action): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['rl_' . $action] = ['attempts' => 0, 'locked_until' => 0];
}

function format_retry_after(int $seconds): string {
    if ($seconds >= 60) {
        $mins = ceil($seconds / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '');
    }
    return $seconds . ' second' . ($seconds > 1 ? 's' : '');
}


// FIX: use only REMOTE_ADDR — never trust X-Forwarded-For from clients
function get_client_ip(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
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
        $stmt = mysqli_prepare($conn, "INSERT INTO `rate_limit`(`ip`,`action`,`attempts`,`last_attempt`) VALUES (?,?,1,?)");
        mysqli_stmt_bind_param($stmt, 'sss', $ip, $action, $now);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return ['allowed' => true, 'attempts_left' => RATE_MAX_ATTEMPTS - 1, 'retry_after' => 0];
    }

    if ($row['locked_until'] && strtotime($row['locked_until']) > time()) {
        return ['allowed' => false, 'attempts_left' => 0, 'retry_after' => strtotime($row['locked_until']) - time()];
    }

    if ($row['locked_until'] && strtotime($row['locked_until']) <= time()) {
        $stmt = mysqli_prepare($conn, "UPDATE `rate_limit` SET `attempts`=1, `locked_until`=NULL, `last_attempt`=? WHERE `ip`=? AND `action`=?");
        mysqli_stmt_bind_param($stmt, 'sss', $now, $ip, $action);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return ['allowed' => true, 'attempts_left' => RATE_MAX_ATTEMPTS - 1, 'retry_after' => 0];
    }

    $new_attempts = $row['attempts'] + 1;

    if ($new_attempts >= RATE_MAX_ATTEMPTS) {
        $stmt = mysqli_prepare($conn, "UPDATE `rate_limit` SET `attempts`=?, `locked_until`=?, `last_attempt`=? WHERE `ip`=? AND `action`=?");
        mysqli_stmt_bind_param($stmt, 'issss', $new_attempts, $lockout, $now, $ip, $action);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return ['allowed' => false, 'attempts_left' => 0, 'retry_after' => RATE_LOCKOUT_SECONDS];
    }

    $stmt = mysqli_prepare($conn, "UPDATE `rate_limit` SET `attempts`=?, `last_attempt`=? WHERE `ip`=? AND `action`=?");
    mysqli_stmt_bind_param($stmt, 'isss', $new_attempts, $now, $ip, $action);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return ['allowed' => true, 'attempts_left' => RATE_MAX_ATTEMPTS - $new_attempts, 'retry_after' => 0];
}

function db_rate_reset(mysqli $conn, string $action, string $ip): void {
    $stmt = mysqli_prepare($conn, "DELETE FROM `rate_limit` WHERE `ip`=? AND `action`=?");
    mysqli_stmt_bind_param($stmt, 'ss', $ip, $action);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}