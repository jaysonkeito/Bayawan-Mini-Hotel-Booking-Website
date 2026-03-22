<?php
// bayawan-mini-hotel-system/ajax/user_get_booked_dates.php
// Returns all booked date ranges for a given room so the
// Flatpickr calendar can disable and highlight them.

require('../admin/includes/admin_configuration.php');
require('../admin/includes/admin_essentials.php');

date_default_timezone_set("Asia/Manila");

header('Content-Type: application/json');

if (!isset($_GET['room_id']) || !is_numeric($_GET['room_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid room ID.']);
    exit;
}

$room_id = (int) $_GET['room_id'];

// Fetch all confirmed bookings for this room
// A date is considered booked if any booking overlaps it:
//   booking check_in <= date < booking check_out
$query = "SELECT `check_in`, `check_out`
          FROM `booking_order`
          WHERE `room_id` = ?
            AND `booking_status` = 'booked'
          ORDER BY `check_in` ASC";

$res  = select($query, [$room_id], 'i');

$booked_ranges = [];

while ($row = mysqli_fetch_assoc($res)) {
    $booked_ranges[] = [
        'from' => $row['check_in'],   // Y-m-d
        'to'   => $row['check_out'],  // Y-m-d (checkout day is free)
    ];
}

// Also check room quantity — if fully booked on a date, disable it
// Get room quantity
$qty_res      = select("SELECT `quantity` FROM `rooms` WHERE `id` = ?", [$room_id], 'i');
$qty_row      = mysqli_fetch_assoc($qty_res);
$room_qty     = $qty_row ? (int) $qty_row['quantity'] : 1;

// Build individual fully-booked dates by checking overlap count per day
// We look 6 months ahead to keep the response small
$fully_booked_dates = [];
$start_date  = new DateTime();
$end_date    = new DateTime('+6 months');

$interval = new DateInterval('P1D');
$period   = new DatePeriod($start_date, $interval, $end_date);

foreach ($period as $day) {
    $date_str = $day->format('Y-m-d');

    // Count bookings overlapping this day
    $overlap_q   = "SELECT COUNT(*) AS cnt FROM `booking_order`
                    WHERE `room_id` = ?
                      AND `booking_status` = 'booked'
                      AND `check_in`  <= ?
                      AND `check_out` >  ?";
    $overlap_res = select($overlap_q, [$room_id, $date_str, $date_str], 'iss');
    $overlap_row = mysqli_fetch_assoc($overlap_res);

    if ((int) $overlap_row['cnt'] >= $room_qty) {
        $fully_booked_dates[] = $date_str;
    }
}

echo json_encode([
    'status'               => 'success',
    'booked_ranges'        => $booked_ranges,
    'fully_booked_dates'   => $fully_booked_dates,
    'room_qty'             => $room_qty,
]);