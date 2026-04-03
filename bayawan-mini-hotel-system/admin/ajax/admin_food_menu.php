<?php
// bayawan-mini-hotel-system/admin/ajax/admin_food_menu.php
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
        "SELECT fm.*, 
                COALESCE(fi.stock_qty, 0)          AS stock_qty,
                COALESCE(fi.low_stock_threshold, 5) AS low_stock_threshold
         FROM food_menu fm
         LEFT JOIN food_inventory fi ON fm.id = fi.food_id
         WHERE fm.removed = 0
         ORDER BY fm.category, fm.name"
    );
    while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
    echo json_encode($rows);
    exit;
}

// ── CREATE ────────────────────────────────────────────────────────────
if ($action === 'create') {
    $name        = trim($_POST['name']        ?? '');
    $category    = trim($_POST['category']    ?? '');
    $price       = floatval($_POST['price']   ?? 0);
    $stock       = intval($_POST['stock']     ?? 0);
    $threshold   = intval($_POST['threshold'] ?? 5);
    $description = trim($_POST['description'] ?? '');
    $is_available = intval($_POST['is_available'] ?? 1);

    if (!$name || !$category || $price <= 0) {
        exit('Please fill all required fields.');
    }

    // Handle image upload
    $image = 'default_food.jpg';
    if (!empty($_FILES['image']['name'])) {
        $uploaded = uploadImage($_FILES['image'], 'food/');
        if (in_array($uploaded, ['inv_img', 'inv_size', 'upd_failed'])) {
            exit('Image upload failed. Use JPG/PNG/WebP under 2MB.');
        }
        $image = $uploaded;
    }

    $res = insert(
        "INSERT INTO food_menu (name, category, price, description, image, is_available, removed)
         VALUES (?, ?, ?, ?, ?, ?, 0)",
        [$name, $category, $price, $description, $image, $is_available],
        'ssdsis'
    );

    if (!$res) exit('Failed to save food item.');

    // Get the new food ID
    $new_id = mysqli_insert_id($conn);

    // Insert into food_inventory
    mysqli_query($conn,
        "INSERT INTO food_inventory (food_id, stock_qty, low_stock_threshold)
         VALUES ($new_id, $stock, $threshold)
         ON DUPLICATE KEY UPDATE
           stock_qty = stock_qty + VALUES(stock_qty),
           low_stock_threshold = VALUES(low_stock_threshold)"
    );

    exit('success');
}

// ── UPDATE ────────────────────────────────────────────────────────────
if ($action === 'update') {
    $id          = intval($_POST['id']        ?? 0);
    $name        = trim($_POST['name']        ?? '');
    $category    = trim($_POST['category']    ?? '');
    $price       = floatval($_POST['price']   ?? 0);
    $stock       = intval($_POST['stock']     ?? 0);
    $threshold   = intval($_POST['threshold'] ?? 5);
    $description = trim($_POST['description'] ?? '');
    $is_available = intval($_POST['is_available'] ?? 1);

    if (!$id || !$name || !$category || $price <= 0) {
        exit('Please fill all required fields.');
    }

    // Handle image upload
    $image_sql   = '';
    $image_vals  = [];
    $image_types = '';

    if (!empty($_FILES['image']['name'])) {
        // Delete old image
        $old_r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM food_menu WHERE id=$id"));
        if ($old_r && $old_r['image'] && $old_r['image'] !== 'default_food.jpg') {
            deleteImage($old_r['image'], 'food/');
        }

        $uploaded = uploadImage($_FILES['image'], 'food/');
        if (in_array($uploaded, ['inv_img', 'inv_size', 'upd_failed'])) {
            exit('Image upload failed. Use JPG/PNG/WebP under 2MB.');
        }
        $image_sql   = ', image=?';
        $image_vals  = [$uploaded];
        $image_types = 's';
    }

    $values = array_merge(
        [$name, $category, $price, $description, $is_available],
        $image_vals,
        [$id]
    );
    $types = 'ssdsi' . $image_types . 'i';

    update(
        "UPDATE food_menu
         SET name=?, category=?, price=?, description=?, is_available=?{$image_sql}
         WHERE id=?",
        $values,
        $types
    );

    // Update food_inventory
    mysqli_query($conn,
        "INSERT INTO food_inventory (food_id, stock_qty, low_stock_threshold)
         VALUES ($id, $stock, $threshold)
         ON DUPLICATE KEY UPDATE
           stock_qty = $stock,
           low_stock_threshold = $threshold"
    );

    exit('success');
}

// ── DELETE (soft) ─────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    if (!$id) exit('Invalid ID.');

    update("UPDATE food_menu SET removed=1 WHERE id=?", [$id], 'i');
    exit('success');
}

exit('Unknown action.');