<?php
// bayawan-mini-hotel-system/ajax/user_confirm_booking.php

require('../admin/includes/admin_configuration.php');
require('../admin/includes/admin_essentials.php');

date_default_timezone_set("Asia/Manila");

// FIX (Bug): session_start() must be called unconditionally at the top,
// not buried inside a conditional block — otherwise sessions are
// unavailable for any other action added to this file in the future.
session_start();

if (isset($_POST['check_availability'])) {
    $frm_data = filteration($_POST);
    $status   = "";
    $result   = "";

    $today_date    = new DateTime(date("Y-m-d"));
    $checkin_date  = new DateTime($frm_data['check_in']);
    $checkout_date = new DateTime($frm_data['check_out']);

    if ($checkin_date == $checkout_date) {
        $status = 'check_in_out_equal';
        $result = json_encode(["status" => $status]);
    } elseif ($checkout_date < $checkin_date) {
        $status = 'check_out_earlier';
        $result = json_encode(["status" => $status]);
    } elseif ($checkin_date < $today_date) {
        $status = 'check_in_earlier';
        $result = json_encode(["status" => $status]);
    }

    if ($status != '') {
        echo $result;
    } else {
        $tb_query = "SELECT COUNT(*) AS `total_bookings` FROM `booking_order`
            WHERE booking_status=? AND room_id=?
            AND check_out > ? AND check_in < ?";

        $values   = ['booked', $_SESSION['room']['id'], $frm_data['check_in'], $frm_data['check_out']];
        $tb_fetch = mysqli_fetch_assoc(select($tb_query, $values, 'siss'));

        $rq_result = select("SELECT `quantity` FROM `rooms` WHERE `id`=?", [$_SESSION['room']['id']], 'i');
        $rq_fetch  = mysqli_fetch_assoc($rq_result);

        if (($rq_fetch['quantity'] - $tb_fetch['total_bookings']) == 0) {
            echo json_encode(['status' => 'unavailable']);
            exit;
        }

        $count_days = date_diff($checkin_date, $checkout_date)->days;
        $payment    = $_SESSION['room']['price'] * $count_days;

        $_SESSION['room']['payment']   = $payment;
        $_SESSION['room']['available'] = true;

        echo json_encode(["status" => 'available', "days" => $count_days, "payment" => $payment]);
    }
}
