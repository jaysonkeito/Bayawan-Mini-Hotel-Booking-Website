<?php
// bayawan-mini-hotel-system/admin/includes/admin_header.php

// CSRF: Generate token once for this session and embed in a meta tag.
// The XHR interceptor script at the bottom of this file reads it and
// attaches it automatically to every AJAX request fired from any admin page.
require_once __DIR__ . '/../../includes/csrf.php';
?>
<meta name="csrf-token" content="<?= csrf_token() ?>">

<style>
  #dashboard-menu {
    width: 260px;
    min-height: 100vh;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 100;
    transition: width 0.3s ease;
    overflow: hidden;
    display: flex;
    flex-direction: column;
  }
  #dashboard-menu.collapsed {
    width: 65px;
  }

  /* ─── Scrollable nav area ─── */
  #dashboard-menu .sidebar-nav {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding-bottom: 20px;
  }

  /* ─── Custom scrollbar ─── */
  #dashboard-menu .sidebar-nav::-webkit-scrollbar {
    width: 4px;
  }
  #dashboard-menu .sidebar-nav::-webkit-scrollbar-track {
    background: transparent;
  }
  #dashboard-menu .sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.2);
    border-radius: 4px;
  }
  #dashboard-menu .sidebar-nav::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.4);
  }

  /* ─── Hide text when collapsed ─── */
  #dashboard-menu .menu-label,
  #dashboard-menu .menu-text {
    transition: opacity 0.2s ease;
    white-space: nowrap;
  }
  #dashboard-menu.collapsed .menu-label,
  #dashboard-menu.collapsed .menu-text {
    opacity: 0;
    pointer-events: none;
    width: 0;
    overflow: hidden;
  }

  /* ─── Nav links ─── */
  #dashboard-menu .nav-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    transition: background 0.2s;
  }
  #dashboard-menu .nav-link:hover {
    background: rgba(255,255,255,0.1);
  }
  #dashboard-menu .nav-link.active {
    background: var(--teal);
  }
  #dashboard-menu .nav-link i {
    font-size: 1.1rem;
    min-width: 20px;
    text-align: center;
  }

  /* ─── Bookings button ─── */
  #dashboard-menu .menu-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    transition: background 0.2s;
    background: none;
    border: none;
    width: 100%;
    text-align: left;
    justify-content: flex-start;
  }
  #dashboard-menu .menu-btn:hover {
    background: rgba(255,255,255,0.1);
  }
  #dashboard-menu .menu-btn i {
    font-size: 1.1rem;
    min-width: 20px;
    text-align: center;
  }

  /* ─── Submenu ─── */
  #dashboard-menu .sub-menu {
    padding-left: 20px;
    overflow: hidden;
    max-height: 0;
    transition: max-height 0.3s ease;
  }
  #dashboard-menu.collapsed .sub-menu {
    display: none;
  }

  /* ─── Role badge ─── */
  #dashboard-menu .role-badge {
    transition: opacity 0.2s ease;
  }
  #dashboard-menu.collapsed .role-badge {
    opacity: 0;
    height: 0;
    overflow: hidden;
    padding: 0 !important;
  }

  /* ─── Toggle button ─── */
  #dashboard-menu .toggle-btn {
    background: none;
    border: none;
    color: white;
    font-size: 1.3rem;
    cursor: pointer;
    padding: 5px 8px;
    border-radius: 6px;
    transition: background 0.2s;
    line-height: 1;
  }
  #dashboard-menu .toggle-btn:hover {
    background: rgba(255,255,255,0.1);
  }

  /* ─── Main content ─── */
  #main-content {
    margin-left: 260px;
    width: calc(100% - 260px);
    transition: margin-left 0.3s ease, width 0.3s ease;
    min-height: 100vh;
    box-sizing: border-box;
  }
  #main-content.expanded {
    margin-left: 65px;
    width: calc(100% - 65px);
  }

  /* ─── Disable transition temporarily ─── */
  #dashboard-menu.no-transition,
  #dashboard-menu.no-transition * {
    transition: none !important;
  }

  @media screen and (max-width: 991px) {
    #dashboard-menu {
      width: 100%;
      height: auto;
      position: relative;
    }
    #dashboard-menu.collapsed {
      width: 100%;
    }
    #main-content {
      margin-left: 0 !important;
      width: 100% !important;
    }
  }
</style>

<div class="bg-dark" id="dashboard-menu">

  <!-- ─── Top: Logo + Toggle ─── -->
  <div class="d-flex align-items-center justify-content-between px-3 py-3 border-bottom border-secondary flex-shrink-0">
    <div class="menu-label">
      <h5 class="mb-0 text-white h-font">Bayawan Mini Hotel</h5>
    </div>
    <button class="toggle-btn" id="sidebarToggle" title="Toggle Sidebar">
      <i class="bi bi-list"></i>
    </button>
  </div>

  <!-- ─── Role Label ─── -->
  <div class="px-3 py-2 role-badge flex-shrink-0">
    <?php if(isAdmin()){ ?>
      <span class="badge bg-success w-100 py-2">
        <i class="bi bi-shield-fill me-1"></i>
        <span class="menu-text">Admin Panel</span>
      </span>
    <?php } else { ?>
      <span class="badge bg-info w-100 py-2">
        <i class="bi bi-person-badge me-1"></i>
        <span class="menu-text">Receptionist Panel</span>
      </span>
    <?php } ?>
  </div>

  <!-- ─── Scrollable Navigation ─── -->
  <div class="sidebar-nav">
    <ul class="nav flex-column px-2 mt-2">

      <!-- Dashboard — both roles -->
      <li class="nav-item">
        <a class="nav-link" href="admin_dashboard.php">
          <i class="bi bi-speedometer2"></i>
          <span class="menu-text">Dashboard</span>
        </a>
      </li>

      <!-- Bookings — both roles -->
      <li class="nav-item">
        <button class="menu-btn" onclick="toggleSubMenu(this)">
          <i class="bi bi-calendar-check"></i>
          <span class="menu-text">Bookings</span>
          <i class="bi bi-caret-down-fill ms-auto menu-text" style="font-size:0.7rem;"></i>
        </button>
        <div class="sub-menu" id="bookingSubMenu">
          <a class="nav-link" href="admin_new_bookings.php">
            <i class="bi bi-plus-circle"></i>
            <span class="menu-text">New Bookings</span>
          </a>
          <a class="nav-link" href="admin_refund_bookings.php">
            <i class="bi bi-cash-stack"></i>
            <span class="menu-text">Refund Bookings</span>
          </a>
          <?php if(isAdmin()){ ?>
          <a class="nav-link" href="admin_booking_records.php">
            <i class="bi bi-journal-text"></i>
            <span class="menu-text">Booking Records</span>
          </a>
          <?php } ?>
        </div>
      </li>

      <!-- Admin only -->
      <?php if(isAdmin()){ ?>
      <li class="nav-item">
        <a class="nav-link" href="admin_users.php">
          <i class="bi bi-people"></i>
          <span class="menu-text">Users</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="admin_user_queries.php">
          <i class="bi bi-chat-dots"></i>
          <span class="menu-text">User Queries</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="admin_rate_review.php">
          <i class="bi bi-star"></i>
          <span class="menu-text">Ratings & Reviews</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="admin_rooms.php">
          <i class="bi bi-door-open"></i>
          <span class="menu-text">Rooms</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="admin_features_facilities.php">
          <i class="bi bi-stars"></i>
          <span class="menu-text">Features & Facilities</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="admin_carousel.php">
          <i class="bi bi-images"></i>
          <span class="menu-text">Carousel</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="admin_reports.php">
          <i class="bi bi-file-earmark-bar-graph"></i>
          <span class="menu-text">Reports</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="admin_settings.php">
          <i class="bi bi-gear"></i>
          <span class="menu-text">Settings</span>
        </a>
      </li>
      <?php } ?>

      <!-- Logout — both roles -->
      <li class="nav-item mt-3 border-top border-secondary pt-3">
        <a class="nav-link text-danger" href="admin_logout.php">
          <i class="bi bi-box-arrow-left"></i>
          <span class="menu-text">Logout</span>
        </a>
      </li>

    </ul>
  </div>

</div>

<script>
  const sidebar   = document.getElementById('dashboard-menu');
  const toggleBtn = document.getElementById('sidebarToggle');

  function getMainContent() {
    return document.getElementById('main-content');
  }

  function applySidebarState(collapsed, animate = true) {
    const mc = getMainContent();

    if(!animate){
      sidebar.style.transition = 'none';
      if(mc) mc.style.transition = 'none';
    } else {
      sidebar.style.transition = 'width 0.3s ease';
      if(mc) mc.style.transition = 'margin-left 0.3s ease, width 0.3s ease';
    }

    if(collapsed){
      sidebar.classList.add('collapsed');
      if(mc){
        mc.style.marginLeft = '65px';
        mc.style.width      = 'calc(100% - 65px)';
      }
    } else {
      sidebar.classList.remove('collapsed');
      if(mc){
        mc.style.marginLeft = '260px';
        mc.style.width      = 'calc(100% - 260px)';
      }
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    applySidebarState(isCollapsed, false);
  });

  toggleBtn.addEventListener('click', () => {
    const nowCollapsed = !sidebar.classList.contains('collapsed');
    applySidebarState(nowCollapsed, true);
    localStorage.setItem('sidebarCollapsed', nowCollapsed);
  });

  document.querySelectorAll('#dashboard-menu .nav-link, #dashboard-menu .menu-btn').forEach(el => {
    el.addEventListener('click', function() {
      if(sidebar.classList.contains('collapsed')){
        sidebar.style.transition = 'none';
      }
    });
  });

  function toggleSubMenu(btn) {
    const subMenu = btn.nextElementSibling;
    if(subMenu.style.maxHeight && subMenu.style.maxHeight !== '0px'){
      subMenu.style.maxHeight = null;
    } else {
      subMenu.style.maxHeight = subMenu.scrollHeight + 'px';
    }
  }

  const bookingPages = ['new_bookings', 'refund_bookings', 'booking_records'];
  const currentPage  = window.location.pathname;
  const bookingSub   = document.getElementById('bookingSubMenu');
  if(bookingPages.some(p => currentPage.includes(p))){
    bookingSub.style.maxHeight = bookingSub.scrollHeight + 'px';
  }

  function setActive() {
    let a_tags = document.getElementById('dashboard-menu').getElementsByTagName('a');
    for(let i = 0; i < a_tags.length; i++){
      let file = a_tags[i].href.split('/').pop().split('.')[0];
      if(document.location.href.indexOf(file) >= 0){
        a_tags[i].classList.add('active');
      }
    }
  }
  setActive();
</script>

<!-- ═══════════════════════════════════════════════════════════════
     CSRF XHR Interceptor
     Automatically attaches the CSRF token to every XMLHttpRequest
     sent from any admin page that includes this header.
     No changes needed in any individual admin JS file.
     ═══════════════════════════════════════════════════════════════ -->
<script>
(function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    if (!csrfToken) return;

    var _send = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.send = function (body) {
        if (body instanceof FormData) {
            // FormData requests (file uploads, multi-field forms)
            body.append('csrf_token', csrfToken);
        } else if (typeof body === 'string' && body.length > 0) {
            // URL-encoded string requests (e.g. 'get_all_rooms=1&page=2')
            body = body + '&csrf_token=' + encodeURIComponent(csrfToken);
        }
        _send.call(this, body);
    };
})();
</script>
