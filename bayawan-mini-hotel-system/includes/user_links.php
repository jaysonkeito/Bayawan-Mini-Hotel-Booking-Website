<?php
// bayawan-mini-hotel-system/includes/user_links.php
session_start();
date_default_timezone_set("Asia/Manila");

require('admin/includes/admin_configuration.php');
require('admin/includes/admin_essentials.php');
require_once __DIR__ . '/csrf.php';

// ── Update last activity timestamp on every page load ─────────────────
if (isset($_SESSION['login']) && $_SESSION['login'] == true) {
    $_SESSION['last_activity'] = time();
}

$contact_q  = "SELECT * FROM `contact_details` WHERE `sr_no`=?";
$settings_q = "SELECT * FROM `settings` WHERE `sr_no`=?";
$values     = [1];
$contact_r  = mysqli_fetch_assoc(select($contact_q, $values, 'i'));
$settings_r = mysqli_fetch_assoc(select($settings_q, $values, 'i'));
?>

<meta name="csrf-token" content="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

<!-- Bootstrap 5.0.2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC"
      crossorigin="anonymous">

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Merienda:wght@400;700&family=Poppins:wght@400;500;600&display=swap"
      rel="stylesheet">

<!-- Bootstrap Icons 1.9.1 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">

<!-- Custom CSS -->
<link rel="stylesheet" href="css/user_common.css">

<?php require_once __DIR__ . '/lang.php'; ?>