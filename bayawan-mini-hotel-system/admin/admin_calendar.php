<?php
// bayawan-mini-hotel-system/admin/admin_calendar.php
require('includes/admin_essentials.php');
require('includes/admin_configuration.php');
adminLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel - Booking Calendar</title>
  <?php require('includes/admin_links.php'); ?>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
  <style>
    #calendar-wrap { background:#fff; border-radius:8px; padding:24px; box-shadow:0 1px 4px rgba(0,0,0,.08); }
    .fc-event { cursor:pointer; font-size:12px; }
    .fc .fc-toolbar-title { font-size:1.1rem; font-weight:600; }

    /* Legend */
    .cal-legend { display:flex; flex-wrap:wrap; gap:12px; margin-bottom:16px; }
    .cal-legend span { display:flex; align-items:center; gap:6px; font-size:13px; }
    .cal-legend i  { width:14px; height:14px; border-radius:3px; display:inline-block; }

    /* Modal detail rows */
    .detail-row { display:flex; justify-content:space-between; padding:7px 0; border-bottom:1px solid #f0f0f0; font-size:14px; }
    .detail-row:last-child { border-bottom:none; }
    .detail-label { color:#888; }
    .detail-value { font-weight:500; }
  </style>
</head>
<body class="bg-light">

<?php require('includes/admin_header.php'); ?>

<div id="main-content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12 p-4">

        <div class="d-flex align-items-center justify-content-between mb-4">
          <h3 class="mb-0">BOOKING CALENDAR</h3>
          <div class="d-flex gap-2">
            <select id="room-filter" class="form-select form-select-sm shadow-none" style="width:200px;" onchange="reloadCalendar()">
              <option value="">All Rooms</option>
            </select>
            <select id="status-filter" class="form-select form-select-sm shadow-none" style="width:160px;" onchange="reloadCalendar()">
              <option value="">All Statuses</option>
              <option value="booked">Booked</option>
              <option value="checked_in">Checked In</option>
              <option value="checked_out">Checked Out</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
        </div>

        <!-- Legend -->
        <div class="cal-legend mb-3">
          <span><i style="background:#198754"></i> Booked</span>
          <span><i style="background:#0dcaf0"></i> Checked In</span>
          <span><i style="background:#6c757d"></i> Checked Out</span>
          <span><i style="background:#dc3545"></i> Cancelled</span>
          <span><i style="background:#ffc107"></i> Payment Failed</span>
        </div>

        <div id="calendar-wrap">
          <div id="calendar"></div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Booking Detail Modal -->
<div class="modal fade" id="bookingDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-calendar-check me-2"></i>
          Booking Details
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="booking-detail-body">
        <div class="text-center py-3">
          <div class="spinner-border spinner-border-sm text-secondary"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm shadow-none" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php require('includes/admin_scripts.php'); ?>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script>
let calendar;

const STATUS_COLORS = {
  booked:         '#198754',
  checked_in:     '#0dcaf0',
  checked_out:    '#6c757d',
  cancelled:      '#dc3545',
  'payment failed':'#ffc107',
  expired:        '#adb5bd',
  pending:        '#6f42c1',
};

// Populate room filter on load
document.addEventListener('DOMContentLoaded', function () {

  // Load rooms for filter dropdown
  fetch('ajax/admin_calendar.php', {
    method: 'POST',
    body: new URLSearchParams({ get_rooms: 1 })
  })
  .then(r => r.json())
  .then(rooms => {
    const sel = document.getElementById('room-filter');
    rooms.forEach(r => {
      const opt = document.createElement('option');
      opt.value = r.id;
      opt.textContent = r.name;
      sel.appendChild(opt);
    });
  });

  // Init FullCalendar
  const el = document.getElementById('calendar');
  calendar = new FullCalendar.Calendar(el, {
    initialView: 'dayGridMonth',
    headerToolbar: {
      left:   'prev,next today',
      center: 'title',
      right:  'dayGridMonth,timeGridWeek,listMonth'
    },
    height: 'auto',
    eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: true },
    events: fetchEvents,
    eventClick: function(info) {
      showDetail(info.event.extendedProps.booking_id);
    },
    eventDidMount: function(info) {
      // Tooltip with guest name + room
      info.el.setAttribute('title', info.event.title);
    }
  });

  calendar.render();
});

function fetchEvents(fetchInfo, successCallback, failureCallback) {
  const room   = document.getElementById('room-filter').value;
  const status = document.getElementById('status-filter').value;

  fetch('ajax/admin_calendar.php', {
    method: 'POST',
    body: new URLSearchParams({
      get_events: 1,
      start:      fetchInfo.startStr,
      end:        fetchInfo.endStr,
      room_id:    room,
      status:     status
    })
  })
  .then(r => r.json())
  .then(data => {
    const events = data.map(b => ({
      id:    b.booking_id,
      title: b.guest + ' · ' + b.room,
      start: b.check_in,
      end:   b.check_out_exclusive,  // FullCalendar end is exclusive
      backgroundColor: STATUS_COLORS[b.status] ?? '#0d6efd',
      borderColor:     STATUS_COLORS[b.status] ?? '#0d6efd',
      extendedProps: { booking_id: b.booking_id }
    }));
    successCallback(events);
  })
  .catch(failureCallback);
}

function reloadCalendar() {
  if (calendar) calendar.refetchEvents();
}

function showDetail(booking_id) {
  document.getElementById('booking-detail-body').innerHTML =
    '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-secondary"></div></div>';

  const modal = new bootstrap.Modal(document.getElementById('bookingDetailModal'));
  modal.show();

  fetch('ajax/admin_calendar.php', {
    method: 'POST',
    body: new URLSearchParams({ get_detail: 1, booking_id })
  })
  .then(r => r.json())
  .then(b => {
    const statusColors = {
      booked: 'success', checked_in: 'info', checked_out: 'secondary',
      cancelled: 'danger', 'payment failed': 'warning', expired: 'light'
    };
    const sc = statusColors[b.booking_status] ?? 'primary';

    document.getElementById('booking-detail-body').innerHTML = `
      <div class="mb-3 text-center">
        <span class="badge bg-${sc} px-3 py-2 fs-6">${b.booking_status.toUpperCase()}</span>
      </div>
      <div class="detail-row"><span class="detail-label">Order ID</span><span class="detail-value">${b.order_id}</span></div>
      <div class="detail-row"><span class="detail-label">Guest</span><span class="detail-value">${b.user_name}</span></div>
      <div class="detail-row"><span class="detail-label">Phone</span><span class="detail-value">${b.phonenum}</span></div>
      <div class="detail-row"><span class="detail-label">Room</span><span class="detail-value">${b.room_name}</span></div>
      <div class="detail-row"><span class="detail-label">Room No.</span><span class="detail-value">${b.room_no || '—'}</span></div>
      <div class="detail-row"><span class="detail-label">Check-in</span><span class="detail-value">${b.check_in}</span></div>
      <div class="detail-row"><span class="detail-label">Check-out</span><span class="detail-value">${b.check_out}</span></div>
      <div class="detail-row"><span class="detail-label">Amount Paid</span><span class="detail-value">&#8369;${parseFloat(b.trans_amt).toLocaleString()}</span></div>
      <div class="detail-row"><span class="detail-label">Booked On</span><span class="detail-value">${b.datentime}</span></div>
    `;
  })
  .catch(() => {
    document.getElementById('booking-detail-body').innerHTML =
      '<p class="text-danger text-center">Failed to load booking details.</p>';
  });
}
</script>
</body>
</html>