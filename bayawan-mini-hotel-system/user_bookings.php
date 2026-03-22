<?php // bayawan-mini-hotel-system/user_bookings.php ?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>">
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
    if (!(isset($_SESSION['login']) && $_SESSION['login'] == true)) redirect('user_index.php');
  ?>

  <div class="container">
    <div class="row">

      <div class="col-12 my-5 px-4">
        <h2 class="fw-bold"><?php echo t('bookings_title'); ?></h2>
        <div style="font-size:14px;">
          <a href="user_index.php" class="text-secondary text-decoration-none"><?php echo t('bc_home'); ?></a>
          <span class="text-secondary"> > </span>
          <a href="#" class="text-secondary text-decoration-none"><?php echo t('bc_bookings'); ?></a>
        </div>
      </div>

      <!-- Cancellation Policy Notice -->
      <div class="col-12 px-4 mb-4">
        <div class="alert alert-info border-0 shadow-sm" role="alert">
          <h6 class="fw-bold mb-2"><i class="bi bi-info-circle-fill me-2"></i><?php echo t('bookings_policy_title'); ?></h6>
          <div class="row g-2" style="font-size:13px;">
            <div class="col-md-4">
              <span class="badge bg-success me-1">✅ <?php echo t('bookings_full_ref'); ?></span>
              <?php echo t('bookings_72h'); ?>
            </div>
            <div class="col-md-4">
              <span class="badge bg-warning text-dark me-1">⚠️ <?php echo t('bookings_50pct'); ?></span>
              <?php echo t('bookings_24_72h'); ?>
            </div>
            <div class="col-md-4">
              <span class="badge bg-danger me-1">❌ <?php echo t('bookings_1night'); ?></span>
              <?php echo t('bookings_lt24h'); ?>
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

          // ── BOOKED ────────────────────────────────────────────────────
          if ($data['booking_status'] == 'booked') {
            $status_bg = "bg-success";

            if ($data['arrival'] == 1) {
              // ── Room assigned by admin — show room number to guest ────
              $room_no = htmlspecialchars($data['room_no'] ?? '');

              $room_assigned_block = "
                <div class='alert border-0 py-2 px-3 mb-3' style='background:rgba(46,193,172,0.12);font-size:13px;'>
                  <div class='d-flex align-items-center gap-2'>
                    <i class='bi bi-door-open-fill fs-4' style='color:var(--teal);'></i>
                    <div>
                      <div class='fw-bold' style='font-size:16px;color:var(--teal);'>{$room_no}</div>
                      <div class='text-muted' style='font-size:12px;'>Your assigned room number</div>
                    </div>
                    <span class='badge ms-auto' style='background:var(--teal);'>✓ Checked In</span>
                  </div>
                </div>
              ";

              $lbl_pdf  = t('bookings_dl_pdf');
              $btn_area = "<a href='user_generate_pdf.php?gen_pdf&id=$data[booking_id]'
                              class='btn btn-dark btn-sm shadow-none'>
                              <i class='bi bi-file-pdf me-1'></i>$lbl_pdf
                           </a>";

              if ($data['rate_review'] == 0) {
                $lbl_rate = t('bookings_rate');
                $btn_area .= " <button type='button'
                               onclick='review_room($data[booking_id], $data[room_id])'
                               data-bs-toggle='modal' data-bs-target='#reviewModal'
                               class='btn btn-dark btn-sm shadow-none ms-1'>
                               <i class='bi bi-star me-1'></i>$lbl_rate
                             </button>";
              }

              $btn = $room_assigned_block . $btn_area;

            } else {
              // ── Waiting for room assignment ───────────────────────────
              $lbl_cancel = t('bookings_cancel');

              $pending_block = "
                <div class='alert alert-warning border-0 py-2 px-3 mb-3' style='font-size:12px;'>
                  <i class='bi bi-hourglass-split me-1'></i>
                  Awaiting room assignment upon arrival at the hotel.
                </div>
              ";

              $cancel_btn = "<button
                              onclick='show_cancel_preview($data[booking_id])'
                              type='button'
                              class='btn btn-danger btn-sm shadow-none'>
                              <i class='bi bi-x-circle me-1'></i>$lbl_cancel
                            </button>";

              $btn = $pending_block . $cancel_btn;
            }

          // ── CANCELLED ─────────────────────────────────────────────────
          } elseif ($data['booking_status'] == 'cancelled') {
            $status_bg = "bg-danger";

            $refund_display = isset($data['refund_amt']) && $data['refund_amt'] !== null
              ? number_format($data['refund_amt'], 2)
              : number_format($data['trans_amt'], 2);

            $lbl_refund_proc = t('bookings_refund_proc');
            $lbl_refund      = t('bookings_refund_lbl');

            if ($data['refund'] == 0) {
              $btn = "<span class='badge bg-primary'>{$lbl_refund_proc}</span>
                      <br><small class='text-muted'>{$lbl_refund}: ₱{$refund_display}</small>";
            } else {
              $lbl_pdf = t('bookings_dl_pdf');
              $btn = "<a href='user_generate_pdf.php?gen_pdf&id=$data[booking_id]'
                        class='btn btn-dark btn-sm shadow-none'>
                        <i class='bi bi-file-pdf me-1'></i>$lbl_pdf
                      </a>";
            }

          // ── PAYMENT FAILED ────────────────────────────────────────────
          } else {
            $status_bg = "bg-warning";
            $lbl_pdf   = t('bookings_dl_pdf');
            $btn       = "<a href='user_generate_pdf.php?gen_pdf&id=$data[booking_id]'
                            class='btn btn-dark btn-sm shadow-none'>
                            <i class='bi bi-file-pdf me-1'></i>$lbl_pdf
                          </a>";
          }

          $no_show_lbl   = t('bookings_no_show');
          $no_show_badge = (!empty($data['no_show']) && $data['no_show'] == 1)
            ? "<span class='badge bg-secondary ms-1'>$no_show_lbl</span>"
            : "";

          $lbl_checkin   = t('bookings_checkin');
          $lbl_checkout  = t('bookings_checkout');
          $lbl_paid      = t('bookings_paid');
          $lbl_order     = t('bookings_order_id');
          $lbl_date      = t('bookings_date');
          $lbl_per_night = t('room_per_night');

          echo <<<bookings
            <div class='col-md-4 px-4 mb-4'>
              <div class='bg-white p-3 rounded shadow-sm h-100 d-flex flex-column'>
                <h5 class='fw-bold'>$data[room_name]</h5>
                <p class='mb-1'>₱$data[price] $lbl_per_night</p>
                <p class='mb-1'>
                  <b>$lbl_checkin:</b> $checkin <br>
                  <b>$lbl_checkout:</b> $checkout
                </p>
                <p class='mb-2'>
                  <b>$lbl_paid:</b> ₱$data[trans_amt] <br>
                  <b>$lbl_order:</b> $data[order_id] <br>
                  <b>$lbl_date:</b> $date
                </p>
                <p class='mb-2'>
                  <span class='badge $status_bg'>$data[booking_status]</span>
                  $no_show_badge
                </p>
                <div class='mt-auto'>$btn</div>
              </div>
            </div>
          bookings;
        }
      ?>

    </div>
  </div>

  <!-- Cancel Preview Modal -->
  <div class="modal fade" id="cancelPreviewModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i><?php echo t('bookings_cancel_title'); ?></h5>
        </div>
        <div class="modal-body" id="cancel-preview-body">
          <div class="text-center py-3">
            <div class="spinner-border text-secondary" role="status"></div>
            <p class="mt-2 text-muted small"><?php echo t('bookings_calc_refund'); ?></p>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn text-secondary shadow-none" data-bs-dismiss="modal"><?php echo t('bookings_keep'); ?></button>
          <button type="button" id="confirm-cancel-btn" class="btn btn-danger shadow-none" disabled>
            <?php echo t('bookings_confirm_cancel'); ?>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Rate & Review Modal -->
  <div class="modal fade" id="reviewModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="review-form">
          <div class="modal-header">
            <h5 class="modal-title d-flex align-items-center">
              <i class="bi bi-chat-square-heart-fill fs-3 me-2"></i> <?php echo t('bookings_review_title'); ?>
            </h5>
            <button type="reset" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label"><?php echo t('bookings_review_rating'); ?></label>
              <select class="form-select shadow-none" name="rating">
                <option value="5"><?php echo t('bookings_excellent'); ?></option>
                <option value="4"><?php echo t('bookings_good'); ?></option>
                <option value="3"><?php echo t('bookings_ok'); ?></option>
                <option value="2"><?php echo t('bookings_poor'); ?></option>
                <option value="1"><?php echo t('bookings_bad'); ?></option>
              </select>
            </div>
            <div class="mb-4">
              <label class="form-label"><?php echo t('bookings_review_lbl'); ?></label>
              <textarea name="review" rows="3" required class="form-control shadow-none"></textarea>
            </div>
            <input type="hidden" name="booking_id">
            <input type="hidden" name="room_id">
            <div class="text-end">
              <button type="submit" class="btn custom-bg text-white shadow-none"><?php echo t('bookings_submit'); ?></button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php
    if (isset($_GET['cancel_status'])) alert('success', 'Booking Cancelled!');
    if (isset($_GET['review_status'])) alert('success', 'Thank you for rating & review!');
  ?>

  <?php require('includes/user_footer.php'); ?>

  <script>
    let pendingCancelId = null;

    function show_cancel_preview(id) {
      pendingCancelId = id;
      document.getElementById('cancel-preview-body').innerHTML = `
        <div class="text-center py-3">
          <div class="spinner-border text-secondary" role="status"></div>
          <p class="mt-2 text-muted small"><?php echo t('bookings_calc_refund'); ?></p>
        </div>`;
      document.getElementById('confirm-cancel-btn').disabled = true;
      new bootstrap.Modal(document.getElementById('cancelPreviewModal')).show();

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
          document.getElementById('cancel-preview-body').innerHTML = `<div class="alert alert-danger">${res.message}</div>`;
          return;
        }
        let tierColor  = res.tier === 'full' ? 'success' : (res.tier === 'partial' ? 'warning' : 'danger');
        let tierBorder = res.tier === 'full' ? 'border-success' : (res.tier === 'partial' ? 'border-warning' : 'border-danger');
        document.getElementById('cancel-preview-body').innerHTML = `
          <p class="mb-3">You are about to cancel your booking for <strong>${res.room_name}</strong>.</p>
          <div class="alert alert-${tierColor} border-start border-4 ${tierBorder} rounded-0 py-2">
            <strong><?php echo t('bookings_policy_title'); ?>:</strong><br>
            <span style="font-size:13px;">${res.policy_msg}</span>
          </div>
          <table class="table table-sm mt-3 mb-0">
            <tr><td class="text-muted"><?php echo t('bookings_paid'); ?></td>
                <td class="fw-bold">₱${parseFloat(res.trans_amt).toLocaleString('en-PH',{minimumFractionDigits:2})}</td></tr>
            <tr><td class="text-muted"><?php echo t('bookings_refund_lbl'); ?></td>
                <td class="fw-bold text-success">₱${parseFloat(res.refund_amt).toLocaleString('en-PH',{minimumFractionDigits:2})}</td></tr>
          </table>
          <p class="text-muted small mt-3 mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Refund will be processed by our team and returned to your original payment method.
          </p>`;
        document.getElementById('confirm-cancel-btn').disabled = false;
      })
      .catch(() => {
        document.getElementById('cancel-preview-body').innerHTML = `<div class="alert alert-danger">Connection error. Please try again.</div>`;
      });
    }

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

    let review_form = document.getElementById('review-form');
    function review_room(bid, rid) {
      review_form.elements['booking_id'].value = bid;
      review_form.elements['room_id'].value    = rid;
    }
    review_form.addEventListener('submit', function (e) {
      e.preventDefault();
      let data = new FormData();
      data.append('review_form', '');
      data.append('rating',     review_form.elements['rating'].value);
      data.append('review',     review_form.elements['review'].value);
      data.append('booking_id', review_form.elements['booking_id'].value);
      data.append('room_id',    review_form.elements['room_id'].value);
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