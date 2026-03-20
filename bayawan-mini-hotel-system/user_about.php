<?php // bayawan-mini-hotel-system/user_about.php ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://unpkg.com/swiper@8/swiper-bundle.min.css">
  <?php require('includes/user_links.php'); ?>
  <title><?php echo $settings_r['site_title'] ?> - ABOUT</title>
  <style>
    .box {
      border-top-color: var(--teal) !important;
    }
  </style>
</head>
<body class="bg-light">

  <?php require('includes/user_header.php'); ?>

  <div class="my-5 px-4">
    <h2 class="fw-bold h-font text-center">ABOUT US</h2>
    <div class="h-line bg-dark"></div>
    <p class="text-center mt-3">
      Learn more about Bayawan Mini Hotel — your home away from home <br>
      in the heart of Bayawan City, Negros Oriental.
    </p>
  </div>

  <div class="container">
    <div class="row justify-content-between align-items-center">
      <div class="col-lg-6 col-md-5 mb-4 order-lg-1 order-md-1 order-2">
        <h3 class="mb-3">Welcome to Bayawan Mini Hotel</h3>
        <p>
          Bayawan Mini Hotel is a cozy and affordable hotel located in Poblacion, 
          Bayawan City, Negros Oriental, Philippines. We are committed to providing 
          our guests with a warm, comfortable, and memorable stay in the heart of 
          the city.
        </p>
        <p>
          Whether you are traveling for business or leisure, our hotel offers 
          well-furnished rooms, modern amenities, and friendly service to ensure 
          that your stay exceeds expectations. From our Standard Rooms to our 
          premium Suite, every room is designed with your comfort in mind.
        </p>
        <p>
          We take pride in our prime location, making it easy for guests to explore 
          the beautiful city of Bayawan and nearby attractions in Negros Oriental. 
          Come and experience the warmth of Filipino hospitality at Bayawan Mini Hotel.
        </p>
      </div>
      <div class="col-lg-5 col-md-5 mb-4 order-lg-2 order-md-2 order-1">
          <img src="images/about/about.jpg" 
              class="w-100 rounded shadow" 
              style="height: 600px; object-fit: cover; object-position: center;">
      </div>
    </div>
  </div>

  <div class="container mt-5">
    <div class="row">
      <div class="col-lg-3 col-md-6 mb-4 px-4">
        <div class="bg-white rounded shadow p-4 border-top border-4 text-center box">
          <img src="images/about/hotel.svg" width="70px">
          <h4 class="mt-3">4 ROOM TYPES</h4>
          <p class="text-muted small mb-0">Standard, Deluxe, Family & Suite</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6 mb-4 px-4">
        <div class="bg-white rounded shadow p-4 border-top border-4 text-center box">
          <img src="images/about/customers.svg" width="70px">
          <h4 class="mt-3">500+ GUESTS</h4>
          <p class="text-muted small mb-0">Satisfied guests since opening</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6 mb-4 px-4">
        <div class="bg-white rounded shadow p-4 border-top border-4 text-center box">
          <img src="images/about/rating.svg" width="70px">
          <h4 class="mt-3">7 FACILITIES</h4>
          <p class="text-muted small mb-0">Modern amenities for your comfort</p>
        </div>
      </div>
      <div class="col-lg-3 col-md-6 mb-4 px-4">
        <div class="bg-white rounded shadow p-4 border-top border-4 text-center box">
          <img src="images/about/staff.svg" width="70px">
          <h4 class="mt-3">24/7 SERVICE</h4>
          <p class="text-muted small mb-0">Always here to assist you</p>
        </div>
      </div>
    </div>
  </div>

  <h3 class="my-5 fw-bold h-font text-center">MANAGEMENT TEAM</h3>

  <div class="container px-4">
    <div class="swiper mySwiper">
      <div class="swiper-wrapper mb-5">
        <?php 
          $about_r = selectAll('team_details');
          $path = ABOUT_IMG_PATH;
          while($row = mysqli_fetch_assoc($about_r)){
            echo <<<data
              <div class="swiper-slide bg-white text-center overflow-hidden rounded shadow">
                <img src="$path$row[picture]" class="w-100">
                <h5 class="mt-2 p-2">$row[name]</h5>
              </div>
            data;
          }
        ?>
      </div>
      <div class="swiper-pagination"></div>
    </div>
  </div>

  <?php require('includes/user_footer.php'); ?>

  <script>
    var swiper = new Swiper(".mySwiper", {
      spaceBetween: 40,
      pagination: {
        el: ".swiper-pagination",
      },
      breakpoints: {
        320:  { slidesPerView: 1 },
        640:  { slidesPerView: 1 },
        768:  { slidesPerView: 3 },
        1024: { slidesPerView: 3 },
      }
    });
  </script>

</body>
</html>