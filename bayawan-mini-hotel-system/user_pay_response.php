<?php
// bayawan-mini-hotel-system/user_pay_response.php

require('admin/includes/admin_configuration.php');
require('admin/includes/admin_essentials.php');
require('includes/paymongo/user_config_paymongo.php');
require('includes/paymongo/user_paymongo_helper.php');
require('includes/user_email_helper.php');

date_default_timezone_set("Asia/Manila");
session_start();

// Clear single-room session flag (applies to both flows)
unset($_SESSION['room']);

function regenrate_session($uid) {
    $user_q    = select("SELECT * FROM `user_cred` WHERE `id` = ? LIMIT 1", [$uid], 'i');
    $user_fetch = mysqli_fetch_assoc($user_q);
    $_SESSION['login']     = true;
    $_SESSION['uId']       = $user_fetch['id'];
    $_SESSION['user_name'] = $user_fetch['name'];
    $_SESSION['user_pic']  = $user_fetch['profile'];
    $_SESSION['uPhone']    = $user_fetch['phonenum'];
}

header("Pragma: no-cache");
header("Cache-Control: no-cache");
header("Expires: 0");

$status   = $_GET['status']   ?? '';
$order_id = $_GET['order_id'] ?? '';

if (empty($status)) {
    redirect('user_index.php');
}

// ── Determine flow: cart or single ──────────────────────────────────
$is_cart = !empty($_SESSION['cart_booking_ids']) && is_array($_SESSION['cart_booking_ids']);


// ══════════════════════════════════════════════════════════════
//  CART FLOW
// ══════════════════════════════════════════════════════════════
if ($is_cart) {

    $booking_ids = $_SESSION['cart_booking_ids'];
    $cart_ref    = $_SESSION['cart_ref'] ?? '';

    // Restore session if opened in a new tab
    if (!(isset($_SESSION['login']) && $_SESSION['login'] == true)) {
        // Find user_id from the first booking in the cart
        $first_q = select(
            "SELECT `user_id` FROM `booking_order` WHERE `booking_id` = ? LIMIT 1",
            [$booking_ids[0]], 'i'
        );
        if ($first_row = mysqli_fetch_assoc($first_q)) {
            regenrate_session($first_row['user_id']);
        }
    }

    // Verify payment with PayMongo
    $session_id      = $_SESSION['paymongo_session_id'] ?? '';
    $verified_status = 'failed';
    $txn_id          = '';
    $txn_amount      = 0;
    $resp_msg        = 'Payment failed or was cancelled.';

    if (!empty($session_id)) {
        $checkout = getPaymongoCheckoutSession($session_id);
        $attrs    = $checkout['data']['attributes'] ?? [];

        if (($attrs['payment_intent']['attributes']['status'] ?? '') === 'succeeded') {
            $verified_status = 'success';
            $resp_msg        = 'Payment successful.';
            $payments        = $attrs['payment_intent']['attributes']['payments'] ?? [];
            if (!empty($payments)) {
                $txn_id     = $payments[0]['id'] ?? '';
                $txn_amount = ($payments[0]['attributes']['amount'] ?? 0) / 100;
            }
        }
    }

    if ($status === 'failed' && $verified_status !== 'success') {
        $verified_status = 'failed';
        $resp_msg        = 'Payment failed or was cancelled.';
    }

    // Update ALL booking rows in the cart
    foreach ($booking_ids as $bid) {
        if ($verified_status === 'success') {
            update(
                "UPDATE `booking_order` SET
                 `booking_status` = 'booked',
                 `trans_id`       = ?,
                 `trans_amt`      = ?,
                 `trans_status`   = 'TXN_SUCCESS',
                 `trans_resp_msg` = ?
                 WHERE `booking_id` = ?",
                [$txn_id, $txn_amount, $resp_msg, $bid], 'sssi'
            );
        } else {
            update(
                "UPDATE `booking_order` SET
                 `booking_status` = 'payment failed',
                 `trans_id`       = ?,
                 `trans_amt`      = '0',
                 `trans_status`   = 'TXN_FAILURE',
                 `trans_resp_msg` = ?
                 WHERE `booking_id` = ?",
                [$txn_id, $resp_msg, $bid], 'ssi'
            );
        }
    }

    // Send confirmation email for each booking on success
    if ($verified_status === 'success') {
        foreach ($booking_ids as $bid) {
            $email_q = "SELECT bo.*, bd.*, uc.email, uc.name AS user_name
                        FROM `booking_order` bo
                        INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
                        INNER JOIN `user_cred` uc ON bo.user_id = uc.id
                        WHERE bo.booking_id = ?";
            $email_data = mysqli_fetch_assoc(select($email_q, [$bid], 'i'));
            if ($email_data) {
                sendBookingConfirmationEmail($email_data);
            }
        }

        // Clear the cart on successful payment
        $_SESSION['cart'] = [];
    }

    // Clear cart session vars
    unset(
        $_SESSION['paymongo_session_id'],
        $_SESSION['cart_booking_ids'],
        $_SESSION['cart_order_ids'],
        $_SESSION['cart_ref']
    );

    // Redirect to pay_status using the first booking's order_id for the status page
    $first_order_q = select(
        "SELECT `order_id` FROM `booking_order` WHERE `booking_id` = ? LIMIT 1",
        [$booking_ids[0]], 'i'
    );
    $first_order = mysqli_fetch_assoc($first_order_q);
    redirect('user_pay_status.php?order=' . ($first_order['order_id'] ?? '') . '&cart=1');
}


// ══════════════════════════════════════════════════════════════
//  SINGLE ROOM FLOW  (original logic — unchanged)
// ══════════════════════════════════════════════════════════════
if (empty($order_id)) {
    redirect('user_index.php');
}

$slct_query = "SELECT `booking_id`, `user_id` FROM `booking_order` WHERE `order_id` = ?";
$slct_res   = select($slct_query, [$order_id], 's');

if (mysqli_num_rows($slct_res) == 0) {
    redirect('user_index.php');
}

$slct_fetch = mysqli_fetch_assoc($slct_res);

if (!(isset($_SESSION['login']) && $_SESSION['login'] == true)) {
    regenrate_session($slct_fetch['user_id']);
}

$session_id      = $_SESSION['paymongo_session_id'] ?? '';
$verified_status = 'failed';
$txn_id          = '';
$txn_amount      = '';
$resp_msg        = 'Payment failed or was cancelled.';

if (!empty($session_id)) {
    $checkout = getPaymongoCheckoutSession($session_id);
    $attrs    = $checkout['data']['attributes'] ?? [];

    if (($attrs['payment_intent']['attributes']['status'] ?? '') === 'succeeded') {
        $verified_status = 'success';
        $resp_msg        = 'Payment successful.';
        $payments        = $attrs['payment_intent']['attributes']['payments'] ?? [];
        if (!empty($payments)) {
            $txn_id     = $payments[0]['id'] ?? '';
            $txn_amount = ($payments[0]['attributes']['amount'] ?? 0) / 100;
        }
    }
}

if ($status === 'failed' && $verified_status !== 'success') {
    $verified_status = 'failed';
    $resp_msg        = 'Payment failed or was cancelled.';
}

if ($verified_status === 'success') {
    update(
        "UPDATE `booking_order` SET
         `booking_status` = 'booked',
         `trans_id`       = ?,
         `trans_amt`      = ?,
         `trans_status`   = 'TXN_SUCCESS',
         `trans_resp_msg` = ?
         WHERE `booking_id` = ?",
        [$txn_id, $txn_amount, $resp_msg, $slct_fetch['booking_id']], 'sssi'
    );

    $email_q    = "SELECT bo.*, bd.*, uc.email, uc.name AS user_name
                   FROM `booking_order` bo
                   INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
                   INNER JOIN `user_cred` uc ON bo.user_id = uc.id
                   WHERE bo.booking_id = ?";
    $email_data = mysqli_fetch_assoc(select($email_q, [$slct_fetch['booking_id']], 'i'));
    if ($email_data) {
        sendBookingConfirmationEmail($email_data);
    }
} else {
    update(
        "UPDATE `booking_order` SET
         `booking_status` = 'payment failed',
         `trans_id`       = ?,
         `trans_amt`      = '0',
         `trans_status`   = 'TXN_FAILURE',
         `trans_resp_msg` = ?
         WHERE `booking_id` = ?",
        [$txn_id, $resp_msg, $slct_fetch['booking_id']], 'ssi'
    );
}

unset($_SESSION['paymongo_session_id'], $_SESSION['current_order_id']);

redirect('user_pay_status.php?order=' . $order_id);