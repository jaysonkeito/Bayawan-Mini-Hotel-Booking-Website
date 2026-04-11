<?php 
  // bayawan-mini-hotel-system/admin/ajax/admin_booking_records.php
  require('../includes/admin_configuration.php');
  require('../includes/admin_essentials.php');
  require_once '../../includes/csrf.php';
  csrf_verify();
  date_default_timezone_set("Asia/Manila");
  adminLogin();

  // IMPROVEMENT: Was hardcoded to 2 (leftover test value). Set to a sensible page size.
  define('RECORDS_PER_PAGE', 15);

  if(isset($_POST['get_bookings']))
  {
    $frm_data = filteration($_POST);
    $page     = max(1, (int)($frm_data['page'] ?? 1));
    $search   = $frm_data['search'] ?? '';
    $start    = ($page - 1) * RECORDS_PER_PAGE;

    $base_query = "SELECT bo.*, bd.* FROM `booking_order` bo
      INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
      WHERE ((bo.booking_status='booked' AND bo.arrival=1) 
      OR (bo.booking_status='cancelled' AND bo.refund=1)
      OR (bo.booking_status='payment failed')) 
      AND (bo.order_id LIKE ? OR bd.phonenum LIKE ? OR bd.user_name LIKE ?)
      ORDER BY bo.booking_id DESC";

    $params = ["%$search%", "%$search%", "%$search%"];

    // Total count for pagination (separate query)
    $count_res  = select($base_query, $params, 'sss');
    $total_rows = mysqli_num_rows($count_res);

    if($total_rows == 0){
      echo json_encode([
        "table_data" => "<tr><td colspan='6' class='text-center'><b>No Data Found!</b></td></tr>",
        "pagination"  => "",
        "total_info"  => ""
      ]);
      exit;
    }

    // Paginated fetch
    $paged_query = $base_query . " LIMIT ?, ?";
    $limit_res   = select($paged_query, array_merge($params, [$start, RECORDS_PER_PAGE]), 'sssii');

    $i          = $start + 1;
    $table_data = "";

    while($data = mysqli_fetch_assoc($limit_res))
    {
      $date     = date("d-m-Y", strtotime($data['datentime']));
      $checkin  = date("d-m-Y", strtotime($data['check_in']));
      $checkout = date("d-m-Y", strtotime($data['check_out']));

      if($data['booking_status'] == 'booked'){
        $status_bg = 'bg-success';
      } elseif($data['booking_status'] == 'cancelled'){
        $status_bg = 'bg-danger';
      } else {
        $status_bg = 'bg-warning text-dark';
      }

      $safe_order  = htmlspecialchars($data['order_id']);
      $safe_name   = htmlspecialchars($data['user_name']);
      $safe_phone  = htmlspecialchars($data['phonenum']);
      $safe_room   = htmlspecialchars($data['room_name']);
      $safe_status = htmlspecialchars($data['booking_status']);

      $table_data .= "
        <tr>
          <td>{$i}</td>
          <td>
            <span class='badge bg-primary'>Order ID: {$safe_order}</span><br>
            <b>Name:</b> {$safe_name}<br>
            <b>Phone No:</b> {$safe_phone}
          </td>
          <td>
            <b>Room:</b> {$safe_room}<br>
            <b>Price:</b> &#8369;{$data['price']}
          </td>
          <td>
            <b>Check-in:</b> {$checkin}<br>
            <b>Check-out:</b> {$checkout}<br>
            <b>Amount:</b> &#8369;{$data['trans_amt']}<br>
            <b>Date:</b> {$date}
          </td>
          <td><span class='badge {$status_bg}'>{$safe_status}</span></td>
          <td>
            <button type='button' onclick='download({$data['booking_id']})' class='btn btn-outline-success btn-sm fw-bold shadow-none'>
              <i class='bi bi-file-earmark-arrow-down-fill'></i>
            </button>
          </td>
        </tr>
      ";
      $i++;
    }

    // Build pagination
    $total_pages = ceil($total_rows / RECORDS_PER_PAGE);
    $pagination  = "";

    if($total_pages > 1)
    {
      $safe_search = addslashes($search);

      if($page > 1){
        $pagination .= "<li class='page-item'><button onclick='change_page(1)' class='page-link shadow-none'>First</button></li>";
        $prev = $page - 1;
        $pagination .= "<li class='page-item'><button onclick='change_page({$prev})' class='page-link shadow-none'>Prev</button></li>";
      }

      // Sliding window of page numbers (±2 around current)
      $win_start = max(1, $page - 2);
      $win_end   = min($total_pages, $page + 2);
      for($p = $win_start; $p <= $win_end; $p++){
        $active = ($p == $page) ? "active" : "";
        $pagination .= "<li class='page-item {$active}'><button onclick='change_page({$p})' class='page-link shadow-none'>{$p}</button></li>";
      }

      if($page < $total_pages){
        $next = $page + 1;
        $pagination .= "<li class='page-item'><button onclick='change_page({$next})' class='page-link shadow-none'>Next</button></li>";
        $pagination .= "<li class='page-item'><button onclick='change_page({$total_pages})' class='page-link shadow-none'>Last</button></li>";
      }
    }

    $from       = $start + 1;
    $to         = min($start + RECORDS_PER_PAGE, $total_rows);
    $total_info = "Showing {$from}–{$to} of {$total_rows} records";

    echo json_encode([
      "table_data" => $table_data,
      "pagination"  => $pagination,
      "total_info"  => $total_info
    ]);
  }
?>