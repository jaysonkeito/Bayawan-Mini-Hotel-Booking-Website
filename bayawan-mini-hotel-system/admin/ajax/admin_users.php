<?php
// bayawan-mini-hotel-system/admin/ajax/admin_users.php
require('../includes/admin_configuration.php');
require('../includes/admin_essentials.php');
require_once '../../includes/csrf.php';
csrf_verify();
date_default_timezone_set("Asia/Manila");
adminLogin();

define('USERS_PER_PAGE', 10);

function build_user_row($row, $i, $path) {
    $del_btn  = "";
    $verified = "<span class='badge bg-warning'><i class='bi bi-x-lg'></i></span>";

    if ($row['is_verified']) {
        $verified = "<span class='badge bg-success'><i class='bi bi-check-lg'></i></span>";
    } else {
        $del_btn = "<button type='button' onclick='remove_user($row[id])' class='btn btn-danger shadow-none btn-sm'>
            <i class='bi bi-trash'></i>
        </button>";
    }

    $status = ($row['status'])
        ? "<button onclick='toggle_status($row[id],0)' class='btn btn-dark btn-sm shadow-none'>active</button>"
        : "<button onclick='toggle_status($row[id],1)' class='btn btn-danger btn-sm shadow-none'>inactive</button>";

    $date = date("d-m-Y", strtotime($row['created_at']));

    return "
        <tr>
            <td>$i</td>
            <td><img src='$path$row[profile]' width='55px'><br>$row[name]</td>
            <td>$row[email]</td>
            <td>$row[phonenum]</td>
            <td>$row[address] | $row[pincode]</td>
            <td>$row[dob]</td>
            <td>$verified</td>
            <td>$status</td>
            <td>$date</td>
            <td>$del_btn</td>
        </tr>
    ";
}

function build_pagination($total_rows, $page, $search = '') {
    $total_pages = ceil($total_rows / USERS_PER_PAGE);
    if ($total_pages <= 1) return '';

    $pagination = "";

    if ($page != 1) {
        $pagination .= "<li class='page-item'><button onclick='get_users(\"$search\",1)' class='page-link shadow-none'>First</button></li>";
    }

    $disabled = ($page == 1) ? "disabled" : "";
    $prev     = $page - 1;
    $pagination .= "<li class='page-item $disabled'><button onclick='get_users(\"$search\",$prev)' class='page-link shadow-none'>Prev</button></li>";

    // Show up to 5 page number buttons around current page
    $start = max(1, $page - 2);
    $end   = min($total_pages, $page + 2);
    for ($p = $start; $p <= $end; $p++) {
        $active = ($p == $page) ? "active" : "";
        $pagination .= "<li class='page-item $active'><button onclick='get_users(\"$search\",$p)' class='page-link shadow-none'>$p</button></li>";
    }

    $disabled = ($page == $total_pages) ? "disabled" : "";
    $next     = $page + 1;
    $pagination .= "<li class='page-item $disabled'><button onclick='get_users(\"$search\",$next)' class='page-link shadow-none'>Next</button></li>";

    if ($page != $total_pages) {
        $pagination .= "<li class='page-item'><button onclick='get_users(\"$search\",$total_pages)' class='page-link shadow-none'>Last</button></li>";
    }

    return $pagination;
}

// ─── GET USERS (with pagination + search) ────────────────────────────
if (isset($_POST['get_users'])) {
    $frm_data = filteration($_POST);
    $page     = max(1, (int)($frm_data['page'] ?? 1));
    $search   = $frm_data['search'] ?? '';
    $start    = ($page - 1) * USERS_PER_PAGE;
    $path     = USERS_IMG_PATH;

    // Total count for pagination
    $count_res   = select("SELECT COUNT(id) AS total FROM `user_cred` WHERE `name` LIKE ? OR `email` LIKE ?",
        ["%$search%", "%$search%"], 'ss');
    $total_rows  = mysqli_fetch_assoc($count_res)['total'];

    // Paginated data
    $res  = select("SELECT * FROM `user_cred` WHERE `name` LIKE ? OR `email` LIKE ? ORDER BY `id` DESC LIMIT ?,?",
        ["%$search%", "%$search%", $start, USERS_PER_PAGE], 'ssii');

    if ($total_rows == 0) {
        echo json_encode(['table_data' => '<tr><td colspan="10" class="text-center"><b>No Data Found!</b></td></tr>', 'pagination' => '', 'total' => 0]);
        exit;
    }

    $i          = $start + 1;
    $table_data = "";
    while ($row = mysqli_fetch_assoc($res)) {
        $table_data .= build_user_row($row, $i, $path);
        $i++;
    }

    echo json_encode([
        'table_data' => $table_data,
        'pagination' => build_pagination($total_rows, $page, $search),
        'total'      => $total_rows,
    ]);
}

// ─── TOGGLE STATUS ────────────────────────────────────────────────────
if (isset($_POST['toggle_status'])) {
    $frm_data = filteration($_POST);
    echo update("UPDATE `user_cred` SET `status`=? WHERE `id`=?",
        [$frm_data['value'], $frm_data['toggle_status']], 'ii') ? 1 : 0;
}

// ─── REMOVE USER ──────────────────────────────────────────────────────
if (isset($_POST['remove_user'])) {
    $frm_data = filteration($_POST);
    echo delete("DELETE FROM `user_cred` WHERE `id`=? AND `is_verified`=?",
        [$frm_data['user_id'], 0], 'ii') ? 1 : 0;
}