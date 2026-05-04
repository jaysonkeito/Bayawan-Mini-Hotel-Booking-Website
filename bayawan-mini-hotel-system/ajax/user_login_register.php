<?php
// bayawan-mini-hotel-system/ajax/user_login_register.php
session_start();
require_once '../admin/includes/admin_essentials.php';
require_once '../admin/includes/admin_configuration.php';
require_once '../includes/vendor/autoload.php';
require_once '../includes/rate_limiter.php';
require_once '../includes/csrf.php';

use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: text/plain; charset=utf-8');

csrf_verify();

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

$action = $_POST['action'] ?? '';

switch ($action) {

case 'login':
    $rl = session_rate_limit('user_login');
    if (!$rl['allowed']) {
        exit("Too many failed attempts. Please wait " . format_retry_after($rl['retry_after']) . " before trying again.");
    }

    $email_mob = trim($_POST['email_mob'] ?? '');
    $pass      = $_POST['pass']           ?? '';
    $remember  = isset($_POST['remember']);

    if (!$email_mob || !$pass) {
        exit("Please fill all fields");
    }

    $stmt = $conn->prepare("SELECT id, name, email, password, profile
        FROM user_cred
        WHERE (email = ? OR phonenum = ?)
        AND is_verified = 1 AND status = 1 LIMIT 1");
    $stmt->bind_param("ss", $email_mob, $email_mob);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        if (password_verify($pass, $row['password'])) {
            session_rate_reset('user_login');

            $_SESSION['login']     = true;
            $_SESSION['uId']       = $row['id'];
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['user_pic']  = $row['profile'];

            if ($remember) {
                $token   = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                $stmt2   = $conn->prepare("UPDATE user_cred SET remember_token=?, remember_expires=? WHERE id=?");
                $stmt2->bind_param("ssi", $token, $expires, $row['id']);
                $stmt2->execute();
                setcookie('remember_token', $token, time()+2592000, '/', '', true, true);
            }

            // FIX 1: prepared statement instead of raw string interpolation
            $upd = $conn->prepare("UPDATE user_cred SET last_login = NOW() WHERE id = ?");
            $upd->bind_param("i", $row['id']);
            $upd->execute();
            $upd->close();

            exit("success");
        } else {
            $left = $rl['attempts_left'] - 1;
            exit("Invalid password." . ($left > 0 ? " {$left} attempt(s) remaining." : " Account temporarily locked."));
        }
    } else {
        $left = $rl['attempts_left'] - 1;
        exit("No account found." . ($left > 0 ? " {$left} attempt(s) remaining." : " Account temporarily locked."));
    }
    break;


case 'send_otp_register':
    $rl = session_rate_limit('register');
    if (!$rl['allowed']) {
        exit("Too many registration attempts. Please wait " . format_retry_after($rl['retry_after']) . " before trying again.");
    }

    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($name) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid name or email");
    }

    $check = $conn->prepare("SELECT id FROM user_cred WHERE email = ? LIMIT 1");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->close();
        die("This email is already registered. Please use a different email or login.");
    }
    $check->close();

    $otp = rand(100000, 999999);
    $_SESSION['reg_otp']      = $otp;
    $_SESSION['reg_otp_time'] = time();
    $_SESSION['reg_name']     = $name;
    $_SESSION['reg_email']    = $email;

    $body = "Hello {$name},<br><br>Your verification code is:<br><h2 style='letter-spacing:6px;'>{$otp}</h2><br>"
          . "This code is valid for 10 minutes.<br><br>Thank you,<br>Cebu Mini Hotel";

    echo sendEmail($email, "Your Cebu Mini Hotel Verification Code", $body)
        ? "OTP sent"
        : "Failed to send email. Please try again later.";
    break;


case 'verify_otp_register':
    $rl = session_rate_limit('otp');
    if (!$rl['allowed']) {
        exit("Too many incorrect attempts. Please wait " . format_retry_after($rl['retry_after']) . " before trying again.");
    }

    $otp = trim($_POST['otp'] ?? '');

    if (!isset($_SESSION['reg_otp']) || time() - $_SESSION['reg_otp_time'] > 600) {
        unset($_SESSION['reg_otp'], $_SESSION['reg_name'], $_SESSION['reg_email']);
        session_rate_reset('otp');
        die("Code expired or invalid session");
    }

    // FIX 2: strict === instead of ==
    if ($otp === (string)$_SESSION['reg_otp']) {
        session_rate_reset('otp');
        echo "OTP verified";
    } else {
        $left = $rl['attempts_left'] - 1;
        echo "Incorrect code." . ($left > 0 ? " {$left} attempt(s) remaining." : " Too many attempts — please wait " . format_retry_after(RATE_LOCKOUT_SECONDS) . ".");
    }
    exit;
    break;


case 'complete_register':
    $rl = session_rate_limit('register_submit');
    if (!$rl['allowed']) {
        exit("Too many registration attempts. Please wait " . format_retry_after($rl['retry_after']) . " before trying again.");
    }

    if (!isset($_SESSION['reg_email']) || !isset($_SESSION['reg_name'])) {
        die("Session expired. Please start registration again.");
    }

    $email    = $_SESSION['reg_email'];
    $name     = $_SESSION['reg_name'];
    $phone    = trim($_POST['phonenum'] ?? '');
    $address  = trim($_POST['address']  ?? '');
    $pincode  = trim($_POST['pincode']  ?? '');
    $dob      = trim($_POST['dob']      ?? '');
    $password = trim($_POST['pass']     ?? '');
    $cpass    = trim($_POST['cpass']    ?? '');

    if (empty($phone) || empty($address) || empty($pincode) || empty($dob)) {
        die("Please fill all required fields.");
    }

    if ($password !== $cpass)                                 die("Passwords do not match.");
    if (strlen($password) < 8)                               die("Password must be at least 8 characters long.");
    if (!preg_match('/[a-z]/', $password))                   die("Password must contain at least one lowercase letter.");
    if (!preg_match('/[A-Z]/', $password))                   die("Password must contain at least one uppercase letter.");
    if (!preg_match('/[0-9]/', $password))                   die("Password must contain at least one number.");
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) die("Password must contain at least one special character.");

    $hash    = password_hash($password, PASSWORD_DEFAULT);
    $picture = 'default.jpg';

    if (isset($_FILES['profile']) && $_FILES['profile']['error'] === UPLOAD_ERR_OK && !empty($_FILES['profile']['name'])) {
        $file       = $_FILES['profile'];
        $target_dir = rtrim(UPLOAD_IMAGE_PATH, '/') . '/' . trim(USERS_FOLDER, '/');

        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);

        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowed)) {
            $picture     = 'USR_' . time() . '_' . uniqid() . '.' . $ext;
            $target_file = $target_dir . '/' . $picture;
            if (!move_uploaded_file($file['tmp_name'], $target_file)) {
                $picture = 'default.jpg';
            }
        }
    }

    $sql  = "INSERT INTO user_cred
             (name, email, phonenum, password, address, pincode, dob, profile, is_verified, email_verified_at, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), 1)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) die("Prepare failed: " . $conn->error);

    $stmt->bind_param("ssssssss", $name, $email, $phone, $hash, $address, $pincode, $dob, $picture);

    if ($stmt->execute()) {
        session_rate_reset('register');
        session_rate_reset('register_submit');
        session_rate_reset('otp');
        unset($_SESSION['reg_otp'], $_SESSION['reg_otp_time'], $_SESSION['reg_name'], $_SESSION['reg_email']);
        echo "success";
    } else {
        error_log("Registration insert failed: " . $stmt->error);
        echo "Database error: Could not create account. Please try again later.";
    }

    $stmt->close();
    break;


case 'recover_password':
    // FIX 3: use dedicated reset_token/reset_expires columns + expiry check
    $email = trim($_POST['email'] ?? '');
    $token = trim($_POST['token'] ?? '');
    $pass  = trim($_POST['pass']  ?? '');

    if (empty($email) || empty($token) || empty($pass)) exit("failed");

    $stmt = $conn->prepare(
        "SELECT id FROM user_cred
         WHERE email = ? AND reset_token = ? AND reset_expires > NOW() LIMIT 1"
    );
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows == 0) exit("failed");

    $row      = $res->fetch_assoc();
    $new_hash = password_hash($pass, PASSWORD_DEFAULT);

    $upd = $conn->prepare(
        "UPDATE user_cred SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?"
    );
    $upd->bind_param("si", $new_hash, $row['id']);

    echo $upd->execute() ? "success" : "failed";
    break;


case 'forgot_pass':
    // FIX 3: store in reset_token/reset_expires, not remember_token
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) exit("inv_email");

    $stmt = $conn->prepare("SELECT id, name, is_verified, status FROM user_cred WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows == 0) exit("inv_email");

    $row = $res->fetch_assoc();
    if (!$row['is_verified']) exit("not_verified");
    if (!$row['status'])      exit("inactive");

    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $upd = $conn->prepare("UPDATE user_cred SET reset_token = ?, reset_expires = ? WHERE id = ?");
    $upd->bind_param("ssi", $token, $expires, $row['id']);
    $upd->execute();

    $reset_link = APP_URL . "/user_index.php?account_recovery&email="
                . urlencode($email) . "&token=" . urlencode($token);

    $body = "Hello {$row['name']},<br><br>"
          . "Click the link below to reset your password:<br><br>"
          . "<a href='$reset_link'>$reset_link</a><br><br>"
          . "This link is valid for 1 hour.<br><br>"
          . "If you did not request this, please ignore this email.<br><br>"
          . "Thank you,<br>Cebu Mini Hotel";

    echo sendEmail($email, "Reset Your Cebu Mini Hotel Password", $body)
        ? "success"
        : "mail_failed";
    break;


default:
    exit("Invalid action");
}