<?php // bayawan-mini-hotel-system/user_confirm_booking.php ?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require('includes/user_links.php'); ?>
    <title><?php echo $settings_r['site_title'] ?> - CONFIRM BOOKING</title>
</head>
<body class="bg-light">

<?php require('includes/user_header.php'); ?>

<?php
    if (!isset($_GET['id']) || $settings_r['shutdown'] == true) redirect('user_rooms.php');
    elseif (!(isset($_SESSION['login']) && $_SESSION['login'] == true)) redirect('user_rooms.php');

    $data = filteration($_GET);

    $room_res = select(
        "SELECT * FROM `rooms` WHERE `id` = ? AND `status` = ? AND `removed` = ?",
        [$data['id'], 1, 0], 'iii'
    );
    if (mysqli_num_rows($room_res) == 0) redirect('user_rooms.php');

    $room_data = mysqli_fetch_assoc($room_res);

    $_SESSION['room'] = [
        'id'        => $room_data['id'],
        'name'      => $room_data['name'],
        'price'     => $room_data['price'],
        'payment'   => null,
        'available' => false,
    ];

    $user_res  = select("SELECT * FROM `user_cred` WHERE `id` = ? LIMIT 1", [$_SESSION['uId']], 'i');
    $user_data = mysqli_fetch_assoc($user_res);

    $cart_count       = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
    $prefill_checkin  = isset($data['checkin'])  ? $data['checkin']  : '';
    $prefill_checkout = isset($data['checkout']) ? $data['checkout'] : '';
?>

<div class="container">
    <div class="row">

        <div class="col-12 my-5 mb-4 px-4">
            <h2 class="fw-bold"><?php echo t('cb_title'); ?></h2>
            <div style="font-size:14px;">
                <a href="user_index.php" class="text-secondary text-decoration-none"><?php echo t('bc_home'); ?></a>
                <span class="text-secondary"> > </span>
                <a href="user_rooms.php" class="text-secondary text-decoration-none"><?php echo t('bc_rooms'); ?></a>
                <span class="text-secondary"> > </span>
                <a href="user_room_details.php?id=<?php echo $room_data['id'] ?>" class="text-secondary text-decoration-none">
                    <?php echo $room_data['name'] ?>
                </a>
                <span class="text-secondary"> > </span>
                <a href="#" class="text-secondary text-decoration-none"><?php echo t('bc_confirm'); ?></a>
            </div>
        </div>

        <!-- Room thumbnail -->
        <div class="col-lg-7 col-md-12 px-4">
            <?php
            $room_thumb = ROOMS_IMG_PATH . 'thumbnail.jpg';
            $thumb_q    = mysqli_query($conn, "SELECT * FROM `room_images`
                WHERE `room_id` = '$room_data[id]' AND `thumb` = '1'");
            if (mysqli_num_rows($thumb_q) > 0) {
                $thumb_res  = mysqli_fetch_assoc($thumb_q);
                $room_thumb = ROOMS_IMG_PATH . $thumb_res['image'];
            }
            $lbl_per_night = t('room_per_night');
            echo "<div class='card p-3 shadow-sm rounded'>
                <img src='$room_thumb' class='img-fluid rounded mb-3'>
                <h5>$room_data[name]</h5>
                <h6>&#8369;$room_data[price] {$lbl_per_night}</h6>
            </div>";
            ?>
        </div>

        <!-- Booking form -->
        <div class="col-lg-5 col-md-12 px-4">
            <div class="card mb-4 border-0 shadow-sm rounded-3">
                <div class="card-body">
                    <form action="user_pay_now.php" method="POST" id="booking_form">
                        <h6 class="mb-3"><?php echo t('cb_details'); ?></h6>
                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('cb_name'); ?></label>
                                <input name="name" type="text"
                                       value="<?php echo $user_data['name'] ?>"
                                       class="form-control shadow-none" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('cb_phone'); ?></label>
                                <input name="phonenum" type="number"
                                       value="<?php echo $user_data['phonenum'] ?>"
                                       class="form-control shadow-none" required>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label"><?php echo t('cb_address'); ?></label>
                                <textarea name="address" class="form-control shadow-none" rows="1" required><?php echo $user_data['address'] ?></textarea>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label"><?php echo t('cb_checkin'); ?></label>
                                <input name="checkin"
                                       onchange="check_availability()"
                                       type="date"
                                       value="<?php echo htmlspecialchars($prefill_checkin) ?>"
                                       class="form-control shadow-none" required>
                            </div>

                            <div class="col-md-6 mb-4">
                                <label class="form-label"><?php echo t('cb_checkout'); ?></label>
                                <input name="checkout"
                                       onchange="check_availability()"
                                       type="date"
                                       value="<?php echo htmlspecialchars($prefill_checkout) ?>"
                                       class="form-control shadow-none" required>
                            </div>

                            <div class="col-12">
                                <div class="spinner-border text-info mb-3 d-none" id="info_loader" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>

                                <h6 class="mb-3 text-danger" id="pay_info">
                                    <?php echo t('cb_provide_dates'); ?>
                                </h6>

                                <button name="pay_now"
                                        id="pay-now-btn"
                                        class="btn w-100 text-white custom-bg shadow-none mb-2"
                                        disabled>
                                    <i class="bi bi-credit-card me-1"></i> <?php echo t('cb_pay_now'); ?>
                                </button>

                                <button type="button"
                                        id="add-to-cart-btn"
                                        onclick="addToCart()"
                                        class="btn w-100 btn-outline-dark shadow-none mb-1"
                                        disabled>
                                    <i class="bi bi-cart-plus me-1"></i>
                                    <?php echo t('cb_add_cart'); ?>
                                    <?php if ($cart_count > 0): ?>
                                        <span class="badge bg-danger ms-1" id="cart-badge-confirm"><?php echo $cart_count ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-danger ms-1 d-none" id="cart-badge-confirm">0</span>
                                    <?php endif; ?>
                                </button>

                                <div id="view-cart-link"
                                     class="text-center mt-1 <?php echo $cart_count > 0 ? '' : 'd-none' ?>">
                                    <a href="user_cart.php" class="text-decoration-none small custom-text-teal">
                                        <i class="bi bi-cart3 me-1"></i>
                                        <?php echo t('cb_view_cart'); ?>
                                        (<span id="cart-count-text"><?php echo $cart_count ?></span>)
                                    </a>
                                </div>

                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require('includes/user_footer.php'); ?>

<script>
    let booking_form = document.getElementById('booking_form');
    let info_loader  = document.getElementById('info_loader');
    let pay_info     = document.getElementById('pay_info');
    let payNowBtn    = document.getElementById('pay-now-btn');
    let addToCartBtn = document.getElementById('add-to-cart-btn');

    // Auto-check if dates were pre-filled from calendar
    window.addEventListener('DOMContentLoaded', function () {
        const checkin  = booking_form.elements['checkin'].value;
        const checkout = booking_form.elements['checkout'].value;
        if (checkin && checkout) check_availability();
    });

    function check_availability() {
        let checkin_val  = booking_form.elements['checkin'].value;
        let checkout_val = booking_form.elements['checkout'].value;

        payNowBtn.setAttribute('disabled', true);
        addToCartBtn.setAttribute('disabled', true);

        if (checkin_val !== '' && checkout_val !== '') {
            pay_info.classList.add('d-none');
            pay_info.classList.replace('text-dark', 'text-danger');
            info_loader.classList.remove('d-none');

            let data = new FormData();
            data.append('check_availability', '');
            data.append('check_in',  checkin_val);
            data.append('check_out', checkout_val);

            let xhr = new XMLHttpRequest();
            xhr.open("POST", "ajax/user_confirm_booking.php", true);

            xhr.onload = function () {
                const raw  = this.responseText.substring(this.responseText.indexOf('{'));
                const resp = JSON.parse(raw);

                if (resp.status === 'check_in_out_equal') {
                    pay_info.innerText = "You cannot check-out on the same day!";
                } else if (resp.status === 'check_out_earlier') {
                    pay_info.innerText = "Check-out date is earlier than check-in date!";
                } else if (resp.status === 'check_in_earlier') {
                    pay_info.innerText = "Check-in date is earlier than today's date!";
                } else if (resp.status === 'unavailable') {
                    pay_info.innerText = "Room not available for these dates!";
                } else {
                    pay_info.innerHTML = "No. of Days: " + resp.days
                        + "<br>Total Amount to Pay: &#8369;" + resp.payment;
                    pay_info.classList.replace('text-danger', 'text-dark');
                    payNowBtn.removeAttribute('disabled');
                    addToCartBtn.removeAttribute('disabled');
                }

                pay_info.classList.remove('d-none');
                info_loader.classList.add('d-none');
            };

            xhr.send(data);
        }
    }

    function addToCart() {
        let checkin_val  = booking_form.elements['checkin'].value;
        let checkout_val = booking_form.elements['checkout'].value;

        if (!checkin_val || !checkout_val) {
            alert('error', 'Please select check-in and check-out dates first.');
            return;
        }

        addToCartBtn.disabled  = true;
        addToCartBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Adding...';

        let data = new FormData();
        data.append('add_to_cart', '');
        data.append('check_in',   checkin_val);
        data.append('check_out',  checkout_val);

        fetch('ajax/user_cart.php', { method: 'POST', body: data })
            .then(r => r.json())
            .then(function (res) {
                if (res.status === 'success') {
                    updateConfirmCartUI(res.count);
                    alert('success', res.message + ' — <a href="user_cart.php" class="alert-link"><?php echo t("cb_view_cart"); ?></a>');
                } else if (res.status === 'duplicate') {
                    alert('error', 'This room with these dates is already in your cart.');
                } else {
                    alert('error', res.message || 'Could not add to cart. Please try again.');
                }
            })
            .catch(() => alert('error', 'Connection error. Please try again.'))
            .finally(() => {
                addToCartBtn.disabled  = false;
                addToCartBtn.innerHTML = '<i class="bi bi-cart-plus me-1"></i> <?php echo t("cb_add_cart"); ?>'
                    + ` <span class="badge bg-danger ms-1">${document.getElementById('cart-badge-confirm').textContent}</span>`;
            });
    }

    function updateConfirmCartUI(count) {
        let badge    = document.getElementById('cart-badge-confirm');
        let link     = document.getElementById('view-cart-link');
        let countTxt = document.getElementById('cart-count-text');
        if (badge)    { badge.textContent = count; badge.classList.remove('d-none'); }
        if (link)     link.classList.remove('d-none');
        if (countTxt) countTxt.textContent = count;
        let headerBadge = document.getElementById('cart-badge');
        if (headerBadge) { headerBadge.textContent = count; headerBadge.style.display = count > 0 ? 'inline-flex' : 'none'; }
    }
</script>

<style>
    .custom-text-teal       { color: var(--teal); }
    .custom-text-teal:hover { color: var(--teal_hover); }
</style>

</body>
</html>