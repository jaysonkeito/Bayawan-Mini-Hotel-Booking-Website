<?php // bayawan-mini-hotel-system/user_cart.php ?>

<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'en'; ?>">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php require('includes/user_links.php'); ?>
  <title><?php echo $settings_r['site_title'] ?> - CART</title>
</head>
<body class="bg-light">

  <?php
    require('includes/user_header.php');
    if (!(isset($_SESSION['login']) && $_SESSION['login'] == true)) redirect('user_index.php');
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];
    $user_q    = select("SELECT * FROM `user_cred` WHERE `id` = ? LIMIT 1", [$_SESSION['uId']], 'i');
    $user_data = mysqli_fetch_assoc($user_q);
  ?>

  <div class="container">
    <div class="row">

      <div class="col-12 my-5 px-4">
        <h2 class="fw-bold"><?php echo t('cart_title'); ?></h2>
        <div style="font-size:14px;">
          <a href="user_index.php" class="text-secondary text-decoration-none"><?php echo t('bc_home'); ?></a>
          <span class="text-secondary"> > </span>
          <a href="user_rooms.php" class="text-secondary text-decoration-none"><?php echo t('bc_rooms'); ?></a>
          <span class="text-secondary"> > </span>
          <a href="#" class="text-secondary text-decoration-none"><?php echo t('bc_cart'); ?></a>
        </div>
      </div>

      <!-- Cart Items -->
      <div class="col-lg-7 col-md-12 px-4 mb-4">
        <div id="cart-empty" class="text-center py-5 d-none">
          <i class="bi bi-cart-x" style="font-size:4rem;color:#ccc;"></i>
          <h5 class="mt-3 text-muted"><?php echo t('cart_empty'); ?></h5>
          <a href="user_rooms.php" class="btn custom-bg text-white mt-3 shadow-none"><?php echo t('cart_browse'); ?></a>
        </div>
        <div id="cart-items-wrapper"></div>
        <div id="cart-total-bar" class="bg-white rounded shadow-sm p-3 d-none">
          <div class="d-flex justify-content-between align-items-center">
            <span class="fw-bold fs-5"><?php echo t('cart_total'); ?></span>
            <span class="fw-bold fs-5 text-success" id="cart-grand-total">₱0</span>
          </div>
          <small class="text-muted"><?php echo t('cart_total_note'); ?></small>
        </div>
      </div>

      <!-- Guest Info + Checkout -->
      <div class="col-lg-5 col-md-12 px-4 mb-5">
        <div class="bg-white rounded shadow-sm p-4">
          <h5 class="fw-bold mb-3"><?php echo t('cart_guest_info'); ?></h5>
          <p class="text-muted small mb-3"><?php echo t('cart_guest_note'); ?></p>
          <form action="user_pay_now.php" method="POST" id="cart-checkout-form">
            <input type="hidden" name="cart_checkout">
            <div class="mb-3">
              <label class="form-label"><?php echo t('cart_name'); ?></label>
              <input name="name" type="text" value="<?php echo htmlspecialchars($user_data['name']) ?>" class="form-control shadow-none" required>
            </div>
            <div class="mb-3">
              <label class="form-label"><?php echo t('cart_phone'); ?></label>
              <input name="phonenum" type="text" value="<?php echo htmlspecialchars($user_data['phonenum']) ?>" class="form-control shadow-none" required>
            </div>
            <div class="mb-4">
              <label class="form-label"><?php echo t('cart_address'); ?></label>
              <textarea name="address" class="form-control shadow-none" rows="2" required><?php echo htmlspecialchars($user_data['address']) ?></textarea>
            </div>
            <?php if ($settings_r['shutdown']): ?>
              <button type="button" class="btn btn-danger w-100 shadow-none" disabled>
                <?php echo t('cart_closed'); ?>
              </button>
            <?php else: ?>
              <button type="submit" id="checkout-btn" class="btn w-100 text-white custom-bg shadow-none fw-bold" disabled>
                <i class="bi bi-credit-card me-1"></i>
                <?php echo t('cart_proceed'); ?>
              </button>
            <?php endif; ?>
          </form>
          <div class="mt-3 text-center">
            <a href="user_rooms.php" class="text-secondary text-decoration-none small">
              <i class="bi bi-plus-circle me-1"></i> <?php echo t('cart_add_more'); ?>
            </a>
          </div>
        </div>
      </div>

    </div>
  </div>

  <?php require('includes/user_footer.php'); ?>

  <script>
  function formatMoney(n) {
    return '₱' + parseFloat(n).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
  }
  function buildCartItemHTML(item) {
    const checkin  = new Date(item.check_in  + 'T00:00:00').toLocaleDateString('en-PH', {year:'numeric',month:'short',day:'numeric'});
    const checkout = new Date(item.check_out + 'T00:00:00').toLocaleDateString('en-PH', {year:'numeric',month:'short',day:'numeric'});
    const thumb    = 'images/rooms/' + item.thumb;
    return `
      <div class="bg-white rounded shadow-sm mb-3 overflow-hidden" id="cart-item-${CSS.escape(item.cart_key)}">
        <div class="row g-0 align-items-center">
          <div class="col-4">
            <img src="${thumb}" class="img-fluid w-100" style="height:120px;object-fit:cover;" onerror="this.src='images/rooms/thumbnail.jpg'">
          </div>
          <div class="col-6 px-3 py-2">
            <h6 class="fw-bold mb-1">${item.room_name}</h6>
            <p class="mb-1 small text-muted"><i class="bi bi-calendar3 me-1"></i>${checkin} → ${checkout}</p>
            <p class="mb-1 small text-muted"><i class="bi bi-moon me-1"></i>${item.days} night${item.days > 1 ? 's' : ''} &nbsp;·&nbsp; ${formatMoney(item.price)}/night</p>
            <p class="mb-0 fw-bold text-success">${formatMoney(item.subtotal)}</p>
          </div>
          <div class="col-2 text-center">
            <button onclick="removeItem('${item.cart_key}')" class="btn btn-sm btn-outline-danger shadow-none" title="Remove">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </div>
      </div>`;
  }
  function loadCart() {
    fetch('ajax/user_cart.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'get_cart' })
    .then(r => r.json())
    .then(data => {
      const wrapper     = document.getElementById('cart-items-wrapper');
      const emptyEl     = document.getElementById('cart-empty');
      const totalBar    = document.getElementById('cart-total-bar');
      const totalEl     = document.getElementById('cart-grand-total');
      const checkoutBtn = document.getElementById('checkout-btn');
      wrapper.innerHTML = '';
      if (!data.items || data.items.length === 0) {
        emptyEl.classList.remove('d-none');
        totalBar.classList.add('d-none');
        if (checkoutBtn) checkoutBtn.disabled = true;
        return;
      }
      emptyEl.classList.add('d-none');
      totalBar.classList.remove('d-none');
      if (checkoutBtn) checkoutBtn.disabled = false;
      data.items.forEach(item => { wrapper.insertAdjacentHTML('beforeend', buildCartItemHTML(item)); });
      totalEl.textContent = formatMoney(data.total);
    })
    .catch(() => alert('error', 'Could not load cart. Please refresh.'));
  }
  function removeItem(cartKey) {
    if (!confirm('Remove this room from your cart?')) return;
    const data = new FormData();
    data.append('remove_from_cart', '');
    data.append('cart_key', cartKey);
    fetch('ajax/user_cart.php', {method:'POST', body:data})
      .then(r => r.json())
      .then(res => {
        if (res.status === 'success') { updateCartBadge(res.count); loadCart(); }
        else alert('error', 'Could not remove item.');
      });
  }
  function updateCartBadge(count) {
    const badge = document.getElementById('cart-badge');
    if (!badge) return;
    badge.textContent    = count;
    badge.style.display  = count > 0 ? 'inline-flex' : 'none';
  }
  document.getElementById('cart-checkout-form').addEventListener('submit', function(e) {
    const items = document.querySelectorAll('#cart-items-wrapper > div');
    if (items.length === 0) { e.preventDefault(); alert('error', 'Your cart is empty. Add rooms before checking out.'); }
  });
  window.onload = loadCart;
  </script>

</body>
</html>