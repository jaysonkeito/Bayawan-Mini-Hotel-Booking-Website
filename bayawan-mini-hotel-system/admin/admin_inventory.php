<?php
// bayawan-mini-hotel-system/admin/admin_inventory.php
require('includes/admin_essentials.php');
require('includes/admin_configuration.php');
adminLogin();

// Count low-stock items for badge
$low_q   = "SELECT COUNT(*) AS cnt FROM food_menu fm
             LEFT JOIN food_inventory fi ON fm.id = fi.food_id
             WHERE fm.removed = 0 AND fi.stock_qty <= fi.low_stock_threshold";
$low_cnt = (int) mysqli_fetch_assoc(mysqli_query($conn, $low_q))['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Admin Panel - Inventory</title>
  <?php require('includes/admin_links.php'); ?>
</head>
<body class="bg-light">
<?php require('includes/admin_header.php'); ?>

<div id="main-content">
<div class="container-fluid p-4">

  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
      <h3 class="mb-0">
        INVENTORY
        <?php if ($low_cnt > 0): ?>
        <span class="badge bg-danger ms-2" style="font-size:14px;">
          <i class="bi bi-exclamation-triangle-fill me-1"></i><?= $low_cnt ?> Low Stock
        </span>
        <?php endif; ?>
      </h3>
    </div>
    <div class="d-flex gap-2 align-items-center">
      <input type="text" id="inv-search" class="form-control shadow-none"
             placeholder="Search item..." oninput="loadInventory()" style="max-width:220px;">
      <select id="inv-filter" class="form-select shadow-none" onchange="loadInventory()" style="max-width:160px;">
        <option value="all">All items</option>
        <option value="low">Low stock only</option>
        <option value="ok">In stock</option>
      </select>
    </div>
  </div>

  <!-- Summary cards -->
  <div class="row g-3 mb-4" id="inv-summary">
    <!-- rendered by JS -->
  </div>

  <!-- Inventory table -->
  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-dark">
          <tr>
            <th>#</th>
            <th>Item</th>
            <th>Category</th>
            <th class="text-center">Stock Qty</th>
            <th class="text-center">Alert Threshold</th>
            <th class="text-center">Status</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody id="inv-tbody">
          <tr><td colspan="7" class="text-center py-4">
            <div class="spinner-border spinner-border-sm text-secondary"></div> Loading...
          </td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div>
</div>

<!-- Restock Modal -->
<div class="modal fade" id="restockModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Restock Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-3 fw-bold" id="restock-item-name"></p>
        <input type="hidden" id="restock-food-id">
        <label class="form-label">Add stock quantity</label>
        <input type="number" class="form-control shadow-none" id="restock-qty" min="1" value="10">
        <label class="form-label mt-3">Low-stock alert threshold</label>
        <input type="number" class="form-control shadow-none" id="restock-threshold" min="1">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success shadow-none" onclick="submitRestock()">
          <i class="bi bi-plus-circle me-1"></i>Restock
        </button>
      </div>
    </div>
  </div>
</div>

<?php require('includes/admin_scripts.php'); ?>
<script>
let allItems = [];

function loadInventory() {
  const search = document.getElementById('inv-search').value.toLowerCase();
  const filter = document.getElementById('inv-filter').value;

  fetch('ajax/admin_inventory.php?action=list')
    .then(r => r.json())
    .then(data => {
      allItems = data;

      // Summary cards
      const total   = data.length;
      const lowCnt  = data.filter(i => parseInt(i.stock_qty) <= parseInt(i.low_stock_threshold)).length;
      const okCnt   = total - lowCnt;
      const outCnt  = data.filter(i => parseInt(i.stock_qty) === 0).length;

      document.getElementById('inv-summary').innerHTML = `
        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
              <div style="font-size:1.8rem;font-weight:700">${total}</div>
              <div class="text-muted small">Total items</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
              <div style="font-size:1.8rem;font-weight:700" class="text-success">${okCnt}</div>
              <div class="text-muted small">In stock</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
              <div style="font-size:1.8rem;font-weight:700" class="text-warning">${lowCnt}</div>
              <div class="text-muted small">Low stock</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
              <div style="font-size:1.8rem;font-weight:700" class="text-danger">${outCnt}</div>
              <div class="text-muted small">Out of stock</div>
            </div>
          </div>
        </div>
      `;

      // Filter & search
      const shown = data.filter(item => {
        const matchSearch = item.name.toLowerCase().includes(search) ||
                            item.category.toLowerCase().includes(search);
        const isLow = parseInt(item.stock_qty) <= parseInt(item.low_stock_threshold);
        if (filter === 'low' && !isLow) return false;
        if (filter === 'ok'  && isLow)  return false;
        return matchSearch;
      });

      const tbody = document.getElementById('inv-tbody');
      if (!shown.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No items found.</td></tr>';
        return;
      }

      tbody.innerHTML = shown.map((item, i) => {
        const qty       = parseInt(item.stock_qty);
        const threshold = parseInt(item.low_stock_threshold);
        const isLow     = qty <= threshold;
        const isOut     = qty === 0;
        let statusBadge, rowClass = '';
        if (isOut)      { statusBadge = '<span class="badge bg-danger">Out of stock</span>';  rowClass = 'table-danger'; }
        else if (isLow) { statusBadge = '<span class="badge bg-warning text-dark">Low stock <i class="bi bi-exclamation-triangle-fill"></i></span>'; rowClass = 'table-warning'; }
        else            { statusBadge = '<span class="badge bg-success">OK</span>'; }

        return `
          <tr class="${rowClass}">
            <td>${i+1}</td>
            <td><strong>${item.name}</strong></td>
            <td><span class="badge bg-light text-dark">${item.category}</span></td>
            <td class="text-center fw-bold">${qty}</td>
            <td class="text-center text-muted">${threshold}</td>
            <td class="text-center">${statusBadge}</td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-success shadow-none"
                      onclick="openRestock(${item.food_id}, '${item.name.replace(/'/g,"\\'")}', ${threshold})">
                <i class="bi bi-plus-circle me-1"></i>Restock
              </button>
            </td>
          </tr>`;
      }).join('');
    });
}

function openRestock(foodId, name, threshold) {
  document.getElementById('restock-food-id').value    = foodId;
  document.getElementById('restock-item-name').textContent = name;
  document.getElementById('restock-threshold').value  = threshold;
  document.getElementById('restock-qty').value        = 10;
  new bootstrap.Modal(document.getElementById('restockModal')).show();
}

function submitRestock() {
  const foodId    = document.getElementById('restock-food-id').value;
  const qty       = parseInt(document.getElementById('restock-qty').value);
  const threshold = parseInt(document.getElementById('restock-threshold').value);
  if (!qty || qty < 1) return alert('error', 'Enter a valid quantity.');

  const fd = new FormData();
  fd.append('action',    'restock');
  fd.append('food_id',   foodId);
  fd.append('qty',       qty);
  fd.append('threshold', threshold);

  fetch('ajax/admin_inventory.php', { method: 'POST', body: fd })
    .then(r => r.text())
    .then(resp => {
      if (resp.trim() === 'success') {
        bootstrap.Modal.getInstance(document.getElementById('restockModal')).hide();
        alert('success', 'Stock updated!');
        loadInventory();
      } else {
        alert('error', resp.trim() || 'Failed to update stock.');
      }
    });
}

loadInventory();
</script>
</body>
</html>