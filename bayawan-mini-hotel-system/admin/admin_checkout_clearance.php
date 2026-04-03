<?php
// bayawan-mini-hotel-system/admin/admin_checkout_clearance.php
// Admin processes guest checkout here.
// If unpaid food orders exist → admin must collect payment first.
// Only then does the room flip to "cleaning".
require('includes/admin_essentials.php');
require('includes/admin_configuration.php');
adminLogin();
date_default_timezone_set('Asia/Manila');

$today = date('Y-m-d');

// Guests who are checked-in and whose check-out date is today or earlier
$guests_q = "SELECT bo.booking_id, bo.check_in, bo.check_out, bo.room_id,
                    bd.user_name, bd.room_no, bd.room_name,
                    uc.email,
                    (SELECT COUNT(*) FROM food_orders fo
                     WHERE fo.booking_id = bo.booking_id
                       AND fo.status NOT IN ('paid','cancelled')) AS unpaid_food_count,
                    (SELECT COALESCE(SUM(fo2.total_amount),0) FROM food_orders fo2
                     WHERE fo2.booking_id = bo.booking_id
                       AND fo2.status NOT IN ('paid','cancelled')) AS unpaid_food_total
             FROM booking_order bo
             INNER JOIN booking_details bd ON bo.booking_id = bd.booking_id
             INNER JOIN user_cred uc       ON bo.user_id    = uc.id
             WHERE bo.booking_status = 'checked_in'
               AND bo.arrival = 1
               AND bo.check_out <= ?
             ORDER BY bo.check_out ASC, bd.room_no ASC";
$guests_r = select($guests_q, [$today], 's');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Admin Panel - Checkout Clearance</title>
  <?php require('includes/admin_links.php'); ?>
</head>
<body class="bg-light">
<?php require('includes/admin_header.php'); ?>

<div id="main-content">
<div class="container-fluid p-4">

  <div class="d-flex align-items-center justify-content-between mb-4">
    <h3 class="mb-0">CHECKOUT CLEARANCE</h3>
    <span class="text-muted small">Today: <?= date('F j, Y') ?></span>
  </div>

  <div class="alert alert-info d-flex align-items-start gap-2 mb-4">
    <i class="bi bi-info-circle-fill mt-1"></i>
    <div>
      This page shows guests due for checkout today. Before completing a checkout,
      <strong>all unpaid food orders must be settled</strong> (cash or GCash on-site).
      Only then will the room be set to <strong>Cleaning</strong> status.
    </div>
  </div>

  <?php
  $count = 0;
  while ($guest = mysqli_fetch_assoc($guests_r)):
      $count++;
      $has_food_due = $guest['unpaid_food_count'] > 0;
  ?>
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h5 class="mb-0"><?= htmlspecialchars($guest['user_name']) ?></h5>
        <small class="text-muted">
          <?= htmlspecialchars($guest['room_no']) ?> &mdash; <?= htmlspecialchars($guest['room_name']) ?>
          &nbsp;&bull;&nbsp;
          Check-out: <?= date('F j, Y', strtotime($guest['check_out'])) ?>
        </small>
      </div>
      <?php if ($has_food_due): ?>
        <span class="badge bg-danger px-3 py-2" style="font-size:13px;">
          <i class="bi bi-exclamation-triangle-fill me-1"></i>
          Unpaid food: &#8369;<?= number_format($guest['unpaid_food_total'], 2) ?>
        </span>
      <?php else: ?>
        <span class="badge bg-success px-3 py-2" style="font-size:13px;">
          <i class="bi bi-check-circle-fill me-1"></i> All settled
        </span>
      <?php endif; ?>
    </div>

    <div class="card-body">
      <!-- Unpaid food orders table -->
      <?php if ($has_food_due): ?>
      <h6 class="mb-3 text-danger">
        <i class="bi bi-bag-x me-1"></i>Unpaid Food Orders
      </h6>
      <?php
        $food_q = "SELECT fo.id, fo.total_amount, fo.status, fo.ordered_at,
                          GROUP_CONCAT(CONCAT(foi.food_name,' x',foi.qty) SEPARATOR ', ') AS items
                   FROM food_orders fo
                   LEFT JOIN food_order_items foi ON foi.order_id = fo.id
                   WHERE fo.booking_id = ? AND fo.status NOT IN ('paid','cancelled')
                   GROUP BY fo.id ORDER BY fo.ordered_at";
        $food_r = select($food_q, [(int)$guest['booking_id']], 'i');
      ?>
      <table class="table table-sm table-bordered mb-3">
        <thead class="table-light">
          <tr><th>#</th><th>Items</th><th class="text-center">Status</th><th class="text-end">Amount</th><th class="text-center">Mark Paid</th></tr>
        </thead>
        <tbody>
        <?php while ($fo = mysqli_fetch_assoc($food_r)): ?>
          <tr id="food-row-<?= $fo['id'] ?>">
            <td><?= $fo['id'] ?></td>
            <td><small><?= htmlspecialchars($fo['items']) ?></small></td>
            <td class="text-center">
              <span class="badge bg-warning text-dark"><?= ucfirst($fo['status']) ?></span>
            </td>
            <td class="text-end">&#8369;<?= number_format($fo['total_amount'], 2) ?></td>
            <td class="text-center">
              <button class="btn btn-sm btn-success shadow-none"
                      onclick="markFoodPaid(<?= $fo['id'] ?>, <?= $guest['booking_id'] ?>)">
                <i class="bi bi-cash-coin me-1"></i>Collected
              </button>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>

      <div class="alert alert-warning py-2 small">
        <i class="bi bi-lock-fill me-1"></i>
        Checkout is <strong>blocked</strong> until all food orders above are marked as collected/paid.
      </div>
      <?php else: ?>
      <p class="text-muted small mb-3">
        <i class="bi bi-check2-circle text-success me-1"></i>
        No pending food charges. Room booking payment was already settled at booking time.
      </p>
      <?php endif; ?>

      <!-- Checkout button -->
      <div class="d-flex justify-content-end">
        <button
          class="btn btn-dark shadow-none checkout-btn"
          data-booking-id="<?= $guest['booking_id'] ?>"
          data-room-no="<?= htmlspecialchars($guest['room_no']) ?>"
          <?= $has_food_due ? 'disabled' : '' ?>
          onclick="completeCheckout(this, <?= $guest['booking_id'] ?>)">
          <i class="bi bi-door-closed me-1"></i>
          Complete Checkout &amp; Set Room to Cleaning
        </button>
      </div>

    </div>
  </div>
  <?php endwhile; ?>

  <?php if ($count === 0): ?>
  <div class="text-center text-muted py-5">
    <i class="bi bi-calendar-check fs-1 d-block mb-3"></i>
    No guests are due for checkout today.
  </div>
  <?php endif; ?>

</div>
</div>

<?php require('includes/admin_scripts.php'); ?>
<script>
function markFoodPaid(foodOrderId, bookingId) {
  if (!confirm('Confirm that you have collected payment for this food order?')) return;
  const fd = new FormData();
  fd.append('action',   'mark_food_paid');
  fd.append('order_id', foodOrderId);
  fd.append('booking_id', bookingId);

  fetch('ajax/admin_checkout_clearance.php', { method: 'POST', body: fd })
    .then(r => r.text())
    .then(resp => {
      if (resp.trim() === 'success') {
        // Remove the row
        const row = document.getElementById('food-row-' + foodOrderId);
        if (row) row.remove();

        // Check if all food rows are gone → enable checkout button
        const remaining = document.querySelectorAll(
          `[id^="food-row-"]`
        ).length;

        // Re-fetch status for this booking to be safe
        checkAllPaid(bookingId);
        alert('success', 'Food payment marked as collected.');
      } else {
        alert('error', resp.trim() || 'Failed to update.');
      }
    });
}

function checkAllPaid(bookingId) {
  fetch('ajax/admin_checkout_clearance.php?action=check_pending&booking_id=' + bookingId)
    .then(r => r.text())
    .then(resp => {
      const allPaid = resp.trim() === '0';
      const btn = document.querySelector(`.checkout-btn[data-booking-id="${bookingId}"]`);
      if (btn) {
        btn.disabled = !allPaid;
        if (allPaid) {
          // Update the badge
          const card = btn.closest('.card');
          const badge = card.querySelector('.card-header .badge');
          if (badge) {
            badge.className = 'badge bg-success px-3 py-2';
            badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> All settled';
          }
          // Hide the warning
          const warn = card.querySelector('.alert-warning');
          if (warn) warn.remove();
        }
      }
    });
}

function completeCheckout(btn, bookingId) {
  const roomNo = btn.dataset.roomNo;
  if (!confirm(`Complete checkout for ${roomNo}? This will mark the room as "Cleaning".`)) return;

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing...';

  const fd = new FormData();
  fd.append('action',     'complete_checkout');
  fd.append('booking_id', bookingId);

  fetch('ajax/admin_checkout_clearance.php', { method: 'POST', body: fd })
    .then(r => r.text())
    .then(resp => {
      if (resp.trim() === 'success') {
        alert('success', `Checkout complete! ${roomNo} is now set to Cleaning.`);
        // Remove the card from view
        btn.closest('.card').remove();
      } else {
        alert('error', resp.trim() || 'Checkout failed. Please try again.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-door-closed me-1"></i>Complete Checkout &amp; Set Room to Cleaning';
      }
    });
}
</script>
</body>
</html>