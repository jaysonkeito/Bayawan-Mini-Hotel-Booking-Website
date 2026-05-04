<?php
// bayawan-mini-hotel-system/user_my_orders.php
require 'includes/user_links.php';

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header('Location: user_index.php');
    exit;
}

$user_id = (int) $_SESSION['uId'];

// Fetch all food orders for this user, newest first
$orders_q = "SELECT fo.id, fo.booking_id, fo.room_no, fo.total_amount,
                    fo.status, fo.notes, fo.ordered_at,
                    r.name AS room_name, bo.check_in, bo.check_out
             FROM food_orders fo
             INNER JOIN booking_order bo ON fo.booking_id = bo.booking_id
             INNER JOIN rooms r          ON bo.room_id    = r.id
             WHERE fo.user_id = ?
             ORDER BY fo.ordered_at DESC";
$orders_r = select($orders_q, [$user_id], 'i');

$status_badge = [
    'pending'   => 'warning',
    'preparing' => 'info',
    'delivered' => 'primary',
    'paid'      => 'success',
    'cancelled' => 'danger',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= htmlspecialchars($settings_r['site_title']) ?> - My Food Orders</title>
</head>
<body class="bg-light">
<?php require 'includes/user_header.php'; ?>

<div class="my-5 px-4">
  <h2 class="fw-bold h-font text-center">MY FOOD ORDERS</h2>
  <div class="h-line bg-dark mx-auto" style="width:150px;"></div>
</div>

<div class="container mb-5">

  <?php if (mysqli_num_rows($orders_r) === 0): ?>
    <div class="text-center text-muted py-5">
      <i class="bi bi-bag-x fs-1 d-block mb-3"></i>
      You haven't placed any food orders yet.
      <br>
      <a href="user_food_menu.php" class="btn btn-dark mt-3">Browse Menu</a>
    </div>
  <?php else: ?>

  <div class="accordion" id="ordersAccordion">
  <?php $idx = 0; while ($order = mysqli_fetch_assoc($orders_r)):
      $idx++;
      $badge  = $status_badge[$order['status']] ?? 'secondary';
      $label  = ucfirst($order['status']);
      $date   = date('F j, Y g:i A', strtotime($order['ordered_at']));

      // Fetch line items
      $items_q = "SELECT food_name, unit_price, qty, subtotal
                  FROM food_order_items WHERE order_id = ? ORDER BY id";
      $items_r = select($items_q, [(int)$order['id']], 'i');
  ?>
  <div class="accordion-item border-0 shadow-sm mb-3 rounded">
    <h2 class="accordion-header" id="heading<?= $idx ?>">
      <button class="accordion-button <?= $idx > 1 ? 'collapsed' : '' ?> rounded"
              type="button" data-bs-toggle="collapse"
              data-bs-target="#collapse<?= $idx ?>" aria-expanded="<?= $idx===1?'true':'false' ?>">
        <div class="d-flex w-100 align-items-center gap-3 flex-wrap">
          <span class="fw-bold">Order #<?= $order['id'] ?></span>
          <span class="text-muted small"><?= htmlspecialchars($order['room_no']) ?> &mdash; <?= htmlspecialchars($order['room_name']) ?></span>
          <span class="badge bg-<?= $badge ?> ms-auto"><?= $label ?></span>
          <span class="fw-bold text-success ms-2">&#8369;<?= number_format($order['total_amount'], 2) ?></span>
        </div>
      </button>
    </h2>
    <div id="collapse<?= $idx ?>" class="accordion-collapse collapse <?= $idx===1?'show':'' ?>"
         data-bs-parent="#ordersAccordion">
      <div class="accordion-body pt-2">
        <p class="text-muted small mb-3">
          <i class="bi bi-clock me-1"></i> Ordered on: <?= $date ?>
          &nbsp;&nbsp;
          <i class="bi bi-calendar me-1"></i> Stay: <?= date('M j', strtotime($order['check_in'])) ?> &ndash; <?= date('M j, Y', strtotime($order['check_out'])) ?>
        </p>
        <table class="table table-sm table-bordered mb-3">
          <thead class="table-light">
            <tr>
              <th>Item</th>
              <th class="text-center">Qty</th>
              <th class="text-end">Unit Price</th>
              <th class="text-end">Subtotal</th>
            </tr>
          </thead>
          <tbody>
          <?php while ($li = mysqli_fetch_assoc($items_r)): ?>
            <tr>
              <td><?= htmlspecialchars($li['food_name']) ?></td>
              <td class="text-center"><?= $li['qty'] ?></td>
              <td class="text-end">&#8369;<?= number_format($li['unit_price'],2) ?></td>
              <td class="text-end">&#8369;<?= number_format($li['subtotal'],2) ?></td>
            </tr>
          <?php endwhile; ?>
            <tr class="table-light fw-bold">
              <td colspan="3" class="text-end">Total</td>
              <td class="text-end">&#8369;<?= number_format($order['total_amount'],2) ?></td>
            </tr>
          </tbody>
        </table>
        <?php if ($order['notes']): ?>
        <p class="small text-muted">
          <i class="bi bi-chat-left-text me-1"></i>
          <strong>Notes:</strong> <?= htmlspecialchars($order['notes']) ?>
        </p>
        <?php endif; ?>
        <?php if (in_array($order['status'], ['pending','preparing'])): ?>
        <div class="alert alert-info py-2 small mb-0">
          <i class="bi bi-truck me-1"></i>
          Your order is being processed and will be delivered to your room.
          Payment will be collected at checkout.
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endwhile; ?>
  </div>

  <?php endif; ?>
</div>

<?php require 'includes/user_footer.php'; ?>
</body>
</html>