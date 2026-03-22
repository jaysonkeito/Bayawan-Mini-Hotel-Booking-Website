<?php // bayawan-mini-hotel-system/user_room_details.php ?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require('includes/user_links.php'); ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .flatpickr-day.booked-date { background-color:#fee2e2!important;color:#dc2626!important;border-color:#fca5a5!important;cursor:not-allowed!important;text-decoration:line-through; }
        .flatpickr-day.booked-date:hover { background-color:#fecaca!important; }
        .flatpickr-day.selected,.flatpickr-day.startRange,.flatpickr-day.endRange { background-color:#2ec1ac!important;border-color:#2ec1ac!important; }
        .flatpickr-day.inRange { background-color:#d1faf5!important;border-color:#d1faf5!important;color:#0f6e56!important; }
        #availability-calendar-wrap .flatpickr-calendar { width:100%!important;max-width:100%;box-shadow:none!important;border:1px solid #dee2e6;border-radius:8px; }
        #availability-calendar-wrap .flatpickr-days,#availability-calendar-wrap .dayContainer { width:100%!important;max-width:100%!important;min-width:100%!important; }
        #availability-calendar-wrap .flatpickr-day { max-width:none!important;flex-basis:14.2857%!important; }
        .legend-dot { width:14px;height:14px;border-radius:3px;display:inline-block; }
    </style>
    <title><?php echo $settings_r['site_title'] ?> - ROOM DETAILS</title>
</head>
<body class="bg-light">

<?php require('includes/user_header.php'); ?>

<?php
    if (!isset($_GET['id'])) redirect('user_rooms.php');
    $data     = filteration($_GET);
    $room_res = select("SELECT * FROM `rooms` WHERE `id`=? AND `status`=? AND `removed`=?", [$data['id'], 1, 0], 'iii');
    if (mysqli_num_rows($room_res) == 0) redirect('user_rooms.php');
    $room_data = mysqli_fetch_assoc($room_res);
?>

<div class="container">
    <div class="row">

        <div class="col-12 my-5 mb-4 px-4">
            <h2 class="fw-bold"><?php echo $room_data['name'] ?></h2>
            <div style="font-size:14px;">
                <a href="user_index.php" class="text-secondary text-decoration-none"><?php echo t('bc_home'); ?></a>
                <span class="text-secondary"> > </span>
                <a href="user_rooms.php" class="text-secondary text-decoration-none"><?php echo t('bc_rooms'); ?></a>
                <span class="text-secondary"> > </span>
                <span class="text-secondary"><?php echo $room_data['name'] ?></span>
            </div>
        </div>

        <!-- Room Image Carousel -->
        <div class="col-lg-7 col-md-12 px-4">
            <div id="roomCarousel" class="carousel slide mb-4" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php
                    $img_q = mysqli_query($conn, "SELECT * FROM `room_images` WHERE `room_id`='$room_data[id]'");
                    if (mysqli_num_rows($img_q) > 0) {
                        $active_class = 'active';
                        while ($img_res = mysqli_fetch_assoc($img_q)) {
                            echo "<div class='carousel-item {$active_class}'>
                                <img src='" . ROOMS_IMG_PATH . $img_res['image'] . "' class='d-block w-100 rounded'>
                            </div>";
                            $active_class = '';
                        }
                    } else {
                        echo "<div class='carousel-item active'>
                            <img src='" . ROOMS_IMG_PATH . "thumbnail.jpg' class='d-block w-100'>
                        </div>";
                    }
                    ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#roomCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#roomCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                </button>
            </div>

            <!-- Availability Calendar -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="mb-0 fw-bold">
                            <i class="bi bi-calendar3 me-2 text-success"></i>
                            <?php echo t('rd_avail_cal'); ?>
                        </h5>
                        <div id="calendar-loading" class="spinner-border spinner-border-sm text-secondary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div class="d-flex gap-3 mb-3 flex-wrap" style="font-size:13px;">
                        <span><span class="legend-dot me-1" style="background:#fee2e2;border:1px solid #fca5a5;"></span><?php echo t('rd_booked'); ?></span>
                        <span><span class="legend-dot me-1" style="background:#2ec1ac;"></span><?php echo t('rd_your_sel'); ?></span>
                        <span><span class="legend-dot me-1" style="background:#d1faf5;border:1px solid #2ec1ac;"></span><?php echo t('rd_sel_range'); ?></span>
                        <span><span class="legend-dot me-1" style="background:#fff;border:1px solid #dee2e6;"></span><?php echo t('rd_available'); ?></span>
                    </div>
                    <div id="availability-calendar-wrap">
                        <input type="text" id="availability-calendar" class="d-none">
                    </div>
                    <div id="selected-dates-summary" class="mt-3 d-none">
                        <div class="alert alert-success border-0 py-2 px-3 mb-2" style="font-size:13px;">
                            <i class="bi bi-check-circle me-1"></i>
                            <span id="summary-text"></span>
                        </div>
                        <?php if (!$settings_r['shutdown']): ?>
                            <?php if (isset($_SESSION['login']) && $_SESSION['login']): ?>
                                <a id="book-from-calendar-btn" href="#"
                                   class="btn custom-bg text-white shadow-none w-100">
                                    <i class="bi bi-credit-card me-1"></i>
                                    <?php echo t('rd_book_dates'); ?>
                                </a>
                            <?php else: ?>
                                <button onclick="alert('error','Please login to book a room!')"
                                        class="btn custom-bg text-white shadow-none w-100">
                                    <i class="bi bi-credit-card me-1"></i>
                                    <?php echo t('rd_book_dates'); ?>
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Room Info Panel -->
        <div class="col-lg-5 col-md-12 px-4">
            <div class="card mb-4 border-0 shadow-sm rounded-3">
                <div class="card-body">
                    <?php
                    $per_night = t('room_per_night');
                    echo "<h4>&#8369;{$room_data['price']} {$per_night}</h4>";

                    $rating_q     = "SELECT AVG(rating) AS avg_rating FROM `rating_review` WHERE `room_id`='$room_data[id]' ORDER BY `sr_no` DESC LIMIT 20";
                    $rating_res   = mysqli_query($conn, $rating_q);
                    $rating_fetch = mysqli_fetch_assoc($rating_res);
                    $rating_data  = "";
                    if ($rating_fetch['avg_rating'] != NULL) {
                        for ($i = 0; $i < $rating_fetch['avg_rating']; $i++) {
                            $rating_data .= "<i class='bi bi-star-fill text-warning'></i> ";
                        }
                    }
                    echo "<div class='mb-3'>$rating_data</div>";

                    $lbl_features = t('room_features');
                    $fea_q = mysqli_query($conn, "SELECT f.name FROM `features` f INNER JOIN `room_features` rfea ON f.id = rfea.features_id WHERE rfea.room_id = '$room_data[id]'");
                    $features_data = "";
                    while ($fea_row = mysqli_fetch_assoc($fea_q)) {
                        $features_data .= "<span class='badge rounded-pill bg-light text-dark me-1 mb-1'>$fea_row[name]</span>";
                    }
                    echo "<div class='mb-3'><h6 class='mb-1'>{$lbl_features}</h6>$features_data</div>";

                    $lbl_facilities = t('room_facilities');
                    $fac_q = mysqli_query($conn, "SELECT f.name FROM `facilities` f INNER JOIN `room_facilities` rfac ON f.id = rfac.facilities_id WHERE rfac.room_id = '$room_data[id]'");
                    $facilities_data = "";
                    while ($fac_row = mysqli_fetch_assoc($fac_q)) {
                        $facilities_data .= "<span class='badge rounded-pill bg-light text-dark me-1 mb-1'>$fac_row[name]</span>";
                    }
                    echo "<div class='mb-3'><h6 class='mb-1'>{$lbl_facilities}</h6>$facilities_data</div>";

                    $lbl_guests   = t('room_guests');
                    $lbl_adults   = t('room_adults');
                    $lbl_children = t('room_children');
                    echo "<div class='mb-3'>
                        <h6 class='mb-1'>{$lbl_guests}</h6>
                        <span class='badge rounded-pill bg-light text-dark me-1'>$room_data[adult] {$lbl_adults}</span>
                        <span class='badge rounded-pill bg-light text-dark'>$room_data[children] {$lbl_children}</span>
                    </div>";

                    $lbl_area = t('room_area');
                    echo "<div class='mb-3'>
                        <h6 class='mb-1'>{$lbl_area}</h6>
                        <span class='badge rounded-pill bg-light text-dark'>$room_data[area] sq. ft.</span>
                    </div>";

                    if (!$settings_r['shutdown']) {
                        $login    = (isset($_SESSION['login']) && $_SESSION['login']) ? 1 : 0;
                        $lbl_book = t('room_book_now');
                        echo "<button onclick='checkLoginToBook($login,$room_data[id])'
                                class='btn w-100 text-white custom-bg shadow-none mb-1'>
                                {$lbl_book}
                              </button>";
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div class="col-12 mt-4 px-4">
            <div class="mb-5">
                <h5><?php echo t('rd_description'); ?></h5>
                <p><?php echo $room_data['description'] ?></p>
            </div>

            <!-- Reviews -->
            <div>
                <h5 class="mb-3"><?php echo t('rd_reviews'); ?></h5>
                <?php
                $review_q = "SELECT rr.*, uc.name AS uname, uc.profile FROM `rating_review` rr
                    INNER JOIN `user_cred` uc ON rr.user_id = uc.id
                    WHERE rr.room_id = '$room_data[id]'
                    ORDER BY `sr_no` DESC LIMIT 15";
                $review_res = mysqli_query($conn, $review_q);
                $img_path   = USERS_IMG_PATH;
                if (mysqli_num_rows($review_res) == 0) {
                    echo '<p class="text-muted">' . t('rd_no_reviews') . '</p>';
                } else {
                    while ($row = mysqli_fetch_assoc($review_res)) {
                        $stars = "";
                        for ($i = 0; $i < $row['rating']; $i++) {
                            $stars .= "<i class='bi bi-star-fill text-warning'></i>";
                        }
                        echo "<div class='mb-4'>
                            <div class='d-flex align-items-center mb-2'>
                                <img src='{$img_path}{$row['profile']}' class='rounded-circle' loading='lazy' width='30px'>
                                <h6 class='m-0 ms-2'>{$row['uname']}</h6>
                            </div>
                            <p class='mb-1'>{$row['review']}</p>
                            <div>$stars</div>
                        </div>";
                    }
                }
                ?>
            </div>
        </div>

    </div>
</div>

<?php require('includes/user_footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
(function () {
    const ROOM_ID    = <?php echo (int) $room_data['id']; ?>;
    const ROOM_PRICE = <?php echo (int) $room_data['price']; ?>;
    let fullyBookedDates = [];
    let selectedCheckin  = null;
    let selectedCheckout = null;
    let fpInstance       = null;

    fetch('ajax/user_get_booked_dates.php?room_id=' + ROOM_ID)
        .then(r => r.text())
        .then(text => {
            const raw  = text.substring(text.indexOf('{'));
            const data = JSON.parse(raw);
            document.getElementById('calendar-loading').classList.add('d-none');
            if (data.status !== 'success') return;
            fullyBookedDates = data.fully_booked_dates || [];
            initCalendar(fullyBookedDates);
        })
        .catch(() => {
            document.getElementById('calendar-loading').classList.add('d-none');
            initCalendar([]);
        });

    function initCalendar(disabledDates) {
        fpInstance = flatpickr('#availability-calendar', {
            mode: 'range', inline: true, minDate: 'today', dateFormat: 'Y-m-d',
            disable: disabledDates,
            onReady:      (s, d, i) => styleBookedDates(i, disabledDates),
            onMonthChange:(s, d, i) => styleBookedDates(i, disabledDates),
            onYearChange: (s, d, i) => styleBookedDates(i, disabledDates),
            onChange: function (selectedDates) {
                if (selectedDates.length === 2) {
                    selectedCheckin  = formatDate(selectedDates[0]);
                    selectedCheckout = formatDate(selectedDates[1]);
                    showSummary(selectedDates[0], selectedDates[1]);
                } else {
                    selectedCheckin = selectedCheckout = null;
                    hideSummary();
                }
            },
        });
    }

    function styleBookedDates(instance, disabledDates) {
        if (!disabledDates || !disabledDates.length) return;
        setTimeout(() => {
            instance.calendarContainer.querySelectorAll('.flatpickr-day').forEach(dayEl => {
                const dateStr = dayEl.getAttribute('aria-label');
                if (!dateStr) return;
                const parsed = new Date(dateStr);
                if (!isNaN(parsed) && disabledDates.includes(formatDate(parsed))) {
                    dayEl.classList.add('booked-date');
                }
            });
        }, 10);
    }

    function showSummary(checkin, checkout) {
        const days  = Math.round((checkout - checkin) / (1000 * 60 * 60 * 24));
        const total = days * ROOM_PRICE;
        const opts  = { year:'numeric', month:'short', day:'numeric' };
        const cin   = checkin.toLocaleDateString('en-PH', opts);
        const cout  = checkout.toLocaleDateString('en-PH', opts);
        document.getElementById('summary-text').innerHTML =
            `<strong>${cin}</strong> → <strong>${cout}</strong> &nbsp;·&nbsp; ` +
            `${days} night${days > 1 ? 's' : ''} &nbsp;·&nbsp; ` +
            `<strong>₱${total.toLocaleString('en-PH')}</strong>`;
        const bookBtn = document.getElementById('book-from-calendar-btn');
        if (bookBtn) {
            bookBtn.href = 'user_confirm_booking.php?id=' + ROOM_ID
                + '&checkin=' + selectedCheckin + '&checkout=' + selectedCheckout;
        }
        document.getElementById('selected-dates-summary').classList.remove('d-none');
    }

    function hideSummary() {
        document.getElementById('selected-dates-summary').classList.add('d-none');
    }

    function formatDate(d) {
        return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    }
})();
</script>

</body>
</html>