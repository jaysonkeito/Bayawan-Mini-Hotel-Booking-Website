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


// ─── Assign Room — Open Modal with Dynamic Room Buttons ───────────────
function open_assign_modal(bookingId, roomId, roomName, checkIn, checkOut) {

  // Store booking id for later submission
  document.getElementById('assign-booking-id').value = bookingId;
  document.getElementById('assign-room-no').value    = '';
  document.getElementById('assign-room-type').innerText = roomName;

  // Reset modal state
  document.getElementById('room-btn-grid').innerHTML    = '';
  document.getElementById('room-btn-grid').classList.add('d-none');
  document.getElementById('room-legend').classList.add('d-none');
  document.getElementById('room-btn-loader').classList.remove('d-none');
  document.getElementById('selected-room-display').classList.add('d-none');
  document.getElementById('assign-confirm-btn').disabled = true;

  // Show the modal
  new bootstrap.Modal(document.getElementById('assign-room')).show();

  // Fetch available room numbers from server
  const data = new FormData();
  data.append('get_available_rooms', '');
  data.append('booking_id', bookingId);
  data.append('room_id',    roomId);
  data.append('check_in',   checkIn);
  data.append('check_out',  checkOut);

  fetch('ajax/admin_new_bookings.php', { method: 'POST', body: data })
    .then(r => r.text())
    .then(text => {
      const raw = text.substring(text.indexOf('{'));
      const res = JSON.parse(raw);

      document.getElementById('room-btn-loader').classList.add('d-none');

      if (res.status !== 'success') {
        document.getElementById('room-btn-grid').innerHTML =
          '<p class="text-danger">Could not load room data.</p>';
        document.getElementById('room-btn-grid').classList.remove('d-none');
        return;
      }

      // Build room number buttons
      const grid     = document.getElementById('room-btn-grid');
      const occupied = res.occupied || [];

      for (let i = 1; i <= res.quantity; i++) {
        const label     = `Room #${i}`;
        const isOccupied = occupied.includes(label);

        const btn = document.createElement('button');
        btn.type      = 'button';
        btn.className = `room-btn ${isOccupied ? 'occupied' : 'available'}`;
        btn.innerHTML = `<i class="bi bi-door-${isOccupied ? 'closed' : 'open'} d-block mb-1"></i>${label}`;
        btn.title     = isOccupied ? 'Already occupied on these dates' : `Select ${label}`;
        btn.disabled  = isOccupied;

        if (!isOccupied) {
          btn.addEventListener('click', function () {
            // Deselect all
            document.querySelectorAll('.room-btn.available').forEach(b => b.classList.remove('selected'));
            // Select this one
            this.classList.add('selected');
            // Store selected room number
            document.getElementById('assign-room-no').value = label;
            // Show selected display
            document.getElementById('selected-room-label').innerText = label;
            document.getElementById('selected-room-display').classList.remove('d-none');
            // Enable assign button
            document.getElementById('assign-confirm-btn').disabled = false;
          });
        }

        grid.appendChild(btn);
      }

      grid.classList.remove('d-none');
      document.getElementById('room-legend').classList.remove('d-none');
    })
    .catch(() => {
      document.getElementById('room-btn-loader').classList.add('d-none');
      document.getElementById('room-btn-grid').innerHTML =
        '<p class="text-danger">Connection error. Please try again.</p>';
      document.getElementById('room-btn-grid').classList.remove('d-none');
    });
}

// ─── Do Assign Room ────────────────────────────────────────────────────
function do_assign_room() {
  const roomNo    = document.getElementById('assign-room-no').value;
  const bookingId = document.getElementById('assign-booking-id').value;

  if (!roomNo) {
    show_alert('error', 'Please select a room number first!');
    return;
  }

  const btn      = document.getElementById('assign-confirm-btn');
  btn.disabled   = true;
  btn.innerHTML  = '<span class="spinner-border spinner-border-sm me-1"></span> Assigning...';

  const data = new FormData();
  data.append('assign_room', '');
  data.append('room_no',     roomNo);
  data.append('booking_id',  bookingId);

  fetch('ajax/admin_new_bookings.php', { method: 'POST', body: data })
    .then(r => r.text())
    .then(res => {
      bootstrap.Modal.getInstance(document.getElementById('assign-room'))?.hide();
      reset_assign_modal();

      if (res.trim() == '1') {
        show_alert('success', `${roomNo} assigned! Booking finalized!`);
        get_bookings();
      } else {
        show_alert('error', 'Assignment failed. Please try again.');
      }
    })
    .catch(() => {
      show_alert('error', 'Connection error. Please try again.');
      btn.disabled  = false;
      btn.innerHTML = 'ASSIGN';
    });
}

// ─── Reset Modal State ─────────────────────────────────────────────────
function reset_assign_modal() {
  document.getElementById('assign-booking-id').value = '';
  document.getElementById('assign-room-no').value    = '';
  document.getElementById('assign-room-type').innerText = '—';
  document.getElementById('room-btn-grid').innerHTML  = '';
  document.getElementById('room-btn-grid').classList.add('d-none');
  document.getElementById('room-legend').classList.add('d-none');
  document.getElementById('room-btn-loader').classList.remove('d-none');
  document.getElementById('selected-room-display').classList.add('d-none');
  document.getElementById('assign-confirm-btn').disabled  = true;
  document.getElementById('assign-confirm-btn').innerHTML = 'ASSIGN';
}


// ─── Cancel Booking (admin — always full refund) ───────────────────────
function cancel_booking(id) {
  if (confirm("Are you sure you want to cancel this booking?\n\nAdmin-initiated cancellations always apply a full refund.")) {
    const data = new FormData();
    data.append('booking_id',     id);
    data.append('cancel_booking', '');

    fetch('ajax/admin_new_bookings.php', { method: 'POST', body: data })
      .then(r => r.text())
      .then(res => {
        if (res.trim() == '1') {
          show_alert('success', 'Booking Cancelled! Full refund will be processed.');
          get_bookings();
        } else {
          show_alert('error', 'Server Down!');
        }
      });
  }
}


// ─── Mark No-Show ──────────────────────────────────────────────────────
function mark_no_show(id) {
  if (confirm("Mark this guest as NO-SHOW?\n\nThe first night charge will be forfeited and no refund will be issued.")) {
    const data = new FormData();
    data.append('booking_id',   id);
    data.append('mark_no_show', '');

    fetch('ajax/admin_new_bookings.php', { method: 'POST', body: data })
      .then(r => r.text())
      .then(res => {
        if (res.trim() == '1') {
          show_alert('success', 'Guest marked as No-Show. First night forfeited.');
          get_bookings();
        } else {
          show_alert('error', 'Server Down!');
        }
      });
  }
}


// ─── Init ──────────────────────────────────────────────────────────────
window.onload = function () {
  get_bookings();
};



