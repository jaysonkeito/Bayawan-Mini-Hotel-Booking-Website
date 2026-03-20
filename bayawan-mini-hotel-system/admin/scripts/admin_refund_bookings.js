/* bayawan-mini-hotel-system/admin/scripts/admin_refund_bookings.js */
function get_bookings(search = '') {
    let xhr = new XMLHttpRequest();
    xhr.open('POST', 'ajax/admin_refund_bookings.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onload = function () {
        document.getElementById('table-data').innerHTML = this.responseText;
    };

    xhr.send('get_bookings&search=' + encodeURIComponent(search));
}

function refund_booking(id) {
    if (!confirm('Process this refund via PayMongo?\n\nThis will immediately submit a refund request to PayMongo. The amount will be returned to the customer\'s original payment method.')) {
        return;
    }

    // Disable the clicked button to prevent double-submission
    let btn = document.querySelector(`button[onclick="refund_booking(${id})"]`);
    if (btn) {
        btn.disabled    = true;
        btn.innerHTML   = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';
    }

    let data = new FormData();
    data.append('booking_id',     id);
    data.append('refund_booking', '');

    let xhr = new XMLHttpRequest();
    xhr.open('POST', 'ajax/admin_refund_bookings.php', true);

    xhr.onload = function () {
        let result;

        try {
            result = JSON.parse(this.responseText);
        } catch (e) {
            alert('error', 'Unexpected server response. Please try again.');
            if (btn) {
                btn.disabled  = false;
                btn.innerHTML = '<i class="bi bi-cash-stack"></i> Refund via PayMongo';
            }
            return;
        }

        if (result.status === 'success') {
            // Show success with the PayMongo refund ID for reference
            let extra = result.refund_id
                ? `\n\nPayMongo Refund ID: ${result.refund_id}\nStatus: ${result.refund_status}`
                : '';

            alert('success', `Refund submitted successfully!${extra ? ' Refund ID: ' + result.refund_id : ''}`);
            get_bookings(); // refresh the table — row should disappear

        } else {
            // Show the PayMongo error detail to the admin
            alert('error', result.message || 'Refund failed. Please try again or use the PayMongo dashboard.');

            // Re-enable the button so the admin can retry or investigate
            if (btn) {
                btn.disabled  = false;
                btn.innerHTML = '<i class="bi bi-cash-stack"></i> Refund via PayMongo';
            }
        }
    };

    xhr.onerror = function () {
        alert('error', 'Network error. Please check your connection and try again.');
        if (btn) {
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-cash-stack"></i> Refund via PayMongo';
        }
    };

    xhr.send(data);
}

window.onload = function () {
    get_bookings();
};