<?php
  // bayawan-mini-hotel-system/user_generate_pdf.php
  require('admin/includes/essentials.php');
  require('admin/includes/configuration.php');
  require('includes/vendor/autoload.php');

  session_start();

  if(!(isset($_SESSION['login']) && $_SESSION['login'] == true)){
    redirect('user_index.php');
  }

  if(isset($_GET['gen_pdf']) && isset($_GET['id']))
  {
    $frm_data = filteration($_GET);

    $query = "SELECT bo.*, bd.*, uc.email FROM `booking_order` bo
      INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
      INNER JOIN `user_cred` uc ON bo.user_id = uc.id
      WHERE ((bo.booking_status='booked' AND bo.arrival=1) 
      OR (bo.booking_status='cancelled' AND bo.refund=1)
      OR (bo.booking_status='payment failed')) 
      AND bo.booking_id = ?";

    $res = select($query, [$frm_data['id']], 'i');

    if(mysqli_num_rows($res) == 0){
      redirect('user_index.php');
    }

    $data    = mysqli_fetch_assoc($res);
    $date    = date("h:ia | d-m-Y", strtotime($data['datentime']));
    $checkin  = date("d-m-Y", strtotime($data['check_in']));
    $checkout = date("d-m-Y", strtotime($data['check_out']));

    $table_data = "
    <style>
      body { font-family: Arial, sans-serif; font-size: 13px; }
      h2 { text-align: center; color: #333; }
      table { width: 100%; border-collapse: collapse; margin-top: 20px; }
      td { padding: 10px; border: 1px solid #ccc; }
      .label { font-weight: bold; background-color: #f5f5f5; width: 30%; }
      .header-row td { background-color: #1a1a2e; color: white; font-weight: bold; text-align: center; }
      .status-booked { color: green; font-weight: bold; }
      .status-cancelled { color: red; font-weight: bold; }
      .status-failed { color: orange; font-weight: bold; }
    </style>

    <h2>BAYAWAN MINI HOTEL</h2>
    <h3 style='text-align:center;'>BOOKING RECEIPT</h3>
    <p style='text-align:center;'>Poblacion, Bayawan City, Negros Oriental, Philippines 6221</p>
    <hr>

    <table>
      <tr class='header-row'>
        <td colspan='2'>BOOKING INFORMATION</td>
      </tr>
      <tr>
        <td class='label'>Order ID</td>
        <td>$data[order_id]</td>
      </tr>
      <tr>
        <td class='label'>Booking Date</td>
        <td>$date</td>
      </tr>
      <tr>
        <td class='label'>Status</td>
        <td>$data[booking_status]</td>
      </tr>
    </table>

    <table style='margin-top:15px;'>
      <tr class='header-row'>
        <td colspan='2'>GUEST INFORMATION</td>
      </tr>
      <tr>
        <td class='label'>Name</td>
        <td>$data[user_name]</td>
      </tr>
      <tr>
        <td class='label'>Email</td>
        <td>$data[email]</td>
      </tr>
      <tr>
        <td class='label'>Phone Number</td>
        <td>$data[phonenum]</td>
      </tr>
      <tr>
        <td class='label'>Address</td>
        <td>$data[address]</td>
      </tr>
    </table>

    <table style='margin-top:15px;'>
      <tr class='header-row'>
        <td colspan='2'>ROOM INFORMATION</td>
      </tr>
      <tr>
        <td class='label'>Room Name</td>
        <td>$data[room_name]</td>
      </tr>
      <tr>
        <td class='label'>Price per Night</td>
        <td>&#8369;$data[price]</td>
      </tr>
      <tr>
        <td class='label'>Check-in</td>
        <td>$checkin</td>
      </tr>
      <tr>
        <td class='label'>Check-out</td>
        <td>$checkout</td>
      </tr>
    ";

    if($data['booking_status'] == 'cancelled'){
      $refund = ($data['refund']) ? "Amount Refunded" : "Not Yet Refunded";
      $table_data .= "
      <tr>
        <td class='label'>Amount Paid</td>
        <td>&#8369;$data[trans_amt]</td>
      </tr>
      <tr>
        <td class='label'>Refund Status</td>
        <td>$refund</td>
      </tr>";
    }
    else if($data['booking_status'] == 'payment failed'){
      $table_data .= "
      <tr>
        <td class='label'>Transaction Amount</td>
        <td>&#8369;$data[trans_amt]</td>
      </tr>
      <tr>
        <td class='label'>Failure Response</td>
        <td>$data[trans_resp_msg]</td>
      </tr>";
    }
    else{
      $table_data .= "
      <tr>
        <td class='label'>Room Number</td>
        <td>$data[room_no]</td>
      </tr>
      <tr>
        <td class='label'>Amount Paid</td>
        <td>&#8369;$data[trans_amt]</td>
      </tr>";
    }

    $table_data .= "
    </table>
    <p style='text-align:center; margin-top:30px; color:#666; font-size:11px;'>
      Thank you for choosing Bayawan Mini Hotel!<br>
      For inquiries, contact us at cebu.mini.hotel.cmh@gmail.com
    </p>";

    $mpdf = new \Mpdf\Mpdf([
      'margin_top'    => 15,
      'margin_bottom' => 15,
      'margin_left'   => 15,
      'margin_right'  => 15,
    ]);

    $mpdf->WriteHTML($table_data);
    $mpdf->Output($data['order_id'] . '.pdf', 'D');

  } else {
    redirect('user_index.php');
  }
?>