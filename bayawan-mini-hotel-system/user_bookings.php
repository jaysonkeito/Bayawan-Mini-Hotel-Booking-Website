<?php // bayawan-mini-hotel-system/user_bookings.php ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php require('includes/user_links.php'); ?>
  <title><?php echo $settings_r['site_title'] ?> - BOOKINGS</title>
</head>
<body class="bg-light">

  <?php
    require('includes/user_header.php');

    if (!(isset($_SESSION['login']) && $_SESSION['login'] == true)) {
        redirect('user_index.php');
    }
  ?>

  <div class="container">
    <div class="row">

      <div class="col-12 my-5 px-4">
        <h2 class="fw-bold">BOOKINGS</h2>
        <div style="font-size: 14px;">
          <a href="user_index.php" class="text-secondary text-decoration-none">HOME</a>
          <span class="text-secondary"> > </span>
          <a href="#" class="text-secondary text-decoration-none">BOOKINGS</a>
        </div>
      </div>

      <!-- ── Cancellation Policy Notice ── -->
      <div class="col-12 px-4 mb-4">
        <div class="alert alert-info border-0 shadow-sm" role="alert">
          <h6 class="fw-bold mb-2"><i class="bi bi-info-circle-fill me-2"></i>Cancellation Policy</h6>
          <div class="row g-2" style="font-size:13px;">
            <div class="col-md-4">
              <span class="badge bg-success me-1">✅ Full Refund</span>
              Cancel <strong>72+ hours</strong> before check-in
            </div>
            <div class="col-md-4">
              <span class="badge bg-warning text-dark me-1">⚠️ 50% Penalty</span>
              Cancel <strong>24–72 hours</strong> before check-in
            </div>
            <div class="col-md-4">
              <span class="badge bg-danger me-1">❌ 1st Night Forfeited</span>
              Cancel <strong>less than 24 hours</strong> before check-in
            </div>
          </div>
        </div>
      </div>

      <?php
        $query = "SELECT bo.*, bd.* FROM `booking_order` bo
          INNER JOIN `booking_details` bd ON bo.booking_id = bd.booking_id
          WHERE ((bo.booking_status='booked')
          OR (bo.booking_status='cancelled')
          OR (bo.booking_status='payment failed'))
          AND (bo.user_id=?)
          ORDER BY bo.booking_id DESC";

        $result = select($query, [$_SESSION['uId']], 'i');

        while ($data = mysqli_fetch_assoc($result)) {
          $date     = date("d-m-Y", strtotime($data['datentime']));
          $checkin  = date("d-m-Y", strtotime($data['check_in']));
          $checkout = date("d-m-Y", strtotime($data['check_out']));

          $status_bg = "";
          $btn       = "";

          if ($data['booking_status'] == 'booked') {
            $status_bg = "bg-success";

            if ($data['arrival'] == 1) {
              $btn = "<a href='user_generate_pdf.php?gen_pdf&id=$data[booking_id]'
                        class='btn btn-dark btn-sm shadow-none'>Download PDF</a>";

              if ($data['rate_review'] == 0) {
                $btn .= "<button type='button'
                           onclick='review_room($data[booking_id], $data[room_id])'
                           data-bs-toggle='modal' data-bs-target='#reviewModal'
                           class='btn btn-dark btn-sm shadow-none ms-2'>
                           Rate &amp; Review
                         </button>";
              }
            } else {
              // Show cancel button — policy preview on click
              $btn = "<button
                        onclick='show_cancel_preview($data[booking_id])'
                        type='button'
                        class='btn btn-danger btn-sm shadow-none'>
                        Cancel
                      </button>";
            }

          } elseif ($data['booking_status'] == 'cancelled') {
            $status_bg = "bg-danger";

            // Show refund_amt if available, else trans_amt
            $refund_display = isset($data['refund_amt']) && $data['refund_amt'] !== null
              ? number_format($data['refund_amt'], 2)
              : number_format($data['trans_amt'], 2);

            if ($data['refund'] == 0) {
              $btn = "<span class='badge bg-primary'>Refund in process!</span>
                      <br><small class='text-muted'>Refund: ₱{$refund_display}</small>";
            } else {
              $btn = "<a href='user_generate_pdf.php?gen_pdf&id=$data[booking_id]'
                        class='btn btn-dark btn-sm shadow-none'>Download PDF</a>";
            }

          } else {
            $status_bg = "bg-warning";
            $btn       = "<a href='user_generate_pdf.php?gen_pdf&id=$data[booking_id]'
                            class='btn btn-dark btn-sm shadow-none'>Download PDF</a>";
          }

          // No-show badge
          $no_show_badge = (!empty($data['no_show']) && $data['no_show'] == 1)
            ? "<span class='badge bg-secondary ms-1'>No-Show</span>"
            : "";

          echo <<<bookings
            <div class='col-md-4 px-4 mb-4'>
              <div class='bg-white p-3 rounded shadow-sm'>
                <h5 class='fw-bold'>$data[room_name]</h5>
                <p>₱$data[price] per night</p>
                <p>
                  <b>Check in:</b> $checkin <br>
                  <b>Check out:</b> $checkout
                </p>
                <p>
                  <b>Amount Paid:</b> ₱$data[trans_amt] <br>
                  <b>Order ID:</b> $data[order_id] <br>
                  <b>Date:</b> $date
                </p>
                <p>
                  <span class='badge $status_bg'>$data[booking_status]</span>
                  $no_show_badge
                </p>
                $btn
              </div>
            </div>
          bookings;
        }
      ?>

    </div>
  </div>


  <!-- ── Cancel Preview Modal ── -->
  <div class="modal fade" id="cancelPreviewModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Cancel Booking</h5>
        </div>
        <div class="modal-body" id="cancel-preview-body">
          <div class="text-center py-3">
            <div class="spinner-border text-secondary" role="status"></div>
            <p class="mt-2 text-muted small">Calculating refund...</p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn text-secondary shadow-none" data-bs-dismiss="modal">Keep Booking</button>
          <button type="button" id="confirm-cancel-btn" class="btn btn-danger shadow-none" disabled>
            Confirm Cancellation
          </button>
        </div>
      </div>
    </div>
  </div>


  <!-- ── Rate & Review Modal ── -->
  <div class="modal fade" id="reviewModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="review-form">
          <div class="modal-header">
            <h5 class="modal-title d-flex align-items-center">
              <i class="bi bi-chat-square-heart-fill fs-3 me-2"></i> Rate &amp; Review
            </h5>
            <button type="reset" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Rating</label>
              <select class="form-select shadow-none" name="rating">
                <option value="5">Excellent</option>
                <option value="4">Good</option>
                <option value="3">Ok</option>
                <option value="2">Poor</option>
                <option value="1">Bad</option>
              </select>
            </div>
            <div class="mb-4">
              <label class="form-label">Review</label>
              <textarea name="review" rows="3" required class="form-control shadow-none"></textarea>
            </div>
            <input type="hidden" name="booking_id">
            <input type="hidden" name="room_id">
            <div class="text-end">
              <button type="submit" class="btn custom-bg text-white shadow-none">SUBMIT</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>


  <?php
    if (isset($_GET['cancel_status']))  alert('success', 'Booking Cancelled!');
    if (isset($_GET['review_status']))  alert('success', 'Thank you for rating & review!');
  ?>

  <?php require('includes/user_footer.php'); ?>

  <script>
    let pendingCancelId = null;

    // ── Show cancel preview modal ──────────────────────────────────────
    function show_cancel_preview(id) {
      pendingCancelId = id;

      // Reset modal body
      document.getElementById('cancel-preview-body').innerHTML = `
        <div class="text-center py-3">
          <div class="spinner-border text-secondary" role="status"></div>
          <p class="mt-2 text-muted small">Calculating refund...</p>
        </div>`;
      document.getElementById('confirm-cancel-btn').disabled = true;

      // Show modal
      new bootstrap.Modal(document.getElementById('cancelPreviewModal')).show();

      // Fetch refund preview
      fetch('ajax/user_cancel_booking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'get_refund_preview=1&id=' + id
      })
      .then(r => r.text())
      .then(text => {
        const raw = text.substring(text.indexOf('{'));
        const res = JSON.parse(raw);

        if (res.status !== 'success') {
          document.getElementById('cancel-preview-body').innerHTML =
            `<div class="alert alert-danger">${res.message}</div>`;
          return;
        }

        // Color based on tier
        let tierColor  = res.tier === 'full' ? 'success' : (res.tier === 'partial' ? 'warning' : 'danger');
        let tierBorder = res.tier === 'full' ? 'border-success' : (res.tier === 'partial' ? 'border-warning' : 'border-danger');

        document.getElementById('cancel-preview-body').innerHTML = `
          <p class="mb-3">You are about to cancel your booking for <strong>${res.room_name}</strong>.</p>

          <div class="alert alert-${tierColor} border-start border-4 ${tierBorder} rounded-0 py-2">
            <strong>Cancellation Policy Applied:</strong><br>
            <span style="font-size:13px;">${res.policy_msg}</span>
          </div>

          <table class="table table-sm mt-3 mb-0">
            <tr>
              <td class="text-muted">Amount Paid</td>
              <td class="fw-bold">₱${parseFloat(res.trans_amt).toLocaleString('en-PH', {minimumFractionDigits:2})}</td>
            </tr>
            <tr>
              <td class="text-muted">Refund Amount</td>
              <td class="fw-bold text-success">₱${parseFloat(res.refund_amt).toLocaleString('en-PH', {minimumFractionDigits:2})}</td>
            </tr>
          </table>

          <p class="text-muted small mt-3 mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Refund will be processed by our team and returned to your original payment method.
          </p>`;

        document.getElementById('confirm-cancel-btn').disabled = false;
      })
      .catch(() => {
        document.getElementById('cancel-preview-body').innerHTML =
          `<div class="alert alert-danger">Connection error. Please try again.</div>`;
      });
    }

    // ── Confirm cancellation ───────────────────────────────────────────
    document.getElementById('confirm-cancel-btn').addEventListener('click', function () {
      if (!pendingCancelId) return;

      this.disabled  = true;
      this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Cancelling...';

      fetch('ajax/user_cancel_booking.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'cancel_booking=1&id=' + pendingCancelId
      })
      .then(r => r.text())
      .then(text => {
        const raw = text.substring(text.indexOf('{'));
        const res = JSON.parse(raw);

        bootstrap.Modal.getInstance(document.getElementById('cancelPreviewModal'))?.hide();

        if (res.status === 'success') {
          window.location.href = 'user_bookings.php?cancel_status=true';
        } else {
          alert('error', res.message || 'Cancellation failed. Please try again.');
        }
      })
      .catch(() => alert('error', 'Connection error. Please try again.'));
    });


    // ── Rate & Review ──────────────────────────────────────────────────
    let review_form = document.getElementById('review-form');

    function review_room(bid, rid) {
      review_form.elements['booking_id'].value = bid;
      review_form.elements['room_id'].value    = rid;
    }

    review_form.addEventListener('submit', function (e) {
      e.preventDefault();

      let data = new FormData();
      data.append('review_form', '');
      data.append('rating',      review_form.elements['rating'].value);
      data.append('review',      review_form.elements['review'].value);
      data.append('booking_id',  review_form.elements['booking_id'].value);
      data.append('room_id',     review_form.elements['room_id'].value);

      let xhr = new XMLHttpRequest();
      xhr.open("POST", "ajax/user_review_room.php", true);

      xhr.onload = function () {
        if (this.responseText == 1) {
          window.location.href = 'user_bookings.php?review_status=true';
        } else {
          bootstrap.Modal.getInstance(document.getElementById('reviewModal'))?.hide();
          alert('error', "Rating & Review Failed!");
        }
      };

      xhr.send(data);
    });
  </script>

</body>
</html>