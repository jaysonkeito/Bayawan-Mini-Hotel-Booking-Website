<?php
// bayawan-mini-hotel-system/ajax/user_profile.php

require('../admin/includes/admin_configuration.php');
require('../admin/includes/admin_essentials.php');

date_default_timezone_set("Asia/Manila");
session_start();

if (!(isset($_SESSION['login']) && $_SESSION['login'] == true)) {
    echo 0;
    exit;
}

// ─────────────────────────────────────────────────────────────
//  CSRF VALIDATION
//  Every mutating POST in this file is verified against the
//  session token generated in user_login_register.php.
// ─────────────────────────────────────────────────────────────
function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Invalid request token. Please refresh the page and try again.');
    }
}


// ─────────────────────────────────────────────────────────────
//  Complete Google Profile
// ─────────────────────────────────────────────────────────────
if (isset($_POST['complete_google_profile'])) {
    verify_csrf();
    $frm_data = filteration($_POST);

    $phone   = trim($frm_data['phonenum'] ?? '');
    $dob     = trim($frm_data['dob']      ?? '');
    $pincode = trim($frm_data['pincode']  ?? '');
    $address = trim($frm_data['address']  ?? '');

    if (empty($phone) || empty($dob) || empty($pincode) || empty($address)) {
        echo 'Please fill in all required fields.';
        exit;
    }

    $phone_check = select(
        "SELECT `id` FROM `user_cred` WHERE `phonenum` = ? AND `id` != ? LIMIT 1",
        [$phone, $_SESSION['uId']], 'si'
    );
    if (mysqli_num_rows($phone_check) > 0) {
        echo 'phone_taken';
        exit;
    }

    // FIX (Bug): Reject future dates AND enforce minimum age of 18 years
    $dob_ts  = strtotime($dob);
    $min_age = strtotime('-18 years');
    if (!$dob_ts || $dob_ts > time() || $dob_ts > $min_age) {
        echo 'Please enter a valid date of birth (must be 18 years or older).';
        exit;
    }

    $query  = "UPDATE `user_cred`
               SET `phonenum` = ?, `dob` = ?, `pincode` = ?, `address` = ?
               WHERE `id` = ? LIMIT 1";
    $values = [$phone, $dob, $pincode, $address, $_SESSION['uId']];

    if (update($query, $values, 'ssssi')) {
        unset($_SESSION['google_new']);
        echo 'success';
    } else {
        echo 'Could not save your profile. Please try again.';
    }
    exit;
}


// ─────────────────────────────────────────────────────────────
//  Basic Information Update
// ─────────────────────────────────────────────────────────────
if (isset($_POST['info_form'])) {
    verify_csrf();
    $frm_data = filteration($_POST);

    $u_exist = select(
        "SELECT * FROM `user_cred` WHERE `phonenum` = ? AND `id` != ? LIMIT 1",
        [$frm_data['phonenum'], $_SESSION['uId']], 'si'
    );

    if (mysqli_num_rows($u_exist) != 0) {
        echo 'phone_already';
        exit;
    }

    $query = "UPDATE `user_cred`
              SET `name` = ?, `address` = ?, `phonenum` = ?,
                  `pincode` = ?, `dob` = ?
              WHERE `id` = ? LIMIT 1";

    $values = [
        $frm_data['name'],
        $frm_data['address'],
        $frm_data['phonenum'],
        $frm_data['pincode'],
        $frm_data['dob'],
        $_SESSION['uId'],
    ];

    if (update($query, $values, 'sssssi')) {
        $_SESSION['user_name'] = $frm_data['name'];
        echo 1;
    } else {
        echo 0;
    }
}


// ─────────────────────────────────────────────────────────────
//  Profile Picture Update
// ─────────────────────────────────────────────────────────────
if (isset($_POST['profile_form'])) {
    verify_csrf();

    $img = uploadUserImage($_FILES['profile']);

    if ($img === 'inv_img') {
        echo 'inv_img';
        exit;
    } elseif ($img === 'upd_failed') {
        echo 'upd_failed';
        exit;
    }

    $u_exist = select(
        "SELECT `profile` FROM `user_cred` WHERE `id` = ? LIMIT 1",
        [$_SESSION['uId']], 'i'
    );
    $u_fetch = mysqli_fetch_assoc($u_exist);

    if (!empty($u_fetch['profile']) && $u_fetch['profile'] !== 'default.jpg') {
        deleteImage($u_fetch['profile'], USERS_FOLDER);
    }

    $query  = "UPDATE `user_cred` SET `profile` = ? WHERE `id` = ? LIMIT 1";
    $values = [$img, $_SESSION['uId']];

    if (update($query, $values, 'si')) {
        $_SESSION['user_pic'] = $img;
        echo 1;
    } else {
        echo 0;
    }
}


// ─────────────────────────────────────────────────────────────
//  Password Update
// ─────────────────────────────────────────────────────────────
if (isset($_POST['pass_form'])) {
    verify_csrf();

    $current_pass = $_POST['current_pass'] ?? '';
    $new_pass     = $_POST['new_pass']     ?? '';
    $confirm_pass = $_POST['confirm_pass'] ?? '';

    if (empty($current_pass)) { echo 'current_required'; exit; }
    if ($new_pass !== $confirm_pass) { echo 'mismatch'; exit; }
    if (empty($new_pass)) { echo 'empty_pass'; exit; }

    $user_q    = select(
        "SELECT `password` FROM `user_cred` WHERE `id` = ? LIMIT 1",
        [$_SESSION['uId']], 'i'
    );
    $user_data = mysqli_fetch_assoc($user_q);

    if (!$user_data || !password_verify($current_pass, $user_data['password'])) {
        echo 'wrong_pass';
        exit;
    }

    if (password_verify($new_pass, $user_data['password'])) {
        echo 'same_pass';
        exit;
    }

    $enc_pass = password_hash($new_pass, PASSWORD_DEFAULT);
    $query    = "UPDATE `user_cred` SET `password` = ? WHERE `id` = ? LIMIT 1";

    echo update($query, [$enc_pass, $_SESSION['uId']], 'si') ? 1 : 0;
}
