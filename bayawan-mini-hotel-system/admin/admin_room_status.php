<?php
// bayawan-mini-hotel-system/admin/admin_room_status.php
require('includes/admin_essentials.php');
require('includes/admin_configuration.php');
adminLogin();

date_default_timezone_set('Asia/Manila');

// ── Fetch all active room types ───────────────────────────────
$rooms_res = select(
    "SELECT * FROM `rooms` WHERE `removed` = ? AND `status` = ? ORDER BY `name` ASC",
    [0, 1], 'ii'
);

$today    = date('Y-m-d');
$all_types = [];

while ($room = mysqli_fetch_assoc($rooms_res)) {
    $room_id  = $room['id'];
    $quantity = (int) $room['quantity'];

    // For each physical unit of this room type, determine its status
    $units = [];
    for ($unit = 1; $unit <= $quantity; $unit++) {
        $room_no    = 'Room #' . $unit;
        $unit_status = 'available';
        $guest_name  = '';
        $check_in    = '';
        $check_out   = '';
        $booking_id  = null;

        // Check if this specific room number is currently assigned
        // to an active booking that overlaps today
        $booking_q = "SELECT bo.booking_id, bo.booking_status, bo.arrival,
                             bo.check_in, bo.check_out,
                             bd.user_name, bd.room_no
                      FROM `booking_order` bo
                      INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
                      WHERE bo.room_id = ?
                        AND bd.room_no = ?
                        AND bo.booking_status IN ('booked', 'checked_in')
                        AND bo.check_in  <= ?
                        AND bo.check_out >  ?
                      ORDER BY bo.check_in ASC
                      LIMIT 1";

        $b_res  = select($booking_q, [$room_id, $room_no, $today, $today], 'isss');
        $b_data = mysqli_fetch_assoc($b_res);

        if ($b_data) {
            $guest_name = $b_data['user_name'];
            $check_in   = date('M j', strtotime($b_data['check_in']));
            $check_out  = date('M j', strtotime($b_data['check_out']));
            $booking_id = $b_data['booking_id'];

            if ($b_data['arrival'] == 1) {
                $unit_status = 'checked_in';
            } else {
                $unit_status = 'booked';
            }
        } else {
            // Check if checked out today (needs cleaning)
            $checkout_q = "SELECT bo.booking_id
                           FROM `booking_order` bo
                           INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
                           WHERE bo.room_id = ?
                             AND bd.room_no = ?
                             AND bo.booking_status IN ('booked', 'checked_in')
                             AND bo.check_out = ?
                           LIMIT 1";

            $co_res  = select($checkout_q, [$room_id, $room_no, $today], 'iss');
            $co_data = mysqli_fetch_assoc($co_res);

            if ($co_data) {
                $unit_status = 'cleaning';
                $guest_name  = 'Checked out today';
            }

            // Also check upcoming booked (arrives in future)
            $upcoming_q = "SELECT bo.booking_id, bo.check_in, bo.check_out, bd.user_name
                           FROM `booking_order` bo
                           INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
                           WHERE bo.room_id = ?
                             AND bd.room_no = ?
                             AND bo.booking_status = 'booked'
                             AND bo.arrival = 0
                             AND bo.check_in > ?
                           ORDER BY bo.check_in ASC
                           LIMIT 1";

            $up_res  = select($upcoming_q, [$room_id, $room_no, $today], 'iss');
            $up_data = mysqli_fetch_assoc($up_res);

            if ($up_data && $unit_status === 'available') {
                $unit_status = 'booked';
                $guest_name  = $up_data['user_name'];
                $check_in    = date('M j', strtotime($up_data['check_in']));
                $check_out   = date('M j', strtotime($up_data['check_out']));
                $booking_id  = $up_data['booking_id'];
            }
        }

        $units[] = [
            'unit_num'   => $unit,
            'room_no'    => $room_no,
            'status'     => $unit_status,
            'guest'      => $guest_name,
            'check_in'   => $check_in,
            'check_out'  => $check_out,
            'booking_id' => $booking_id,
        ];
    }

    // Count statuses for this room type
    $counts = array_count_values(array_column($units, 'status'));

    $all_types[] = [
        'id'        => $room_id,
        'name'      => $room['name'],
        'price'     => $room['price'],
        'quantity'  => $quantity,
        'units'     => $units,
        'available' => $counts['available'] ?? 0,
        'booked'    => $counts['booked']    ?? 0,
        'checked_in'=> $counts['checked_in']?? 0,
        'cleaning'  => $counts['cleaning']  ?? 0,
    ];
}

// ── Grand totals ──────────────────────────────────────────────
$total_available  = array_sum(array_column($all_types, 'available'));
$total_booked     = array_sum(array_column($all_types, 'booked'));
$total_checked_in = array_sum(array_column($all_types, 'checked_in'));
$total_cleaning   = array_sum(array_column($all_types, 'cleaning'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel - Room Status</title>
  <?php require('includes/admin_links.php'); ?>
  <style>
    .status-card {
      border-left: 3px solid transparent;
      border-radius: 0 8px 8px 0;
      transition: transform .15s, box-shadow .15s;
      cursor: default;
    }
    .status-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.08); }
    .status-available  { border-left-color: #639922; }
    .status-booked     { border-left-color: #378ADD; }
    .status-checked_in { border-left-color: #1D9E75; }
    .status-cleaning   { border-left-color: #BA7517; }

    .badge-available  { background:#EAF3DE; color:#27500A; }
    .badge-booked     { background:#E6F1FB; color:#0C447C; }
    .badge-checked_in { background:#E1F5EE; color:#085041; }
    .badge-cleaning   { background:#FAEEDA; color:#633806; }

    .room-unit-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:10px; }
    .summary-num { font-size:2rem; font-weight:700; line-height:1; }
    .legend-dot  { width:12px; height:12px; border-radius:3px; display:inline-block; }
    .section-header {
      display:flex; align-items:center; justify-content:space-between;
      padding:10px 0; border-bottom:1px solid #dee2e6; margin-bottom:16px;
    }
    .filter-btn { border-radius:20px !important; font-size:13px; }
    .filter-btn.active { background:#212529; color:#fff; border-color:#212529; }
    .room-type-section { display:block; }
    .room-type-section.hidden { display:none; }
  </style>
</head>
<body class="bg-light">

<?php require('includes/admin_header.php'); ?>

<div id="main-content">
  <div class="container-fluid p-4">

    <!-- Page header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
      <h3 class="mb-0">ROOM STATUS BOARD</h3>
      <span class="text-muted small">Today: <?= date('F j, Y') ?></span>
    </div>

    <!-- Summary cards -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="summary-num text-success"><?= $total_available ?></div>
            <div class="text-muted small mt-1">Available</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="summary-num text-primary"><?= $total_booked ?></div>
            <div class="text-muted small mt-1">Booked (arriving)</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="summary-num" style="color:#1D9E75"><?= $total_checked_in ?></div>
            <div class="text-muted small mt-1">Checked in</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body text-center">
            <div class="summary-num text-warning"><?= $total_cleaning ?></div>
            <div class="text-muted small mt-1">For cleaning</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Legend + Filter -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
          <!-- Legend -->
          <div class="d-flex flex-wrap gap-3">
            <span class="d-flex align-items-center gap-2 small">
              <span class="legend-dot" style="background:#639922"></span> Available
            </span>
            <span class="d-flex align-items-center gap-2 small">
              <span class="legend-dot" style="background:#378ADD"></span> Booked (arriving)
            </span>
            <span class="d-flex align-items-center gap-2 small">
              <span class="legend-dot" style="background:#1D9E75"></span> Checked in
            </span>
            <span class="d-flex align-items-center gap-2 small">
              <span class="legend-dot" style="background:#BA7517"></span> For cleaning
            </span>
          </div>
          <!-- Filter -->
          <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-sm btn-outline-dark filter-btn active" onclick="filterRooms('all', this)">
              All rooms
            </button>
            <?php foreach ($all_types as $type): ?>
            <button class="btn btn-sm btn-outline-dark filter-btn"
                    onclick="filterRooms(<?= $type['id'] ?>, this)">
              <?= htmlspecialchars($type['name']) ?>
            </button>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Room type sections -->
    <?php foreach ($all_types as $type): ?>
    <div class="card border-0 shadow-sm mb-4 room-type-section" data-type-id="<?= $type['id'] ?>">
      <div class="card-body">
        <div class="section-header">
          <div>
            <h5 class="mb-0"><?= htmlspecialchars($type['name']) ?></h5>
            <span class="text-muted small">
              &#8369;<?= number_format($type['price']) ?>/night &nbsp;&middot;&nbsp;
              <?= $type['quantity'] ?> units
            </span>
          </div>
          <div class="d-flex gap-2">
            <span class="badge badge-available rounded-pill px-3"><?= $type['available'] ?> available</span>
            <span class="badge badge-booked rounded-pill px-3"><?= $type['booked'] ?> booked</span>
            <span class="badge badge-checked_in rounded-pill px-3"><?= $type['checked_in'] ?> checked in</span>
            <?php if ($type['cleaning'] > 0): ?>
            <span class="badge badge-cleaning rounded-pill px-3"><?= $type['cleaning'] ?> cleaning</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="room-unit-grid">
          <?php foreach ($type['units'] as $unit): ?>
          <div class="card border status-card status-<?= $unit['status'] ?>">
            <div class="card-body p-3">
              <div class="fw-bold mb-1"><?= $unit['room_no'] ?></div>
              <div class="text-muted small mb-2" style="font-size:11px">
                <?= htmlspecialchars($type['name']) ?>
              </div>
              <span class="badge badge-<?= $unit['status'] ?> rounded-pill px-2 py-1" style="font-size:11px">
                <?= str_replace('_', ' ', ucfirst($unit['status'])) ?>
              </span>
              <?php if ($unit['guest']): ?>
              <div class="mt-2 text-truncate small"><?= htmlspecialchars($unit['guest']) ?></div>
              <?php endif; ?>
              <?php if ($unit['check_in'] && $unit['check_out']): ?>
              <div class="text-muted" style="font-size:11px">
                <?= $unit['check_in'] ?> – <?= $unit['check_out'] ?>
              </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

      </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($all_types)): ?>
    <div class="text-center text-muted py-5">
      <i class="bi bi-door-open fs-1 d-block mb-3"></i>
      No active rooms found. Add rooms from the <a href="admin_rooms.php">Rooms</a> page.
    </div>
    <?php endif; ?>

  </div>
</div>

<?php require('includes/admin_scripts.php'); ?>
<script>
function filterRooms(typeId, btn) {
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');

  document.querySelectorAll('.room-type-section').forEach(section => {
    if (typeId === 'all' || section.dataset.typeId == typeId) {
      section.classList.remove('hidden');
    } else {
      section.classList.add('hidden');
    }
  });
}
</script>
</body>
</html>