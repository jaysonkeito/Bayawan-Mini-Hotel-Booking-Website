/*bayawan-mini-hotel-system/scripts/user_login_register.js */
console.log("user_login_register.js → loaded");

// ──────────────── Password Visibility Toggle ────────────────
function togglePasswordVisibility(inputId, toggleId) {
    const input  = document.getElementById(inputId);
    const toggle = document.getElementById(toggleId);
    const icon   = toggle?.querySelector('i.bi');

    if (!input || !toggle || !icon) return;

    const newToggle = toggle.cloneNode(true);
    toggle.parentNode.replaceChild(newToggle, toggle);

    newToggle.addEventListener('click', function() {
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        newToggle.querySelector('i.bi').className = isPassword ? 'bi bi-eye fs-5' : 'bi bi-eye-slash fs-5';
    });
}

// ──────────────── Password Strength Checker ────────────────
function updatePasswordStrength() {
    const pwd = document.getElementById('regPassword');
    if (!pwd) return;

    const val = pwd.value;
    const checks = {
        length:  val.length >= 8,
        lower:   /[a-z]/.test(val),
        upper:   /[A-Z]/.test(val),
        number:  /[0-9]/.test(val),
        special: /[!@#$%^&*(),.?":{}|<>]/.test(val)
    };

    for (let key in checks) {
        const el = document.getElementById(key);
        if (!el) continue;
        const valid = checks[key];
        el.classList.toggle('text-success', valid);
        el.classList.toggle('text-danger',  !valid);
        const icon = el.querySelector('i.bi');
        if (icon) {
            icon.className = valid ? 'bi bi-check-circle me-2' : 'bi bi-x-circle me-2';
        }
    }

    updateRegisterButton();
}

// ──────────────── Enable/Disable Register Button ────────────────
function updateRegisterButton() {
    const pwd   = document.getElementById('regPassword')?.value  || '';
    const cpwd  = document.getElementById('regCPassword')?.value || '';
    const agree = document.getElementById('agreeTerms')?.checked ?? false;
    const btn   = document.getElementById('finalRegisterBtn');

    if (!btn) return;

    const passwordsMatch = pwd === cpwd && pwd.length > 0;
    const isStrong =
        pwd.length >= 8 &&
        /[a-z]/.test(pwd) &&
        /[A-Z]/.test(pwd) &&
        /[0-9]/.test(pwd) &&
        /[!@#$%^&*(),.?":{}|<>]/.test(pwd);

    const canSubmit = passwordsMatch && isStrong && agree;
    btn.disabled = !canSubmit;
    btn.classList.toggle('btn-primary',   canSubmit);
    btn.classList.toggle('btn-secondary', !canSubmit);
}

// ──────────────── DOM Ready ────────────────
document.addEventListener('DOMContentLoaded', function () {

    // Password strength listeners
    const pwdInput  = document.getElementById('regPassword');
    const cpwdInput = document.getElementById('regCPassword');
    const agreeChk  = document.getElementById('agreeTerms');

    if (pwdInput)  pwdInput.addEventListener('input', updatePasswordStrength);
    if (cpwdInput) cpwdInput.addEventListener('input', updatePasswordStrength);
    if (agreeChk)  agreeChk.addEventListener('change', updateRegisterButton);

    updatePasswordStrength();

    // Initialize toggles
    togglePasswordVisibility('loginPassword',  'toggleLoginPassword');
    togglePasswordVisibility('regPassword',    'toggleRegPassword');
    togglePasswordVisibility('regCPassword',   'toggleRegCPassword');

    // ─── Login Modal ───
    const loginModalEl = document.getElementById('loginModal');
    if (loginModalEl) {
        loginModalEl.addEventListener('shown.bs.modal', function () {
            togglePasswordVisibility('loginPassword', 'toggleLoginPassword');
            document.querySelector('#login-form input[name="email_mob"]')?.focus();
        });
    }

    // ─── Register Modal ───
    const registerModal = document.getElementById('registerModal');
    if (registerModal) {
        registerModal.addEventListener('shown.bs.modal', function () {
            togglePasswordVisibility('regPassword',  'toggleRegPassword');
            togglePasswordVisibility('regCPassword', 'toggleRegCPassword');
        });
    }

    // ─── Login Form Submit ───
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'login');

            fetch('ajax/user_login_register.php', { method: 'POST', body: formData })
            .then(r => r.text())
            .then(text => {
                const resp = text.trim();
                if (resp === 'success') {
                    window.location.reload();
                } else {
                    setTimeout(() => alert('error', resp || 'Login failed. Please try again.'), 50);
                }
            })
            .catch(() => setTimeout(() => alert('error', 'Connection error. Please try again.'), 50));
        });
    }

    // ─── Register Form Submit ───
    const regForm = document.getElementById('registerForm');
    if (regForm) {
        regForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'complete_register');

            fetch('ajax/user_login_register.php', { method: 'POST', body: formData })
            .then(r => r.text())
            .then(resp => {
                const t = resp.trim();
                if (t === 'success') {
                    setTimeout(() => alert('success', 'Registration successful! You can now log in.'), 50);
                    bootstrap.Modal.getInstance(document.getElementById('registerModal'))?.hide();
                    new bootstrap.Modal(document.getElementById('loginModal')).show();
                } else {
                    setTimeout(() => alert('error', t || 'Registration failed. Please try again.'), 50);
                }
            })
            .catch(() => setTimeout(() => alert('error', 'Connection error. Please try again.'), 50));
        });
    }

    // ─── Send OTP & Verify OTP ───
    const registerModalEl = document.getElementById('registerModal');
    if (registerModalEl) {
        registerModalEl.addEventListener('shown.bs.modal', function () {

            // Send OTP
            const sendBtn = document.getElementById('sendCodeBtn');
            if (sendBtn) {
                const newSendBtn = sendBtn.cloneNode(true);
                sendBtn.parentNode.replaceChild(newSendBtn, sendBtn);

                newSendBtn.addEventListener('click', function () {
                    const name  = document.getElementById('regName')?.value.trim();
                    const email = document.getElementById('regEmail')?.value.trim();
                    const msg   = document.getElementById('otpMessage');

                    if (!name || !email || !/\S+@\S+\.\S+/.test(email)) {
                        msg.className   = 'mt-2 small text-danger';
                        msg.textContent = 'Please enter a valid name and email';
                        return;
                    }

                    msg.className          = 'mt-2 small text-muted';
                    msg.textContent        = 'Sending code... Please wait';
                    newSendBtn.disabled    = true;
                    newSendBtn.textContent = 'Sending...';

                    fetch('ajax/user_login_register.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=send_otp_register&name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}`
                    })
                    .then(r => r.text())
                    .then(text => {
                        const trimmed = text.trim();
                        if (trimmed === 'OTP sent') {
                            msg.className   = 'mt-2 small text-success';
                            msg.textContent = 'Verification code sent! Check your email.';
                            document.getElementById('otpSection').style.display    = 'block';
                            newSendBtn.style.display                               = 'none';
                            document.getElementById('otpCode').disabled            = false;
                            document.getElementById('otpCode').focus();
                            document.getElementById('resendCodeBtn').style.display = 'inline-block';
                            document.getElementById('verifyCodeBtn').style.display = 'block';
                        } else if (trimmed.includes('already registered') || trimmed.includes('This email is already')) {
                            msg.className   = 'mt-2 fw-bold text-danger';
                            msg.textContent = trimmed;
                            setTimeout(() => alert('error', trimmed), 50);
                            document.getElementById('regEmail')?.focus();
                        } else {
                            msg.className   = 'mt-2 small text-danger';
                            msg.textContent = trimmed || 'Failed to send code. Please try again.';
                            setTimeout(() => alert('error', trimmed || 'Failed to send code. Please try again.'), 50);
                        }
                    })
                    .catch(err => {
                        msg.className   = 'mt-2 small text-danger';
                        msg.textContent = 'Failed to connect. Check console.';
                        console.error(err);
                    })
                    .finally(() => {
                        newSendBtn.disabled    = false;
                        newSendBtn.textContent = 'Send Verification Code';
                    });
                });
            }

            // Verify OTP
            const verifyBtn = document.getElementById('verifyCodeBtn');
            if (verifyBtn) {
                const newVerifyBtn = verifyBtn.cloneNode(true);
                verifyBtn.parentNode.replaceChild(newVerifyBtn, verifyBtn);

                newVerifyBtn.addEventListener('click', function () {
                    const otp = document.getElementById('otpCode')?.value.trim();
                    const msg = document.getElementById('otpMessage');

                    if (!otp || otp.length !== 6 || !/^\d{6}$/.test(otp)) {
                        msg.className   = 'mt-2 small text-danger';
                        msg.textContent = 'Please enter a valid 6-digit code';
                        return;
                    }

                    msg.className            = 'mt-2 small text-muted';
                    msg.textContent          = 'Verifying code...';
                    newVerifyBtn.disabled    = true;
                    newVerifyBtn.textContent = 'Verifying...';

                    fetch('ajax/user_login_register.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=verify_otp_register&otp=${encodeURIComponent(otp)}`
                    })
                    .then(r => r.text())
                    .then(resp => {
                        const t = resp.trim();
                        if (t === 'OTP verified') {
                            document.getElementById('lockedName').value  = document.getElementById('regName').value.trim();
                            document.getElementById('lockedEmail').value = document.getElementById('regEmail').value.trim();
                            document.getElementById('step1').style.display            = 'none';
                            document.getElementById('additionalFields').style.display = 'block';
                            document.querySelector('#registerModal .modal-content').scrollTop = 0;
                            msg.className   = 'mt-2 small text-success';
                            msg.textContent = 'Email verified successfully!';
                        } else {
                            msg.className   = 'mt-2 small text-danger';
                            msg.textContent = t || 'Invalid or expired code';
                        }
                    })
                    .catch(() => {
                        msg.className   = 'mt-2 small text-danger';
                        msg.textContent = 'Network error – please try again';
                    })
                    .finally(() => {
                        newVerifyBtn.disabled    = false;
                        newVerifyBtn.textContent = 'Verify Email';
                    });
                });
            }
        });
    }

});