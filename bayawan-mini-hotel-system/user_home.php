<?php // bayawan-mini-hotel-system/user_home.php ?>
<!-- Carousel – Fixed height + one slide visible -->
<div class="container-fluid px-0 mt-4">
  <div class="swiper swiper-container" style="height: 500px; width: 100%; position: relative;">
    <div class="swiper-wrapper">
      <?php 
        $res = mysqli_query($conn, "SELECT * FROM carousel ORDER BY sr_no");
        if ($res && mysqli_num_rows($res) > 0) {
          while ($row = mysqli_fetch_assoc($res)) {
            $path = CAROUSEL_IMG_PATH;
            $img_src = $path . $row['image'];
            echo <<<data
              <div class="swiper-slide">
                <img src="$img_src" 
                     class="w-100 h-100" 
                     style="object-fit: cover;" 
                     alt="carousel image" 
                     onerror="this.src='assets/images/carousel/fallback.jpg'">
              </div>
            data;
          }
          mysqli_free_result($res);
        } else {
          // Fallback when table is empty
          echo <<<data
            <div class="swiper-slide bg-dark text-white d-flex align-items-center justify-content-center">
              <div class="text-center">
                <h3>No carousel images yet</h3>
                <p class="lead">Upload images to the 'carousel' table</p>
              </div>
            </div>
          data;
        }
      ?>
    </div>
    
    <!-- Navigation & Pagination -->
    <div class="swiper-button-next"></div>
    <div class="swiper-button-prev"></div>
    <div class="swiper-pagination"></div>
  </div>
</div>

<!-- Availability Form – Matches reference style -->
<div class="container availability-form mt-n5 position-relative z-2">
  <div class="row justify-content-center">
    <div class="col-lg-10 col-xl-8">
      <div class="card shadow-lg border-0 rounded-4 overflow-hidden">
        <div class="card-body p-4 p-lg-5 bg-white">
          <h3 class="fw-bold text-center mb-4 text-dark">Check Booking Availability</h3>
          <form action="user_rooms.php" method="get" class="row g-3 align-items-end">
            <div class="col-md-3">
              <label class="form-label fw-medium text-muted small mb-1">Check-in</label>
              <div class="input-group">
                <input type="date" class="form-control shadow-none" name="checkin" required>
                <span class="input-group-text bg-white border-start-0"><i class="bi bi-calendar3"></i></span>
              </div>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-medium text-muted small mb-1">Check-out</label>
              <div class="input-group">
                <input type="date" class="form-control shadow-none" name="checkout" required>
                <span class="input-group-text bg-white border-start-0"><i class="bi bi-calendar3"></i></span>
              </div>
            </div>
            <div class="col-md-2">
              <label class="form-label fw-medium text-muted small mb-1">Adults</label>
              <select class="form-select shadow-none" name="adult" required>
                <option value="1">1</option>
                <option value="2" selected>2</option>
                <option value="3">3</option>
                <option value="4">4+</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label fw-medium text-muted small mb-1">Children</label>
              <select class="form-select shadow-none" name="children">
                <option value="0">0</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3+</option>
              </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
              <button type="submit" class="btn btn-primary w-100 shadow-none fw-bold py-2">Check Now</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Our Rooms Preview -->
<h2 class="mt-5 pt-5 mb-4 text-center fw-bold h-font">OUR ROOMS</h2>
<div class="container">
  <div class="row g-4">
    <?php 
      $room_res = mysqli_query($conn, "
          SELECT * FROM rooms 
          WHERE status = 1 AND removed = 0 
          ORDER BY id DESC 
          LIMIT 3
      ");

      if ($room_res) {
        while ($room_data = mysqli_fetch_assoc($room_res)) {
          // Thumbnail
          $thumb = ROOMS_IMG_PATH . 'thumbnail-default.jpg';

          $thumb_q = mysqli_query($conn, "
              SELECT image 
              FROM room_images 
              WHERE room_id = {$room_data['id']} AND thumb = 1 
              LIMIT 1
          ");

          if (mysqli_num_rows($thumb_q) > 0) {
              $thumb_row = mysqli_fetch_assoc($thumb_q);
              $thumb = ROOMS_IMG_PATH . $thumb_row['image'];
          }
          mysqli_free_result($thumb_q);

          // Features
          $fea_data = '';
          $fea_q = mysqli_query($conn, "
              SELECT f.name 
              FROM features f 
              INNER JOIN room_features rf ON f.id = rf.features_id 
              WHERE rf.room_id = {$room_data['id']}
          ");
          while ($f = mysqli_fetch_assoc($fea_q)) {
              $fea_data .= "<span class='badge bg-light text-dark me-1 mb-1'>{$f['name']}</span>";
          }
          mysqli_free_result($fea_q);

          // Facilities
          $fac_data = '';
          $fac_q = mysqli_query($conn, "
              SELECT f.name 
              FROM facilities f 
              INNER JOIN room_facilities rf ON f.id = rf.facilities_id 
              WHERE rf.room_id = {$room_data['id']}
          ");
          while ($f = mysqli_fetch_assoc($fac_q)) {
              $fac_data .= "<span class='badge bg-light text-dark me-1 mb-1'>{$f['name']}</span>";
          }
          mysqli_free_result($fac_q);

          // Rating
          $rating = '';
          $avg_r = mysqli_fetch_assoc(mysqli_query($conn, "
              SELECT AVG(rating) AS avg 
              FROM rating_review 
              WHERE room_id = {$room_data['id']}
          "));
          if ($avg_r['avg'] > 0) {
              $stars = round($avg_r['avg']);
              for ($i = 0; $i < $stars; $i++) {
                  $rating .= "<i class='bi bi-star-fill text-warning'></i>";
              }
          }

          $book_btn = (!$settings_r['shutdown']) 
            ? ((isset($_SESSION['login']) && $_SESSION['login']) 
              ? "<a href='user_booking.php?id={$room_data['id']}' class='btn btn-sm btn-success shadow-none'>Book Now</a>"
              : "<button onclick='alert(\"Please login to book\");' class='btn btn-sm btn-success shadow-none'>Book Now</button>")
            : "<button class='btn btn-sm btn-danger shadow-none' disabled>Booking Closed</button>";
    ?>
    <div class="col-lg-4 col-md-6">
      <div class="card border-0 shadow-sm h-100">
        <img src="<?= htmlspecialchars($thumb) ?>" class="card-img-top" alt="<?= htmlspecialchars($room_data['name']) ?>" 
             style="height: 220px; object-fit: cover;">
        <div class="card-body d-flex flex-column">
          <h5 class="card-title"><?= htmlspecialchars($room_data['name']) ?></h5>
          <h6 class="mb-3 text-success fw-bold">₱<?= number_format($room_data['price']) ?> / night</h6>

          <div class="mb-3">
            <small class="d-block fw-medium mb-1">Features</small>
            <div class="d-flex flex-wrap gap-1"><?= $fea_data ?: '<small class="text-muted">None listed</small>' ?></div>
          </div>

          <div class="mb-3">
            <small class="d-block fw-medium mb-1">Facilities</small>
            <div class="d-flex flex-wrap gap-1"><?= $fac_data ?: '<small class="text-muted">None listed</small>' ?></div>
          </div>

          <div class="mb-3">
            <small class="d-block fw-medium mb-1">Guests</small>
            <span class="badge bg-light text-dark"><?= $room_data['adult'] ?> Adults</span>
            <span class="badge bg-light text-dark ms-1"><?= $room_data['children'] ?> Children</span>
          </div>

          <?php if ($rating): ?>
            <div class="mb-3">
              <small class="d-block fw-medium mb-1">Rating</small>
              <?= $rating ?>
            </div>
          <?php endif; ?>

          <div class="mt-auto d-flex gap-2">
            <?= $book_btn ?>
            <a href="user_room_details.php?id=<?= $room_data['id'] ?>" class="btn btn-sm btn-outline-dark shadow-none flex-grow-1">More Details</a>
          </div>
        </div>
      </div>
    </div>
    <?php 
        }
        mysqli_free_result($room_res);
      } 
    ?>
  </div>

  <div class="text-center mt-5">
    <a href="user_rooms.php" class="btn btn-outline-dark btn-lg px-5 fw-bold shadow-none">View All Rooms →</a>
  </div>
</div>

<!-- Swiper JS + Init -->
<script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>
<script>
  var swiper = new Swiper(".swiper-container", {
    spaceBetween: 0,
    effect: "fade",
    loop: true,
    autoplay: {
      delay: 4000,
      disableOnInteraction: false,
      pauseOnMouseEnter: true
    },
    pagination: {
      el: ".swiper-pagination",
      clickable: true
    },
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev"
    }
  });
</script>