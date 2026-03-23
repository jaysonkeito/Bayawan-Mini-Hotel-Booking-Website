<?php
// bayawan-mini-hotel-system/admin/ajax/admin_calendar.php
require('../includes/admin_configuration.php');
require('../includes/admin_essentials.php');
require_once '../../includes/csrf.php';
csrf_verify();
adminLogin();

date_default_timezone_set('Asia/Manila');
header('Content-Type: application/json; charset=utf-8');

// ── Get all active rooms for the filter dropdown ──────────────
if (isset($_POST['get_rooms'])) {
    $res   = select("SELECT `id`, `name` FROM `rooms` WHERE `removed`=? ORDER BY `name`", [0], 'i');
    $rooms = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $rooms[] = ['id' => $row['id'], 'name' => $row['name']];
    }
    echo json_encode($rooms);
    exit;
}

// ── Get calendar events ───────────────────────────────────────
if (isset($_POST['get_events'])) {
    $start   = $_POST['start']   ?? '';
    $end     = $_POST['end']     ?? '';
    $room_id = (int)($_POST['room_id'] ?? 0);
    $status  = trim($_POST['status'] ?? '');

    // Build dynamic query
    $where  = "bo.check_out >= ? AND bo.check_in <= ?";
    $params = [$start, $end];
    $types  = 'ss';

    if ($room_id > 0) {
        $where   .= " AND bo.room_id = ?";
        $params[] = $room_id;
        $types   .= 'i';
    }

    if ($status !== '') {
        $where   .= " AND bo.booking_status = ?";
        $params[] = $status;
        $types   .= 's';
    } else {
        // Exclude pending/expired by default — they clutter the calendar
        $where .= " AND bo.booking_status NOT IN ('pending','expired')";
    }

    $query = "SELECT bo.booking_id, bo.check_in, bo.check_out,
                     bo.booking_status, r.name AS room_name,
                     bd.user_name
              FROM `booking_order` bo
              INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
              INNER JOIN `rooms` r ON bo.room_id = r.id
              WHERE {$where}
              ORDER BY bo.check_in ASC";

    $res    = select($query, $params, $types);
    $events = [];

    while ($row = mysqli_fetch_assoc($res)) {
        // FullCalendar end date is exclusive — add 1 day so checkout day is included
        $end_exclusive = date('Y-m-d', strtotime($row['check_out'] . ' +1 day'));

        $events[] = [
            'booking_id'      => $row['booking_id'],
            'guest'           => $row['user_name'],
            'room'            => $row['room_name'],
            'check_in'        => $row['check_in'],
            'check_out'       => $row['check_out'],
            'check_out_exclusive' => $end_exclusive,
            'status'          => $row['booking_status'],
        ];
    }

    echo json_encode($events);
    exit;
}

// ── Get single booking detail ─────────────────────────────────
if (isset($_POST['get_detail'])) {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    if (!$booking_id) { echo json_encode([]); exit; }

    $query = "SELECT bo.booking_id, bo.order_id, bo.booking_status,
                     bo.check_in, bo.check_out, bo.trans_amt, bo.datentime,
                     bd.user_name, bd.phonenum, bd.room_name, bd.room_no
              FROM `booking_order` bo
              INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
              WHERE bo.booking_id = ? LIMIT 1";

    $res = select($query, [$booking_id], 'i');
    $row = mysqli_fetch_assoc($res);

    if (!$row) { echo json_encode([]); exit; }

    // Format dates nicely
    $row['check_in']  = date('F j, Y', strtotime($row['check_in']));
    $row['check_out'] = date('F j, Y', strtotime($row['check_out']));
    $row['datentime'] = date('F j, Y h:i A', strtotime($row['datentime']));

    echo json_encode($row);
    exit;
}