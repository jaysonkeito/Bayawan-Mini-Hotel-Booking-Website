<?php
// bayawan-mini-hotel-system/ajax/user_food_order.php
// Receives a JSON POST payload, validates, inserts food_orders + food_order_items,
// decrements inventory, and sends confirmation email.

session_start();
require_once '../admin/includes/admin_configuration.php';
require_once '../admin/includes/admin_essentials.php';
require_once '../includes/user_email_helper.php';
require_once '../includes/csrf.php';

header('Content-Type: text/plain; charset=utf-8');
date_default_timezone_set('Asia/Manila');

// ── Auth guard ────────────────────────────────────────────────────────
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    exit('Please log in first.');
}

$user_id = (int) $_SESSION['uId'];

// ── Decode JSON payload ────────────────────────────────────────────────
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    exit('Invalid request.');
}

/* ── CSRF ──────────────────────────────────────────────────────────────
if (!isset($data['csrf_token']) || !csrf_check($data['csrf_token'])) {
    exit('Invalid CSRF token.');
}*/

csrf_verify();

$booking_id = (int)   ($data['booking_id'] ?? 0);
$room_no    = trim(   ($data['room_no']    ?? ''));
$notes      = trim(   ($data['notes']      ?? ''));
$cart       = (array) ($data['cart']       ?? []);

if (!$booking_id || empty($cart)) {
    exit('Missing required data.');
}

// ── Verify this booking belongs to this user and is checked-in ─────────
$bq = "SELECT bo.booking_id, bo.user_id, bo.booking_status, bo.arrival,
              bd.room_no, uc.email, uc.name AS user_name
       FROM booking_order bo
       INNER JOIN booking_details bd ON bo.booking_id = bd.booking_id
       INNER JOIN user_cred uc       ON bo.user_id    = uc.id
       WHERE bo.booking_id = ? AND bo.user_id = ? AND bo.booking_status = 'checked_in'
       LIMIT 1";
$brow = mysqli_fetch_assoc(select($bq, [$booking_id, $user_id], 'ii'));

if (!$brow) {
    exit('Invalid or inactive booking.');
}

// ── Validate each cart item & compute total ─────────────────────────────
$order_items = [];
$total       = 0.0;

foreach ($cart as $food_id => $item) {
    $food_id = (int) $food_id;
    $qty     = max(1, (int) ($item['qty'] ?? 1));

    // Verify item exists, is available, and has stock
    $fq = "SELECT fm.id, fm.name, fm.price, fi.stock_qty
           FROM food_menu fm
           LEFT JOIN food_inventory fi ON fm.id = fi.food_id
           WHERE fm.id = ? AND fm.is_available = 1 AND fm.removed = 0
           LIMIT 1";
    $frow = mysqli_fetch_assoc(select($fq, [$food_id], 'i'));

    if (!$frow) {
        exit("Item not found: food_id $food_id");
    }

    $stock = (int) ($frow['stock_qty'] ?? 0);
    if ($stock < $qty) {
        exit("Not enough stock for: " . htmlspecialchars($frow['name']));
    }

    $subtotal      = round((float)$frow['price'] * $qty, 2);
    $total        += $subtotal;
    $order_items[] = [
        'food_id'   => $food_id,
        'food_name' => $frow['name'],
        'unit_price'=> (float) $frow['price'],
        'qty'       => $qty,
        'subtotal'  => $subtotal,
    ];
}

// ── Insert food_orders ──────────────────────────────────────────────────
$ins_order = "INSERT INTO food_orders (booking_id, user_id, room_no, total_amount, status, notes)
              VALUES (?, ?, ?, ?, 'pending', ?)";
$inserted  = insert($ins_order, [$booking_id, $user_id, $room_no, $total, $notes], 'iisds');

if (!$inserted) {
    exit('Failed to create order. Please try again.');
}

// Get the new order id
$order_id = mysqli_insert_id($conn);

// ── Insert food_order_items & decrement inventory ───────────────────────
foreach ($order_items as $li) {
    $ins_item = "INSERT INTO food_order_items (order_id, food_id, food_name, unit_price, qty, subtotal)
                 VALUES (?, ?, ?, ?, ?, ?)";
    insert($ins_item, [
        $order_id,
        $li['food_id'],
        $li['food_name'],
        $li['unit_price'],
        $li['qty'],
        $li['subtotal'],
    ], 'iisdid');

    // Decrement stock
    update(
        "UPDATE food_inventory SET stock_qty = GREATEST(stock_qty - ?, 0) WHERE food_id = ?",
        [$li['qty'], $li['food_id']],
        'ii'
    );
}

// ── Send confirmation email to guest ──────────────────────────────────
sendFoodOrderConfirmationEmail([
    'email'        => $brow['email'],
    'user_name'    => $brow['user_name'],
    'order_id'     => $order_id,
    'room_no'      => $brow['room_no'],
    'items'        => $order_items,
    'total_amount' => $total,
]);

echo 'success';