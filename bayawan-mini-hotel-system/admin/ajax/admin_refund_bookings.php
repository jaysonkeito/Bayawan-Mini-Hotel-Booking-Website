<?php
// bayawan-mini-hotel-system/admin/ajax/admin_refund_bookings.php

require('../includes/admin_configuration.php');
require('../includes/admin_essentials.php');
require('../../includes/paymongo/user_config_paymongo.php');
require('../../includes/paymongo/user_paymongo_helper.php');
require('../../includes/user_email_helper.php');

date_default_timezone_set("Asia/Manila");
adminLogin();

// ─────────────────────────────────────────────────────────────
//  GET BOOKINGS — list all cancelled bookings pending refund
// ─────────────────────────────────────────────────────────────
if (isset($_POST['get_bookings'])) {
    $frm_data = filteration($_POST);

    $query = "SELECT bo.*, bd.* FROM `booking_order` bo
        INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
        WHERE (bo.order_id LIKE ? OR bd.phonenum LIKE ? OR bd.user_name LIKE ?)
        AND (bo.booking_status = ? AND bo.refund = ?)
        ORDER BY bo.booking_id ASC";

    $res = select($query, [
        "%$frm_data[search]%",
        "%$frm_data[search]%",
        "%$frm_data[search]%",
        'cancelled', 0,
    ], 'ssssi');

    if (mysqli_num_rows($res) == 0) {
        echo "<b>No Data Found!</b>";
        exit;
    }

    $i          = 1;
    $table_data = '';

    while ($data = mysqli_fetch_assoc($res)) {
        $date     = date('d-m-Y', strtotime($data['datentime']));
        $checkin  = date('d-m-Y', strtotime($data['check_in']));
        $checkout = date('d-m-Y', strtotime($data['check_out']));

        // Use refund_amt if set, otherwise fall back to trans_amt
        $refund_amt = isset($data['refund_amt']) && $data['refund_amt'] !== null
            ? (float) $data['refund_amt']
            : (float) $data['trans_amt'];

        $paid_amt = (float) $data['trans_amt'];

        // Policy breakdown display
        $penalty      = round($paid_amt - $refund_amt, 2);
        $policy_label = '';

        if ($penalty == 0) {
            $policy_label = "<span class='badge bg-success mt-1'>Full refund</span>";
        } elseif ($refund_amt == 0) {
            $policy_label = "<span class='badge bg-danger mt-1'>No refund — first night forfeited</span>";
        } else {
            $policy_label = "<span class='badge bg-warning text-dark mt-1'>Partial refund — ₱" 
                . number_format($penalty, 2) . " penalty applied</span>";
        }

        // Disable refund button if refund_amt is 0 (no-show / full forfeit)
        $no_refund = $refund_amt <= 0;

        // Disable refund button if no PayMongo payment ID
        $no_trans = empty($data['trans_id']);

        $refund_note = '';
        if ($no_trans) {
            $refund_note = "<br><span class='badge bg-warning text-dark mt-1'>No payment ID — manual refund required</span>";
        }

        $btn_disabled = ($no_refund || $no_trans) ? "disabled" : "";
        $btn_title    = $no_refund
            ? "title='No refund due — first night was forfeited'"
            : ($no_trans ? "title='No PayMongo payment ID on record'" : "");

        $table_data .= "
            <tr>
              <td>{$i}</td>
              <td>
                <span class='badge bg-primary'>Order ID: {$data['order_id']}</span><br>
                <b>Name:</b> {$data['user_name']}<br>
                <b>Phone No:</b> {$data['phonenum']}
                {$refund_note}
                <br>{$policy_label}
              </td>
              <td>
                <b>Room:</b> {$data['room_name']}<br>
                <b>Check-in:</b> {$checkin}<br>
                <b>Check-out:</b> {$checkout}<br>
                <b>Date:</b> {$date}
              </td>
              <td>
                <b>Paid:</b> &#8369;" . number_format($paid_amt, 2) . "<br>
                <b>Refund:</b> <span class='fw-bold text-success'>&#8369;" . number_format($refund_amt, 2) . "</span>
              </td>
              <td>
                <button
                  type='button'
                  onclick='refund_booking({$data['booking_id']})'
                  class='btn btn-success btn-sm fw-bold shadow-none'
                  {$btn_disabled} {$btn_title}>
                  <i class='bi bi-cash-stack'></i> Refund via PayMongo
                </button>
              </td>
            </tr>
        ";
        $i++;
    }

    echo $table_data;
}


// ─────────────────────────────────────────────────────────────
//  REFUND BOOKING — use refund_amt not trans_amt
// ─────────────────────────────────────────────────────────────
if (isset($_POST['refund_booking'])) {
    $frm_data = filteration($_POST);

    $fetch_q = "SELECT bo.*, bd.*, uc.email, uc.name AS user_name
                FROM `booking_order` bo
                INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
                INNER JOIN `user_cred` uc ON bo.user_id = uc.id
                WHERE bo.booking_id = ?
                  AND bo.booking_status = 'cancelled'
                  AND bo.refund = 0";

    $fetch_res = select($fetch_q, [$frm_data['booking_id']], 'i');

    if (mysqli_num_rows($fetch_res) == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Booking not found or already refunded.']);
        exit;
    }

    $booking = mysqli_fetch_assoc($fetch_res);

    // Use refund_amt (policy-calculated); fall back to trans_amt if not set
    $refund_amount = isset($booking['refund_amt']) && $booking['refund_amt'] !== null
        ? (float) $booking['refund_amt']
        : (float) $booking['trans_amt'];

    if (empty($booking['trans_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'No PayMongo payment ID found. Please process this refund manually.']);
        exit;
    }

    if ($refund_amount <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Refund amount is zero. No refund is due for this booking.']);
        exit;
    }

    // Call PayMongo Refund API with the policy-calculated amount
    $paymongo_response = createPaymongoRefund(
        $booking['trans_id'],
        $refund_amount,
        'others'
    );

    if (isset($paymongo_response['data']['id'])) {
        $refund_id     = $paymongo_response['data']['id'];
        $refund_status = $paymongo_response['data']['attributes']['status'] ?? 'pending';

        $upd_res = update(
            "UPDATE `booking_order` SET `refund` = ?, `refund_id` = ? WHERE `booking_id` = ?",
            [1, $refund_id, $frm_data['booking_id']],
            'isi'
        );

        if ($upd_res) {
            $booking['refund_amt'] = $refund_amount;
            sendRefundProcessedEmail($booking);
        }

        echo json_encode([
            'status'        => 'success',
            'message'       => 'Refund submitted successfully via PayMongo!',
            'refund_id'     => $refund_id,
            'refund_status' => $refund_status,
            'refund_amount' => $refund_amount,
        ]);

    } else {
        $api_error = 'PayMongo API error.';
        if (!empty($paymongo_response['errors'])) {
            $first     = $paymongo_response['errors'][0];
            $api_error = $first['detail'] ?? ($first['code'] ?? $api_error);
        }
        error_log('PayMongo refund failed for booking ' . $frm_data['booking_id'] . ': ' . json_encode($paymongo_response));
        echo json_encode(['status' => 'error', 'message' => $api_error]);
    }
}