<?php
// bayawan-mini-hotel-system/ajax/user_google_auth.php
session_start();
require_once '../admin/includes/admin_essentials.php';
require_once '../admin/includes/admin_configuration.php';
require_once '../includes/user_config_google.php';
require_once '../includes/vendor/autoload.php';
require_once '../includes/csrf.php';
csrf_verify();

$provider = new League\OAuth2\Client\Provider\Google([
    'clientId'     => GOOGLE_CLIENT_ID,
    'clientSecret' => GOOGLE_CLIENT_SECRET,
    'redirectUri'  => GOOGLE_REDIRECT_URI,
]);

$authUrl = $provider->getAuthorizationUrl([
    'scope' => ['openid', 'profile', 'email']
]);

$_SESSION['oauth2state'] = $provider->getState();
$_SESSION['google_action'] = $_GET['action'] ?? 'login'; // 'login' or 'register'

header('Location: ' . $authUrl);
exit;