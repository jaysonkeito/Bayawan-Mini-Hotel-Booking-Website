<?php
// bayawan-mini-hotel-system/includes/user_complete_profile_modal.php
// Drop-in include for the frontend user_header.php.
// Renders a non-dismissible Bootstrap modal when $_SESSION['google_new'] is true.
// The modal collects the four fields that Google OAuth cannot supply:
//   phone number, address, pincode, date of birth.
// On success the modal is hidden and the session flag is cleared server-side.

$show_complete_modal = isset($_SESSION['google_new']) && $_SESSION['google_new'] === true;
?>

<?php if ($show_complete_modal): ?>

<!-- ═══════════════════════════════════════════════════════
     Complete Your Profile Modal
     backdrop="static"  →  cannot click outside to close
     keyboard="false"   →  Escape key disabled
     ═══════════════════════════════════════════════════════ -->
<div class="modal fade"
     id="completeProfileModal"
     data-bs-backdrop="static"
     data-bs-keyboard="false"
     tabindex="-1"
     aria-labelledby="completeProfileLabel"
     aria-hidden="true">

  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 shadow-lg overflow-hidden">

      <!-- Header band -->
      <div class="modal-header border-0 pb-0"
           style="background: linear-gradient(135deg,#1a1a2e 0%,#16213e 100%);">
        <div class="w-100 text-center py-2">

          <!-- Google avatar from session (profile pic saved during OAuth) -->
          <?php
            $pic = (!empty($_SESSION['user_pic']) && $_SESSION['user_pic'] !== 'default.jpg')
              ? USERS_IMG_PATH . $_SESSION['user_pic']
              : 'images/default.jpg';
          ?>
          <img src="<?= htmlspecialchars($pic) ?>"
               alt="Profile picture"
               class="rounded-circle border border-2 border-white mb-2"
               style="width:64px;height:64px;object-fit:cover;"
               onerror="this.src='images/default.jpg'">

          <h5 id="completeProfileLabel" class="text-white mb-0 fw-bold">
            Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'there') ?>!
          </h5>
          <p class="text-white-50 small mb-2">
            Please complete your profile before continuing.
          </p>
        </div>
      </div>

      <!-- Body -->
      <div class="modal-body px-4 pt-3 pb-4">

        <!-- Inline alert for validation / server errors -->
        <div id="cp-alert" class="d-none mb-3"></div>

        <!-- Progress dots — keeps the UI friendly -->
        <div class="d-flex justify-content-center gap-2 mb-4">
          <span class="badge rounded-pill px-3 py-2"
                style="background:#2ec1ac;color:#fff;font-size:12px;">
            1. Google sign-in ✓
          </span>
          <span class="badge rounded-pill px-3 py-2"
                style="background:#1a1a2e;color:#fff;font-size:12px;">
            2. Complete profile
          </span>
        </div>

        <form id="complete-profile-form" novalidate>

          <!-- Phone Number -->
          <div class="mb-3">
            <label class="form-label fw-semibold" for="cp_phone">
              Phone Number <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text bg-white">
                <i class="bi bi-telephone-fill text-secondary"></i>
              </span>
              <input type="tel"
                     id="cp_phone"
                     name="phonenum"
                     class="form-control shadow-none"
                     placeholder="e.g. 09171234567"
                     maxlength="15"
                     required>
            </div>
            <div class="invalid-feedback">Please enter a valid phone number.</div>
          </div>

          <!-- Date of Birth -->
          <div class="mb-3">
            <label class="form-label fw-semibold" for="cp_dob">
              Date of Birth <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text bg-white">
                <i class="bi bi-calendar3 text-secondary"></i>
              </span>
              <input type="date"
                     id="cp_dob"
                     name="dob"
                     class="form-control shadow-none"
                     max="<?= date('Y-m-d', strtotime('-1 year')) ?>"
                     required>
            </div>
            <div class="invalid-feedback">Please enter your date of birth.</div>
          </div>

          <!-- Pincode -->
          <div class="mb-3">
            <label class="form-label fw-semibold" for="cp_pincode">
              Pincode / ZIP <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text bg-white">
                <i class="bi bi-mailbox text-secondary"></i>
              </span>
              <input type="text"
                     id="cp_pincode"
                     name="pincode"
                     class="form-control shadow-none"
                     placeholder="e.g. 6221"
                     maxlength="10"
                     required>
            </div>
            <div class="invalid-feedback">Please enter your pincode.</div>
          </div>

          <!-- Address -->
          <div class="mb-4">
            <label class="form-label fw-semibold" for="cp_address">
              Address <span class="text-danger">*</span>
            </label>
            <div class="input-group">
              <span class="input-group-text bg-white align-items-start pt-2">
                <i class="bi bi-geo-alt-fill text-secondary"></i>
              </span>
              <textarea id="cp_address"
                        name="address"
                        class="form-control shadow-none"
                        placeholder="House/Unit No., Street, City, Province"
                        rows="2"
                        required></textarea>
            </div>
            <div class="invalid-feedback">Please enter your address.</div>
          </div>

          <!-- Why we need this — transparency note -->
          <p class="text-muted small mb-3">
            <i class="bi bi-info-circle me-1"></i>
            This information is required to process bookings and generate your receipt.
            You can update it anytime from your Profile page.
          </p>

          <button type="submit"
                  id="cp-submit-btn"
                  class="btn w-100 text-white fw-bold shadow-none"
                  style="background:#2ec1ac;border-color:#2ec1ac;">
            Save & Continue
            <i class="bi bi-arrow-right ms-1"></i>
          </button>

        </form>
      </div>

    </div>
  </div>
</div>


<script>
(function () {

  // ── Auto-open the modal as soon as Bootstrap is ready ──
  document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('completeProfileModal');
    if (!modalEl) return;

    // Small delay ensures Bootstrap JS has initialised
    setTimeout(function () {
      new bootstrap.Modal(modalEl).show();
    }, 300);
  });

  // ── Form submission ──
  document.addEventListener('DOMContentLoaded', function () {
    const form      = document.getElementById('complete-profile-form');
    const submitBtn = document.getElementById('cp-submit-btn');
    const alertBox  = document.getElementById('cp-alert');

    if (!form) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      // Client-side validation using Bootstrap's built-in classes
      if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
      }

      // Show loading state
      submitBtn.disabled   = true;
      submitBtn.innerHTML  = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
      alertBox.className   = 'd-none mb-3';

      const data = new FormData();
      data.append('complete_google_profile', '');
      data.append('phonenum', form.elements['phonenum'].value.trim());
      data.append('dob',      form.elements['dob'].value);
      data.append('pincode',  form.elements['pincode'].value.trim());
      data.append('address',  form.elements['address'].value.trim());

      fetch('ajax/user_profile.php', { method: 'POST', body: data })
        .then(r => r.text())
        .then(function (resp) {
          const t = resp.trim();

          if (t === 'success') {
            // Dismiss the modal permanently
            const modalEl  = document.getElementById('completeProfileModal');
            const instance = bootstrap.Modal.getInstance(modalEl);
            if (instance) instance.hide();

            // Show global success alert (uses the alert() helper in user_footer.php)
            if (typeof alert === 'function') {
              alert('success', 'Profile completed! You can now make bookings.');
            }

          } else if (t === 'phone_taken') {
            showAlert('That phone number is already registered to another account.', 'danger');

          } else {
            showAlert(t || 'Could not save your profile. Please try again.', 'danger');
          }
        })
        .catch(function () {
          showAlert('Connection error. Please check your internet and try again.', 'danger');
        })
        .finally(function () {
          submitBtn.disabled  = false;
          submitBtn.innerHTML = 'Save & Continue <i class="bi bi-arrow-right ms-1"></i>';
        });
    });

    function showAlert(msg, type) {
      alertBox.className   = 'mb-3 alert alert-' + type;
      alertBox.textContent = msg;
    }
  });

})();
</script>

<?php endif; ?>