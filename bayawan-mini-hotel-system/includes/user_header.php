<?php // bayawan-mini-hotel-system/includes/user_header.php ?>
<nav id="nav-bar" class="navbar navbar-expand-lg navbar-light bg-white px-lg-3 py-lg-2 shadow-sm sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand me-5 fw-bold fs-3 h-font" href="user_index.php">Bayawan Mini Hotel</a>
        <button class="navbar-toggler shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="user_index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="user_rooms.php">Rooms</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="user_facilities.php">Facilities</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="user_contact.php">Contact us</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="user_about.php">About</a>
                </li>
                <?php if (isset($_SESSION['login']) && $_SESSION['login'] == true): ?>
                    <li class="nav-item">
                        <a href="user_cart.php" class="nav-link position-relative" title="My Cart">
                        <i class="bi bi-cart3 fs-5"></i>
                        <?php
                            $cart_badge_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
                        ?>
                        <span id="cart-badge"
                                class="position-absolute top-0 start-100 translate-middle
                                    badge rounded-pill bg-danger"
                                style="font-size:10px;
                                    display:<?php echo $cart_badge_count > 0 ? 'inline-flex' : 'none' ?>;">
                            <?php echo $cart_badge_count ?>
                        </span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            <div class="d-flex">
                <?php 
                if (isset($_SESSION['login']) && $_SESSION['login'] == true) {
                    $path = USERS_IMG_PATH;

                    $profilePic = $_SESSION['user_pic'] ?? '';
                    $userName   = $_SESSION['user_name'] ?? 'User';

                    $avatarHtml = '';
                    if ($profilePic && $profilePic !== 'default.jpg') {
                        $avatarHtml = <<<HTML
                            <img src="{$path}{$profilePic}" 
                                style="width: 25px; height: 25px;" 
                                class="me-1 rounded-circle object-fit-cover"
                                alt="Profile Picture"
                                onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                            <i class="bi bi-person-circle fs-5 me-1" style="display:none;"></i>
                        HTML;
                    } else {
                        $avatarHtml = '<i class="bi bi-person-circle fs-5 me-1"></i>';
                    }

                    echo <<<data
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-dark shadow-none dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                            {$avatarHtml}
                            {$userName}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-lg-end">
                            <li><a class="dropdown-item" href="user_profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="user_bookings.php">Bookings</a></li>
                            <li><a class="dropdown-item" href="user_logout.php">Logout</a></li>
                        </ul>
                    </div>
                    data;
                } else {
                    echo <<<data
                    <button type="button" class="btn btn-outline-dark shadow-none me-lg-3 me-2" data-bs-toggle="modal" data-bs-target="#loginModal">
                        Login
                    </button>
                    <button type="button" class="btn btn-outline-dark shadow-none" data-bs-toggle="modal" data-bs-target="#registerModal">
                        Register
                    </button>
                    data;
                }
                ?>
            </div>
        </div>
    </div>
</nav>
<?php require('includes/user_complete_profile_modal.php'); ?>

<!-- Login Modal -->
<div class="modal fade" id="loginModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center" id="loginModalLabel">
                    <i class="bi bi-person-circle fs-3 me-2"></i> User Login
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="login-form">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Email / Mobile</label>
                        <input type="text" name="email_mob" required class="form-control shadow-none">
                    </div>
                    <div class="mb-4 position-relative">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" name="pass" id="loginPassword" required class="form-control shadow-none">
                            <span class="input-group-text bg-white border-start-0" id="toggleLoginPassword" style="cursor: pointer;">
                                <i class="bi bi-eye-slash fs-5"></i>
                            </span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="rememberMe">
                            <label class="form-check-label" for="rememberMe">Remember me</label>
                        </div>
                        <button type="button" class="btn text-secondary text-decoration-none shadow-none p-0" data-bs-toggle="modal" data-bs-target="#forgotModal" data-bs-dismiss="modal">
                            Forgot Password?
                        </button>
                    </div>
                    <button type="submit" class="btn btn-dark shadow-none w-100">LOGIN</button>

                    <!-- ─── Add this ─── -->
                    <div class="d-flex align-items-center my-3">
                        <hr class="flex-grow-1">
                        <span class="mx-2 text-muted small">or</span>
                        <hr class="flex-grow-1">
                    </div>
                    <a href="ajax/user_google_auth.php?action=login" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2">
                        <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="20px">
                        Login with Google
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Register Modal -->
<div class="modal fade" id="registerModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center" id="registerModalLabel">
                    <i class="bi bi-person-plus-fill fs-3 me-2"></i> User Registration
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="registerForm" enctype="multipart/form-data">
                    <!-- Step 1 -->
                    <div id="step1">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="regName" name="name" placeholder="Juan Dela Cruz" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="regEmail" name="email" placeholder="you@example.com" required>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="button" class="btn btn-primary w-100" id="sendCodeBtn">Send Verification Code</button>
                        </div>

                        <!-- ─── Add this ─── -->
                        <div class="d-flex align-items-center my-3">
                            <hr class="flex-grow-1">
                            <span class="mx-2 text-muted small">or</span>
                            <hr class="flex-grow-1">
                        </div>
                        <a href="ajax/user_google_auth.php?action=register" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2">
                            <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" width="20px">
                            Sign up with Google
                        </a>

                        <div id="otpSection" class="mt-4" style="display:none;">
                            <label class="form-label">Verification Code</label>
                            <div class="input-group">
                                <input type="text" class="form-control text-center" id="otpCode" name="otp" maxlength="6" placeholder="------" pattern="[0-9]{6}" inputmode="numeric" required disabled>
                                <button type="button" class="btn btn-outline-secondary" id="resendCodeBtn" style="display:none;">Resend</button>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-success w-100" id="verifyCodeBtn" style="display:none;">Verify Email</button>
                            </div>
                            <p id="otpMessage" class="mt-2 small fw-bold" style="min-height:1.5rem;"></p>
                        </div>
                    </div>
                    <!-- Step 2 -->
                    <div id="additionalFields" style="display:none;">
                        <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                            <i class="bi bi-check-circle-fill fs-4 me-2"></i>
                            <div>Email verified successfully!</div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control bg-light" id="lockedName" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control bg-light" id="lockedEmail" name="email" readonly>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" name="phonenum" placeholder="+63 9xx xxx xxxx" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Profile Picture (optional)</label>
                                <input type="file" class="form-control" name="profile" accept="image/jpeg,image/png,image/webp">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address</label>
                                <textarea class="form-control" name="address" rows="2" placeholder="Street, Barangay, City" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Postal Code</label>
                                <input type="text" class="form-control" name="pincode" placeholder="6000" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" name="dob" required>
                            </div>
                            <div class="col-md-6 position-relative">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="regPassword" name="pass" required>
                                    <span class="input-group-text bg-white border-start-0" id="toggleRegPassword" style="cursor: pointer;">
                                        <i class="bi bi-eye-slash fs-5"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6 position-relative">
                                <label class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="regCPassword" name="cpass" required>
                                    <span class="input-group-text bg-white border-start-0" id="toggleRegCPassword" style="cursor: pointer;">
                                        <i class="bi bi-eye-slash fs-5"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="card mt-4 border-0 bg-light mx-auto" style="max-width: 480px;">
                            <div class="card-body text-center">
                                <p class="fw-bold mb-3">Password must contain:</p>
                                <ul class="list-unstyled small d-inline-block text-start" id="passwordRequirements" style="max-width: 360px;">
                                    <li id="length" class="text-danger"><i class="bi bi-x-circle me-2"></i>At least 8 characters</li>
                                    <li id="lower" class="text-danger"><i class="bi bi-x-circle me-2"></i>Lowercase letter</li>
                                    <li id="upper" class="text-danger"><i class="bi bi-x-circle me-2"></i>Uppercase letter</li>
                                    <li id="number" class="text-danger"><i class="bi bi-x-circle me-2"></i>Number</li>
                                    <li id="special" class="text-danger"><i class="bi bi-x-circle me-2"></i>Special character</li>
                                </ul>
                            </div>
                        </div>
                        <div class="form-check mt-4 d-flex align-items-center justify-content-center gap-2">
                            <input class="form-check-input mt-0" type="checkbox" id="agreeTerms" name="agree_terms" required>
                            <label class="form-check-label mb-0" for="agreeTerms" style="font-size: 0.95rem;">
                                I agree to the 
                                <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal" class="text-primary">Terms and Conditions</a> 
                                and 
                                <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal" class="text-primary">Privacy Policy</a>
                            </label>
                        </div>
                        <button type="submit" id="finalRegisterBtn" class="btn btn-primary w-100 mt-4" disabled>
                            Complete Registration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Forgot Password Modal -->
<div class="modal fade" id="forgotModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="forgot-form">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="bi bi-person-circle fs-3 me-2"></i> Forgot Password
                    </h5>
                </div>
                <div class="modal-body">
                    <span class="badge rounded-pill bg-light text-dark mb-3 text-wrap lh-base">
                        Note: A link will be sent to your email to reset your password!
                    </span>
                    <div class="mb-4">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" required class="form-control shadow-none">
                    </div>
                    <div class="mb-2 text-end">
                        <button type="button" class="btn shadow-none p-0 me-2" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">CANCEL</button>
                        <button type="submit" class="btn btn-dark shadow-none">SEND LINK</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Terms Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Terms and Conditions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="font-size: 0.96rem; line-height: 1.65;">
        <p class="text-center fw-bold mb-4"><strong>Last updated: February 2026</strong></p>
        <p class="text-center">Welcome to Bayawan Mini Hotel. By using our website and services, you agree to be bound by the following terms and conditions:</p>
        <h6 class="mt-4">1. Booking & Payment</h6>
        <ul>
          <li>All rates are in Philippine Pesos (&#8369;) and include applicable taxes unless stated otherwise.</li>
          <li>A valid government-issued ID is required upon check-in.</li>
          <li>Full payment is required at check-in. We accept cash and major credit/debit cards.</li>
        </ul>
        <h6 class="mt-4">2. Cancellation Policy</h6>
        <ul>
          <li>Free cancellation up to 48 hours before arrival.</li>
          <li>Cancellations within 48 hours or no-shows will be charged one night.</li>
        </ul>
        <h6 class="mt-4">3. Check-in / Check-out</h6>
        <p>Check-in: 2:00 PM | Check-out: 12:00 PM (noon)</p>
        <h6 class="mt-4">4. House Rules</h6>
        <ul>
          <li>No smoking inside rooms.</li>
          <li>No pets allowed.</li>
          <li>Quiet hours: 10:00 PM – 7:00 AM</li>
          <li>Maximum occupancy per room type must be respected.</li>
        </ul>
        <p class="text-center text-muted mt-4 small">Bayawan Mini Hotel reserves the right to modify these terms at any time.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Privacy Policy Modal -->
<div class="modal fade" id="privacyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Privacy Policy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-size: 0.96rem; line-height: 1.65;">
                <p class="text-center fw-bold mb-4"><strong>Effective Date: March 2026</strong></p>
                <p class="text-center">At Bayawan Mini Hotel, we respect your privacy and are committed to protecting your personal information.</p>
                <h6 class="mt-4">1. Information We Collect</h6>
                <ul>
                    <li>Name, email, phone number, address</li>
                    <li>Booking dates and preferences</li>
                    <li>Profile picture (optional)</li>
                    <li>Payment information (processed securely by third-party providers)</li>
                </ul>
                <h6 class="mt-4">2. How We Use Your Information</h6>
                <ul>
                    <li>To process and confirm your booking</li>
                    <li>To communicate with you about your reservation</li>
                    <li>To improve our services and website</li>
                    <li>For security and fraud prevention</li>
                </ul>
                <p class="text-center mt-4">We do not sell your personal information to third parties.</p>
                <p class="text-center text-muted small mt-4">For any privacy-related questions, please contact us at cebu.mini.hotel.cmh@gmail.com</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>