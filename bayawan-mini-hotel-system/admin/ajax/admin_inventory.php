<?php
// bayawan-mini-hotel-system/admin/ajax/admin_inventory.php
require('../includes/admin_essentials.php');
require('../includes/admin_configuration.php');
adminLogin();

header('Content-Type: text/plain; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── LIST ─────────────────────────────────────────────────────────────
if ($action === 'list') {
    header('Content-Type: application/json; charset=utf-8');
    $rows = [];
    $r = mysqli_query($conn,
        "SELECT fm.id AS food_id,
                fm.name,
                fm.category,
                COALESCE(fi.stock_qty, 0)          AS stock_qty,
                COALESCE(fi.low_stock_threshold, 5) AS low_stock_threshold
         FROM food_menu fm
         LEFT JOIN food_inventory fi ON fm.id = fi.food_id
         WHERE fm.removed = 0
         ORDER BY fm.name ASC"
    );
    while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
    echo json_encode($rows);
    exit;
}

// ── RESTOCK ───────────────────────────────────────────────────────────
if ($action === 'restock') {
    $food_id   = intval($_POST['food_id']   ?? 0);
    $qty       = intval($_POST['qty']       ?? 0);
    $threshold = intval($_POST['threshold'] ?? 5);

    if (!$food_id || $qty < 1) {
        exit('Invalid data. Quantity must be at least 1.');
    }

    // Verify the food item exists
    $check = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id FROM food_menu WHERE id=$food_id AND removed=0"
    ));
    if (!$check) exit('Food item not found.');

    // Insert or add to existing stock
    $result = mysqli_query($conn,
        "INSERT INTO food_inventory (food_id, stock_qty, low_stock_threshold)
         VALUES ($food_id, $qty, $threshold)
         ON DUPLICATE KEY UPDATE
           stock_qty           = stock_qty + VALUES(stock_qty),
           low_stock_threshold = VALUES(low_stock_threshold)"
    );

    if (!$result) exit('Failed to update stock. Please try again.');

    exit('success');
}

// ── SET THRESHOLD ONLY ────────────────────────────────────────────────
if ($action === 'set_threshold') {
    $food_id   = intval($_POST['food_id']   ?? 0);
    $threshold = intval($_POST['threshold'] ?? 5);

    if (!$food_id || $threshold < 1) exit('Invalid data.');

    mysqli_query($conn,
        "INSERT INTO food_inventory (food_id, stock_qty, low_stock_threshold)
         VALUES ($food_id, 0, $threshold)
         ON DUPLICATE KEY UPDATE
           low_stock_threshold = VALUES(low_stock_threshold)"
    );

    exit('success');
}

exit('Unknown action.');