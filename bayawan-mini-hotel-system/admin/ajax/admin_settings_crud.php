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

  // ── Change Admin Password ─────────────────────────────────────────────
  // ADDED: Handles password change from admin_settings.php.
  // Verifies current password with password_verify(), then saves
  // the new password hashed with password_hash() (bcrypt).
  // ─────────────────────────────────────────────────────────────────────
  if (isset($_POST['change_admin_pass']))
  {
    $current_pass = $_POST['current_pass'] ?? '';
    $new_pass     = $_POST['new_pass']     ?? '';
    $confirm_pass = $_POST['confirm_pass'] ?? '';

    // Server-side: new and confirm must match
    if ($new_pass !== $confirm_pass) {
      echo 'mismatch';
      exit;
    }

    // Minimum length check
    if (strlen($new_pass) < 8) {
      echo 'too_short';
      exit;
    }

    // Fetch the current admin's record using the session ID
    $res = select(
      "SELECT * FROM `admin_cred` WHERE `sr_no` = ? LIMIT 1",
      [$_SESSION['adminId']], 'i'
    );

    if ($res->num_rows !== 1) {
      echo '0';
      exit;
    }

    $row = mysqli_fetch_assoc($res);

    // Verify current password against stored hash
    if (!password_verify($current_pass, $row['admin_pass'])) {
      echo 'wrong_pass';
      exit;
    }

    // Prevent reusing the same password
    if (password_verify($new_pass, $row['admin_pass'])) {
      echo 'same_pass';
      exit;
    }

    // Hash and save the new password
    $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);

    echo update(
      "UPDATE `admin_cred` SET `admin_pass` = ? WHERE `sr_no` = ?",
      [$new_hash, $_SESSION['adminId']], 'si'
    );
  }
?>