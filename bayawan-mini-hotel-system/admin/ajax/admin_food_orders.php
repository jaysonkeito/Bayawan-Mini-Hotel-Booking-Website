<?php
// bayawan-mini-hotel-system/admin/ajax/admin_food_orders.php
require('../includes/admin_essentials.php');
require('../includes/admin_configuration.php');
adminLogin();

header('Content-Type: text/plain; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── LIST ──────────────────────────────────────────────────────────────
if ($action === 'list') {
    header('Content-Type: application/json; charset=utf-8');
    $filter = $_GET['filter'] ?? 'active';

    $where = match($filter) {
        'active'    => "fo.status IN ('pending','preparing')",
        'delivered' => "fo.status = 'delivered'",
        'paid'      => "fo.status = 'paid'",
        'cancelled' => "fo.status = 'cancelled'",
        default     => '1=1',
    };

    $rows = [];
    $q = "SELECT fo.id, fo.booking_id, fo.room_no, fo.total_amount,
                 fo.status, fo.notes, fo.ordered_at,
                 uc.name AS user_name,
                 GROUP_CONCAT(CONCAT(foi.food_name,' x',foi.qty) ORDER BY foi.id SEPARATOR ', ') AS item_summary
          FROM food_orders fo
          INNER JOIN user_cred uc         ON fo.user_id   = uc.id
          LEFT  JOIN food_order_items foi ON foi.order_id = fo.id
          WHERE $where
          GROUP BY fo.id
          ORDER BY fo.ordered_at DESC";
    $r = mysqli_query($conn, $q);
    while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
    echo json_encode($rows);
    exit;
}

// ── ITEMS for a specific order ─────────────────────────────────────────
if ($action === 'items') {
    header('Content-Type: application/json; charset=utf-8');
    $order_id = (int)($_GET['order_id'] ?? 0);
    if (!$order_id) exit('[]');
    $rows = [];
    $r = mysqli_query($conn,
        "SELECT food_name, unit_price, qty, subtotal FROM food_order_items
         WHERE order_id = $order_id ORDER BY id");
    while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
    echo json_encode($rows);
    exit;
}

// ── UPDATE STATUS ─────────────────────────────────────────────────────
if ($action === 'update_status') {
    $order_id  = (int)  ($_POST['order_id'] ?? 0);
    $new_status = trim( ($_POST['status']   ?? ''));
    $allowed   = ['preparing','delivered','paid','cancelled'];

    if (!$order_id || !in_array($new_status, $allowed)) exit('Invalid data.');

    update(
        "UPDATE food_orders SET status=? WHERE id=?",
        [$new_status, $order_id], 'si'
    );
    exit('success');
}

exit('Unknown action.');