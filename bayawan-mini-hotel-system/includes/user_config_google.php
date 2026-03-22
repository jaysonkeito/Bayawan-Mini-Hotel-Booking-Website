<?php
// bayawan-mini-hotel-system/includes/user_config_google.php

// All Google OAuth values (client ID, secret, redirect URI) are now
// defined in config/env.php and sourced from the .env file.
//
// GOOGLE_REDIRECT_URI is built automatically from APP_URL in env.php:
//   {APP_URL}/ajax/user_google_callback.php
// Register that exact URL in Google Cloud Console under
// APIs & Services > Credentials > Authorized redirect URIs.

require_once __DIR__ . '/../config/env.php';

// All constants (GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET,
// GOOGLE_REDIRECT_URI) are already defined by env.php above.
// This file intentionally contains no additional logic.