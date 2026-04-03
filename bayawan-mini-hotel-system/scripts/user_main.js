/* bayawan-mini-hotel-system/scripts/user_main.js */

/**
 * Called by the "Book Now" button on each room card.
 *
 * FIX: When the user is NOT logged in, instead of showing a plain alert(),
 * we now open the Bootstrap Login Modal directly so they can log in
 * right away without leaving the page.
 *
 * If the user logs in successfully (handled by user_login_register.js),
 * they are then redirected to user_confirm_booking.php?id=<room_id>.
 * We store the intended room_id in sessionStorage so the login handler
 * can pick it up and redirect after a successful login.
 *
 * @param {number} status  - 1 if logged in, 0 if not
 * @param {number} room_id - The room's id from the DB
 */
function checkLoginToBook(status, room_id) {
    if (status) {
        window.location.href = 'user_confirm_booking.php?id=' + room_id;
    } else {
        // Save intended destination so login handler can redirect after login
        sessionStorage.setItem('redirectAfterLogin', 'user_confirm_booking.php?id=' + room_id);

        // Show a friendly toast/inline message above the modal (optional nicety)
        const toastEl = document.getElementById('bmh-login-toast');
        if (toastEl) {
            toastEl.querySelector('.toast-body').textContent =
                'Please log in first to book a room.';
            const toast = new bootstrap.Toast(toastEl, { delay: 3500 });
            toast.show();
        }

        // Open the Login Modal
        const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
        loginModal.show();
    }
}