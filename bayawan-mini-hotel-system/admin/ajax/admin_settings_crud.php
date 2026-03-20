<?php
  // bayawan-mini-hotel-system/admin/ajax/admin_settings_crud.php
  require('../includes/admin_configuration.php');
  require('../includes/admin_essentials.php');
  adminLogin();

  if(isset($_POST['get_general']))
  {
    $res  = select("SELECT * FROM `settings` WHERE `sr_no`=?", [1], "i");
    echo json_encode(mysqli_fetch_assoc($res));
  }

  if(isset($_POST['upd_general']))
  {
    $frm_data = filteration($_POST);
    echo update("UPDATE `settings` SET `site_title`=?, `site_about`=? WHERE `sr_no`=?",
      [$frm_data['site_title'], $frm_data['site_about'], 1], 'ssi');
  }

  if(isset($_POST['upd_shutdown']))
  {
    $val = ($_POST['upd_shutdown'] == 0) ? 1 : 0;
    echo update("UPDATE `settings` SET `shutdown`=? WHERE `sr_no`=?", [$val, 1], 'ii');
  }

  if(isset($_POST['get_contacts']))
  {
    $res  = select("SELECT * FROM `contact_details` WHERE `sr_no`=?", [1], "i");
    echo json_encode(mysqli_fetch_assoc($res));
  }

  if(isset($_POST['upd_contacts']))
  {
    $frm_data = filteration($_POST);
    echo update("UPDATE `contact_details` SET `address`=?,`gmap`=?,`pn1`=?,`pn2`=?,`email`=?,`fb`=?,`insta`=?,`tw`=?,`iframe`=? WHERE `sr_no`=?",
      [$frm_data['address'],$frm_data['gmap'],$frm_data['pn1'],$frm_data['pn2'],$frm_data['email'],$frm_data['fb'],$frm_data['insta'],$frm_data['tw'],$frm_data['iframe'],1],
      'sssssssssi');
  }

  if(isset($_POST['add_member']))
  {
    $frm_data = filteration($_POST);
    $img_r    = uploadImage($_FILES['picture'], ABOUT_FOLDER);

    if(in_array($img_r, ['inv_img','inv_size','upd_failed'])){
      echo $img_r;
    } else {
      echo insert("INSERT INTO `team_details`(`name`,`picture`) VALUES (?,?)", [$frm_data['name'], $img_r], 'ss');
    }
  }

  if(isset($_POST['get_members']))
  {
    $res  = selectAll('team_details');
    $path = ABOUT_IMG_PATH;

    while($row = mysqli_fetch_assoc($res)){
      echo <<<data
        <div class="col-md-2 mb-3">
          <div class="card bg-dark text-white">
            <img src="$path$row[picture]" class="card-img">
            <div class="card-img-overlay text-end">
              <button type="button" onclick="rem_member($row[sr_no])" class="btn btn-danger btn-sm shadow-none">
                <i class="bi bi-trash"></i> Delete
              </button>
            </div>
            <p class="card-text text-center px-3 py-2">$row[name]</p>
          </div>
        </div>
      data;
    }
  }

  if(isset($_POST['rem_member']))
  {
    $frm_data = filteration($_POST);
    $res = select("SELECT * FROM `team_details` WHERE `sr_no`=?", [$frm_data['rem_member']], 'i');
    $img = mysqli_fetch_assoc($res);

    if(deleteImage($img['picture'], ABOUT_FOLDER)){
      echo delete("DELETE FROM `team_details` WHERE `sr_no`=?", [$frm_data['rem_member']], 'i');
    } else {
      echo 0;
    }
  }
?>