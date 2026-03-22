<?php
// bayawan-mini-hotel-system/cron/cleanup_stale_bookings.php
//
// ============================================================
//  STALE BOOKING CLEANUP — PHP CRON SCRIPT
//  (Feature: pending booking pruning)
// ============================================================
//
//  PURPOSE:
//    When a user starts a PayMongo checkout session but closes
//    the browser, the booking_order row stays 'pending' forever,
//    blocking room availability. This script marks those rows as
//    'expired' so the room is freed up automatically.
//
//  TWO WAYS TO RUN THIS:
//
//  Option A — MySQL Event Scheduler (preferred):
//    Run security_fixes_v2.sql which creates the event.
//    No cron needed. Runs every 30 minutes inside MySQL itself.
//
//  Option B — PHP Cron Job (shared hosting / MySQL Events disabled):
//    Add this to your server's crontab:
//    */30 * * * * php /path/to/bayawan-mini-hotel-system/cron/cleanup_stale_bookings.php
//
//    Or in cPanel → Cron Jobs, set to every 30 minutes and point to this file.
//
//  CONFIGURATION:
//    STALE_AFTER_MINUTES — how long a 'pending' booking is tolerated.
//    60 minutes matches the PayMongo checkout session timeout.
//
// ============================================================

define('STALE_AFTER_MINUTES', 60);

// ── Bootstrap ────────────────────────────────────────────────
// Allow CLI execution OR a locked-down HTTP call (with a secret key).
$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    // If called over HTTP, require a secret key set in .env for safety.
    // Add CRON_SECRET=your_random_secret to your .env file.
    require_once __DIR__ . '/../config/env.php';
    $expected = env('CRON_SECRET', '');
    $provided = $_GET['secret'] ?? '';

    if (empty($expected) || !hash_equals($expected, $provided)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

// Load DB connection
$base = $is_cli
    ? __DIR__ . '/../admin/includes/'
    : __DIR__ . '/../admin/includes/';

require_once $base . 'admin_configuration.php';

// ── Run cleanup ───────────────────────────────────────────────
$cutoff = date('Y-m-d H:i:s', strtotime('-' . STALE_AFTER_MINUTES . ' minutes'));

$sql = "UPDATE `booking_order`
        SET
            `booking_status` = 'expired',
            `trans_status`   = 'TXN_EXPIRED',
            `trans_resp_msg` = 'Booking expired: payment not completed within " . STALE_AFTER_MINUTES . " minutes.'
        WHERE
            `booking_status` = 'pending'
            AND `datentime`  < ?";

$affected = update($sql, [$cutoff], 's');

// ── Log result ───────────────────────────────────────────────
$log_dir  = __DIR__ . '/../logs/';
$log_file = $log_dir . 'cron_cleanup.log';

if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

$timestamp = date('Y-m-d H:i:s');
$log_line  = "[{$timestamp}] Stale booking cleanup: {$affected} row(s) expired.\n";

file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);

if ($is_cli) {
    echo $log_line;
} else {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'expired' => $affected, 'cutoff' => $cutoff]);
}
