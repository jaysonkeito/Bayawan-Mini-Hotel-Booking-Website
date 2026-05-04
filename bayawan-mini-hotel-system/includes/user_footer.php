<!-- bayawan-mini-hotel-system/includes/user_footer.php -->
<div class="container-fluid bg-white mt-5">
  <div class="row">
    <div class="col-lg-4 p-4">
      <h3 class="h-font fw-bold fs-3 mb-2"><?php echo $settings_r['site_title'] ?></h3>
      <p><?php echo $settings_r['site_about'] ?></p>
    </div>
    <div class="col-lg-4 p-4">
      <h5 class="mb-3">Links</h5>
      <a href="user_index.php" class="d-inline-block mb-2 text-dark text-decoration-none">Home</a> <br>
      <a href="user_rooms.php" class="d-inline-block mb-2 text-dark text-decoration-none">Rooms</a> <br>
      <a href="user_facilities.php" class="d-inline-block mb-2 text-dark text-decoration-none">Facilities</a> <br>
      <a href="user_contact.php" class="d-inline-block mb-2 text-dark text-decoration-none">Contact us</a> <br>
      <a href="user_about.php" class="d-inline-block mb-2 text-dark text-decoration-none">About</a>
    </div>
    <div class="col-lg-4 p-4">
      <h5 class="mb-3">Follow us</h5>
      <?php 
        if($contact_r['tw'] != ''){
          echo<<<data
            <a href="$contact_r[tw]" class="d-inline-block text-dark text-decoration-none mb-2">
              <i class="bi bi-twitter me-1"></i> Twitter
            </a><br>
          data;
        }
      ?>
      <a href="<?php echo $contact_r['fb'] ?>" class="d-inline-block text-dark text-decoration-none mb-2">
        <i class="bi bi-facebook me-1"></i> Facebook
      </a><br>
      <a href="<?php echo $contact_r['insta'] ?>" class="d-inline-block text-dark text-decoration-none">
        <i class="bi bi-instagram me-1"></i> Instagram
      </a><br>
    </div>
  </div>
</div>

<h6 class="text-center bg-dark text-white p-3 m-0">Designed and Developed by Jayson P. Francisco</h6>

<!-- ── Login-nudge toast (shown when guest clicks Book Now while logged out) ── -->
<div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index:9999; pointer-events:none;">
  <div id="bmh-login-toast" class="toast align-items-center text-white bg-dark border-0" role="alert" aria-live="assertive" aria-atomic="true" style="pointer-events:auto;">
    <div class="d-flex">
      <div class="toast-body">
        Please log in first to book a room.
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<!-- CSRF token injector — must load before any other script -->
<script src="scripts/user_csrf.js"></script>

<!-- Bootstrap JS (once, at the bottom) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" 
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" 
        crossorigin="anonymous"></script>

<!-- Swiper JS -->
<!-- <script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script> -->

<!-- Custom auth script (handles login, register, OTP, toggles) -->
<script src="scripts/user_login_register.js"></script>

<script>
  // ─── Alert Helper ───
  function show_alert(type, msg, position='body') {
      let bs_class = (type == 'success') ? 'alert-success' : 'alert-danger';
      
      let existing = document.querySelector('.custom-alert');
      if(existing) existing.remove();

      let element = document.createElement('div');
      element.classList.add('custom-alert');
      element.innerHTML = `
          <div class="alert ${bs_class} alert-dismissible fade show" role="alert">
              <strong class="me-3">${msg}</strong>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
      `;

      if(position == 'body'){
          document.body.append(element);
      } else {
          document.getElementById(position).appendChild(element);
      }

      setTimeout(() => {
          if(element && element.parentNode) element.remove();
      }, 3000);
  }

  function remAlert(){
      let existing = document.querySelector('.custom-alert');
      if(existing) existing.remove();
  }

  // ─── Set Active Nav Link ───
  function setActive() {
    let navbar = document.getElementById('nav-bar');
    let a_tags = navbar.getElementsByTagName('a');
    for(let i = 0; i < a_tags.length; i++) {
      let file = a_tags[i].href.split('/').pop();
      let file_name = file.split('.')[0];
      if(document.location.href.indexOf(file_name) >= 0){
        a_tags[i].classList.add('active');
      }
    }
  }

  // ─── Forgot Password Form ───
  let forgot_form = document.getElementById('forgot-form');
  if(forgot_form){
    forgot_form.addEventListener('submit', (e) => {
      e.preventDefault();
      let data = new FormData();
      data.append('email', forgot_form.elements['email'].value);
      data.append('action', 'forgot_pass');

      bootstrap.Modal.getInstance(document.getElementById('forgotModal'))?.hide();

      fetch('ajax/user_auth.php', { method: 'POST', body: data })
      .then(r => r.text())
      .then(resp => {
        const t = resp.trim();
        if(t == 'inv_email')         show_alert('error',   "Invalid Email!");
        else if(t == 'not_verified') show_alert('error',   "Email is not verified! Please contact Admin.");
        else if(t == 'inactive')     show_alert('error',   "Account Suspended! Please contact Admin.");
        else if(t == 'mail_failed')  show_alert('error',   "Cannot send email. Server Down!");
        else if(t == 'upd_failed')   show_alert('error',   "Account recovery failed. Server Down!");
        else {
          show_alert('success', "Reset link sent to email!");
          forgot_form.reset();
        }
      })
      .catch(() => show_alert('error', 'Connection error. Please try again.'));
    });
  }

  // ─────────────────────────────────────────────────────────────────
  //  checkLoginToBook
  //  FIX: When not logged in, open the Login Modal directly instead
  //       of showing a plain show_alert(). The pending room_id is saved in
  //       sessionStorage so user_login_register.js can redirect there
  //       after a successful login.
  // ─────────────────────────────────────────────────────────────────
  function checkLoginToBook(status, room_id) {
    if (status) {
      window.location.href = 'user_confirm_booking.php?id=' + room_id;
    } else {
      // Remember where to redirect after login
      sessionStorage.setItem('redirectAfterLogin', 'user_confirm_booking.php?id=' + room_id);

      // Show a friendly toast at top of page
      const toastEl = document.getElementById('bmh-login-toast');
      if (toastEl) {
        toastEl.querySelector('.toast-body').textContent =
          'Please log in first to book a room.';
        new bootstrap.Toast(toastEl, { delay: 3500 }).show();
      }

      // Open the Login Modal
      new bootstrap.Modal(document.getElementById('loginModal')).show();
    }
  }

  setActive();
</script>

<!-- Session Timeout -->
<script src="scripts/session_timeout.js"></script>
<?php if (isset($_SESSION['login']) && $_SESSION['login'] == true): ?>
<script>
  initSessionTimeout({
    checkUrl:   'ajax/user_session_check.php',
    logoutUrl:  'user_logout.php',
    checkEvery: 60,
  });
</script>
<?php endif; ?>