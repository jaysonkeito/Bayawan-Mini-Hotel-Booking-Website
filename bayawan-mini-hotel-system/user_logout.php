<?php
// bayawan-mini-hotel-system/user_logout.php
session_start();
session_destroy();
header("Location: user_index.php");
exit;