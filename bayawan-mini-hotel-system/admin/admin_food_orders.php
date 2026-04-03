<?php
// bayawan-mini-hotel-system/admin/admin_food_orders.php
require('includes/admin_essentials.php');
require('includes/admin_configuration.php');
adminLogin();

// Count pending orders for badge
$pending_q   = "SELECT COUNT(*) AS cnt FROM food_orders WHERE status IN ('pending','preparing')";
$pending_cnt = (int) mysqli_fetch_assoc(mysqli_query($conn, $pending_q))['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Admin Panel - Food Orders</title>
  <?php require('includes/admin_links.php'); ?>
</head>
<body class="bg-light">
<?php require('includes/admin_header.php'); ?>

<div id="main-content">
<div class="container-fluid p-4">

  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <h3 class="mb-0">
      FOOD ORDERS
      <?php if ($pending_cnt > 0): ?>
      <span class="badge bg-warning text-dark ms-2" style="font-size:14px;">
        <i class="bi bi-bell-fill me-1"></i><?= $pending_cnt ?> Active
      </span>
      <?php endif; ?>
    </h3>
    <div class="d-flex gap-2 align-items-center flex-wrap">
      <select id="status-filter" class="form-select shadow-none" onchange="loadOrders()" style="max-width:180px;">
        <option value="active">Active (pending/preparing)</option>
        <option value="delivered">Delivered</option>
        <option value="paid">Paid</option>
        <option value="cancelled">Cancelled</option>
        <option value="all">All orders</option>
      </select>
      <button class="btn btn-outline-secondary shadow-none" onclick="loadOrders()">
        <i class="bi bi-arrow-clockwise"></i> Refresh
      </button>
    </div>
  </div>

  <div id="orders-container">
    <div class="text-center py-5">
      <div class="spinner-border text-secondary"></div>
    </div>
  </div>

</div>
</div>

<!-- Order Detail Modal -->
<div class="modal fade" id="orderDetailModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Order Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="order-detail-body">
        <!-- loaded dynamically -->
      </div>
      <div class="modal-footer" id="order-detail-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php require('includes/admin_scripts.php'); ?>
<script>
const statusBadge = {
  pending:   'warning text-dark',
  preparing: 'info text-dark',
  delivered: 'primary',
  paid:      'success',
  cancelled: 'danger',
};

function loadOrders() {
  const filter = document.getElementById('status-filter').value;
  document.getElementById('orders-container').innerHTML =
    '<div class="text-center py-5"><div class="spinner-border text-secondary"></div></div>';

  fetch('ajax/admin_food_orders.php?action=list&filter=' + filter)
    .then(r => r.json())
    .then(data => {
      if (!data.length) {
        document.getElementById('orders-container').innerHTML =
          '<div class="text-center text-muted py-5"><i class="bi bi-inbox fs-1 d-block mb-3"></i>No orders found.</div>';
        return;
      }

      const html = `
        <div class="card border-0 shadow-sm">
          <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-dark">
                <tr>
                  <th>#</th>
                  <th>Guest / Room</th>
                  <th>Items</th>
                  <th class="text-end">Total</th>
                  <th class="text-center">Status</th>
                  <th>Ordered</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                ${data.map(o => `
                  <tr>
                    <td>${o.id}</td>
                    <td>
                      <strong>${o.user_name}</strong><br>
                      <small class="text-muted">${o.room_no} &mdash; Booking #${o.booking_id}</small>
                    </td>
                    <td><small>${o.item_summary}</small></td>
                    <td class="text-end fw-bold">&#8369;${parseFloat(o.total_amount).toFixed(2)}</td>
                    <td class="text-center">
                      <span class="badge bg-${statusBadge[o.status] || 'secondary'}">
                        ${o.status.charAt(0).toUpperCase() + o.status.slice(1)}
                      </span>
                    </td>
                    <td><small>${o.ordered_at}</small></td>
                    <td class="text-center">
                      <button class="btn btn-sm btn-outline-dark shadow-none"
                              onclick='openOrderDetail(${JSON.stringify(o)})'>
                        <i class="bi bi-eye"></i>
                      </button>
                    </td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
        </div>`;
      document.getElementById('orders-container').innerHTML = html;
    });
}

function openOrderDetail(order) {
  const body   = document.getElementById('order-detail-body');
  const footer = document.getElementById('order-detail-footer');

  // Fetch full items
  fetch('ajax/admin_food_orders.php?action=items&order_id=' + order.id)
    .then(r => r.json())
    .then(items => {
      const rows = items.map(li => `
        <tr>
          <td>${li.food_name}</td>
          <td class="text-center">${li.qty}</td>
          <td class="text-end">&#8369;${parseFloat(li.unit_price).toFixed(2)}</td>
          <td class="text-end">&#8369;${parseFloat(li.subtotal).toFixed(2)}</td>
        </tr>`).join('');

      body.innerHTML = `
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <p class="mb-1"><strong>Guest:</strong> ${order.user_name}</p>
            <p class="mb-1"><strong>Room:</strong> ${order.room_no}</p>
            <p class="mb-1"><strong>Booking #:</strong> ${order.booking_id}</p>
          </div>
          <div class="col-md-6">
            <p class="mb-1"><strong>Order #:</strong> ${order.id}</p>
            <p class="mb-1"><strong>Ordered:</strong> ${order.ordered_at}</p>
            <p class="mb-1"><strong>Status:</strong>
              <span class="badge bg-${statusBadge[order.status]||'secondary'}">
                ${order.status.charAt(0).toUpperCase()+order.status.slice(1)}
              </span>
            </p>
          </div>
        </div>
        ${order.notes ? `<p class="text-muted small"><i class="bi bi-chat-left-text me-1"></i><strong>Notes:</strong> ${order.notes}</p>` : ''}
        <table class="table table-sm table-bordered">
          <thead class="table-light">
            <tr><th>Item</th><th class="text-center">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Subtotal</th></tr>
          </thead>
          <tbody>${rows}</tbody>
          <tfoot class="table-light">
            <tr><td colspan="3" class="text-end fw-bold">Total</td>
                <td class="text-end fw-bold">&#8369;${parseFloat(order.total_amount).toFixed(2)}</td></tr>
          </tfoot>
        </table>`;

      // Status action buttons
      let actionBtns = '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>';
      if (order.status === 'pending') {
        actionBtns += `<button class="btn btn-info text-dark shadow-none ms-2" onclick="updateStatus(${order.id},'preparing')">
                         <i class="bi bi-fire me-1"></i>Mark Preparing
                       </button>`;
      }
      if (order.status === 'preparing') {
        actionBtns += `<button class="btn btn-primary shadow-none ms-2" onclick="updateStatus(${order.id},'delivered')">
                         <i class="bi bi-truck me-1"></i>Mark Delivered
                       </button>`;
      }
      if (order.status === 'delivered') {
        actionBtns += `<button class="btn btn-success shadow-none ms-2" onclick="updateStatus(${order.id},'paid')">
                         <i class="bi bi-cash-coin me-1"></i>Mark Paid
                       </button>`;
      }
      if (['pending','preparing'].includes(order.status)) {
        actionBtns += `<button class="btn btn-outline-danger shadow-none ms-2" onclick="updateStatus(${order.id},'cancelled')">
                         <i class="bi bi-x-circle me-1"></i>Cancel
                       </button>`;
      }
      footer.innerHTML = actionBtns;

      new bootstrap.Modal(document.getElementById('orderDetailModal')).show();
    });
}

function updateStatus(orderId, newStatus) {
  const fd = new FormData();
  fd.append('action',    'update_status');
  fd.append('order_id',  orderId);
  fd.append('status',    newStatus);

  fetch('ajax/admin_food_orders.php', { method: 'POST', body: fd })
    .then(r => r.text())
    .then(resp => {
      bootstrap.Modal.getInstance(document.getElementById('orderDetailModal')).hide();
      if (resp.trim() === 'success') {
        alert('success', 'Order status updated.');
        loadOrders();
      } else {
        alert('error', resp.trim() || 'Update failed.');
      }
    });
}

// Auto-refresh every 60 seconds for live incoming orders
setInterval(() => {
  const filter = document.getElementById('status-filter').value;
  if (filter === 'active') loadOrders();
}, 60000);

loadOrders();
</script>
</body>
</html>