<?php
  // bayawan-mini-hotel-system/admin/admin_booking_records.php

  require('includes/admin_essentials.php');
  require('includes/admin_configuration.php');
  adminOnly();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel - Bookings Records</title>
  <?php require('includes/admin_links.php'); ?>
</head>
<body class="bg-light">

  <?php require('includes/admin_header.php'); ?>

  <div id="main-content">
    <div class="container-fluid">
      <div class="row">
        <div class="col-12 p-4 overflow-hidden">
          <h3 class="mb-4">BOOKING RECORDS</h3>

          <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">

              <div class="d-flex align-items-center justify-content-between mb-4">
                <!-- IMPROVEMENT: "Showing X-Y of Z records" label -->
                <small class="text-muted" id="records-info"></small>
                <!-- IMPROVEMENT: debounced search via oninput -->
                <input type="text"
                       id="search_input"
                       oninput="debounced_search(this.value)"
                       class="form-control shadow-none w-25"
                       placeholder="Search order ID, name, phone...">
              </div>

              <div class="table-responsive">
                <table class="table table-hover border" style="min-width: 1200px;">
                  <thead>
                    <tr class="bg-dark text-light">
                      <th scope="col">#</th>
                      <th scope="col">User Details</th>
                      <th scope="col">Room Details</th>
                      <th scope="col">Booking Details</th>
                      <th scope="col">Status</th>
                      <th scope="col">Action</th>
                    </tr>
                  </thead>
                  <tbody id="table-data"></tbody>
                </table>
              </div>

              <div class="d-flex align-items-center justify-content-between mt-3">
                <small class="text-muted" id="records-info-bottom"></small>
                <nav>
                  <ul class="pagination mb-0" id="table-pagination"></ul>
                </nav>
              </div>

            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <?php require('includes/admin_scripts.php'); ?>
  <script src="scripts/admin_booking_records.js"></script>

</body>
</html>