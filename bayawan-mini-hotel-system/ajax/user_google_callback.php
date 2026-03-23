<?php
// bayawan-mini-hotel-system/ajax/user_google_callback.php
session_start();
require_once '../admin/includes/admin_essentials.php';
require_once '../admin/includes/admin_configuration.php';
require_once '../includes/user_config_google.php';
require_once '../includes/vendor/autoload.php';
require_once '../includes/csrf.php';
csrf_verify();

// Validate OAuth state to prevent CSRF
if (empty($_GET['state']) || $_GET['state'] !== $_SESSION['oauth2state']) {
    unset($_SESSION['oauth2state']);
    header('Location: ../user_index.php');
    exit;
}

$provider = new League\OAuth2\Client\Provider\Google([
    'clientId'     => GOOGLE_CLIENT_ID,
    'clientSecret' => GOOGLE_CLIENT_SECRET,
    'redirectUri'  => GOOGLE_REDIRECT_URI,
]);

try {
    $token      = $provider->getAccessToken('authorization_code', ['code' => $_GET['code']]);
    $googleUser = $provider->getResourceOwner($token);
    $userData   = $googleUser->toArray();

    $name   = $userData['name']    ?? '';
    $email  = $userData['email']   ?? '';
    $avatar = $userData['picture'] ?? '';

    // Check if account already exists
    $stmt = $conn->prepare("SELECT * FROM user_cred WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        // ── Existing user: log in directly ──
        $row = $res->fetch_assoc();

        if (!$row['status']) {
            $_SESSION['google_error'] = "Your account has been suspended. Please contact admin.";
            header('Location: ../user_index.php');
            exit;
        }

        $_SESSION['login']     = true;
        $_SESSION['uId']       = $row['id'];
        $_SESSION['user_name'] = $row['name'];
        $_SESSION['user_pic']  = $row['profile'];

        $conn->query("UPDATE user_cred SET last_login = NOW() WHERE id = {$row['id']}");

        // If an existing Google user still has incomplete profile fields, re-prompt them
        if (empty($row['phonenum']) || empty($row['address']) || $row['dob'] === '2000-01-01') {
            $_SESSION['google_new'] = true;
        }

    } else {
        // ── New user: auto-register with placeholder fields ──
        // Phone, address, pincode, dob are intentionally blank/placeholder here.
        // The user_complete_profile_modal.php will prompt the user to fill these in
        // immediately after redirect, before they can do anything else.

        $picture = 'default.jpg';
        if (!empty($avatar)) {
            $img_data = @file_get_contents($avatar);
            if ($img_data !== false) {
                $filename    = 'USR_' . time() . '_' . uniqid() . '.jpg';
                // Use UPLOAD_IMAGE_PATH from essentials.php — no hardcoded path
                $target_path = UPLOAD_IMAGE_PATH . USERS_FOLDER . $filename;
                if (file_put_contents($target_path, $img_data)) {
                    $picture = $filename;
                }
            }
        }

        $placeholder_pass = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        $phone            = '';
        $address          = '';
        $pincode          = '';
        $dob              = '2000-01-01'; // replaced once the user completes the modal

        $sql  = "INSERT INTO user_cred
                 (name, email, phonenum, password, address, pincode, dob, profile, is_verified, email_verified_at, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), 1)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssss", $name, $email, $phone, $placeholder_pass, $address, $pincode, $dob, $picture);
        $stmt->execute();
        $new_id = $conn->insert_id;

        $_SESSION['login']      = true;
        $_SESSION['uId']        = $new_id;
        $_SESSION['user_name']  = $name;
        $_SESSION['user_pic']   = $picture;
        $_SESSION['google_new'] = true; // triggers user_complete_profile_modal.php
    }

    header('Location: ../user_index.php');
    exit;

} catch (Exception $e) {
    error_log("Google OAuth error: " . $e->getMessage());
    $_SESSION['google_error'] = "Google login failed. Please try again.";
    header('Location: ../user_index.php');
    exit;
}