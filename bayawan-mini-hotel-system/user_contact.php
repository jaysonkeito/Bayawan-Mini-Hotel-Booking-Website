<?php
// bayawan-mini-hotel-system/user_contact.php   
// ─────────────────────────────────────────────────────────────────────
// DB config and form processing MUST come before any HTML output
// so that header() redirects work correctly (PRG pattern).
// ─────────────────────────────────────────────────────────────────────

// Load only what's needed before HTML output
require('includes/user_links.php');
require_once('config/env.php');

// Direct DB connection (avoids redeclare conflict with user_links.php)
if (!isset($conn)) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
}

require_once('includes/rate_limiter.php');


// ── Form processing ───────────────────────────────────────────────────
if (isset($_POST['send'])) {
    $ip = get_client_ip();
    $rl = db_rate_limit($conn, 'contact_form', $ip);

    if (!$rl['allowed']) {
        header('Location: user_contact.php?status=rate_limited');
        exit;
    }

    $frm_data = filteration($_POST);
    $q        = "INSERT INTO `user_queries`(`name`,`email`,`subject`,`message`) VALUES (?,?,?,?)";
    $values   = [$frm_data['name'], $frm_data['email'], $frm_data['subject'], $frm_data['message']];
    $res      = insert($q, $values, 'ssss');

    if ($res == 1) {
        db_rate_reset($conn, 'contact_form', $ip);
        header('Location: user_contact.php?status=sent');
        exit;
    } else {
        header('Location: user_contact.php?status=error');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $settings_r['site_title'] ?> - CONTACT</title>
</head>
<body class="bg-light">

<?php require('includes/user_header.php'); ?>

<div class="my-5 px-4">
    <h2 class="fw-bold h-font text-center"><?php echo t('contact_title'); ?></h2>
    <div class="h-line bg-dark"></div>
    <p class="text-center mt-3"><?php echo t('contact_subtitle'); ?></p>
</div>

<div class="container">
    <div class="row">
        <div class="col-lg-6 col-md-6 mb-5 px-4">
            <div class="bg-white rounded shadow p-4">
                <?php
                preg_match('/src="([^"]+)"/', $contact_r['iframe'], $iframe_match);
                $iframe_src = $iframe_match[1] ?? '';
                ?>
                <iframe class="w-100 rounded mb-4" height="320px"
                        src="<?php echo htmlspecialchars($iframe_src); ?>" loading="lazy"></iframe>

                <h5><?php echo t('contact_address'); ?></h5>
                <a href="<?php echo $contact_r['gmap'] ?>" target="_blank"
                   class="d-inline-block text-decoration-none text-dark mb-2">
                    <i class="bi bi-geo-alt-fill"></i> <?php echo $contact_r['address'] ?>
                </a>

                <h5 class="mt-4"><?php echo t('contact_call'); ?></h5>
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

                <h5 class="mt-4"><?php echo t('contact_email_lbl'); ?></h5>
                <a href="mailto:<?php echo $contact_r['email'] ?>"
                   class="d-inline-block text-decoration-none text-dark">
                    <i class="bi bi-envelope-fill"></i> <?php echo $contact_r['email'] ?>
                </a>

                <h5 class="mt-4"><?php echo t('contact_follow'); ?></h5>
                <?php if ($contact_r['tw'] != ''): ?>
                    <a href="<?php echo $contact_r['tw'] ?>" class="d-inline-block text-dark fs-5 me-2">
                        <i class="bi bi-twitter me-1"></i>
                    </a>
                <?php endif; ?>
                <a href="<?php echo $contact_r['fb'] ?>" class="d-inline-block text-dark fs-5 me-2">
                    <i class="bi bi-facebook me-1"></i>
                </a>
                <a href="<?php echo $contact_r['insta'] ?>" class="d-inline-block text-dark fs-5">
                    <i class="bi bi-instagram me-1"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-6 col-md-6 px-4">
            <div class="bg-white rounded shadow p-4">
                <form method="POST">
                    <h5><?php echo t('contact_send_msg'); ?></h5>
                    <div class="mt-3">
                        <label class="form-label fw-medium"><?php echo t('contact_name'); ?></label>
                        <input name="name" required type="text" class="form-control shadow-none">
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-medium"><?php echo t('contact_email_lbl'); ?></label>
                        <input name="email" required type="email" class="form-control shadow-none">
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-medium"><?php echo t('contact_subject'); ?></label>
                        <input name="subject" required type="text" class="form-control shadow-none">
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-medium"><?php echo t('contact_message'); ?></label>
                        <textarea name="message" required class="form-control shadow-none"
                                  rows="5" style="resize:none;"></textarea>
                    </div>
                    <button type="submit" name="send"
                            class="btn text-white custom-bg mt-3"><?php echo t('contact_send_btn'); ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// ── Show alert based on GET status after PRG redirect ─────────────────
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'sent') {
        alert('success', 'Message sent!');
    } elseif ($_GET['status'] === 'error') {
        alert('error', 'Server Down! Try again later.');
    } elseif ($_GET['status'] === 'rate_limited') {
        alert('error', 'Too many messages sent. Please wait before trying again.');
    }
}
?>

<?php require('includes/user_footer.php'); ?>
</body>
</html>