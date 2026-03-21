<?php // bayawan-mini-hotel-system/admin/admin_user_queries.php
require('includes/admin_essentials.php');
require('includes/admin_configuration.php');
adminOnly();

define('QUERIES_PER_PAGE', 10);

if (isset($_GET['seen'])) {
    $frm_data = filteration($_GET);
    if ($frm_data['seen'] == 'all') {
        if (update("UPDATE `user_queries` SET `seen`=?", [1], 'i')) alert('success', 'Marked all as read!');
        else alert('error', 'Operation Failed!');
    } else {
        if (update("UPDATE `user_queries` SET `seen`=? WHERE `sr_no`=?", [1, $frm_data['seen']], 'ii')) alert('success', 'Marked as read!');
        else alert('error', 'Operation Failed!');
    }
}

if (isset($_GET['del'])) {
    $frm_data = filteration($_GET);
    if ($frm_data['del'] == 'all') {
        if (mysqli_query($conn, "DELETE FROM `user_queries`")) alert('success', 'All data deleted!');
        else alert('error', 'Operation failed!');
    } else {
        if (delete("DELETE FROM `user_queries` WHERE `sr_no`=?", [$frm_data['del']], 'i')) alert('success', 'Data deleted!');
        else alert('error', 'Operation failed!');
    }
}

// ── Pagination data ───────────────────────────────────────────────────
$page        = max(1, (int)($_GET['page'] ?? 1));
$start       = ($page - 1) * QUERIES_PER_PAGE;

$count_res   = mysqli_query($conn, "SELECT COUNT(sr_no) AS total FROM `user_queries`");
$total_rows  = mysqli_fetch_assoc($count_res)['total'];
$total_pages = ceil($total_rows / QUERIES_PER_PAGE);

$data = mysqli_query($conn, "SELECT * FROM `user_queries`
    ORDER BY `sr_no` DESC
    LIMIT $start, " . QUERIES_PER_PAGE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - User Queries</title>
    <?php require('includes/admin_links.php'); ?>
</head>
<body class="bg-light">

<?php require('includes/admin_header.php'); ?>

<div id="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 p-4 overflow-hidden">
                <h3 class="mb-4">USER QUERIES</h3>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="text-muted small">
                                Showing <?php echo $total_rows > 0 ? $start + 1 : 0; ?>–<?php echo min($start + QUERIES_PER_PAGE, $total_rows); ?> of <?php echo $total_rows; ?> queries
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
                                <thead class="sticky-top">
                                    <tr class="bg-dark text-light">
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th width="20%">Subject</th>
                                        <th width="30%">Message</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($total_rows == 0) {
                                        echo "<tr><td colspan='7' class='text-center'>No queries yet!</td></tr>";
                                    } else {
                                        $i = $start + 1;
                                        while ($row = mysqli_fetch_assoc($data)) {
                                            $date = date('d-m-Y', strtotime($row['datentime']));
                                            $seen = '';
                                            if ($row['seen'] != 1) {
                                                $seen = "<a href='?seen=$row[sr_no]&page=$page' class='btn btn-sm rounded-pill btn-primary'>Mark as read</a><br>";
                                            }
                                            $seen .= "<a href='?del=$row[sr_no]&page=$page' class='btn btn-sm rounded-pill btn-danger mt-2'>Delete</a>";
                                            $unread_dot = ($row['seen'] != 1) ? "<span class='badge bg-warning ms-1'>New</span>" : "";
                                            echo <<<query
                                                <tr>
                                                    <td>$i</td>
                                                    <td>$row[name] $unread_dot</td>
                                                    <td>$row[email]</td>
                                                    <td>$row[subject]</td>
                                                    <td>$row[message]</td>
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
<script>
  // ─── Alert Helper ───
  function alert(type, msg) {
      let bs_class = (type == 'success') ? 'alert-success' : 'alert-danger';
      
      // Remove any existing alert first
      let existing = document.querySelector('.custom-alert');
      if(existing) existing.remove();

      let element = document.createElement('div');
      element.classList.add('custom-alert');
      element.innerHTML = `
          <div class="alert ${bs_class} alert-dismissible fade show" role="alert">
              <strong class="me-3">${msg}</strong>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
      `;

      document.body.append(element);

      // Auto dismiss after 3 seconds
      setTimeout(() => {
          if(element && element.parentNode){
              element.remove();
          }
      }, 3000);
  }

  function remAlert(){
      let existing = document.querySelector('.custom-alert');
      if(existing) existing.remove();
  }

  // ─── Set Active Nav Link ───
  function setActive() {
    let navbar = document.getElementById('nav-bar');
    let a_tags = navbar.getElementsByTagName('a');
    for(let i = 0; i < a_tags.length; i++) {
      let file = a_tags[i].href.split('/').pop();
      let file_name = file.split('.')[0];
      if(document.location.href.indexOf(file_name) >= 0){
        a_tags[i].classList.add('active');
      }
    }
  }

  setActive(); 
</script>
</body>
</html>