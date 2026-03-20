<?php
// bayawan-mini-hotel-system/includes/paymongo/user_config_paymongo.php
// All PayMongo values (keys, environment, callback URLs) are now defined
// in config/env.php and sourced from the .env file.
//
// PAYMONGO_SUCCESS_URL and PAYMONGO_FAILED_URL are built automatically
// from APP_URL in env.php — you never need to edit them here.

require_once __DIR__ . '/../../config/env.php';

// All constants (PAYMONGO_ENVIRONMENT, PAYMONGO_SECRET_KEY,
// PAYMONGO_PUBLIC_KEY, PAYMONGO_API_URL, PAYMONGO_SUCCESS_URL,
// PAYMONGO_FAILED_URL) are already defined by env.php above.
// This file intentionally contains no additional logic.