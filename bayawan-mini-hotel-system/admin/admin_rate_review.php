<?php // bayawan-mini-hotel-system/admin/admin_rate_review.php
require('includes/admin_essentials.php');
require('includes/admin_configuration.php');
adminOnly();

define('REVIEWS_PER_PAGE', 10);

if (isset($_GET['seen'])) {
    $frm_data = filteration($_GET);
    if ($frm_data['seen'] == 'all') {
        if (update("UPDATE `rating_review` SET `seen`=?", [1], 'i')) alert('success', 'Marked all as read!');
        else alert('error', 'Operation Failed!');
    } else {
        if (update("UPDATE `rating_review` SET `seen`=? WHERE `sr_no`=?", [1, $frm_data['seen']], 'ii')) alert('success', 'Marked as read!');
        else alert('error', 'Operation Failed!');
    }
}

if (isset($_GET['del'])) {
    $frm_data = filteration($_GET);
    if ($frm_data['del'] == 'all') {
        if (mysqli_query($conn, "DELETE FROM `rating_review`")) alert('success', 'All data deleted!');
        else alert('error', 'Operation failed!');
    } else {
        if (delete("DELETE FROM `rating_review` WHERE `sr_no`=?", [$frm_data['del']], 'i')) alert('success', 'Data deleted!');
        else alert('error', 'Operation failed!');
    }
}

// ── Pagination data ───────────────────────────────────────────────────
$page       = max(1, (int)($_GET['page'] ?? 1));
$start      = ($page - 1) * REVIEWS_PER_PAGE;

$count_res  = mysqli_query($conn, "SELECT COUNT(sr_no) AS total FROM `rating_review`");
$total_rows = mysqli_fetch_assoc($count_res)['total'];
$total_pages = ceil($total_rows / REVIEWS_PER_PAGE);

$data = mysqli_query($conn, "SELECT rr.*, uc.name AS uname, r.name AS rname
    FROM `rating_review` rr
    INNER JOIN `user_cred` uc ON rr.user_id = uc.id
    INNER JOIN `rooms` r ON rr.room_id = r.id
    ORDER BY `sr_no` DESC
    LIMIT $start, " . REVIEWS_PER_PAGE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Ratings & Reviews</title>
    <?php require('includes/admin_links.php'); ?>
</head>
<body class="bg-light">

<?php require('includes/admin_header.php'); ?>

<div id="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 p-4 overflow-hidden">
                <h3 class="mb-4">RATINGS & REVIEWS</h3>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="text-muted small">
                                Showing <?php echo $total_rows > 0 ? $start + 1 : 0; ?>–<?php echo min($start + REVIEWS_PER_PAGE, $total_rows); ?> of <?php echo $total_rows; ?> reviews
                            </span>
                            <div>
                                <a href="?seen=all" class="btn btn-dark rounded-pill shadow-none btn-sm">
                                    <i class="bi bi-check-all"></i> Mark all read
                                </a>
                                <a href="?del=all" class="btn btn-danger rounded-pill shadow-none btn-sm ms-1">
                                    <i class="bi bi-trash"></i> Delete all
                                </a>
                            </div>
                        </div>

                        <div class="table-responsive-md">
                            <table class="table table-hover border">
                                <thead>
                                    <tr class="bg-dark text-light">
                                        <th>#</th>
                                        <th>Room Name</th>
                                        <th>User Name</th>
                                        <th>Rating</th>
                                        <th width="30%">Review</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($total_rows == 0) {
                                        echo "<tr><td colspan='7' class='text-center'>No reviews yet!</td></tr>";
                                    } else {
                                        $i = $start + 1;
                                        while ($row = mysqli_fetch_assoc($data)) {
                                            $date = date('d-m-Y', strtotime($row['datentime']));
                                            $seen = '';
                                            if ($row['seen'] != 1) {
                                                $seen = "<a href='?seen=$row[sr_no]&page=$page' class='btn btn-sm rounded-pill btn-primary mb-2'>Mark as read</a><br>";
                                            }
                                            $seen .= "<a href='?del=$row[sr_no]&page=$page' class='btn btn-sm rounded-pill btn-danger'>Delete</a>";
                                            $unread_dot = ($row['seen'] != 1) ? "<span class='badge bg-warning ms-1'>New</span>" : "";
                                            echo <<<query
                                                <tr>
                                                    <td>$i</td>
                                                    <td>$row[rname]</td>
                                                    <td>$row[uname] $unread_dot</td>
                                                    <td>$row[rating] ⭐</td>
                                                    <td>$row[review]</td>
                                                    <td>$date</td>
                                                    <td>$seen</td>
                                                </tr>
                                            query;
                                            $i++;
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav class="mt-3">
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                    <li class="page-item"><a class="page-link shadow-none" href="?page=1">First</a></li>
                                    <li class="page-item"><a class="page-link shadow-none" href="?page=<?= $page - 1 ?>">Prev</a></li>
                                <?php endif; ?>

                                <?php
                                $p_start = max(1, $page - 2);
                                $p_end   = min($total_pages, $page + 2);
                                for ($p = $p_start; $p <= $p_end; $p++):
                                    $active = ($p == $page) ? 'active' : '';
                                ?>
                                    <li class="page-item <?= $active ?>">
                                        <a class="page-link shadow-none" href="?page=<?= $p ?>"><?= $p ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item"><a class="page-link shadow-none" href="?page=<?= $page + 1 ?>">Next</a></li>
                                    <li class="page-item"><a class="page-link shadow-none" href="?page=<?= $total_pages ?>">Last</a></li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require('includes/admin_scripts.php'); ?>
</body>
</html>