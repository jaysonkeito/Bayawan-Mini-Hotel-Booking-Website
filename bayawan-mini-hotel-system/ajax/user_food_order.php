<?php
// bayawan-mini-hotel-system/ajax/user_food_order.php
require('admin/includes/admin_configuration.php');
require('admin/includes/admin_essentials.php');
require_once 'includes/csrf.php';

header('Content-Type: text/plain; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

// ── Auth check ────────────────────────────────────────────────────────
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    http_response_code(401);
    exit('Not logged in.');
}

$user_id = (int) $_SESSION['uId'];

// ── Parse JSON body ───────────────────────────────────────────────────
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    http_response_code(400);
    exit('Invalid request data.');
}

// ── CSRF verification ─────────────────────────────────────────────────
$submitted_token = $data['csrf_token'] ?? '';
if (!hash_equals(csrf_token(), $submitted_token)) {
    http_response_code(403);
    exit('CSRF validation failed.');
}

// ── Validate required fields ──────────────────────────────────────────
$booking_id = intval($data['booking_id'] ?? 0);
$room_no    = trim($data['room_no']    ?? '');
$notes      = trim($data['notes']      ?? '');
$cart       = $data['cart']            ?? [];

if (!$booking_id || !$room_no || empty($cart)) {
    http_response_code(400);
    exit('Missing required order data.');
}

// ── Verify the booking belongs to this user and is currently checked in ─
$booking_r = mysqli_fetch_assoc(select(
    "SELECT booking_id FROM booking_order
     WHERE booking_id = ? AND user_id = ?
       AND booking_status = 'checked_in' AND arrival = 1",
    [$booking_id, $user_id],
    'ii'
));

if (!$booking_r) {
    http_response_code(403);
    exit('No active checked-in booking found.');
}

// ── Validate cart items and calculate total ───────────────────────────
$total       = 0.0;
$order_items = []; // will hold validated rows

foreach ($cart as $food_id => $item) {
    $food_id = (int) $food_id;
    $qty     = (int) ($item['qty'] ?? 0);

    if ($food_id <= 0 || $qty <= 0) continue;

    // Fetch live price and stock from DB — never trust client-side price
    $food_r = mysqli_fetch_assoc(select(
        "SELECT fm.id, fm.name, fm.price, COALESCE(fi.stock_qty,0) AS stock_qty
         FROM food_menu fm
         LEFT JOIN food_inventory fi ON fm.id = fi.food_id
         WHERE fm.id = ? AND fm.is_available = 1 AND fm.removed = 0",
        [$food_id],
        'i'
    ));

    if (!$food_r) continue; // skip removed/unavailable items silently

    if ((int)$food_r['stock_qty'] < $qty) {
        exit("Insufficient stock for: " . htmlspecialchars($food_r['name']));
    }

    $unit_price = (float) $food_r['price'];
    $subtotal   = $unit_price * $qty;
    $total     += $subtotal;

    $order_items[] = [
        'food_id'    => $food_id,
        'food_name'  => $food_r['name'],
        'unit_price' => $unit_price,
        'qty'        => $qty,
        'subtotal'   => $subtotal,
    ];
}

if (empty($order_items)) {
    exit('No valid items in cart.');
}

// ── Insert food_order ─────────────────────────────────────────────────
$ins = insert(
    "INSERT INTO food_orders (booking_id, user_id, room_no, total_amount, status, notes, ordered_at)
     VALUES (?, ?, ?, ?, 'pending', ?, NOW())",
    [$booking_id, $user_id, $room_no, $total, $notes],
    'iisds'
);

if (!$ins) exit('Failed to place order. Please try again.');

$order_id = mysqli_insert_id($conn);

// ── Insert food_order_items ───────────────────────────────────────────
foreach ($order_items as $li) {
    insert(
        "INSERT INTO food_order_items (order_id, food_name, unit_price, qty, subtotal)
         VALUES (?, ?, ?, ?, ?)",
        [$order_id, $li['food_name'], $li['unit_price'], $li['qty'], $li['subtotal']],
        'isdid'
    );

    // Decrement stock
    mysqli_query($conn,
        "UPDATE food_inventory
         SET stock_qty = GREATEST(stock_qty - {$li['qty']}, 0)
         WHERE food_id = {$li['food_id']}"
    );
}

exit('success');