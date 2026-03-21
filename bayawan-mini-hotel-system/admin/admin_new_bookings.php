<?php
  // bayawan-mini-hotel-system/admin/admin_new_bookings.php
  require('includes/admin_essentials.php');
  require('includes/admin_configuration.php');
  adminLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel - New Bookings</title>
  <?php require('includes/admin_links.php'); ?>
  <style>
    .room-btn {
      width: 80px;
      height: 60px;
      font-size: 13px;
      font-weight: 600;
      border-radius: 8px;
      transition: all 0.2s;
    }
    .room-btn.available {
      background-color: #fff;
      border: 2px solid #198754;
      color: #198754;
      cursor: pointer;
    }
    .room-btn.available:hover {
      background-color: #198754;
      color: #fff;
    }
    .room-btn.available.selected {
      background-color: #198754;
      color: #fff;
      box-shadow: 0 0 0 3px rgba(25,135,84,0.3);
    }
    .room-btn.occupied {
      background-color: #f8d7da;
      border: 2px solid #dc3545;
      color: #dc3545;
      cursor: not-allowed;
      opacity: 0.7;
    }
  </style>
</head>
<body class="bg-light">

  <?php require('includes/admin_header.php'); ?>

  <div id="main-content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12 p-4 overflow-hidden">
          <h3 class="mb-4">NEW BOOKINGS</h3>

          <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
              <div class="text-end mb-4">
                <input type="text" oninput="get_bookings(this.value)" class="form-control shadow-none w-25 ms-auto" placeholder="Type to search...">
              </div>
              <div class="table-responsive">
                <table class="table table-hover border" style="min-width: 1200px;">
                  <thead>
                    <tr class="bg-dark text-light">
                      <th scope="col">#</th>
                      <th scope="col">User Details</th>
                      <th scope="col">Room Details</th>
                      <th scope="col">Bookings Details</th>
                      <th scope="col">Action</th>
                    </tr>
                  </thead>
                  <tbody id="table-data"></tbody>
                </table>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- ── Assign Room Modal ── -->
  <div class="modal fade" id="assign-room" data-bs-backdrop="static" data-bs-keyboard="true" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="bi bi-door-open me-2"></i>Assign Room
          </h5>
        </div>
        <div class="modal-body">

          <!-- Room type label -->
          <p class="mb-1 text-muted small">Room Type</p>
          <h6 class="fw-bold mb-3" id="assign-room-type">—</h6>

          <!-- Loading spinner -->
          <div id="room-btn-loader" class="text-center py-3">
            <div class="spinner-border text-secondary" role="status"></div>
            <p class="text-muted small mt-2">Loading available rooms...</p>
          </div>

          <!-- Legend -->
          <div id="room-legend" class="d-flex gap-3 mb-3 d-none" style="font-size:13px;">
            <span><span class="d-inline-block rounded me-1" style="width:14px;height:14px;background:#fff;border:2px solid #198754;"></span> Available</span>
            <span><span class="d-inline-block rounded me-1" style="width:14px;height:14px;background:#f8d7da;border:2px solid #dc3545;"></span> Occupied</span>
            <span><span class="d-inline-block rounded me-1" style="width:14px;height:14px;background:#198754;"></span> Selected</span>
          </div>

          <!-- Room number buttons grid -->
          <div id="room-btn-grid" class="d-flex flex-wrap gap-2 mb-3 d-none"></div>

          <!-- Selected room display -->
          <div id="selected-room-display" class="alert alert-success py-2 d-none" style="font-size:13px;">
            <i class="bi bi-check-circle me-1"></i>
            Selected: <strong id="selected-room-label"></strong>
          </div>

          <p class="text-muted small mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Assign a room number only when the guest has physically arrived.
          </p>

          <!-- Hidden inputs -->
          <input type="hidden" id="assign-booking-id">
          <input type="hidden" id="assign-room-no">

        </div>
        <div class="modal-footer">
          <button type="button" class="btn text-secondary shadow-none" data-bs-dismiss="modal" onclick="reset_assign_modal()">CANCEL</button>
          <button type="button" id="assign-confirm-btn" class="btn custom-bg text-white shadow-none" disabled onclick="do_assign_room()">
            ASSIGN
          </button>
        </div>
      </div>
    </div>
  </div>

  <?php require('includes/admin_scripts.php'); ?>
  <script src="scripts/admin_new_bookings.js"></script>

</body>
</html>