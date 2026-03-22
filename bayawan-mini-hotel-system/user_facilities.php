<?php // bayawan-mini-hotel-system/user_facilities.php ?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php require('includes/user_links.php'); ?>
  <title><?php echo $settings_r['site_title'] ?> - FACILITIES</title>
  <style>
    .pop:hover { border-top-color:var(--teal)!important; transform:scale(1.03); transition:all 0.3s; }
  </style>
</head>
<body class="bg-light">

  <?php require('includes/user_header.php'); ?>

  <div class="my-5 px-4">
    <h2 class="fw-bold h-font text-center"><?php echo t('fac_title'); ?></h2>
    <div class="h-line bg-dark"></div>
    <p class="text-center mt-3"><?php echo t('fac_subtitle'); ?></p>
  </div>

  <div class="container">
    <div class="row">
      <?php
        $res  = selectAll('facilities');
        $path = FACILITIES_IMG_PATH;
        while ($row = mysqli_fetch_assoc($res)) {
          echo<<<data
            <div class="col-lg-4 col-md-6 mb-5 px-4">
              <div class="bg-white rounded shadow p-4 border-top border-4 border-dark pop">
                <div class="d-flex align-items-center mb-2">
                  <img src="$path$row[icon]" width="40px">
                  <h5 class="m-0 ms-3">$row[name]</h5>
                </div>
                <p>$row[description]</p>
              </div>
            </div>
          data;
        }
      ?>
    </div>
  </div>

  <?php require('includes/user_footer.php'); ?>
</body>
</html>