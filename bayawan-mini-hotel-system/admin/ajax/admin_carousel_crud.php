<?php
  // bayawan-mini-hotel-system/admin/ajax/admin_carouse_crud.php
  require('../includes/admin_essentials.php');
  require('../includes/admin_configuration.php');
  adminLogin();

  if(isset($_POST['add_image']))
  {
    $img_r = uploadImage($_FILES['picture'], CAROUSEL_FOLDER);

    if($img_r == 'inv_img') echo $img_r;
    else if($img_r == 'inv_size') echo $img_r;
    else if($img_r == 'upd_failed') echo $img_r;
    else {
      $q = "INSERT INTO `carousel`(`image`) VALUES (?)";
      echo insert($q, [$img_r], 's');
    }
  }

  if(isset($_POST['get_carousel']))
  {
    $res  = selectAll('carousel');
    $path = CAROUSEL_IMG_PATH;

    while($row = mysqli_fetch_assoc($res)){
      echo <<<data
        <div class="col-md-4 mb-3">
          <div class="card bg-dark text-white">
            <img src="$path$row[image]" class="card-img">
            <div class="card-img-overlay text-end">
              <button type="button" onclick="rem_image($row[sr_no])" class="btn btn-danger btn-sm shadow-none">
                <i class="bi bi-trash"></i> Delete
              </button>
            </div>
          </div>
        </div>
      data;
    }
  }

  if(isset($_POST['rem_image']))
  {
    $frm_data = filteration($_POST);
    $values   = [$frm_data['rem_image']];

    $res = select("SELECT * FROM `carousel` WHERE `sr_no`=?", $values, 'i');
    $img = mysqli_fetch_assoc($res);

    if(deleteImage($img['image'], CAROUSEL_FOLDER)){
      echo delete("DELETE FROM `carousel` WHERE `sr_no`=?", $values, 'i');
    } else {
      echo 0;
    }
  }
?>