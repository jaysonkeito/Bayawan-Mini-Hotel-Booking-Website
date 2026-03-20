<?php 
  // bayawan-mini-hotel-system/admin/admin_logout.php
  require('includes/admin_essentials.php');

  session_start();
  session_destroy();
  redirect('admin_index.php');

?>