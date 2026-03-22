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
//  Complete Google Profile (Fix 3)
//  Saves the four fields Google OAuth cannot supply, then clears
//  the $_SESSION['google_new'] flag so the modal never shows again.
// ─────────────────────────────────────────────────────────────
if (isset($_POST['complete_google_profile'])) {
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

    $dob_ts = strtotime($dob);
    if (!$dob_ts || $dob_ts >= strtotime('-1 year')) {
        echo 'Please enter a valid date of birth.';
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
//  Password Update  ← FIXED
//
//  Changes from the original:
//  1. Requires and verifies the current password before allowing
//     a change — prevents any logged-in session from silently
//     overwriting the password without knowing it.
//  2. Reads all three password values directly from raw $_POST
//     instead of through filteration(). filteration() calls
//     htmlspecialchars() which corrupts passwords containing
//     &, <, >, ", or ' — the raw value is needed both for
//     password_verify() to work correctly and to ensure the
//     newly hashed password matches what the user types at login.
//  3. Prevents re-using the same password.
// ─────────────────────────────────────────────────────────────
if (isset($_POST['pass_form'])) {

    // Read passwords from raw $_POST — NOT through filteration()
    // Reason: filteration() calls htmlspecialchars() which would
    // mangle special characters (e.g. "P@ss&word" → "P@ss&amp;word")
    // before hashing, making the stored hash unmatchable at login.
    $current_pass = $_POST['current_pass'] ?? '';
    $new_pass     = $_POST['new_pass']     ?? '';
    $confirm_pass = $_POST['confirm_pass'] ?? '';

    // 1. Current password must be provided
    if (empty($current_pass)) {
        echo 'current_required';
        exit;
    }

    // 2. New and confirm must match
    if ($new_pass !== $confirm_pass) {
        echo 'mismatch';
        exit;
    }

    // 3. New password must not be empty
    if (empty($new_pass)) {
        echo 'empty_pass';
        exit;
    }

    // 4. Fetch stored hash and verify current password
    $user_q    = select(
        "SELECT `password` FROM `user_cred` WHERE `id` = ? LIMIT 1",
        [$_SESSION['uId']], 'i'
    );
    $user_data = mysqli_fetch_assoc($user_q);

    if (!$user_data || !password_verify($current_pass, $user_data['password'])) {
        echo 'wrong_pass';
        exit;
    }

    // 5. Prevent reusing the same password
    if (password_verify($new_pass, $user_data['password'])) {
        echo 'same_pass';
        exit;
    }

    // 6. Hash and save the new password
    $enc_pass = password_hash($new_pass, PASSWORD_DEFAULT);
    $query    = "UPDATE `user_cred` SET `password` = ? WHERE `id` = ? LIMIT 1";

    echo update($query, [$enc_pass, $_SESSION['uId']], 'si') ? 1 : 0;
}