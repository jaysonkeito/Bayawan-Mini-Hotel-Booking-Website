<?php // bayawan-mini-hotel-system/user_rooms.php ?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require 'includes/user_links.php'; ?>
    <title><?= htmlspecialchars($settings_r['site_title']) ?> - Rooms</title>
</head>
<body class="bg-light">

    <?php require 'includes/user_header.php';

    $checkin_default  = "";
    $checkout_default = "";
    $adult_default    = "";
    $children_default = "";

    if (isset($_GET['check_availability'])) {
        $frm_data         = filteration($_GET);
        $checkin_default  = $frm_data['checkin'];
        $checkout_default = $frm_data['checkout'];
        $adult_default    = $frm_data['adult'];
        $children_default = $frm_data['children'];
    }
    ?>

    <div class="my-5 px-4">
        <h2 class="fw-bold h-font text-center"><?php echo t('rooms_title'); ?></h2>
        <div class="h-line bg-dark mx-auto" style="width:150px;"></div>
    </div>

    <div class="container-fluid">
        <div class="row">

            <!-- Sidebar Filters -->
            <div class="col-lg-3 col-md-12 mb-4 ps-lg-4">
                <nav class="navbar navbar-expand-lg navbar-light bg-white rounded shadow">
                    <div class="container-fluid flex-lg-column align-items-stretch">
                        <h4 class="mt-2"><?php echo t('rooms_filters'); ?></h4>
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#filterDropdown">
                            <span class="navbar-toggler-icon"></span>
                        </button>

                        <div class="collapse navbar-collapse flex-column align-items-stretch mt-2" id="filterDropdown">

                            <!-- Check Availability -->
                            <div class="border bg-light p-3 rounded mb-3">
                                <h5 class="d-flex justify-content-between mb-3">
                                    <span><?php echo t('rooms_avail'); ?></span>
                                    <button id="chk_avail_btn" class="btn btn-sm text-secondary shadow-none d-none" onclick="chk_avail_clear()"><?php echo t('rooms_reset'); ?></button>
                                </h5>
                                <label class="form-label"><?php echo t('home_checkin'); ?></label>
                                <input type="date" class="form-control shadow-none mb-3" id="checkin">
                                <label class="form-label"><?php echo t('home_checkout'); ?></label>
                                <input type="date" class="form-control shadow-none" id="checkout">
                            </div>

                            <!-- Facilities -->
                            <div class="border bg-light p-3 rounded mb-3">
                                <h5 class="d-flex justify-content-between mb-3">
                                    <span><?php echo t('rooms_fac_filter'); ?></span>
                                    <button id="facilities_btn" class="btn btn-sm text-secondary shadow-none d-none" onclick="facilities_clear()"><?php echo t('rooms_reset'); ?></button>
                                </h5>
                                <?php
                                $fac_q = mysqli_query($conn, "SELECT * FROM facilities ORDER BY name");
                                while ($row = mysqli_fetch_assoc($fac_q)) {
                                    echo "
                                    <div class='mb-2'>
                                        <input type='checkbox' name='facilities' value='$row[id]' class='form-check-input shadow-none me-1' id='fac$row[id]' onclick='fetch_rooms()'>
                                        <label class='form-check-label' for='fac$row[id]'>$row[name]</label>
                                    </div>";
                                }
                                ?>
                            </div>

                            <!-- Guests -->
                            <div class="border bg-light p-3 rounded mb-3">
                                <h5 class="d-flex justify-content-between mb-3">
                                    <span><?php echo t('rooms_guests_filter'); ?></span>
                                    <button id="guests_btn" class="btn btn-sm text-secondary shadow-none d-none" onclick="guests_clear()"><?php echo t('rooms_reset'); ?></button>
                                </h5>
                                <div class="d-flex">
                                    <div class="me-3">
                                        <label class="form-label"><?php echo t('home_adults'); ?></label>
                                        <input type="number" min="1" class="form-control shadow-none" id="adults" oninput="guests_filter()">
                                    </div>
                                    <div>
                                        <label class="form-label"><?php echo t('home_children'); ?></label>
                                        <input type="number" min="0" class="form-control shadow-none" id="children" oninput="guests_filter()">
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </nav>
            </div>

            <!-- Rooms Container -->
            <div class="col-lg-9 col-md-12 px-4" id="rooms-data">
                <!-- AJAX content loads here -->
            </div>

        </div>
    </div>

    <script>
    function fetch_rooms() {
        let chk_avail = JSON.stringify({
            checkin:  document.getElementById('checkin').value,
            checkout: document.getElementById('checkout').value
        });
        let guests = JSON.stringify({
            adults:   document.getElementById('adults').value,
            children: document.getElementById('children').value
        });
        let facility_list = {facilities:[]};
        document.querySelectorAll('input[name="facilities"]:checked').forEach(el => {
            facility_list.facilities.push(el.value);
        });
        facility_list = JSON.stringify(facility_list);

        let xhr = new XMLHttpRequest();
        xhr.open("GET", `ajax/user_rooms.php?fetch_rooms&chk_avail=${chk_avail}&guests=${guests}&facility_list=${facility_list}`, true);
        xhr.onprogress = () => {
            document.getElementById('rooms-data').innerHTML = `
                <div class="text-center my-5">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>`;
        };
        xhr.onload = () => {
            document.getElementById('rooms-data').innerHTML = xhr.responseText;
        };
        xhr.send();
    }

    function chk_avail_clear() {
        document.getElementById('checkin').value  = '';
        document.getElementById('checkout').value = '';
        document.getElementById('chk_avail_btn').classList.add('d-none');
        fetch_rooms();
    }
    function guests_clear() {
        document.getElementById('adults').value   = '';
        document.getElementById('children').value = '';
        document.getElementById('guests_btn').classList.add('d-none');
        fetch_rooms();
    }
    function facilities_clear() {
        document.querySelectorAll('input[name="facilities"]').forEach(el => el.checked = false);
        document.getElementById('facilities_btn').classList.add('d-none');
        fetch_rooms();
    }
    function guests_filter() {
        if (document.getElementById('adults').value > 0 || document.getElementById('children').value > 0) {
            document.getElementById('guests_btn').classList.remove('d-none');
            fetch_rooms();
        }
    }
    document.getElementById('checkin').addEventListener('change', () => {
        if (document.getElementById('checkin').value && document.getElementById('checkout').value) {
            document.getElementById('chk_avail_btn').classList.remove('d-none');
            fetch_rooms();
        }
    });
    document.getElementById('checkout').addEventListener('change', () => {
        if (document.getElementById('checkin').value && document.getElementById('checkout').value) {
            document.getElementById('chk_avail_btn').classList.remove('d-none');
            fetch_rooms();
        }
    });
    window.onload = fetch_rooms;
    </script>

    <?php require 'includes/user_footer.php'; ?>
</body>
</html>