<?php
// bayawan-mini-hotel-system/ajax/user_cart.php
// Handles all cart operations for multi-room booking.
//
// Cart structure in $_SESSION['cart']:
// [
//   'ROOMID_CHECKIN_CHECKOUT' => [
//     'cart_key'  => string,
//     'room_id'   => int,
//     'room_name' => string,
//     'price'     => float,
//     'check_in'  => 'Y-m-d',
//     'check_out' => 'Y-m-d',
//     'days'      => int,
//     'subtotal'  => float,
//     'thumb'     => string,   (filename only — not full URL)
//   ],
//   ...
// ]
//
// The compound key (room_id + dates) prevents the exact same
// room+dates combination from being added twice.

require('../admin/includes/admin_configuration.php');
require('../admin/includes/admin_essentials.php');

date_default_timezone_set("Asia/Manila");
session_start();

header('Content-Type: application/json');

if (!(isset($_SESSION['login']) && $_SESSION['login'] == true)) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
    exit;
}

// Initialise cart if it doesn't exist yet
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}


// ─────────────────────────────────────────────────────────────
//  ADD TO CART
//  Reads the room + availability data already validated and
//  stored in $_SESSION['room'] by ajax/user_confirm_booking.php.
//  Client calls this AFTER availability is confirmed (same
//  moment the "Pay Now" button becomes enabled).
// ─────────────────────────────────────────────────────────────
if (isset($_POST['add_to_cart'])) {

    // Availability must have been confirmed first
    if (
        empty($_SESSION['room']['id']) ||
        !isset($_SESSION['room']['available']) ||
        $_SESSION['room']['available'] !== true ||
        empty($_SESSION['room']['payment'])
    ) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Please select valid dates and confirm availability first.',
        ]);
        exit;
    }

    $frm_data  = filteration($_POST);
    $check_in  = $frm_data['check_in'];
    $check_out = $frm_data['check_out'];

    // Unique key for this room + date combination
    $cart_key = $_SESSION['room']['id'] . '_' . $check_in . '_' . $check_out;

    // Prevent duplicate entry
    if (isset($_SESSION['cart'][$cart_key])) {
        echo json_encode([
            'status'  => 'duplicate',
            'message' => 'This room with the same dates is already in your cart.',
            'count'   => count($_SESSION['cart']),
        ]);
        exit;
    }

    // Get thumbnail filename for cart display
    $thumb     = 'thumbnail.jpg';
    $thumb_q   = select(
        "SELECT `image` FROM `room_images` WHERE `room_id` = ? AND `thumb` = 1 LIMIT 1",
        [$_SESSION['room']['id']], 'i'
    );
    if ($thumb_row = mysqli_fetch_assoc($thumb_q)) {
        $thumb = $thumb_row['image'];
    }

    $checkin_dt  = new DateTime($check_in);
    $checkout_dt = new DateTime($check_out);
    $days        = (int) date_diff($checkin_dt, $checkout_dt)->days;
    $subtotal    = (float) $_SESSION['room']['price'] * $days;

    $_SESSION['cart'][$cart_key] = [
        'cart_key'  => $cart_key,
        'room_id'   => (int)   $_SESSION['room']['id'],
        'room_name' => (string)$_SESSION['room']['name'],
        'price'     => (float) $_SESSION['room']['price'],
        'check_in'  => $check_in,
        'check_out' => $check_out,
        'days'      => $days,
        'subtotal'  => $subtotal,
        'thumb'     => $thumb,
    ];

    echo json_encode([
        'status'  => 'success',
        'message' => htmlspecialchars($_SESSION['room']['name']) . ' added to cart!',
        'count'   => count($_SESSION['cart']),
    ]);
    exit;
}


// ─────────────────────────────────────────────────────────────
//  REMOVE FROM CART
// ─────────────────────────────────────────────────────────────
if (isset($_POST['remove_from_cart'])) {
    $frm_data = filteration($_POST);
    $cart_key = $frm_data['cart_key'] ?? '';

    if (isset($_SESSION['cart'][$cart_key])) {
        unset($_SESSION['cart'][$cart_key]);
        echo json_encode([
            'status' => 'success',
            'count'  => count($_SESSION['cart']),
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Item not found in cart.']);
    }
    exit;
}


// ─────────────────────────────────────────────────────────────
//  GET CART  — full cart data for user_cart.php
// ─────────────────────────────────────────────────────────────
if (isset($_POST['get_cart'])) {
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['subtotal'];
    }

    echo json_encode([
        'status' => 'success',
        'items'  => array_values($_SESSION['cart']),
        'total'  => $total,
        'count'  => count($_SESSION['cart']),
    ]);
    exit;
}


// ─────────────────────────────────────────────────────────────
//  GET COUNT  — lightweight ping for the header badge
// ─────────────────────────────────────────────────────────────
if (isset($_POST['get_count'])) {
    echo json_encode(['count' => count($_SESSION['cart'])]);
    exit;
}


// ─────────────────────────────────────────────────────────────
//  CLEAR CART
// ─────────────────────────────────────────────────────────────
if (isset($_POST['clear_cart'])) {
    $_SESSION['cart'] = [];
    echo json_encode(['status' => 'success', 'count' => 0]);
    exit;
}

// Fallback
echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);