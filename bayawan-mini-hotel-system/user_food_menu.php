<?php
// bayawan-mini-hotel-system/user_food_menu.php
// Only accessible to logged-in guests who have an ACTIVE (checked-in) booking.
require 'includes/user_links.php';
require_once 'includes/csrf.php';

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header('Location: user_index.php');
    exit;
}

$user_id = $_SESSION['uId'];

// Find the guest's active checked-in booking
$active_q = "SELECT bo.booking_id, bo.room_id, bo.check_in, bo.check_out,
                     bd.room_no, r.name AS room_name
              FROM booking_order bo
              INNER JOIN booking_details bd ON bo.booking_id = bd.booking_id
              INNER JOIN rooms r            ON bo.room_id    = r.id
              WHERE bo.user_id       = ?
                AND bo.booking_status = 'checked_in'
                AND bo.arrival       = 1
              ORDER BY bo.check_in DESC
              LIMIT 1";
$active_r = mysqli_fetch_assoc(select($active_q, [$user_id], 'i'));

$is_checked_in = (bool) $active_r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($settings_r['site_title']) ?> - Food Menu</title>
  <?php require 'includes/user_links.php'; ?>
</head>
<body class="bg-light">
<?php require 'includes/user_header.php'; ?>

<div class="my-5 px-4">
  <h2 class="fw-bold h-font text-center">ROOM SERVICE MENU</h2>
  <div class="h-line bg-dark mx-auto" style="width:150px;"></div>
  <p class="text-center text-muted mt-2">Order food delivered straight to your room</p>
</div>

<?php if (!$is_checked_in): ?>
  <!-- Not checked in — show friendly notice -->
  <div class="container">
    <div class="alert alert-warning text-center" role="alert">
      <i class="bi bi-info-circle-fill me-2"></i>
      Food ordering is only available for guests who are currently checked in.
      <br>
      <a href="user_bookings.php" class="btn btn-sm btn-outline-dark mt-3">View My Bookings</a>
    </div>
  </div>
<?php else: ?>

<div class="container mb-5">

  <!-- Room info banner -->
  <div class="alert alert-info d-flex align-items-center gap-3 mb-4" role="alert">
    <i class="bi bi-door-open-fill fs-4"></i>
    <div>
      Ordering for <strong><?= htmlspecialchars($active_r['room_no']) ?></strong>
      &mdash; <?= htmlspecialchars($active_r['room_name']) ?>
      <span class="text-muted ms-3 small">
        Check-out: <?= date('F j, Y', strtotime($active_r['check_out'])) ?>
      </span>
    </div>
  </div>

  <div class="row">

    <!-- ── Food Categories & Cards ── -->
    <div class="col-lg-8">

      <!-- Category filter tabs -->
      <ul class="nav nav-pills mb-4" id="categoryTabs">
        <li class="nav-item">
          <button class="nav-link active me-1" onclick="filterCategory('all', this)">All</button>
        </li>
        <?php
        $cat_q = "SELECT DISTINCT category FROM food_menu WHERE is_available=1 AND removed=0 ORDER BY category";
        $cat_r = mysqli_query($conn, $cat_q);
        while ($cat = mysqli_fetch_assoc($cat_r)):
        ?>
        <li class="nav-item">
          <button class="nav-link me-1" onclick="filterCategory('<?= htmlspecialchars($cat['category']) ?>', this)">
            <?= htmlspecialchars($cat['category']) ?>
          </button>
        </li>
        <?php endwhile; ?>
      </ul>

      <!-- Food cards -->
      <div class="row g-3" id="food-cards">
        <?php
        $menu_q = "SELECT fm.*, fi.stock_qty FROM food_menu fm
                   LEFT JOIN food_inventory fi ON fm.id = fi.food_id
                   WHERE fm.is_available = 1 AND fm.removed = 0
                   ORDER BY fm.category, fm.name";
        $menu_r = mysqli_query($conn, $menu_q);
        while ($item = mysqli_fetch_assoc($menu_r)):
            $in_stock = ((int)($item['stock_qty'] ?? 0)) > 0;
            $img_src  = 'images/food/' . htmlspecialchars($item['image']);
        ?>
        <div class="col-md-6 food-card" data-category="<?= htmlspecialchars($item['category']) ?>">
          <div class="card border-0 shadow-sm h-100 <?= $in_stock ? '' : 'opacity-50' ?>">
            <img src="<?= $img_src ?>" class="card-img-top" style="height:160px;object-fit:cover;"
                 onerror="this.src='images/food/default_food.jpg'">
            <div class="card-body d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-1">
                <h6 class="card-title mb-0"><?= htmlspecialchars($item['name']) ?></h6>
                <span class="badge bg-<?= $in_stock ? 'success' : 'secondary' ?> ms-2">
                  <?= $in_stock ? 'Available' : 'Out of stock' ?>
                </span>
              </div>
              <p class="text-muted small mb-2"><?= htmlspecialchars($item['description']) ?></p>
              <div class="mt-auto d-flex justify-content-between align-items-center">
                <strong class="text-success">&#8369;<?= number_format($item['price'], 2) ?></strong>
                <?php if ($in_stock): ?>
                <div class="d-flex align-items-center gap-2">
                  <div class="input-group input-group-sm" style="width:110px;">
                    <button class="btn btn-outline-secondary" onclick="changeQty(<?= $item['id'] ?>, -1)">-</button>
                    <input type="number" id="qty_<?= $item['id'] ?>" value="0" min="0"
                           class="form-control text-center shadow-none" readonly>
                    <button class="btn btn-outline-secondary" onclick="changeQty(<?= $item['id'] ?>, 1)">+</button>
                  </div>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        <?php endwhile; ?>
      </div>

    </div>

    <!-- ── Order Summary Sidebar ── -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm sticky-top" style="top:80px;">
        <div class="card-body">
          <h5 class="card-title mb-3">
            <i class="bi bi-bag-check me-2"></i>Your Order
          </h5>
          <div id="order-summary">
            <p class="text-muted small text-center mt-3" id="empty-msg">No items added yet.</p>
          </div>
          <hr>
          <div class="d-flex justify-content-between fw-bold mb-3">
            <span>Total</span>
            <span id="order-total">&#8369;0.00</span>
          </div>
          <div class="mb-3">
            <label class="form-label small">Special instructions (optional)</label>
            <textarea class="form-control shadow-none" id="order-notes" rows="2"
                      placeholder="e.g. No onions, extra rice..."></textarea>
          </div>
          <button class="btn btn-dark w-100 shadow-none" id="place-order-btn" disabled
                  onclick="placeOrder()">
            Place Order
          </button>
          <p class="text-muted small text-center mt-2 mb-0">
            <i class="bi bi-info-circle me-1"></i>
            Payment will be added to your checkout bill.
          </p>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Hidden fields for AJAX -->
<input type="hidden" id="active-booking-id" value="<?= $active_r['booking_id'] ?>">
<input type="hidden" id="active-room-no"    value="<?= htmlspecialchars($active_r['room_no']) ?>">
<input type="hidden" id="csrf-token"        value="<?= csrf_token() ?>">

<script>
// ── Cart state ──
const cart = {}; // { food_id: { name, price, qty } }

// ── Menu item data (for display in sidebar) ──
const menuItems = {};
<?php
// Re-query to build JS object
$menu_r2 = mysqli_query($conn, "SELECT id, name, price FROM food_menu WHERE is_available=1 AND removed=0");
while ($mi = mysqli_fetch_assoc($menu_r2)) {
    echo "menuItems[{$mi['id']}] = " . json_encode(['name'=>$mi['name'], 'price'=>(float)$mi['price']]) . ";\n";
}
?>

function changeQty(id, delta) {
    const input = document.getElementById('qty_' + id);
    let val = parseInt(input.value) + delta;
    if (val < 0) val = 0;
    input.value = val;

    if (val === 0) {
        delete cart[id];
    } else {
        cart[id] = { name: menuItems[id].name, price: menuItems[id].price, qty: val };
    }
    renderSummary();
}

function renderSummary() {
    const summary = document.getElementById('order-summary');
    const emptyMsg = document.getElementById('empty-msg');
    const totalEl  = document.getElementById('order-total');
    const placeBtn = document.getElementById('place-order-btn');

    const ids = Object.keys(cart);
    if (ids.length === 0) {
        summary.innerHTML = '<p class="text-muted small text-center mt-3" id="empty-msg">No items added yet.</p>';
        totalEl.textContent = '₱0.00';
        placeBtn.disabled = true;
        return;
    }

    let html  = '<ul class="list-group list-group-flush mb-2">';
    let total = 0;
    ids.forEach(id => {
        const item    = cart[id];
        const sub     = item.price * item.qty;
        total        += sub;
        html += `<li class="list-group-item px-0 d-flex justify-content-between small">
                   <span>${item.name} x${item.qty}</span>
                   <span>&#8369;${sub.toFixed(2)}</span>
                 </li>`;
    });
    html += '</ul>';

    summary.innerHTML = html;
    totalEl.textContent = '₱' + total.toFixed(2);
    placeBtn.disabled = false;
}

function filterCategory(cat, btn) {
    document.querySelectorAll('#categoryTabs .nav-link').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.food-card').forEach(card => {
        card.style.display = (cat === 'all' || card.dataset.category === cat) ? '' : 'none';
    });
}

function placeOrder() {
    const bookingId = document.getElementById('active-booking-id').value;
    const roomNo    = document.getElementById('active-room-no').value;
    const notes     = document.getElementById('order-notes').value.trim();
    const csrf      = document.getElementById('csrf-token').value;
    const btn       = document.getElementById('place-order-btn');

    if (Object.keys(cart).length === 0) return;

    btn.disabled    = true;
    btn.textContent = 'Placing order...';

    const payload = { booking_id: bookingId, room_no: roomNo, notes, cart, csrf_token: csrf };

    fetch('ajax/user_food_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.text())
    .then(resp => {
        if (resp.trim() === 'success') {
            alert('success', 'Your order has been placed! Staff will deliver it shortly.');
            // Reset cart
            Object.keys(cart).forEach(id => {
                const input = document.getElementById('qty_' + id);
                if (input) input.value = 0;
                delete cart[id];
            });
            renderSummary();
            document.getElementById('order-notes').value = '';
        } else {
            alert('error', resp.trim() || 'Failed to place order. Please try again.');
        }
        btn.disabled    = false;
        btn.textContent = 'Place Order';
    })
    .catch(() => {
        alert('error', 'Connection error. Please try again.');
        btn.disabled    = false;
        btn.textContent = 'Place Order';
    });
}
</script>

<?php endif; ?>

<?php require 'includes/user_footer.php'; ?>
</body>
</html>