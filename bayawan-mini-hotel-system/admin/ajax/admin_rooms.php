<?php
// bayawan-mini-hotel-system/admin/ajax/admin_rooms.php

require('../includes/admin_configuration.php');
require('../includes/admin_essentials.php');
adminLogin();

define('ROOMS_PER_PAGE', 10);

function build_rooms_pagination($total_rows, $page) {
    $total_pages = ceil($total_rows / ROOMS_PER_PAGE);
    if ($total_pages <= 1) return '';

    $pagination = "";

    if ($page != 1) {
        $pagination .= "<li class='page-item'><button onclick='get_all_rooms(1)' class='page-link shadow-none'>First</button></li>";
    }

    $disabled = ($page == 1) ? "disabled" : "";
    $prev     = $page - 1;
    $pagination .= "<li class='page-item $disabled'><button onclick='get_all_rooms($prev)' class='page-link shadow-none'>Prev</button></li>";

    $start_p = max(1, $page - 2);
    $end_p   = min($total_pages, $page + 2);
    for ($p = $start_p; $p <= $end_p; $p++) {
        $active = ($p == $page) ? "active" : "";
        $pagination .= "<li class='page-item $active'><button onclick='get_all_rooms($p)' class='page-link shadow-none'>$p</button></li>";
    }

    $disabled = ($page == $total_pages) ? "disabled" : "";
    $next     = $page + 1;
    $pagination .= "<li class='page-item $disabled'><button onclick='get_all_rooms($next)' class='page-link shadow-none'>Next</button></li>";

    if ($page != $total_pages) {
        $pagination .= "<li class='page-item'><button onclick='get_all_rooms($total_pages)' class='page-link shadow-none'>Last</button></li>";
    }

    return $pagination;
}

// ─── ADD ROOM ─────────────────────────────────────────────────────────
if (isset($_POST['add_room'])) {
    $features   = filteration(json_decode($_POST['features']));
    $facilities = filteration(json_decode($_POST['facilities']));
    $frm_data   = filteration($_POST);
    $flag = 0;

    $q1 = "INSERT INTO `rooms` (`name`,`area`,`price`,`quantity`,`adult`,`children`,`description`) VALUES (?,?,?,?,?,?,?)";
    if (insert($q1, [$frm_data['name'],$frm_data['area'],$frm_data['price'],$frm_data['quantity'],$frm_data['adult'],$frm_data['children'],$frm_data['desc']], 'siiiiis')) {
        $flag = 1;
    }

    $room_id = mysqli_insert_id($GLOBALS['conn']);

    $q2 = "INSERT INTO `room_facilities`(`room_id`,`facilities_id`) VALUES (?,?)";
    if ($stmt = mysqli_prepare($GLOBALS['conn'], $q2)) {
        foreach ($facilities as $f) {
            mysqli_stmt_bind_param($stmt, 'ii', $room_id, $f);
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);
    } else { $flag = 0; }

    $q3 = "INSERT INTO `room_features`(`room_id`,`features_id`) VALUES (?,?)";
    if ($stmt = mysqli_prepare($GLOBALS['conn'], $q3)) {
        foreach ($features as $f) {
            mysqli_stmt_bind_param($stmt, 'ii', $room_id, $f);
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);
    } else { $flag = 0; }

    echo $flag;
}

// ─── GET ALL ROOMS (paginated) ────────────────────────────────────────
if (isset($_POST['get_all_rooms'])) {
    $frm_data = filteration($_POST);
    $page     = max(1, (int)($frm_data['page'] ?? 1));
    $start    = ($page - 1) * ROOMS_PER_PAGE;

    $count_res  = select("SELECT COUNT(id) AS total FROM `rooms` WHERE `removed`=?", [0], 'i');
    $total_rows = mysqli_fetch_assoc($count_res)['total'];

    $res  = select("SELECT * FROM `rooms` WHERE `removed`=? ORDER BY `id` DESC LIMIT ?,?",
        [0, $start, ROOMS_PER_PAGE], 'iii');

    if ($total_rows == 0) {
        echo json_encode(['table_data' => '<tr><td colspan="8" class="text-center"><b>No rooms found!</b></td></tr>', 'pagination' => '', 'total' => 0]);
        exit;
    }

    $i    = $start + 1;
    $data = "";

    while ($row = mysqli_fetch_assoc($res)) {
        $status = ($row['status'] == 1)
            ? "<button onclick='toggle_status($row[id],0)' class='btn btn-dark btn-sm shadow-none'>active</button>"
            : "<button onclick='toggle_status($row[id],1)' class='btn btn-warning btn-sm shadow-none'>inactive</button>";

        $data .= "
            <tr class='align-middle'>
                <td>$i</td>
                <td>$row[name]</td>
                <td>$row[area] sq. ft.</td>
                <td>
                    <span class='badge rounded-pill bg-light text-dark'>Adult: $row[adult]</span><br>
                    <span class='badge rounded-pill bg-light text-dark'>Children: $row[children]</span>
                </td>
                <td>&#8369;$row[price]</td>
                <td>$row[quantity]</td>
                <td>$status</td>
                <td>
                    <button type='button' onclick='edit_details($row[id])' class='btn btn-primary shadow-none btn-sm' data-bs-toggle='modal' data-bs-target='#edit-room'>
                        <i class='bi bi-pencil-square'></i>
                    </button>
                    <button type='button' onclick=\"room_images($row[id],'$row[name]')\" class='btn btn-info shadow-none btn-sm' data-bs-toggle='modal' data-bs-target='#room-images'>
                        <i class='bi bi-images'></i>
                    </button>
                    <button type='button' onclick='remove_room($row[id])' class='btn btn-danger shadow-none btn-sm'>
                        <i class='bi bi-trash'></i>
                    </button>
                </td>
            </tr>
        ";
        $i++;
    }

    echo json_encode([
        'table_data' => $data,
        'pagination' => build_rooms_pagination($total_rows, $page),
        'total'      => $total_rows,
    ]);
}

// ─── GET ROOM ─────────────────────────────────────────────────────────
if (isset($_POST['get_room'])) {
    $frm_data = filteration($_POST);
    $res1 = select("SELECT * FROM `rooms` WHERE `id`=?", [$frm_data['get_room']], 'i');
    $res2 = select("SELECT * FROM `room_features` WHERE `room_id`=?", [$frm_data['get_room']], 'i');
    $res3 = select("SELECT * FROM `room_facilities` WHERE `room_id`=?", [$frm_data['get_room']], 'i');

    $roomdata   = mysqli_fetch_assoc($res1);
    $features   = [];
    $facilities = [];

    while ($row = mysqli_fetch_assoc($res2)) array_push($features, $row['features_id']);
    while ($row = mysqli_fetch_assoc($res3)) array_push($facilities, $row['facilities_id']);

    echo json_encode(["roomdata" => $roomdata, "features" => $features, "facilities" => $facilities]);
}

// ─── EDIT ROOM ────────────────────────────────────────────────────────
if (isset($_POST['edit_room'])) {
    $features   = filteration(json_decode($_POST['features']));
    $facilities = filteration(json_decode($_POST['facilities']));
    $frm_data   = filteration($_POST);
    $flag = 0;

    $q1 = "UPDATE `rooms` SET `name`=?,`area`=?,`price`=?,`quantity`=?,`adult`=?,`children`=?,`description`=? WHERE `id`=?";
    if (update($q1, [$frm_data['name'],$frm_data['area'],$frm_data['price'],$frm_data['quantity'],$frm_data['adult'],$frm_data['children'],$frm_data['desc'],$frm_data['room_id']], 'siiiiisi')) {
        $flag = 1;
    }

    delete("DELETE FROM `room_features` WHERE `room_id`=?", [$frm_data['room_id']], 'i');
    delete("DELETE FROM `room_facilities` WHERE `room_id`=?", [$frm_data['room_id']], 'i');

    $q2 = "INSERT INTO `room_facilities`(`room_id`,`facilities_id`) VALUES (?,?)";
    if ($stmt = mysqli_prepare($GLOBALS['conn'], $q2)) {
        foreach ($facilities as $f) {
            mysqli_stmt_bind_param($stmt, 'ii', $frm_data['room_id'], $f);
            mysqli_stmt_execute($stmt);
        }
        $flag = 1;
        mysqli_stmt_close($stmt);
    }

    $q3 = "INSERT INTO `room_features`(`room_id`,`features_id`) VALUES (?,?)";
    if ($stmt = mysqli_prepare($GLOBALS['conn'], $q3)) {
        foreach ($features as $f) {
            mysqli_stmt_bind_param($stmt, 'ii', $frm_data['room_id'], $f);
            mysqli_stmt_execute($stmt);
        }
        $flag = 1;
        mysqli_stmt_close($stmt);
    }

    echo $flag;
}

// ─── TOGGLE STATUS ────────────────────────────────────────────────────
if (isset($_POST['toggle_status'])) {
    $frm_data = filteration($_POST);
    echo update("UPDATE `rooms` SET `status`=? WHERE `id`=?",
        [$frm_data['value'], $frm_data['toggle_status']], 'ii') ? 1 : 0;
}

// ─── ADD IMAGE ────────────────────────────────────────────────────────
if (isset($_POST['add_image'])) {
    $frm_data = filteration($_POST);
    $img_r    = uploadImage($_FILES['image'], ROOMS_FOLDER);

    if (in_array($img_r, ['inv_img','inv_size','upd_failed'])) {
        echo $img_r;
    } else {
        echo insert("INSERT INTO `room_images`(`room_id`,`image`) VALUES (?,?)",
            [$frm_data['room_id'], $img_r], 'is');
    }
}

// ─── GET ROOM IMAGES ─────────────────────────────────────────────────
if (isset($_POST['get_room_images'])) {
    $frm_data = filteration($_POST);
    $res  = select("SELECT * FROM `room_images` WHERE `room_id`=?", [$frm_data['get_room_images']], 'i');
    $path = ROOMS_IMG_PATH;

    while ($row = mysqli_fetch_assoc($res)) {
        $thumb_btn = ($row['thumb'] == 1)
            ? "<i class='bi bi-check-lg text-light bg-success px-2 py-1 rounded fs-5'></i>"
            : "<button onclick='thumb_image($row[sr_no],$row[room_id])' class='btn btn-secondary shadow-none'><i class='bi bi-check-lg'></i></button>";

        echo <<<data
            <tr class='align-middle'>
                <td><img src='$path$row[image]' class='img-fluid'></td>
                <td>$thumb_btn</td>
                <td>
                    <button onclick='rem_image($row[sr_no],$row[room_id])' class='btn btn-danger shadow-none'>
                        <i class='bi bi-trash'></i>
                    </button>
                </td>
            </tr>
        data;
    }
}

// ─── REMOVE IMAGE ─────────────────────────────────────────────────────
if (isset($_POST['rem_image'])) {
    $frm_data = filteration($_POST);
    $values   = [$frm_data['image_id'], $frm_data['room_id']];
    $res = select("SELECT * FROM `room_images` WHERE `sr_no`=? AND `room_id`=?", $values, 'ii');
    $img = mysqli_fetch_assoc($res);

    if (deleteImage($img['image'], ROOMS_FOLDER)) {
        echo delete("DELETE FROM `room_images` WHERE `sr_no`=? AND `room_id`=?", $values, 'ii');
    } else {
        echo 0;
    }
}

// ─── THUMB IMAGE ──────────────────────────────────────────────────────
if (isset($_POST['thumb_image'])) {
    $frm_data = filteration($_POST);
    update("UPDATE `room_images` SET `thumb`=? WHERE `room_id`=?", [0, $frm_data['room_id']], 'ii');
    echo update("UPDATE `room_images` SET `thumb`=? WHERE `sr_no`=? AND `room_id`=?",
        [1, $frm_data['image_id'], $frm_data['room_id']], 'iii');
}

// ─── REMOVE ROOM ──────────────────────────────────────────────────────
if (isset($_POST['remove_room'])) {
    $frm_data = filteration($_POST);
    $res1 = select("SELECT * FROM `room_images` WHERE `room_id`=?", [$frm_data['room_id']], 'i');
    while ($row = mysqli_fetch_assoc($res1)) deleteImage($row['image'], ROOMS_FOLDER);

    delete("DELETE FROM `room_images` WHERE `room_id`=?",     [$frm_data['room_id']], 'i');
    delete("DELETE FROM `room_features` WHERE `room_id`=?",   [$frm_data['room_id']], 'i');
    delete("DELETE FROM `room_facilities` WHERE `room_id`=?", [$frm_data['room_id']], 'i');
    echo update("UPDATE `rooms` SET `removed`=? WHERE `id`=?", [1, $frm_data['room_id']], 'ii') ? 1 : 0;
}