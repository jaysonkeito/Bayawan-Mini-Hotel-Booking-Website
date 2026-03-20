<?php // bayawan-mini-hotel-system/admin/admin_users.php
require('includes/admin_essentials.php');
require('includes/admin_configuration.php');
adminOnly();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Users</title>
    <?php require('includes/admin_links.php'); ?>
</head>
<body class="bg-light">

<?php require('includes/admin_header.php'); ?>

<div id="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 p-4 overflow-hidden">
                <h3 class="mb-4">USERS</h3>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="text-end mb-4">
                            <input type="text"
                                   oninput="search_user(this.value)"
                                   class="form-control shadow-none w-25 ms-auto"
                                   placeholder="Type to search...">
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover border text-center" style="min-width: 1300px;">
                                <thead>
                                    <tr class="bg-dark text-light">
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone no.</th>
                                        <th>Location</th>
                                        <th>DOB</th>
                                        <th>Verified</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="users-data"></tbody>
                            </table>
                        </div>
                        <nav>
                            <ul class="pagination mt-3" id="users-pagination"></ul>
                        </nav>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require('includes/admin_scripts.php'); ?>
<script src="scripts/admin_users.js"></script>

</body>
</html>