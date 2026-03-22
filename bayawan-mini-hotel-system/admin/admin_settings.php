<?php
  // bayawan-mini-hotel-system/admin/admin_settings.php
  
  require('includes/admin_essentials.php');
  require('includes/admin_configuration.php');
  adminOnly();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel - Settings</title>
  <?php require('includes/admin_links.php'); ?>
</head>
<body class="bg-light">

  <?php require('includes/admin_header.php'); ?>

  <div id="main-content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12 p-4 overflow-hidden">
          <h3 class="mb-4">SETTINGS</h3>

          <!-- General Settings -->
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="card-title m-0">General Settings</h5>
                <button type="button" class="btn btn-dark shadow-none btn-sm" data-bs-toggle="modal" data-bs-target="#general-s">
                  <i class="bi bi-pencil-square"></i> Edit
                </button>
              </div>
              <h6 class="card-subtitle mb-1 fw-bold">Site Title</h6>
              <p class="card-text" id="site_title"></p>
              <h6 class="card-subtitle mb-1 fw-bold">About us</h6>
              <p class="card-text" id="site_about"></p>
            </div>
          </div>

          <!-- Shutdown -->
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="card-title m-0">Shutdown Website</h5>
                <div class="form-check form-switch">
                  <form>
                    <input onchange="upd_shutdown(this.value)" class="form-check-input" type="checkbox" id="shutdown-toggle">
                  </form>
                </div>
              </div>
              <p class="card-text">No customers will be allowed to book hotel room, when shutdown mode is turned on.</p>
            </div>
          </div>

          <!-- Contact details -->
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="card-title m-0">Contacts Settings</h5>
                <button type="button" class="btn btn-dark shadow-none btn-sm" data-bs-toggle="modal" data-bs-target="#contacts-s">
                  <i class="bi bi-pencil-square"></i> Edit
                </button>
              </div>
              <div class="row">
                <div class="col-lg-6">
                  <div class="mb-4">
                    <h6 class="card-subtitle mb-1 fw-bold">Address</h6>
                    <p class="card-text" id="address"></p>
                  </div>
                  <div class="mb-4">
                    <h6 class="card-subtitle mb-1 fw-bold">Google Map</h6>
                    <p class="card-text" id="gmap"></p>
                  </div>
                  <div class="mb-4">
                    <h6 class="card-subtitle mb-1 fw-bold">Phone Numbers</h6>
                    <p class="card-text mb-1"><i class="bi bi-telephone-fill"></i> <span id="pn1"></span></p>
                    <p class="card-text"><i class="bi bi-telephone-fill"></i> <span id="pn2"></span></p>
                  </div>
                  <div class="mb-4">
                    <h6 class="card-subtitle mb-1 fw-bold">E-mail</h6>
                    <p class="card-text" id="email"></p>
                  </div>
                </div>
                <div class="col-lg-6">
                  <div class="mb-4">
                    <h6 class="card-subtitle mb-1 fw-bold">Social Links</h6>
                    <p class="card-text mb-1"><i class="bi bi-facebook me-1"></i> <span id="fb"></span></p>
                    <p class="card-text mb-1"><i class="bi bi-instagram me-1"></i> <span id="insta"></span></p>
                    <p class="card-text"><i class="bi bi-twitter me-1"></i> <span id="tw"></span></p>
                  </div>
                  <div class="mb-4">
                    <h6 class="card-subtitle mb-1 fw-bold">iFrame</h6>
                    <iframe id="iframe" class="border p-2 w-100" loading="lazy"></iframe>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Management Team -->
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="card-title m-0">Management Team</h5>
                <button type="button" class="btn btn-dark shadow-none btn-sm" data-bs-toggle="modal" data-bs-target="#team-s">
                  <i class="bi bi-plus-square"></i> Add
                </button>
              </div>
              <div class="row" id="team-data"></div>
            </div>
          </div>

          <!-- ── Change Admin Password ── NEW SECTION ────────────────── -->
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="card-title m-0">
                  <i class="bi bi-shield-lock me-2"></i>Change Admin Password
                </h5>
              </div>
              <p class="text-muted small mb-3">
                Your password is stored securely using bcrypt hashing. 
                Enter your current password to set a new one.
              </p>
              <form id="change-pass-form" style="max-width: 480px;">
                <div class="mb-3">
                  <label class="form-label fw-bold">Current Password</label>
                  <input type="password" name="current_pass" id="current_pass"
                         class="form-control shadow-none" required
                         placeholder="Enter your current password">
                </div>
                <div class="mb-3">
                  <label class="form-label fw-bold">New Password</label>
                  <input type="password" name="new_pass" id="new_pass"
                         class="form-control shadow-none" required
                         placeholder="Min. 8 characters">
                </div>
                <div class="mb-4">
                  <label class="form-label fw-bold">Confirm New Password</label>
                  <input type="password" name="confirm_pass" id="confirm_pass"
                         class="form-control shadow-none" required
                         placeholder="Re-enter new password">
                </div>
                <button type="submit" class="btn btn-dark shadow-none">
                  <i class="bi bi-key me-1"></i> Update Password
                </button>
              </form>
            </div>
          </div>
          <!-- ──────────────────────────────────────────────────────────── -->

        </div>
      </div>
    </div>
  </div>

  <!-- General Settings Modal -->
  <div class="modal fade" id="general-s" data-bs-backdrop="static" data-bs-keyboard="true" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form id="general_s_form">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Edit General Settings</h5>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label fw-bold">Site Title</label>
              <input type="text" name="site_title" id="site_title_inp" class="form-control shadow-none" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">About Us</label>
              <textarea name="site_about" id="site_about_inp" class="form-control shadow-none" rows="4" required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="reset" class="btn text-secondary shadow-none" data-bs-dismiss="modal">CANCEL</button>
            <button type="submit" class="btn custom-bg text-white shadow-none">SAVE</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Contacts Settings Modal -->
  <div class="modal fade" id="contacts-s" data-bs-backdrop="static" data-bs-keyboard="true" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <form id="contacts_s_form">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Edit Contact Settings</h5>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Address</label>
                <input type="text" id="address_inp" name="address" class="form-control shadow-none">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Google Map URL</label>
                <input type="text" id="gmap_inp" name="gmap" class="form-control shadow-none">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Phone Number 1</label>
                <input type="text" id="pn1_inp" name="pn1" class="form-control shadow-none">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Phone Number 2</label>
                <input type="text" id="pn2_inp" name="pn2" class="form-control shadow-none">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">E-mail</label>
                <input type="email" id="email_inp" name="email" class="form-control shadow-none">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Facebook</label>
                <input type="text" id="fb_inp" name="fb" class="form-control shadow-none">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Instagram</label>
                <input type="text" id="insta_inp" name="insta" class="form-control shadow-none">
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Twitter</label>
                <input type="text" id="tw_inp" name="tw" class="form-control shadow-none">
              </div>
              <div class="col-12 mb-3">
                <label class="form-label fw-bold">iFrame (Google Maps embed)</label>
                <textarea id="iframe_inp" name="iframe" class="form-control shadow-none" rows="3"></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="reset" class="btn text-secondary shadow-none" data-bs-dismiss="modal">CANCEL</button>
            <button type="submit" class="btn custom-bg text-white shadow-none">SAVE</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Management Team Modal -->
  <div class="modal fade" id="team-s" data-bs-backdrop="static" data-bs-keyboard="true" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <form id="team_s_form">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Add Team Member</h5>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label fw-bold">Name</label>
              <input type="text" name="member_name" id="member_name_inp" class="form-control shadow-none" required>
            </div>
            <div class="mb-3">
              <label class="form-label fw-bold">Picture</label>
              <input type="file" name="member_picture" id="member_picture_inp" accept=".jpg, .png, .webp, .jpeg" class="form-control shadow-none" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" onclick="member_name_inp.value=''; member_picture_inp.value=''" class="btn text-secondary shadow-none" data-bs-dismiss="modal">CANCEL</button>
            <button type="submit" class="btn custom-bg text-white shadow-none">SUBMIT</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <?php require('includes/admin_scripts.php'); ?>
  <script src="scripts/admin_settings.js"></script>

  <!-- Change Password Script -->
  <script>
  document.getElementById('change-pass-form').addEventListener('submit', function(e) {
    e.preventDefault();

    const current_pass = document.getElementById('current_pass').value;
    const new_pass     = document.getElementById('new_pass').value;
    const confirm_pass = document.getElementById('confirm_pass').value;

    if (new_pass !== confirm_pass) {
      alert('error', 'New passwords do not match!');
      return;
    }

    if (new_pass.length < 8) {
      alert('error', 'New password must be at least 8 characters!');
      return;
    }

    const data = new FormData();
    data.append('change_admin_pass', '');
    data.append('current_pass',      current_pass);
    data.append('new_pass',          new_pass);
    data.append('confirm_pass',      confirm_pass);

    fetch('ajax/admin_settings_crud.php', { method: 'POST', body: data })
      .then(r => r.text())
      .then(res => {
        const resp = res.trim();
        if (resp === '1') {
          alert('success', 'Password updated successfully!');
          document.getElementById('change-pass-form').reset();
        } else if (resp === 'wrong_pass') {
          alert('error', 'Current password is incorrect!');
        } else if (resp === 'same_pass') {
          alert('error', 'New password must be different from the current password!');
        } else if (resp === 'mismatch') {
          alert('error', 'New passwords do not match!');
        } else {
          alert('error', 'Password update failed. Please try again.');
        }
      })
      .catch(() => alert('error', 'Connection error. Please try again.'));
  });
  </script>

</body>
</html>