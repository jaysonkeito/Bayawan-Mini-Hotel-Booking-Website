<?php
// bayawan-mini-hotel-system/admin/admin_food_menu.php
require('includes/admin_essentials.php');
require('includes/admin_configuration.php');
adminLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Admin Panel - Food Menu</title>
  <?php require('includes/admin_links.php'); ?>
</head>
<body class="bg-light">
<?php require('includes/admin_header.php'); ?>

<div id="main-content">
<div class="container-fluid p-4">

  <div class="d-flex align-items-center justify-content-between mb-4">
    <h3 class="mb-0">FOOD MENU</h3>
    <button class="btn btn-dark shadow-none" data-bs-toggle="modal" data-bs-target="#addItemModal">
      <i class="bi bi-plus-lg me-1"></i> Add Item
    </button>
  </div>

  <!-- Items table -->
  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <table class="table table-hover align-middle mb-0" id="food-table">
        <thead class="table-dark">
          <tr>
            <th>#</th>
            <th>Image</th>
            <th>Name</th>
            <th>Category</th>
            <th class="text-end">Price</th>
            <th class="text-center">Status</th>
            <th class="text-center">Stock</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody id="food-tbody">
          <!-- loaded via JS -->
        </tbody>
      </table>
    </div>
  </div>

</div>
</div>

<!-- ── Add / Edit Item Modal ── -->
<div class="modal fade" id="addItemModal" data-bs-backdrop="static" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="itemModalTitle">Add Food Item</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="item-id" value="">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control shadow-none" id="item-name" maxlength="150">
          </div>
          <div class="col-md-6">
            <label class="form-label">Category <span class="text-danger">*</span></label>
            <input type="text" class="form-control shadow-none" id="item-category"
                   list="category-list" placeholder="e.g. Filipino Meals">
            <datalist id="category-list">
              <option value="Filipino Meals">
              <option value="Snacks">
              <option value="Beverages">
              <option value="Desserts">
            </datalist>
          </div>
          <div class="col-md-4">
            <label class="form-label">Price (&#8369;) <span class="text-danger">*</span></label>
            <input type="number" class="form-control shadow-none" id="item-price" min="0" step="0.01">
          </div>
          <div class="col-md-4">
            <label class="form-label">Stock Qty <span class="text-danger">*</span></label>
            <input type="number" class="form-control shadow-none" id="item-stock" min="0">
          </div>
          <div class="col-md-4">
            <label class="form-label">Low-stock alert at</label>
            <input type="number" class="form-control shadow-none" id="item-threshold" min="1" value="5">
          </div>
          <div class="col-12">
            <label class="form-label">Description</label>
            <textarea class="form-control shadow-none" id="item-desc" rows="2" maxlength="300"></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Image</label>
            <input type="file" class="form-control shadow-none" id="item-image" accept="image/jpeg,image/png,image/webp">
            <div class="mt-2"><img id="img-preview" src="" style="max-height:80px;display:none;" class="rounded"></div>
          </div>
          <div class="col-md-6 d-flex align-items-center gap-3 mt-4">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="item-available" checked>
              <label class="form-check-label" for="item-available">Available on menu</label>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-dark shadow-none" id="save-item-btn" onclick="saveItem()">Save</button>
      </div>
    </div>
  </div>
</div>

<?php require('includes/admin_scripts.php'); ?>
<script>
function loadFoodTable() {
  fetch('ajax/admin_food_menu.php?action=list')
    .then(r => r.json())
    .then(data => {
      const tbody = document.getElementById('food-tbody');
      if (!data.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">No food items yet.</td></tr>';
        return;
      }
      tbody.innerHTML = data.map((item, i) => `
        <tr>
          <td>${i+1}</td>
          <td><img src="../images/food/${item.image}" style="width:48px;height:48px;object-fit:cover;"
               class="rounded" onerror="this.src='../images/food/default_food.jpg'"></td>
          <td><strong>${item.name}</strong><br><small class="text-muted">${item.description || ''}</small></td>
          <td><span class="badge bg-light text-dark">${item.category}</span></td>
          <td class="text-end">&#8369;${parseFloat(item.price).toFixed(2)}</td>
          <td class="text-center">
            <span class="badge bg-${item.is_available=='1'?'success':'secondary'}">
              ${item.is_available=='1'?'Available':'Hidden'}
            </span>
          </td>
          <td class="text-center">
            <span class="badge bg-${parseInt(item.stock_qty)<=parseInt(item.low_stock_threshold)?'danger':'light text-dark'}">
              ${item.stock_qty ?? 0}
              ${parseInt(item.stock_qty)<=parseInt(item.low_stock_threshold)?'<i class="bi bi-exclamation-triangle-fill ms-1"></i>':''}
            </span>
          </td>
          <td class="text-center">
            <button class="btn btn-sm btn-outline-primary shadow-none me-1" onclick='openEditModal(${JSON.stringify(item)})'>
              <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger shadow-none" onclick="removeItem(${item.id})">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        </tr>
      `).join('');
    });
}

function openEditModal(item) {
  document.getElementById('itemModalTitle').textContent = 'Edit Food Item';
  document.getElementById('item-id').value        = item.id;
  document.getElementById('item-name').value      = item.name;
  document.getElementById('item-category').value  = item.category;
  document.getElementById('item-price').value     = item.price;
  document.getElementById('item-stock').value     = item.stock_qty ?? 0;
  document.getElementById('item-threshold').value = item.low_stock_threshold ?? 5;
  document.getElementById('item-desc').value      = item.description || '';
  document.getElementById('item-available').checked = item.is_available == '1';
  const prev = document.getElementById('img-preview');
  prev.src = '../images/food/' + item.image;
  prev.style.display = 'block';
  new bootstrap.Modal(document.getElementById('addItemModal')).show();
}

document.getElementById('item-image').addEventListener('change', function() {
  if (this.files[0]) {
    const prev = document.getElementById('img-preview');
    prev.src = URL.createObjectURL(this.files[0]);
    prev.style.display = 'block';
  }
});

document.getElementById('addItemModal').addEventListener('hidden.bs.modal', () => {
  document.getElementById('itemModalTitle').textContent = 'Add Food Item';
  document.getElementById('item-id').value = '';
  document.getElementById('item-name').value = '';
  document.getElementById('item-category').value = '';
  document.getElementById('item-price').value = '';
  document.getElementById('item-stock').value = '';
  document.getElementById('item-threshold').value = '5';
  document.getElementById('item-desc').value = '';
  document.getElementById('item-available').checked = true;
  document.getElementById('item-image').value = '';
  document.getElementById('img-preview').style.display = 'none';
});

function saveItem() {
  const btn  = document.getElementById('save-item-btn');
  const name = document.getElementById('item-name').value.trim();
  const cat  = document.getElementById('item-category').value.trim();
  const price= document.getElementById('item-price').value;
  const stock= document.getElementById('item-stock').value;

  if (!name || !cat || !price) {
    return alert('error', 'Please fill all required fields.');
  }

  btn.disabled = true;
  const fd = new FormData();
  fd.append('action',        document.getElementById('item-id').value ? 'update' : 'create');
  fd.append('id',            document.getElementById('item-id').value);
  fd.append('name',          name);
  fd.append('category',      cat);
  fd.append('price',         price);
  fd.append('stock',         stock);
  fd.append('threshold',     document.getElementById('item-threshold').value || 5);
  fd.append('description',   document.getElementById('item-desc').value.trim());
  fd.append('is_available',  document.getElementById('item-available').checked ? 1 : 0);
  const imgFile = document.getElementById('item-image').files[0];
  if (imgFile) fd.append('image', imgFile);

  fetch('ajax/admin_food_menu.php', { method: 'POST', body: fd })
    .then(r => r.text())
    .then(resp => {
      btn.disabled = false;
      if (resp.trim() === 'success') {
        bootstrap.Modal.getInstance(document.getElementById('addItemModal')).hide();
        alert('success', 'Food item saved!');
        loadFoodTable();
      } else {
        alert('error', resp.trim() || 'Failed to save item.');
      }
    });
}

function removeItem(id) {
  if (!confirm('Remove this food item?')) return;
  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('id', id);
  fetch('ajax/admin_food_menu.php', { method: 'POST', body: fd })
    .then(r => r.text())
    .then(resp => {
      if (resp.trim() === 'success') {
        alert('success', 'Item removed.');
        loadFoodTable();
      } else {
        alert('error', resp.trim());
      }
    });
}

loadFoodTable();
</script>
</body>
</html>