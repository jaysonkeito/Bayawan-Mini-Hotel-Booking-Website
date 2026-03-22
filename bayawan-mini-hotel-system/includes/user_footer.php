<?php // bayawan-mini-hotel-system/includes/user_footer.php ?>

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

      <a href="user_terms.php" class="d-inline-block mb-2 text-dark text-decoration-none">Terms & Conditions</a> <br>
      <a href="user_privacy.php" class="d-inline-block mb-2 text-dark text-decoration-none">Privacy Policy</a>
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

<!-- Bootstrap JS (once, at the bottom) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" 
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" 
        crossorigin="anonymous"></script>

<!-- Swiper JS -->
<script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>

<!-- Custom auth script (handles login, register, OTP, toggles) -->
<script src="scripts/user_login_register.js"></script>

<script>
  // ─── Alert Helper ───
  function alert(type, msg, position='body') {
      let bs_class = (type == 'success') ? 'alert-success' : 'alert-danger';
      
      // Remove any existing alert first
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

      // Auto dismiss after 3 seconds
      setTimeout(() => {
          if(element && element.parentNode){
              element.remove();
          }
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
        if(t == 'inv_email')      alert('error',   "Invalid Email!");
        else if(t == 'not_verified') alert('error', "Email is not verified! Please contact Admin.");
        else if(t == 'inactive')     alert('error', "Account Suspended! Please contact Admin.");
        else if(t == 'mail_failed')  alert('error', "Cannot send email. Server Down!");
        else if(t == 'upd_failed')   alert('error', "Account recovery failed. Server Down!");
        else {
          alert('success', "Reset link sent to email!");
          forgot_form.reset();
        }
      })
      .catch(() => alert('error', 'Connection error. Please try again.'));
    });
  }

  // ─── Check Login To Book ───
  function checkLoginToBook(status, room_id) {
    if(status) window.location.href = 'user_confirm_booking.php?id=' + room_id;
    else alert('error', 'Please login to book room!');
  }

  // Auto-dismiss PHP-rendered static alerts (e.g. contact form success)
  document.querySelectorAll('.custom-alert').forEach(el => {
      setTimeout(() => { if (el && el.parentNode) el.remove(); }, 3000);
  });

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