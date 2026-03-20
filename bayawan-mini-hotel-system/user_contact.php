<?php // bayawan-mini-hotel-system/user_contact.php ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require('includes/user_links.php'); ?>
    <title><?php echo $settings_r['site_title'] ?> - CONTACT</title>
</head>
<body class="bg-light">

<?php require('includes/user_header.php'); ?>

<div class="my-5 px-4">
    <h2 class="fw-bold h-font text-center">CONTACT US</h2>
    <div class="h-line bg-dark"></div>
    <p class="text-center mt-3">
        Have questions or need assistance? We'd love to hear from you. <br>
        Reach out to us and our friendly staff will get back to you as soon as possible.
    </p>
</div>

<div class="container">
    <div class="row">
        <div class="col-lg-6 col-md-6 mb-5 px-4">
            <div class="bg-white rounded shadow p-4">
                <iframe class="w-100 rounded mb-4" height="320px"
                        src="<?php echo $contact_r['iframe'] ?>" loading="lazy"></iframe>

                <h5>Address</h5>
                <a href="<?php echo $contact_r['gmap'] ?>" target="_blank"
                   class="d-inline-block text-decoration-none text-dark mb-2">
                    <i class="bi bi-geo-alt-fill"></i> <?php echo $contact_r['address'] ?>
                </a>

                <h5 class="mt-4">Call us</h5>
                <a href="tel:+<?php echo $contact_r['pn1'] ?>"
                   class="d-inline-block mb-2 text-decoration-none text-dark">
                    <i class="bi bi-telephone-fill"></i> +<?php echo $contact_r['pn1'] ?>
                </a><br>
                <?php if ($contact_r['pn2'] != ''): ?>
                    <a href="tel:+<?php echo $contact_r['pn2'] ?>"
                       class="d-inline-block text-decoration-none text-dark">
                        <i class="bi bi-telephone-fill"></i> +<?php echo $contact_r['pn2'] ?>
                    </a>
                <?php endif; ?>

                <h5 class="mt-4">Email</h5>
                <a href="mailto:<?php echo $contact_r['email'] ?>"
                   class="d-inline-block text-decoration-none text-dark">
                    <i class="bi bi-envelope-fill"></i> <?php echo $contact_r['email'] ?>
                </a>

                <h5 class="mt-4">Follow us</h5>
                <?php if ($contact_r['tw'] != ''): ?>
                    <a href="<?php echo $contact_r['tw'] ?>"
                       class="d-inline-block text-dark fs-5 me-2">
                        <i class="bi bi-twitter me-1"></i>
                    </a>
                <?php endif; ?>
                <a href="<?php echo $contact_r['fb'] ?>"
                   class="d-inline-block text-dark fs-5 me-2">
                    <i class="bi bi-facebook me-1"></i>
                </a>
                <a href="<?php echo $contact_r['insta'] ?>"
                   class="d-inline-block text-dark fs-5">
                    <i class="bi bi-instagram me-1"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-6 col-md-6 px-4">
            <div class="bg-white rounded shadow p-4">
                <form method="POST">
                    <h5>Send a message</h5>
                    <div class="mt-3">
                        <label class="form-label fw-medium">Name</label>
                        <input name="name" required type="text" class="form-control shadow-none">
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-medium">Email</label>
                        <input name="email" required type="email" class="form-control shadow-none">
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-medium">Subject</label>
                        <input name="subject" required type="text" class="form-control shadow-none">
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-medium">Message</label>
                        <textarea name="message" required class="form-control shadow-none"
                                  rows="5" style="resize:none;"></textarea>
                    </div>
                    <button type="submit" name="send"
                            class="btn text-white custom-bg mt-3">SEND</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
if (isset($_POST['send'])) {
    require_once('includes/rate_limiter.php');

    $ip = get_client_ip();
    $rl = db_rate_limit($conn, 'contact_form', $ip);

    if (!$rl['allowed']) {
        $wait = format_retry_after($rl['retry_after']);
        alert('error', "Too many messages sent. Please wait {$wait} before sending again.");
    } else {
        $frm_data = filteration($_POST);

        $q      = "INSERT INTO `user_queries`(`name`,`email`,`subject`,`message`) VALUES (?,?,?,?)";
        $values = [$frm_data['name'], $frm_data['email'], $frm_data['subject'], $frm_data['message']];
        $res    = insert($q, $values, 'ssss');

        if ($res == 1) {
            // Reset on success so legitimate users aren't permanently penalised
            db_rate_reset($conn, 'contact_form', $ip);
            alert('success', 'Message sent!');
        } else {
            alert('error', 'Server Down! Try again later.');
        }
    }
}
?>

<?php require('includes/user_footer.php'); ?>

</body>
</html>