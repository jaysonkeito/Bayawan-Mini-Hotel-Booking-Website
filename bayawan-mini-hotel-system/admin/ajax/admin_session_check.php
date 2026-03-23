<?php
// bayawan-mini-hotel-system/admin/ajax/admin_session_check.php
require('../includes/admin_configuration.php');
require('../includes/admin_essentials.php');
require_once '../../includes/csrf.php';
csrf_verify();
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

define('SESSION_TIMEOUT', 30 * 60); // 30 minutes in seconds
define('WARNING_BEFORE',   1 * 60); // warn 1 minute before timeout

// ── Extend session (admin clicked Stay Logged In) ─────────────────────
if (isset($_POST['extend_session'])) {
    if (isset($_SESSION['adminLogin']) && $_SESSION['adminLogin'] == true) {
        $_SESSION['last_activity'] = time();
        echo json_encode(['status' => 'extended']);
    } else {
        echo json_encode(['status' => 'logged_out']);
    }
    exit;
}

// ── Check session status ──────────────────────────────────────────────
if (isset($_POST['check_session'])) {
    if (!(isset($_SESSION['adminLogin']) && $_SESSION['adminLogin'] == true)) {
        echo json_encode(['status' => 'logged_out']);
        exit;
    }

    $last      = $_SESSION['last_activity'] ?? time();
    $elapsed   = time() - $last;
    $remaining = SESSION_TIMEOUT - $elapsed;

    if ($remaining <= 0) {
        session_destroy();
        echo json_encode(['status' => 'expired']);
        exit;
    }

    if ($remaining <= WARNING_BEFORE) {
        echo json_encode([
            'status'    => 'warning',
            'remaining' => $remaining,
        ]);
        exit;
    }

    echo json_encode([
        'status'    => 'active',
        'remaining' => $remaining,
    ]);
    exit;
}

echo json_encode(['status' => 'error']);