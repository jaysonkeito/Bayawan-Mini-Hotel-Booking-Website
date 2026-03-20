<?php 
  // bayawan-mini-hotel-system/admin/ajax/admin_features_facilities.php
  require('../includes/admin_configuration.php');
  require('../includes/admin_essentials.php');
  adminLogin();

  if(isset($_POST['add_feature']))
  {
    $frm_data = filteration($_POST);
    $q = "INSERT INTO `features`(`name`) VALUES (?)";
    echo insert($q, [$frm_data['name']], 's');
  }

  if(isset($_POST['get_features']))
  {
    $res = selectAll('features');
    $i   = 1;
    while($row = mysqli_fetch_assoc($res)){
      echo <<<data
        <tr>
          <td>$i</td>
          <td>$row[name]</td>
          <td>
            <button type="button" onclick="rem_feature($row[id])" class="btn btn-danger btn-sm shadow-none">
              <i class="bi bi-trash"></i> Delete
            </button>
          </td>
        </tr>
      data;
      $i++;
    }
  }

  if(isset($_POST['rem_feature']))
  {
    $frm_data = filteration($_POST);
    $check_q  = select('SELECT * FROM `room_features` WHERE `features_id`=?', [$frm_data['rem_feature']], 'i');
    if(mysqli_num_rows($check_q) == 0){
      echo delete("DELETE FROM `features` WHERE `id`=?", [$frm_data['rem_feature']], 'i');
    } else {
      echo 'room_added';
    }
  }

  if(isset($_POST['add_facility']))
  {
    $frm_data = filteration($_POST);
    $img_r    = uploadSVGImage($_FILES['icon'], FACILITIES_FOLDER);

    if(in_array($img_r, ['inv_img','inv_size','upd_failed'])){
      echo $img_r;
    } else {
      $q = "INSERT INTO `facilities`(`icon`,`name`,`description`) VALUES (?,?,?)";
      echo insert($q, [$img_r, $frm_data['name'], $frm_data['desc']], 'sss');
    }
  }

  if(isset($_POST['get_facilities']))
  {
    $res  = selectAll('facilities');
    $i    = 1;
    $path = FACILITIES_IMG_PATH;

    while($row = mysqli_fetch_assoc($res)){
      echo <<<data
        <tr class='align-middle'>
          <td>$i</td>
          <td><img src="$path$row[icon]" width="50px"></td>
          <td>$row[name]</td>
          <td>$row[description]</td>
          <td>
            <button type="button" onclick="rem_facility($row[id])" class="btn btn-danger btn-sm shadow-none">
              <i class="bi bi-trash"></i> Delete
            </button>
          </td>
        </tr>
      data;
      $i++;
    }
  }

  if(isset($_POST['rem_facility']))
  {
    $frm_data = filteration($_POST);
    $check_q  = select('SELECT * FROM `room_facilities` WHERE `facilities_id`=?', [$frm_data['rem_facility']], 'i');

    if(mysqli_num_rows($check_q) == 0){
      $res = select("SELECT * FROM `facilities` WHERE `id`=?", [$frm_data['rem_facility']], 'i');
      $img = mysqli_fetch_assoc($res);
      if(deleteImage($img['icon'], FACILITIES_FOLDER)){
        echo delete("DELETE FROM `facilities` WHERE `id`=?", [$frm_data['rem_facility']], 'i');
      } else {
        echo 0;
      }
    } else {
      echo 'room_added';
    }
  }
?>