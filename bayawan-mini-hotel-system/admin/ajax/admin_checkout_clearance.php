<?php
// bayawan-mini-hotel-system/admin/ajax/admin_checkout_clearance.php
require('../includes/admin_essentials.php');
require('../includes/admin_configuration.php');
adminLogin();

header('Content-Type: text/plain; charset=utf-8');
date_default_timezone_set('Asia/Manila');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── CHECK PENDING FOOD ORDERS ─────────────────────────────────────────
if ($action === 'check_pending') {
    $booking_id = (int)($_GET['booking_id'] ?? 0);
    if (!$booking_id) exit('0');

    $row = mysqli_fetch_assoc(select(
        "SELECT COUNT(*) AS cnt FROM food_orders
         WHERE booking_id = ? AND status NOT IN ('paid','cancelled')",
        [$booking_id], 'i'
    ));
    echo (int) $row['cnt'];
    exit;
}

// ── MARK A FOOD ORDER AS PAID ─────────────────────────────────────────
if ($action === 'mark_food_paid') {
    $order_id   = (int)($_POST['order_id']   ?? 0);
    $booking_id = (int)($_POST['booking_id'] ?? 0);

    if (!$order_id || !$booking_id) exit('Invalid data.');

    // Verify the order belongs to this booking
    $check = mysqli_fetch_assoc(select(
        "SELECT id FROM food_orders WHERE id = ? AND booking_id = ?",
        [$order_id, $booking_id], 'ii'
    ));
    if (!$check) exit('Order not found.');

    update(
        "UPDATE food_orders SET status='paid', payment_method='cash' WHERE id=?",
        [$order_id], 'i'
    );
    exit('success');
}

// ── COMPLETE CHECKOUT ─────────────────────────────────────────────────
if ($action === 'complete_checkout') {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    if (!$booking_id) exit('Invalid booking.');

    // Safety check: block if still unpaid food orders
    $pending = mysqli_fetch_assoc(select(
        "SELECT COUNT(*) AS cnt FROM food_orders
         WHERE booking_id = ? AND status NOT IN ('paid','cancelled')",
        [$booking_id], 'i'
    ));
    if ((int)$pending['cnt'] > 0) {
        exit('Cannot checkout: there are still unpaid food orders.');
    }

    // Verify booking is actually checked_in
    $booking = mysqli_fetch_assoc(select(
        "SELECT bo.booking_id, bo.booking_status, bd.room_no, bo.check_out
         FROM booking_order bo
         INNER JOIN booking_details bd ON bo.booking_id = bd.booking_id
         WHERE bo.booking_id = ? AND bo.booking_status = 'checked_in'
         LIMIT 1",
        [$booking_id], 'i'
    ));
    if (!$booking) exit('Booking not found or already checked out.');

    // ── Set booking status to "checked_out" ──
    // (You can adapt this to whatever status your system uses post-check-out;
    //  here we use 'checked_out'. The room_status page uses check_out date to
    //  determine "cleaning", so this is consistent.)
    update(
        "UPDATE booking_order SET booking_status='checked_out' WHERE booking_id=?",
        [$booking_id], 'i'
    );

    exit('success');
}

exit('Unknown action.');