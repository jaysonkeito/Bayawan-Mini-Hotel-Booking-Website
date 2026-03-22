<?php
//  bayawan-mini-hotel-system/user_pay_now.php

require('admin/includes/admin_configuration.php');
require('admin/includes/admin_essentials.php');
require('includes/paymongo/user_config_paymongo.php');
require('includes/paymongo/user_paymongo_helper.php');

date_default_timezone_set("Asia/Manila");
session_start();

if (!(isset($_SESSION['login']) && $_SESSION['login'] == true)) {
    redirect('user_index.php');
}


// ══════════════════════════════════════════════════════════════
//  SINGLE ROOM BOOKING  (existing flow — completely unchanged)
// ══════════════════════════════════════════════════════════════
if (isset($_POST['pay_now'])) {

    if (!$_SESSION['room']['available'] || empty($_SESSION['room']['payment'])) {
        redirect('user_rooms.php');
    }

    $ORDER_ID   = 'ORD_' . $_SESSION['uId'] . random_int(11111, 9999999);
    $CUST_ID    = $_SESSION['uId'];
    $TXN_AMOUNT = $_SESSION['room']['payment'];

    $frm_data = filteration($_POST);

    $query1 = "INSERT INTO `booking_order`(`user_id`, `room_id`, `check_in`, `check_out`, `order_id`)
               VALUES (?, ?, ?, ?, ?)";
    insert($query1, [
        $CUST_ID,
        $_SESSION['room']['id'],
        $frm_data['checkin'],
        $frm_data['checkout'],
        $ORDER_ID,
    ], 'issss');

    $booking_id = mysqli_insert_id($conn);

    $query2 = "INSERT INTO `booking_details`
               (`booking_id`, `room_name`, `price`, `total_pay`, `user_name`, `phonenum`, `address`)
               VALUES (?, ?, ?, ?, ?, ?, ?)";
    insert($query2, [
        $booking_id,
        $_SESSION['room']['name'],
        $_SESSION['room']['price'],
        $TXN_AMOUNT,
        $frm_data['name'],
        $frm_data['phonenum'],
        $frm_data['address'],
    ], 'issssss');

    $description = 'Hotel Booking - ' . $_SESSION['room']['name'];
    $result      = createPaymongoCheckout($ORDER_ID, $TXN_AMOUNT, $description);

    if (isset($result['checkout_url'])) {
        $_SESSION['paymongo_session_id'] = $result['session_id'];
        $_SESSION['current_order_id']    = $ORDER_ID;
        header('Location: ' . $result['checkout_url']);
        exit();
    } else {
        die('Payment initialization failed: ' . ($result['error'] ?? 'Unknown error'));
    }
}


// ══════════════════════════════════════════════════════════════
//  CART CHECKOUT  (new multi-room flow)
// ══════════════════════════════════════════════════════════════
if (isset($_POST['cart_checkout'])) {

    // Guard: cart must exist and have at least one item
    if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        redirect('user_cart.php');
    }

    $frm_data = filteration($_POST);
    $CUST_ID  = $_SESSION['uId'];

    // Guest info — same for all rooms in the cart
    $guest_name    = $frm_data['name'];
    $guest_phone   = $frm_data['phonenum'];
    $guest_address = $frm_data['address'];

    // Build a shared cart reference suffix so related orders are identifiable
    $cart_ref        = 'CART_' . $CUST_ID . '_' . random_int(11111, 9999999);
    $booking_ids     = [];   // booking_order IDs created below
    $order_ids       = [];   // individual ORDER_IDs (one per room)
    $paymongo_items  = [];   // line_items array for PayMongo

    foreach ($_SESSION['cart'] as $item) {
        $order_id = $cart_ref . '_' . $item['room_id'];

        // Insert booking_order row (status defaults to 'pending' in the DB)
        $q1 = "INSERT INTO `booking_order`
               (`user_id`, `room_id`, `check_in`, `check_out`, `order_id`)
               VALUES (?, ?, ?, ?, ?)";
        insert($q1, [
            $CUST_ID,
            $item['room_id'],
            $item['check_in'],
            $item['check_out'],
            $order_id,
        ], 'issss');

        $booking_id = mysqli_insert_id($conn);

        // Insert booking_details row
        $q2 = "INSERT INTO `booking_details`
               (`booking_id`, `room_name`, `price`, `total_pay`, `user_name`, `phonenum`, `address`)
               VALUES (?, ?, ?, ?, ?, ?, ?)";
        insert($q2, [
            $booking_id,
            $item['room_name'],
            $item['price'],
            $item['subtotal'],
            $guest_name,
            $guest_phone,
            $guest_address,
        ], 'issssss');

        $booking_ids[] = $booking_id;
        $order_ids[]   = $order_id;

        // Build PayMongo line item for this room
        $paymongo_items[] = [
            'name'      => $item['room_name'] . ' (' . $item['days'] . ' night' . ($item['days'] > 1 ? 's' : '') . ')',
            'amount'    => $item['subtotal'],
            'currency'  => 'PHP',
            'quantity'  => 1,
        ];
    }

    // Use cart_ref as the PayMongo reference number
    $result = createPaymongoCartCheckout($cart_ref, $paymongo_items);

    if (isset($result['checkout_url'])) {
        // Store all booking IDs + the cart ref in session for user_pay_response.php
        $_SESSION['paymongo_session_id']  = $result['session_id'];
        $_SESSION['cart_booking_ids']     = $booking_ids;
        $_SESSION['cart_order_ids']       = $order_ids;
        $_SESSION['cart_ref']             = $cart_ref;

        header('Location: ' . $result['checkout_url']);
        exit();
    } else {
        // PayMongo failed — mark all pending rows as 'payment failed' to keep DB clean
        if (!empty($booking_ids)) {
            foreach ($booking_ids as $bid) {
                update(
                    "UPDATE `booking_order` SET `booking_status` = 'payment failed',
                     `trans_status` = 'TXN_FAILURE',
                     `trans_resp_msg` = 'PayMongo checkout session could not be created.'
                     WHERE `booking_id` = ?",
                    [$bid], 'i'
                );
            }
        }
        die('Payment initialization failed: ' . ($result['error'] ?? 'Unknown error'));
    }
}