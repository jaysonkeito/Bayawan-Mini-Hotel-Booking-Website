/* bayawan-mini-hotel-system/admin/scripts/admin_new_bookings.js */

function get_bookings(search = '') {
  let xhr = new XMLHttpRequest();
  xhr.open("POST", "ajax/admin_new_bookings.php", true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

  xhr.onload = function () {
    document.getElementById('table-data').innerHTML = this.responseText;
  };

  xhr.send('get_bookings&search=' + encodeURIComponent(search));
}

// ─── Assign Room ───────────────────────────────────────────────────────
let assign_room_form = document.getElementById('assign_room_form');

function assign_room(id) {
  assign_room_form.elements['booking_id'].value = id;
}

assign_room_form.addEventListener('submit', function (e) {
  e.preventDefault();

  let data = new FormData();
  data.append('room_no',    assign_room_form.elements['room_no'].value);
  data.append('booking_id', assign_room_form.elements['booking_id'].value);
  data.append('assign_room', '');

  let xhr = new XMLHttpRequest();
  xhr.open("POST", "ajax/admin_new_bookings.php", true);

  xhr.onload = function () {
    bootstrap.Modal.getInstance(document.getElementById('assign-room'))?.hide();

    if (this.responseText == 1) {
      alert('success', 'Room Number Alloted! Booking Finalized!');
      assign_room_form.reset();
      get_bookings();
    } else {
      alert('error', 'Server Down!');
    }
  };

  xhr.send(data);
});

// ─── Cancel Booking (admin — always full refund) ───────────────────────
function cancel_booking(id) {
  if (confirm("Are you sure you want to cancel this booking?\n\nAdmin-initiated cancellations always apply a full refund.")) {
    let data = new FormData();
    data.append('booking_id',    id);
    data.append('cancel_booking', '');

    let xhr = new XMLHttpRequest();
    xhr.open("POST", "ajax/admin_new_bookings.php", true);

    xhr.onload = function () {
      if (this.responseText == 1) {
        alert('success', 'Booking Cancelled! Full refund will be processed.');
        get_bookings();
      } else {
        alert('error', 'Server Down!');
      }
    };

    xhr.send(data);
  }
}

// ─── Mark No-Show ──────────────────────────────────────────────────────
function mark_no_show(id) {
  if (confirm("Mark this guest as NO-SHOW?\n\nThe first night charge will be forfeited and no refund will be issued.")) {
    let data = new FormData();
    data.append('booking_id',  id);
    data.append('mark_no_show', '');

    let xhr = new XMLHttpRequest();
    xhr.open("POST", "ajax/admin_new_bookings.php", true);

    xhr.onload = function () {
      if (this.responseText == 1) {
        alert('success', 'Guest marked as No-Show. First night forfeited.');
        get_bookings();
      } else {
        alert('error', 'Server Down!');
      }
    };

    xhr.send(data);
  }
}

// ─── Init ──────────────────────────────────────────────────────────────
window.onload = function () {
  get_bookings();
};