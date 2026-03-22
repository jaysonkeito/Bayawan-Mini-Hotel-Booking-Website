<?php
// bayawan-mini-hotel-system/admin/ajax/admin_new_bookings.php
require('../includes/admin_configuration.php');
require('../includes/admin_essentials.php');
require('../../includes/user_email_helper.php');

date_default_timezone_set("Asia/Manila");
adminLogin();

// ─────────────────────────────────────────────────────────────
//  GET BOOKINGS
// ─────────────────────────────────────────────────────────────
if (isset($_POST['get_bookings'])) {
    $frm_data = filteration($_POST);

    $query = "SELECT bo.*, bd.* FROM `booking_order` bo
        INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
        WHERE (bo.order_id LIKE ? OR bd.phonenum LIKE ? OR bd.user_name LIKE ?)
        AND (bo.booking_status = ? AND bo.arrival = ? AND bo.no_show = ?)
        ORDER BY bo.booking_id ASC";

    $res = select($query, [
        "%$frm_data[search]%",
        "%$frm_data[search]%",
        "%$frm_data[search]%",
        "booked", 0, 0
    ], 'ssssii');

    if (mysqli_num_rows($res) == 0) {
        echo "<b>No Data Found!</b>";
        exit;
    }

    $i          = 1;
    $table_data = "";
    $now        = new DateTime();

    while ($data = mysqli_fetch_assoc($res)) {
        $date     = date("d-m-Y", strtotime($data['datentime']));
        $checkin  = date("d-m-Y", strtotime($data['check_in']));
        $checkout = date("d-m-Y", strtotime($data['check_out']));

        $checkin_dt  = new DateTime($data['check_in']);
        $hours_until = ($checkin_dt->getTimestamp() - $now->getTimestamp()) / 3600;

        if ($hours_until >= 72) {
            $policy_badge = "<span class='badge bg-success mt-1'>Full refund if cancelled</span>";
        } elseif ($hours_until >= 24) {
            $policy_badge = "<span class='badge bg-warning text-dark mt-1'>50% penalty if cancelled</span>";
        } elseif ($hours_until > 0) {
            $policy_badge = "<span class='badge bg-danger mt-1'>1st night forfeited if cancelled</span>";
        } else {
            $policy_badge = "<span class='badge bg-dark mt-1'>Check-in date passed</span>";
        }

        $table_data .= "
            <tr>
              <td>{$i}</td>
              <td>
                <span class='badge bg-primary'>Order ID: {$data['order_id']}</span><br>
                <b>Name:</b> {$data['user_name']}<br>
                <b>Phone No:</b> {$data['phonenum']}
                <br>{$policy_badge}
              </td>
              <td>
                <b>Room:</b> {$data['room_name']}<br>
                <b>Price:</b> &#8369;{$data['price']}
              </td>
              <td>
                <b>Check-in:</b> {$checkin}<br>
                <b>Check-out:</b> {$checkout}<br>
                <b>Paid:</b> &#8369;{$data['trans_amt']}<br>
                <b>Date:</b> {$date}
              </td>
              <td>
                <button type='button'
                  onclick='open_assign_modal({$data['booking_id']}, {$data['room_id']}, \"{$data['room_name']}\", \"{$data['check_in']}\", \"{$data['check_out']}\")'
                  class='btn text-white btn-sm fw-bold custom-bg shadow-none mb-1'>
                  <i class='bi bi-check2-square'></i> Assign Room
                </button><br>
                <button type='button'
                  onclick='cancel_booking({$data['booking_id']})'
                  class='btn btn-outline-danger btn-sm fw-bold shadow-none mb-1'>
                  <i class='bi bi-trash'></i> Cancel Booking
                </button><br>
                <button type='button'
                  onclick='mark_no_show({$data['booking_id']})'
                  class='btn btn-secondary btn-sm fw-bold shadow-none'>
                  <i class='bi bi-person-x'></i> No-Show
                </button>
              </td>
            </tr>
        ";
        $i++;
    }

    echo $table_data;
}


// ─────────────────────────────────────────────────────────────
//  GET AVAILABLE ROOMS
//  Returns: { room_name, quantity, occupied: ["Room #1", ...] }
//  "Occupied" = a room number already assigned to another active
//  booking of the same room type whose dates overlap this booking.
// ─────────────────────────────────────────────────────────────
if (isset($_POST['get_available_rooms'])) {
    $booking_id = (int) ($_POST['booking_id'] ?? 0);
    $room_id    = (int) ($_POST['room_id']    ?? 0);
    $check_in   = $_POST['check_in']  ?? '';
    $check_out  = $_POST['check_out'] ?? '';

    // 1. Get room name and quantity
    $room_res  = select("SELECT `name`, `quantity` FROM `rooms` WHERE `id` = ? LIMIT 1", [$room_id], 'i');
    $room_data = mysqli_fetch_assoc($room_res);

    if (!$room_data) {
        echo json_encode(['status' => 'error', 'message' => 'Room not found']);
        exit;
    }

    // 2. Find which room numbers are already assigned to overlapping
    //    confirmed bookings of the same room type (excluding this booking)
    $occupied_q = "
        SELECT bd.room_no
        FROM `booking_order` bo
        INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
        WHERE bo.room_id        = ?
          AND bo.booking_id    != ?
          AND bo.booking_status = 'booked'
          AND bo.arrival        = 1
          AND bd.room_no       IS NOT NULL
          AND bd.room_no       != ''
          AND bo.check_in      < ?
          AND bo.check_out     > ?
    ";

    $occupied_res  = select($occupied_q, [$room_id, $booking_id, $check_out, $check_in], 'iiss');
    $occupied_list = [];

    while ($row = mysqli_fetch_assoc($occupied_res)) {
        $occupied_list[] = $row['room_no'];
    }

    echo json_encode([
        'status'    => 'success',
        'room_name' => $room_data['name'],
        'quantity'  => (int) $room_data['quantity'],
        'occupied'  => $occupied_list,
    ]);
    exit;
}


// ─────────────────────────────────────────────────────────────
//  ASSIGN ROOM
// ─────────────────────────────────────────────────────────────
if (isset($_POST['assign_room'])) {
    $frm_data = filteration($_POST);

    $query = "UPDATE `booking_order` bo
              INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
              SET bo.arrival = ?, bo.rate_review = ?, bd.room_no = ?
              WHERE bo.booking_id = ?";

    $res = update($query, [1, 0, $frm_data['room_no'], $frm_data['booking_id']], 'iisi');

    if ($res == 2) {
        $email_q    = "SELECT bo.*, bd.*, uc.email, uc.name AS user_name
                       FROM `booking_order` bo
                       INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
                       INNER JOIN `user_cred` uc ON bo.user_id = uc.id
                       WHERE bo.booking_id = ?";
        $email_data = mysqli_fetch_assoc(select($email_q, [$frm_data['booking_id']], 'i'));
        if ($email_data) sendArrivalConfirmationEmail($email_data);
        echo 1;
    } else {
        echo 0;
    }
}


// ─────────────────────────────────────────────────────────────
//  CANCEL BOOKING (admin) — always full refund
// ─────────────────────────────────────────────────────────────
if (isset($_POST['cancel_booking'])) {
    $frm_data = filteration($_POST);

    $email_q    = "SELECT bo.*, bd.*, uc.email, uc.name AS user_name
                   FROM `booking_order` bo
                   INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
                   INNER JOIN `user_cred` uc ON bo.user_id = uc.id
                   WHERE bo.booking_id = ?";
    $email_data = mysqli_fetch_assoc(select($email_q, [$frm_data['booking_id']], 'i'));

    $refund_amt = $email_data ? (float) $email_data['trans_amt'] : 0;

    $res = update(
        "UPDATE `booking_order`
         SET `booking_status` = ?, `refund` = ?, `refund_amt` = ?
         WHERE `booking_id` = ?",
        ['cancelled', 0, $refund_amt, $frm_data['booking_id']],
        'siid'
    );

    if ($res && $email_data) {
        $email_data['refund_amt'] = $refund_amt;
        $email_data['policy_msg'] = 'Admin-initiated cancellation — full refund applied.';
        sendGuestCancellationEmail($email_data);
    }

    echo $res ? 1 : 0;
}


// ─────────────────────────────────────────────────────────────
//  MARK NO-SHOW — first night forfeited, no refund
// ─────────────────────────────────────────────────────────────
if (isset($_POST['mark_no_show'])) {
    $frm_data = filteration($_POST);

    $fetch_q = "SELECT bo.*, bd.*, uc.email, uc.name AS user_name
                FROM `booking_order` bo
                INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
                INNER JOIN `user_cred` uc ON bo.user_id = uc.id
                WHERE bo.booking_id = ?";
    $booking = mysqli_fetch_assoc(select($fetch_q, [$frm_data['booking_id']], 'i'));

    if (!$booking) { echo 0; exit; }

    $refund_amt = 0;

    $res = update(
        "UPDATE `booking_order`
         SET `booking_status` = ?, `refund` = ?, `refund_amt` = ?, `no_show` = ?
         WHERE `booking_id` = ?",
        ['cancelled', 0, $refund_amt, 1, $frm_data['booking_id']],
        'siiid'
    );

    echo $res ? 1 : 0;
}