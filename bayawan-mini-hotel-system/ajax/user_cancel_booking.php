<?php
// bayawan-mini-hotel-system/ajax/user_cancel_booking.php
session_start();
require('../admin/includes/admin_configuration.php');
require('../admin/includes/admin_essentials.php');
require('../includes/user_email_helper.php');
require_once '../includes/csrf.php';
csrf_verify();

date_default_timezone_set("Asia/Manila");

if (!(isset($_SESSION['login']) && $_SESSION['login'] == true)) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
    exit;
}

// ─────────────────────────────────────────────────────────────
//  CALCULATE REFUND AMOUNT based on cancellation policy
//
//  Policy:
//   72+ hours before check-in  → full refund (trans_amt)
//   24–72 hours before check-in → trans_amt minus 50% of room price (1 night)
//   < 24 hours before check-in  → trans_amt minus full room price (1 night forfeited)
// ─────────────────────────────────────────────────────────────
function calculate_refund(float $trans_amt, float $room_price, string $check_in): array {
    $now           = new DateTime();
    $checkin_dt    = new DateTime($check_in);
    $hours_until   = ($checkin_dt->getTimestamp() - $now->getTimestamp()) / 3600;

    if ($hours_until >= 72) {
        // Full refund
        $refund_amt = $trans_amt;
        $policy_msg = 'Full refund — cancelled 72+ hours before check-in.';
        $tier       = 'full';
    } elseif ($hours_until >= 24) {
        // 50% of first night charged
        $penalty    = round($room_price * 0.50, 2);
        $refund_amt = max(0, round($trans_amt - $penalty, 2));
        $policy_msg = '50% of first night (₱' . number_format($penalty, 2) . ') charged — cancelled 24–72 hours before check-in.';
        $tier       = 'partial';
    } else {
        // First night forfeited
        $penalty    = round($room_price, 2);
        $refund_amt = max(0, round($trans_amt - $penalty, 2));
        $policy_msg = 'First night (₱' . number_format($penalty, 2) . ') forfeited — cancelled less than 24 hours before check-in.';
        $tier       = 'forfeit';
    }

    return [
        'refund_amt' => $refund_amt,
        'policy_msg' => $policy_msg,
        'tier'       => $tier,
    ];
}


// ─────────────────────────────────────────────────────────────
//  GET REFUND PREVIEW
//  Called before confirming cancellation so guest can see
//  how much they will receive back.
// ─────────────────────────────────────────────────────────────
if (isset($_POST['get_refund_preview'])) {
    $frm_data = filteration($_POST);

    $fetch_q = "SELECT bo.booking_id, bo.trans_amt, bo.check_in,
                       bd.price, bd.room_name
                FROM `booking_order` bo
                INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
                WHERE bo.booking_id = ? AND bo.user_id = ?
                  AND bo.booking_status = 'booked' AND bo.arrival = 0";

    $res     = select($fetch_q, [$frm_data['id'], $_SESSION['uId']], 'ii');
    $booking = mysqli_fetch_assoc($res);

    if (!$booking) {
        echo json_encode(['status' => 'error', 'message' => 'Booking not found.']);
        exit;
    }

    $calc = calculate_refund(
        (float) $booking['trans_amt'],
        (float) $booking['price'],
        $booking['check_in']
    );

    echo json_encode([
        'status'     => 'success',
        'refund_amt' => $calc['refund_amt'],
        'trans_amt'  => $booking['trans_amt'],
        'policy_msg' => $calc['policy_msg'],
        'tier'       => $calc['tier'],
        'room_name'  => $booking['room_name'],
    ]);
    exit;
}


// ─────────────────────────────────────────────────────────────
//  CANCEL BOOKING
// ─────────────────────────────────────────────────────────────
if (isset($_POST['cancel_booking'])) {
    $frm_data = filteration($_POST);

    // Fetch full booking details
    $fetch_q = "SELECT bo.*, bd.*, uc.email, uc.name AS user_name
                FROM `booking_order` bo
                INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
                INNER JOIN `user_cred` uc ON bo.user_id = uc.id
                WHERE bo.booking_id = ? AND bo.user_id = ?
                  AND bo.booking_status = 'booked' AND bo.arrival = 0";

    $fetch_res = select($fetch_q, [$frm_data['id'], $_SESSION['uId']], 'ii');
    $booking   = mysqli_fetch_assoc($fetch_res);

    if (!$booking) {
        echo json_encode(['status' => 'error', 'message' => 'Booking not found or already cancelled.']);
        exit;
    }

    // Calculate refund amount
    $calc = calculate_refund(
        (float) $booking['trans_amt'],
        (float) $booking['price'],
        $booking['check_in']
    );

    // Update booking — store refund_amt, set refund=0 (pending admin processing)
    $query  = "UPDATE `booking_order`
               SET `booking_status` = ?,
                   `refund`         = ?,
                   `refund_amt`     = ?
               WHERE `booking_id` = ? AND `user_id` = ?";
    $result = update($query, ['cancelled', 0, $calc['refund_amt'], $frm_data['id'], $_SESSION['uId']], 'siidii');

    if ($result) {
        // Pass refund_amt and policy_msg into email data
        $booking['refund_amt'] = $calc['refund_amt'];
        $booking['policy_msg'] = $calc['policy_msg'];
        sendGuestCancellationEmail($booking);

        echo json_encode([
            'status'     => 'success',
            'refund_amt' => $calc['refund_amt'],
            'policy_msg' => $calc['policy_msg'],
            'tier'       => $calc['tier'],
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Cancellation failed. Please try again.']);
    }
    exit;
}