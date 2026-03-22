/* bayawan-mini-hotel-system/scripts/session_timeout.js

 *
 * Usage:
 *   initSessionTimeout({
 *     checkUrl:   'ajax/user_session_check.php',  // endpoint to ping
 *     logoutUrl:  'user_logout.php',              // redirect on timeout
 *     checkEvery: 60,                             // ping interval in seconds
 *   });
 */

function initSessionTimeout(config) {

    const CHECK_URL   = config.checkUrl;
    const LOGOUT_URL  = config.logoutUrl;
    const CHECK_EVERY = (config.checkEvery || 60) * 1000; // ms

    let countdownInterval = null;
    let warningShown      = false;
    let modal             = null;
    let modalEl           = null;

    // ── Create warning modal ───────────────────────────────────────────
    function createModal() {
        const div = document.createElement('div');
        div.innerHTML = `
        <div class="modal fade" id="sessionTimeoutModal"
             data-bs-backdrop="static" data-bs-keyboard="false"
             tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg">
                    <div class="modal-header border-0 pb-0">
                        <div class="w-100 text-center pt-2">
                            <div style="font-size:2.5rem;">⏱️</div>
                            <h5 class="modal-title fw-bold mt-2">Session Expiring Soon</h5>
                        </div>
                    </div>
                    <div class="modal-body text-center px-4 pb-2">
                        <p class="text-muted mb-3">
                            You have been inactive for a while.<br>
                            You will be automatically logged out in:
                        </p>
                        <div id="session-countdown-display"
                             style="font-size:3rem;font-weight:bold;color:#dc3545;line-height:1;">
                            60
                        </div>
                        <p class="text-muted mt-1 small">seconds</p>
                    </div>
                    <div class="modal-footer border-0 justify-content-center pb-4">
                        <button type="button"
                                id="session-stay-btn"
                                class="btn text-white px-5 shadow-none"
                                style="background:#2ec1ac;border-color:#2ec1ac;">
                            Stay Logged In
                        </button>
                        <a href="${LOGOUT_URL}"
                           class="btn btn-outline-secondary px-4 shadow-none">
                            Logout Now
                        </a>
                    </div>
                </div>
            </div>
        </div>`;
        document.body.appendChild(div);

        modalEl = document.getElementById('sessionTimeoutModal');
        modal   = new bootstrap.Modal(modalEl);

        document.getElementById('session-stay-btn').addEventListener('click', extendSession);
    }

    // ── Show warning modal with countdown ─────────────────────────────
    function showWarning(remaining) {
        if (warningShown) return;
        warningShown = true;

        if (!modalEl) createModal();
        modal.show();

        let secs = Math.max(1, Math.round(remaining));
        document.getElementById('session-countdown-display').textContent = secs;

        countdownInterval = setInterval(() => {
            secs--;
            const el = document.getElementById('session-countdown-display');
            if (el) el.textContent = Math.max(0, secs);

            if (secs <= 0) {
                clearInterval(countdownInterval);
                doLogout();
            }
        }, 1000);
    }

    // ── Hide warning modal ─────────────────────────────────────────────
    function hideWarning() {
        if (!warningShown) return;
        warningShown = false;
        clearInterval(countdownInterval);
        if (modal) modal.hide();
    }

    // ── Extend session ─────────────────────────────────────────────────
    function extendSession() {
        fetch(CHECK_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'extend_session=1',
        })
        .then(r => r.text())
        .then(text => {
            const raw  = text.substring(text.indexOf('{'));
            const data = JSON.parse(raw);

            if (data.status === 'extended') {
                hideWarning();
            } else {
                doLogout();
            }
        })
        .catch(() => hideWarning()); // network error — don't log out
    }

    // ── Logout ─────────────────────────────────────────────────────────
    function doLogout() {
        window.location.href = LOGOUT_URL;
    }

    // ── Ping server every CHECK_EVERY ms ──────────────────────────────
    function checkSession() {
        fetch(CHECK_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'check_session=1',
        })
        .then(r => r.text())
        .then(text => {
            const raw  = text.substring(text.indexOf('{'));
            const data = JSON.parse(raw);

            if (data.status === 'expired' || data.status === 'logged_out') {
                doLogout();
            } else if (data.status === 'warning') {
                showWarning(data.remaining);
            } else if (data.status === 'active') {
                hideWarning();
            }
        })
        .catch(() => {}); // network error — keep session alive
    }

    // ── Update last_activity on user interaction ───────────────────────
    // Reset activity on mouse move, click, keypress, scroll
    let activityDebounce = null;

    function onUserActivity() {
        clearTimeout(activityDebounce);
        activityDebounce = setTimeout(() => {
            // Only ping if warning is not shown — avoid spamming
            if (!warningShown) {
                fetch(CHECK_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'extend_session=1',
                }).catch(() => {});
            }
        }, 5000); // debounce 5 seconds
    }

    ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(evt => {
        document.addEventListener(evt, onUserActivity, { passive: true });
    });

    // ── Start polling ──────────────────────────────────────────────────
    // Initial check after 1 minute, then every CHECK_EVERY
    setTimeout(() => {
        checkSession();
        setInterval(checkSession, CHECK_EVERY);
    }, CHECK_EVERY);
}