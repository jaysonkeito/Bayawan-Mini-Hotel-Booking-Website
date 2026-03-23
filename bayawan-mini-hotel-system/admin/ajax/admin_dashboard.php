<?php
  // bayawan-mini-hotel-system/admin/ajax/admin_dashboard.php
  
  require('../includes/admin_essentials.php');
  require('../includes/admin_configuration.php');
  require_once '../../includes/csrf.php';
  csrf_verify();
  date_default_timezone_set("Asia/Manila");
  adminLogin();

  if(isset($_POST['booking_analytics']))
  {
    // ─── Admin only ───
    if(!isAdmin()){ echo json_encode([]); exit; }

    $frm_data  = filteration($_POST);
    $condition = "";

    if($frm_data['period'] == 1)      $condition = "WHERE datentime BETWEEN NOW() - INTERVAL 30 DAY AND NOW()";
    else if($frm_data['period'] == 2) $condition = "WHERE datentime BETWEEN NOW() - INTERVAL 90 DAY AND NOW()";
    else if($frm_data['period'] == 3) $condition = "WHERE datentime BETWEEN NOW() - INTERVAL 1 YEAR AND NOW()";

    $result = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 
      COUNT(CASE WHEN booking_status!='pending' AND booking_status!='payment failed' THEN 1 END) AS `total_bookings`,
      SUM(CASE WHEN booking_status!='pending' AND booking_status!='payment failed' THEN `trans_amt` END) AS `total_amt`,
      COUNT(CASE WHEN booking_status='booked' AND arrival=1 THEN 1 END) AS `active_bookings`,
      SUM(CASE WHEN booking_status='booked' AND arrival=1 THEN `trans_amt` END) AS `active_amt`,
      COUNT(CASE WHEN booking_status='cancelled' AND refund=1 THEN 1 END) AS `cancelled_bookings`,
      SUM(CASE WHEN booking_status='cancelled' AND refund=1 THEN `trans_amt` END) AS `cancelled_amt`
      FROM `booking_order` $condition"));

    echo json_encode($result);
  }

  if(isset($_POST['user_analytics']))
  {
    // ─── Admin only ───
    if(!isAdmin()){ echo json_encode([]); exit; }

    $frm_data  = filteration($_POST);
    $condition = "";

    if($frm_data['period'] == 1)      $condition = "WHERE datentime BETWEEN NOW() - INTERVAL 30 DAY AND NOW()";
    else if($frm_data['period'] == 2) $condition = "WHERE datentime BETWEEN NOW() - INTERVAL 90 DAY AND NOW()";
    else if($frm_data['period'] == 3) $condition = "WHERE datentime BETWEEN NOW() - INTERVAL 1 YEAR AND NOW()";

    $reg_condition = str_replace('datentime', 'created_at', $condition);

    $total_reviews = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(sr_no) AS `count` FROM `rating_review` $condition"));
    $total_queries = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(sr_no) AS `count` FROM `user_queries` $condition"));
    $total_new_reg = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(id) AS `count` FROM `user_cred` $reg_condition"));

    echo json_encode([
      'total_queries' => $total_queries['count'],
      'total_reviews' => $total_reviews['count'],
      'total_new_reg' => $total_new_reg['count']
    ]);
  }
?>