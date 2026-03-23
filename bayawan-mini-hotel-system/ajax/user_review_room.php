<?php 
// bayawan-mini-hotel-system/ajax/user_review_room.php
  session_start();
  require('../admin/includes/admin_configuration.php');
  require('../admin/includes/admin_essentials.php');
  require_once '../includes/csrf.php';
  csrf_verify();

  date_default_timezone_set("Asia/Manila");


  if(!(isset($_SESSION['login']) && $_SESSION['login']==true)){
    redirect('user_index.php');
  }

  if(isset($_POST['review_form']))
  {
    $frm_data = filteration($_POST);

    $upd_query = "UPDATE `booking_order` SET `rate_review`=? WHERE `booking_id`=? AND `user_id`=?";
    $upd_values = [1,$frm_data['booking_id'],$_SESSION['uId']];
    $upd_result = update($upd_query,$upd_values,'iii');

    $ins_query = "INSERT INTO `rating_review`(`booking_id`, `room_id`, `user_id`, `rating`, `review`)
      VALUES (?,?,?,?,?)";

    $ins_values = [$frm_data['booking_id'],$frm_data['room_id'],$_SESSION['uId'],
      $frm_data['rating'],$frm_data['review']];

    $ins_result = insert($ins_query,$ins_values,'iiiis');

    echo $ins_result;
  }

?>