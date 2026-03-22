<?php
// bayawan-mini-hotel-system/admin/ajax/admin_reports.php
require('../includes/admin_configuration.php');
require('../includes/admin_essentials.php');
date_default_timezone_set("Asia/Manila");
adminOnly();

// Load vendor autoload for both mPDF and PhpSpreadsheet
require_once '../../includes/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

header('Content-Type: application/json');

$frm_data  = filteration($_POST);
$date_from = $frm_data['date_from'] ?? '';
$date_to   = $frm_data['date_to']   ?? '';

// Build date condition
$condition = "";
$params    = [];
$types     = "";

if (!empty($date_from) && !empty($date_to)) {
    $condition = "AND DATE(bo.datentime) BETWEEN ? AND ?";
    $params    = [$date_from, $date_to];
    $types     = "ss";
} elseif (!empty($date_from)) {
    $condition = "AND DATE(bo.datentime) >= ?";
    $params    = [$date_from];
    $types     = "s";
} elseif (!empty($date_to)) {
    $condition = "AND DATE(bo.datentime) <= ?";
    $params    = [$date_to];
    $types     = "s";
}


// ─────────────────────────────────────────────────────────────
//  BOOKINGS SUMMARY
// ─────────────────────────────────────────────────────────────
if (isset($_POST['get_bookings_summary'])) {

    $q = "SELECT bo.booking_id, bo.order_id, bo.booking_status, bo.trans_amt,
                 bo.check_in, bo.check_out, bo.datentime,
                 bd.user_name, bd.room_name
          FROM `booking_order` bo
          INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
          WHERE bo.booking_status != 'pending'
          $condition
          ORDER BY bo.datentime DESC";

    $res  = !empty($params) ? select($q, $params, $types) : mysqli_query($conn, $q);
    $rows = [];

    while ($row = mysqli_fetch_assoc($res)) {
        $rows[] = $row;
    }

    // Aggregate stats
    $total     = count($rows);
    $active    = 0; $active_amt    = 0;
    $cancelled = 0; $cancelled_amt = 0;
    $failed    = 0;
    $total_amt = 0;

    foreach ($rows as $r) {
        if ($r['booking_status'] === 'booked') {
            $active++;
            $active_amt += $r['trans_amt'];
        } elseif ($r['booking_status'] === 'cancelled') {
            $cancelled++;
            $cancelled_amt += $r['trans_amt'];
        } elseif ($r['booking_status'] === 'payment failed') {
            $failed++;
        }
        if ($r['booking_status'] !== 'payment failed') {
            $total_amt += $r['trans_amt'];
        }
    }

    echo json_encode([
        'status'        => 'success',
        'total'         => $total,
        'total_amt'     => $total_amt,
        'active'        => $active,
        'active_amt'    => $active_amt,
        'cancelled'     => $cancelled,
        'cancelled_amt' => $cancelled_amt,
        'failed'        => $failed,
        'rows'          => $rows,
    ]);
}


// ─────────────────────────────────────────────────────────────
//  REVENUE
// ─────────────────────────────────────────────────────────────
if (isset($_POST['get_revenue'])) {

    $q = "SELECT bo.order_id, bo.trans_amt, bo.check_in, bo.check_out, bo.datentime,
                 bd.room_name, bd.user_name
          FROM `booking_order` bo
          INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
          WHERE bo.booking_status = 'booked'
          $condition
          ORDER BY bo.datentime DESC";

    $res  = !empty($params) ? select($q, $params, $types) : mysqli_query($conn, $q);
    $rows = [];

    while ($row = mysqli_fetch_assoc($res)) {
        // Calculate nights
        $cin   = new DateTime($row['check_in']);
        $cout  = new DateTime($row['check_out']);
        $nights = (int) date_diff($cin, $cout)->days;
        $row['nights'] = $nights;
        $rows[] = $row;
    }

    $total_rev = array_sum(array_column($rows, 'trans_amt'));
    $avg_rev   = count($rows) > 0 ? round($total_rev / count($rows), 2) : 0;
    $max_rev   = count($rows) > 0 ? max(array_column($rows, 'trans_amt')) : 0;

    echo json_encode([
        'status'    => 'success',
        'total_rev' => $total_rev,
        'avg_rev'   => $avg_rev,
        'max_rev'   => $max_rev,
        'rows'      => $rows,
    ]);
}


// ─────────────────────────────────────────────────────────────
//  OCCUPANCY RATE
// ─────────────────────────────────────────────────────────────
if (isset($_POST['get_occupancy'])) {

    // Date range for occupancy calculation
    $from = !empty($date_from) ? new DateTime($date_from) : new DateTime('-30 days');
    $to   = !empty($date_to)   ? new DateTime($date_to)   : new DateTime();
    $total_days = (int) date_diff($from, $to)->days + 1;

    // Get all active rooms
    $rooms_res = select("SELECT `id`, `name`, `quantity` FROM `rooms` WHERE `removed` = ?", [0], 'i');
    $rooms     = [];

    while ($room = mysqli_fetch_assoc($rooms_res)) {
        $room_id  = $room['id'];
        $quantity = (int) $room['quantity'];

        // Count booked nights for this room in the date range
        $booked_q = "SELECT bo.check_in, bo.check_out
                     FROM `booking_order` bo
                     WHERE bo.room_id = ?
                       AND bo.booking_status = 'booked'
                       AND bo.check_in  < ?
                       AND bo.check_out > ?";

        $booked_res = select($booked_q, [
            $room_id,
            $to->format('Y-m-d'),
            $from->format('Y-m-d')
        ], 'iss');

        $booked_nights = 0;

        while ($b = mysqli_fetch_assoc($booked_res)) {
            $b_from = max(new DateTime($b['check_in']),  $from);
            $b_to   = min(new DateTime($b['check_out']), $to);
            $nights = (int) date_diff($b_from, $b_to)->days;
            $booked_nights += $nights;
        }

        $available_nights = $total_days * $quantity;
        $occupancy_rate   = $available_nights > 0
            ? round(($booked_nights / $available_nights) * 100, 1)
            : 0;

        $rooms[] = [
            'name'             => $room['name'],
            'quantity'         => $quantity,
            'available_nights' => $available_nights,
            'booked_nights'    => $booked_nights,
            'occupancy_rate'   => $occupancy_rate,
        ];
    }

    // Overall stats
    $total_available = array_sum(array_column($rooms, 'available_nights'));
    $total_booked    = array_sum(array_column($rooms, 'booked_nights'));
    $overall_rate    = $total_available > 0
        ? round(($total_booked / $total_available) * 100, 1)
        : 0;

    echo json_encode([
        'status'           => 'success',
        'overall_rate'     => $overall_rate,
        'total_booked'     => $total_booked,
        'total_available'  => $total_available,
        'rooms'            => $rooms,
        'date_from'        => $from->format('Y-m-d'),
        'date_to'          => $to->format('Y-m-d'),
    ]);
}


// ─────────────────────────────────────────────────────────────
//  EXPORT PDF
// ─────────────────────────────────────────────────────────────
if (isset($_POST['export_pdf'])) {
    header('Content-Type: text/html'); // mPDF will override

    $report_type = $frm_data['report_type'] ?? '';
    $date_label  = (!empty($date_from) && !empty($date_to))
        ? "$date_from to $date_to"
        : (!empty($date_from) ? "From $date_from" : (!empty($date_to) ? "To $date_to" : "All Time"));

    $html = "
    <style>
        body  { font-family: Arial, sans-serif; font-size: 12px; }
        h1    { color: #1a1a2e; font-size: 18px; margin-bottom: 4px; }
        h2    { color: #2ec1ac; font-size: 15px; margin-bottom: 4px; }
        p     { font-size: 11px; color: #666; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th    { background: #1a1a2e; color: white; padding: 7px; font-size: 11px; }
        td    { padding: 6px 7px; border-bottom: 1px solid #eee; font-size: 11px; }
        tr:nth-child(even) td { background: #f9f9f9; }
        .stat { display: inline-block; margin-right: 20px; margin-bottom: 10px; }
        .stat span { font-size: 20px; font-weight: bold; color: #1a1a2e; }
        .badge-success { background: #d1fae5; color: #065f46; padding: 2px 7px; border-radius: 10px; }
        .badge-danger  { background: #fee2e2; color: #991b1b; padding: 2px 7px; border-radius: 10px; }
        .badge-warning { background: #fef3c7; color: #92400e; padding: 2px 7px; border-radius: 10px; }
    </style>
    <h1>Bayawan Mini Hotel</h1>
    <h2>" . ucwords(str_replace('_', ' ', $report_type)) . " Report</h2>
    <p>Period: $date_label &nbsp;|&nbsp; Generated: " . date('F j, Y h:i A') . "</p>
    <hr>
    ";

    if ($report_type === 'bookings_summary') {
        $q   = "SELECT bo.order_id, bo.booking_status, bo.trans_amt,
                       bo.check_in, bo.check_out, bo.datentime,
                       bd.user_name, bd.room_name
                FROM `booking_order` bo
                INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
                WHERE bo.booking_status != 'pending' $condition
                ORDER BY bo.datentime DESC";
        $res = !empty($params) ? select($q, $params, $types) : mysqli_query($conn, $q);
        $all = [];
        while ($r = mysqli_fetch_assoc($res)) $all[] = $r;

        $total = count($all);
        $active = $cancelled = $failed = $t_amt = 0;
        foreach ($all as $r) {
            if ($r['booking_status'] === 'booked')          { $active++;    $t_amt += $r['trans_amt']; }
            elseif ($r['booking_status'] === 'cancelled')   { $cancelled++; $t_amt += $r['trans_amt']; }
            elseif ($r['booking_status'] === 'payment failed') $failed++;
        }

        $html .= "<div>
            <div class='stat'>Total: <span>$total</span></div>
            <div class='stat'>Active: <span>$active</span></div>
            <div class='stat'>Cancelled: <span>$cancelled</span></div>
            <div class='stat'>Failed: <span>$failed</span></div>
            <div class='stat'>Revenue: <span>₱" . number_format($t_amt, 2) . "</span></div>
        </div>
        <table>
            <tr><th>#</th><th>Order ID</th><th>Guest</th><th>Room</th><th>Check-in</th><th>Check-out</th><th>Amount</th><th>Status</th></tr>";
        $i = 1;
        foreach ($all as $r) {
            $badge = $r['booking_status'] === 'booked'
                ? "<span class='badge-success'>booked</span>"
                : ($r['booking_status'] === 'cancelled'
                    ? "<span class='badge-danger'>cancelled</span>"
                    : "<span class='badge-warning'>$r[booking_status]</span>");
            $html .= "<tr>
                <td>$i</td>
                <td>$r[order_id]</td>
                <td>$r[user_name]</td>
                <td>$r[room_name]</td>
                <td>" . date('d-m-Y', strtotime($r['check_in'])) . "</td>
                <td>" . date('d-m-Y', strtotime($r['check_out'])) . "</td>
                <td>₱" . number_format($r['trans_amt'], 2) . "</td>
                <td>$badge</td>
            </tr>";
            $i++;
        }
        $html .= "</table>";

    } elseif ($report_type === 'revenue') {
        $q   = "SELECT bo.order_id, bo.trans_amt, bo.check_in, bo.check_out, bo.datentime,
                       bd.room_name, bd.user_name
                FROM `booking_order` bo
                INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
                WHERE bo.booking_status = 'booked' $condition
                ORDER BY bo.datentime DESC";
        $res = !empty($params) ? select($q, $params, $types) : mysqli_query($conn, $q);
        $all = [];
        while ($r = mysqli_fetch_assoc($res)) {
            $nights = (int) date_diff(new DateTime($r['check_in']), new DateTime($r['check_out']))->days;
            $r['nights'] = $nights;
            $all[] = $r;
        }

        $total = array_sum(array_column($all, 'trans_amt'));
        $avg   = count($all) > 0 ? round($total / count($all), 2) : 0;
        $max   = count($all) > 0 ? max(array_column($all, 'trans_amt')) : 0;

        $html .= "<div>
            <div class='stat'>Total Revenue: <span>₱" . number_format($total, 2) . "</span></div>
            <div class='stat'>Average: <span>₱" . number_format($avg, 2) . "</span></div>
            <div class='stat'>Highest: <span>₱" . number_format($max, 2) . "</span></div>
        </div>
        <table>
            <tr><th>#</th><th>Date</th><th>Order ID</th><th>Room</th><th>Guest</th><th>Nights</th><th>Amount</th></tr>";
        $i = 1;
        foreach ($all as $r) {
            $html .= "<tr>
                <td>$i</td>
                <td>" . date('d-m-Y', strtotime($r['datentime'])) . "</td>
                <td>$r[order_id]</td>
                <td>$r[room_name]</td>
                <td>$r[user_name]</td>
                <td>$r[nights]</td>
                <td>₱" . number_format($r['trans_amt'], 2) . "</td>
            </tr>";
            $i++;
        }
        $html .= "</table>";

    } elseif ($report_type === 'occupancy') {
        $from       = !empty($date_from) ? new DateTime($date_from) : new DateTime('-30 days');
        $to         = !empty($date_to)   ? new DateTime($date_to)   : new DateTime();
        $total_days = (int) date_diff($from, $to)->days + 1;

        $rooms_res = select("SELECT `id`,`name`,`quantity` FROM `rooms` WHERE `removed`=?", [0], 'i');
        $all       = [];
        $t_avail   = 0; $t_booked = 0;

        while ($room = mysqli_fetch_assoc($rooms_res)) {
            $booked_res = select(
                "SELECT check_in, check_out FROM `booking_order`
                 WHERE room_id=? AND booking_status='booked'
                 AND check_in < ? AND check_out > ?",
                [$room['id'], $to->format('Y-m-d'), $from->format('Y-m-d')], 'iss'
            );
            $bn = 0;
            while ($b = mysqli_fetch_assoc($booked_res)) {
                $bf = max(new DateTime($b['check_in']),  $from);
                $bt = min(new DateTime($b['check_out']), $to);
                $bn += (int) date_diff($bf, $bt)->days;
            }
            $avail = $total_days * (int)$room['quantity'];
            $rate  = $avail > 0 ? round(($bn / $avail) * 100, 1) : 0;
            $t_avail  += $avail;
            $t_booked += $bn;
            $all[] = ['name' => $room['name'], 'quantity' => $room['quantity'],
                      'available' => $avail, 'booked' => $bn, 'rate' => $rate];
        }

        $overall = $t_avail > 0 ? round(($t_booked / $t_avail) * 100, 1) : 0;

        $html .= "<div>
            <div class='stat'>Overall Rate: <span>$overall%</span></div>
            <div class='stat'>Booked Nights: <span>$t_booked</span></div>
            <div class='stat'>Available Nights: <span>$t_avail</span></div>
        </div>
        <table>
            <tr><th>#</th><th>Room</th><th>Qty</th><th>Available Nights</th><th>Booked Nights</th><th>Occupancy Rate</th></tr>";
        $i = 1;
        foreach ($all as $r) {
            $color = $r['rate'] >= 70 ? 'badge-success' : ($r['rate'] >= 40 ? 'badge-warning' : 'badge-danger');
            $html .= "<tr>
                <td>$i</td>
                <td>$r[name]</td>
                <td>$r[quantity]</td>
                <td>$r[available]</td>
                <td>$r[booked]</td>
                <td><span class='$color'>$r[rate]%</span></td>
            </tr>";
            $i++;
        }
        $html .= "</table>";
    }

    $mpdf = new \Mpdf\Mpdf(['margin_top' => 15, 'margin_bottom' => 15,
                            'margin_left' => 15, 'margin_right' => 15]);
    $mpdf->WriteHTML($html);
    $mpdf->Output('report_' . $report_type . '_' . date('Ymd') . '.pdf', 'D');
    exit;
}


// ─────────────────────────────────────────────────────────────
//  EXPORT EXCEL
// ─────────────────────────────────────────────────────────────
if (isset($_POST['export_excel'])) {
    header('Content-Type: text/html');

    $report_type = $frm_data['report_type'] ?? '';
    $date_label  = (!empty($date_from) && !empty($date_to))
        ? "$date_from to $date_to" : "All Time";

    $spreadsheet = new Spreadsheet();
    $sheet       = $spreadsheet->getActiveSheet();

    // Header style
    $header_style = [
        'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A1A2E']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ];

    // Title rows
    $sheet->setCellValue('A1', 'Bayawan Mini Hotel');
    $sheet->setCellValue('A2', ucwords(str_replace('_', ' ', $report_type)) . ' Report');
    $sheet->setCellValue('A3', 'Period: ' . $date_label);
    $sheet->setCellValue('A4', 'Generated: ' . date('F j, Y h:i A'));
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

    $data_start = 6;

    if ($report_type === 'bookings_summary') {
        $q   = "SELECT bo.order_id, bo.booking_status, bo.trans_amt,
                       bo.check_in, bo.check_out, bo.datentime,
                       bd.user_name, bd.room_name
                FROM `booking_order` bo
                INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
                WHERE bo.booking_status != 'pending' $condition
                ORDER BY bo.datentime DESC";
        $res = !empty($params) ? select($q, $params, $types) : mysqli_query($conn, $q);

        $headers = ['#', 'Order ID', 'Guest Name', 'Room', 'Check-in', 'Check-out', 'Amount (₱)', 'Status'];
        foreach ($headers as $col => $h) {
            $cell = chr(65 + $col) . $data_start;
            $sheet->setCellValue($cell, $h);
            $sheet->getStyle($cell)->applyFromArray($header_style);
        }

        $i = 1; $row_num = $data_start + 1;
        while ($r = mysqli_fetch_assoc($res)) {
            $sheet->setCellValue("A$row_num", $i);
            $sheet->setCellValue("B$row_num", $r['order_id']);
            $sheet->setCellValue("C$row_num", $r['user_name']);
            $sheet->setCellValue("D$row_num", $r['room_name']);
            $sheet->setCellValue("E$row_num", date('d-m-Y', strtotime($r['check_in'])));
            $sheet->setCellValue("F$row_num", date('d-m-Y', strtotime($r['check_out'])));
            $sheet->setCellValue("G$row_num", $r['trans_amt']);
            $sheet->setCellValue("H$row_num", $r['booking_status']);
            $i++; $row_num++;
        }

    } elseif ($report_type === 'revenue') {
        $q   = "SELECT bo.order_id, bo.trans_amt, bo.check_in, bo.check_out, bo.datentime,
                       bd.room_name, bd.user_name
                FROM `booking_order` bo
                INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
                WHERE bo.booking_status = 'booked' $condition
                ORDER BY bo.datentime DESC";
        $res = !empty($params) ? select($q, $params, $types) : mysqli_query($conn, $q);

        $headers = ['#', 'Date', 'Order ID', 'Room', 'Guest Name', 'Nights', 'Amount (₱)'];
        foreach ($headers as $col => $h) {
            $cell = chr(65 + $col) . $data_start;
            $sheet->setCellValue($cell, $h);
            $sheet->getStyle($cell)->applyFromArray($header_style);
        }

        $i = 1; $row_num = $data_start + 1;
        while ($r = mysqli_fetch_assoc($res)) {
            $nights = (int) date_diff(new DateTime($r['check_in']), new DateTime($r['check_out']))->days;
            $sheet->setCellValue("A$row_num", $i);
            $sheet->setCellValue("B$row_num", date('d-m-Y', strtotime($r['datentime'])));
            $sheet->setCellValue("C$row_num", $r['order_id']);
            $sheet->setCellValue("D$row_num", $r['room_name']);
            $sheet->setCellValue("E$row_num", $r['user_name']);
            $sheet->setCellValue("F$row_num", $nights);
            $sheet->setCellValue("G$row_num", $r['trans_amt']);
            $i++; $row_num++;
        }

    } elseif ($report_type === 'occupancy') {
        $from       = !empty($date_from) ? new DateTime($date_from) : new DateTime('-30 days');
        $to         = !empty($date_to)   ? new DateTime($date_to)   : new DateTime();
        $total_days = (int) date_diff($from, $to)->days + 1;

        $headers = ['#', 'Room Name', 'Quantity', 'Available Nights', 'Booked Nights', 'Occupancy Rate (%)'];
        foreach ($headers as $col => $h) {
            $cell = chr(65 + $col) . $data_start;
            $sheet->setCellValue($cell, $h);
            $sheet->getStyle($cell)->applyFromArray($header_style);
        }

        $rooms_res = select("SELECT `id`,`name`,`quantity` FROM `rooms` WHERE `removed`=?", [0], 'i');
        $i = 1; $row_num = $data_start + 1;

        while ($room = mysqli_fetch_assoc($rooms_res)) {
            $booked_res = select(
                "SELECT check_in, check_out FROM `booking_order`
                 WHERE room_id=? AND booking_status='booked'
                 AND check_in < ? AND check_out > ?",
                [$room['id'], $to->format('Y-m-d'), $from->format('Y-m-d')], 'iss'
            );
            $bn = 0;
            while ($b = mysqli_fetch_assoc($booked_res)) {
                $bf = max(new DateTime($b['check_in']),  $from);
                $bt = min(new DateTime($b['check_out']), $to);
                $bn += (int) date_diff($bf, $bt)->days;
            }
            $avail = $total_days * (int)$room['quantity'];
            $rate  = $avail > 0 ? round(($bn / $avail) * 100, 1) : 0;

            $sheet->setCellValue("A$row_num", $i);
            $sheet->setCellValue("B$row_num", $room['name']);
            $sheet->setCellValue("C$row_num", $room['quantity']);
            $sheet->setCellValue("D$row_num", $avail);
            $sheet->setCellValue("E$row_num", $bn);
            $sheet->setCellValue("F$row_num", $rate . '%');
            $i++; $row_num++;
        }
    }

    // Auto-size columns
    foreach (range('A', 'H') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Output
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="report_' . $report_type . '_' . date('Ymd') . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}