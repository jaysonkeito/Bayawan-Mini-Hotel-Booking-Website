<?php // bayawan-mini-hotel-system/user_profile.php ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php require('includes/user_links.php'); ?>
<title><?php echo $settings_r['site_title'] ?> - PROFILE</title>
</head>
<body class="bg-light">

<?php
  require('includes/user_header.php');

  if (!(isset($_SESSION['login']) && $_SESSION['login'] == true)) {
      redirect('user_index.php');
  }

  $u_exist = select("SELECT * FROM `user_cred` WHERE `id`=? LIMIT 1", [$_SESSION['uId']], 'i');

  if (mysqli_num_rows($u_exist) == 0) {
      redirect('user_index.php');
  }

  $u_fetch = mysqli_fetch_assoc($u_exist);
?>

<div class="container">
  <div class="row">

    <div class="col-12 my-5 px-4">
      <h2 class="fw-bold">PROFILE</h2>
      <div style="font-size: 14px;">
        <a href="user_index.php" class="text-secondary text-decoration-none">HOME</a>
        <span class="text-secondary"> > </span>
        <a href="#" class="text-secondary text-decoration-none">PROFILE</a>
      </div>
    </div>

    <!-- ── Basic Information ── -->
    <div class="col-12 mb-5 px-4">
      <div class="bg-white p-3 p-md-4 rounded shadow-sm">
        <form id="info-form">
          <h5 class="mb-3 fw-bold">Basic Information</h5>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Name</label>
              <input name="name" type="text"
                     value="<?php echo htmlspecialchars($u_fetch['name']) ?>"
                     class="form-control shadow-none" required>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Email</label>
              <input type="email"
                     value="<?php echo htmlspecialchars($u_fetch['email']) ?>"
                     class="form-control shadow-none bg-light" readonly>
              <small class="text-muted">Email cannot be changed.</small>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Phone Number</label>
              <input name="phonenum" type="text"
                     value="<?php echo htmlspecialchars($u_fetch['phonenum']) ?>"
                     class="form-control shadow-none" required>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Date of Birth</label>
              <input name="dob" type="date"
                     value="<?php echo $u_fetch['dob'] ?>"
                     class="form-control shadow-none" required>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Pincode</label>
              <input name="pincode" type="text"
                     value="<?php echo htmlspecialchars($u_fetch['pincode']) ?>"
                     class="form-control shadow-none" required>
            </div>
            <div class="col-md-8 mb-4">
              <label class="form-label">Address</label>
              <textarea name="address" class="form-control shadow-none" rows="1" required><?php echo htmlspecialchars($u_fetch['address']) ?></textarea>
            </div>
          </div>
          <button type="submit" class="btn text-white custom-bg shadow-none">Save Changes</button>
        </form>
      </div>
    </div>

    <!-- ── Profile Picture ── -->
    <div class="col-md-4 mb-5 px-4">
      <div class="bg-white p-3 p-md-4 rounded shadow-sm">
        <form id="profile-form">
          <h5 class="mb-3 fw-bold">Picture</h5>
          <?php
            $pic = ($u_fetch['profile'] && $u_fetch['profile'] !== 'default.jpg')
              ? USERS_IMG_PATH . $u_fetch['profile']
              : 'images/default.jpg';
          ?>
          <img src="<?php echo $pic ?>"
               class="rounded-circle img-fluid mb-3"
               style="width:120px;height:120px;object-fit:cover;"
               onerror="this.src='images/default.jpg'">
          <label class="form-label">New Picture</label>
          <input name="profile" type="file"
                 accept=".jpg,.jpeg,.png,.webp"
                 class="mb-4 form-control shadow-none" required>
          <button type="submit" class="btn text-white custom-bg shadow-none">Save Changes</button>
        </form>
      </div>
    </div>

    <!-- ── Change Password ── -->
    <div class="col-md-8 mb-5 px-4">
      <div class="bg-white p-3 p-md-4 rounded shadow-sm">
        <form id="pass-form">
          <h5 class="mb-3 fw-bold">Change Password</h5>

          <div class="row">

            <!-- Current Password — NEW field -->
            <div class="col-md-12 mb-3">
              <label class="form-label">Current Password</label>
              <div class="input-group">
                <input name="current_pass"
                       type="password"
                       id="currentPassword"
                       class="form-control shadow-none"
                       placeholder="Enter your current password"
                       required>
                <span class="input-group-text bg-white border-start-0"
                      id="toggleCurrentPass"
                      style="cursor:pointer;">
                  <i class="bi bi-eye-slash fs-5"></i>
                </span>
              </div>
            </div>

            <!-- New Password -->
            <div class="col-md-6 mb-3">
              <label class="form-label">New Password</label>
              <div class="input-group">
                <input name="new_pass"
                       type="password"
                       id="newPassword"
                       class="form-control shadow-none"
                       placeholder="Enter new password"
                       required>
                <span class="input-group-text bg-white border-start-0"
                      id="toggleNewPass"
                      style="cursor:pointer;">
                  <i class="bi bi-eye-slash fs-5"></i>
                </span>
              </div>
            </div>

            <!-- Confirm Password -->
            <div class="col-md-6 mb-4">
              <label class="form-label">Confirm New Password</label>
              <div class="input-group">
                <input name="confirm_pass"
                       type="password"
                       id="confirmPassword"
                       class="form-control shadow-none"
                       placeholder="Re-enter new password"
                       required>
                <span class="input-group-text bg-white border-start-0"
                      id="toggleConfirmPass"
                      style="cursor:pointer;">
                  <i class="bi bi-eye-slash fs-5"></i>
                </span>
              </div>
            </div>

          </div>

          <!-- Google OAuth notice -->
          <div class="alert alert-info py-2 px-3 mb-3" style="font-size:13px;">
            <i class="bi bi-google me-1"></i>
            Signed in with Google and don't have a password?
            <a href="user_index.php?forgot_password" class="alert-link">Use Forgot Password</a>
            from the login page to set one.
          </div>

          <button type="submit" class="btn text-white custom-bg shadow-none">
            Save Changes
          </button>
        </form>
      </div>
    </div>

  </div>
</div>

<?php require('includes/user_footer.php'); ?>

<script>

  // ─── Basic Info Form ───
  let info_form = document.getElementById('info-form');

  info_form.addEventListener('submit', function(e) {
    e.preventDefault();

    let data = new FormData();
    data.append('info_form', '');
    data.append('name',     info_form.elements['name'].value);
    data.append('phonenum', info_form.elements['phonenum'].value);
    data.append('address',  info_form.elements['address'].value);
    data.append('pincode',  info_form.elements['pincode'].value);
    data.append('dob',      info_form.elements['dob'].value);

    let xhr = new XMLHttpRequest();
    xhr.open("POST", "ajax/user_profile.php", true);

    xhr.onload = function() {
      if (this.responseText === 'phone_already') {
        alert('error', "Phone number is already registered!");
      } else if (this.responseText === '0') {
        alert('error', "No changes made!");
      } else {
        alert('success', 'Changes saved!');
      }
    };

    xhr.send(data);
  });


  // ─── Profile Picture Form ───
  let profile_form = document.getElementById('profile-form');

  profile_form.addEventListener('submit', function(e) {
    e.preventDefault();

    let data = new FormData();
    data.append('profile_form', '');
    data.append('profile', profile_form.elements['profile'].files[0]);

    let xhr = new XMLHttpRequest();
    xhr.open("POST", "ajax/user_profile.php", true);

    xhr.onload = function() {
      if (this.responseText === 'inv_img') {
        alert('error', "Only JPG, WEBP & PNG images are allowed!");
      } else if (this.responseText === 'upd_failed') {
        alert('error', "Image upload failed!");
      } else if (this.responseText === '0') {
        alert('error', "Update failed!");
      } else {
        window.location.href = window.location.pathname;
      }
    };

    xhr.send(data);
  });


  // ─── Change Password Form ───
  let pass_form = document.getElementById('pass-form');

  pass_form.addEventListener('submit', function(e) {
    e.preventDefault();

    let current_pass = pass_form.elements['current_pass'].value;
    let new_pass     = pass_form.elements['new_pass'].value;
    let confirm_pass = pass_form.elements['confirm_pass'].value;

    // Client-side: new and confirm must match before sending
    if (new_pass !== confirm_pass) {
      alert('error', 'New passwords do not match!');
      return false;
    }

    let data = new FormData();
    data.append('pass_form',    '');
    data.append('current_pass', current_pass);
    data.append('new_pass',     new_pass);
    data.append('confirm_pass', confirm_pass);

    let xhr = new XMLHttpRequest();
    xhr.open("POST", "ajax/user_profile.php", true);

    xhr.onload = function() {
      const resp = this.responseText.trim();

      // ── Handle all server responses ──
      if (resp === '1') {
        alert('success', 'Password changed successfully!');
        pass_form.reset();

      } else if (resp === 'wrong_pass') {
        alert('error', 'Current password is incorrect!');
        // Highlight the current password field so the user knows which to fix
        let currentField = document.getElementById('currentPassword');
        currentField.classList.add('is-invalid');
        currentField.focus();
        setTimeout(() => currentField.classList.remove('is-invalid'), 3000);

      } else if (resp === 'same_pass') {
        alert('error', 'New password must be different from your current password!');

      } else if (resp === 'mismatch') {
        alert('error', 'New passwords do not match!');

      } else if (resp === 'current_required') {
        alert('error', 'Please enter your current password to continue!');
        document.getElementById('currentPassword').focus();

      } else if (resp === 'empty_pass') {
        alert('error', 'New password cannot be empty!');

      } else {
        alert('error', 'Password update failed. Please try again.');
      }
    };

    xhr.send(data);
  });


  // ─── Password Toggle Helper ───
  function attachToggle(inputId, toggleId) {
    let btn = document.getElementById(toggleId);
    if (!btn) return;
    btn.addEventListener('click', function() {
      let input = document.getElementById(inputId);
      let icon  = this.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
      } else {
        input.type = 'password';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
      }
    });
  }

  attachToggle('currentPassword',  'toggleCurrentPass');
  attachToggle('newPassword',       'toggleNewPass');
  attachToggle('confirmPassword',   'toggleConfirmPass');

</script>

</body>
</html>