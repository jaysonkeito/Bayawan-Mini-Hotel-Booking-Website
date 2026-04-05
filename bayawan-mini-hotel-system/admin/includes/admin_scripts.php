<?php // bayawan-mini-hotel-system/admin/includes/admin_scripts.php ?>

<!-- CSRF token injector — must load before any other script -->
<script src="../scripts/admin_csrf.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" 
  integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" 
  crossorigin="anonymous"></script>

<script>
  function alert(type, msg, position='body') {
    let bs_class = (type == 'success') ? 'alert-success' : 'alert-danger';

    // Remove existing alert first
    let existing = document.querySelector('.custom-alert');
    if(existing) existing.remove();

    let element = document.createElement('div');
    element.classList.add('custom-alert');
    element.innerHTML = `
      <div class="alert ${bs_class} alert-dismissible fade show" role="alert">
        <strong class="me-3">${msg}</strong>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    `;

    if(position == 'body'){
      document.body.append(element);
    } else {
      document.getElementById(position).appendChild(element);
    }

    setTimeout(() => {
      if(element && element.parentNode) element.remove();
    }, 3000);
  }

  function remAlert() {
    let existing = document.querySelector('.custom-alert');
    if(existing) existing.remove();
  }

  function setActive() {
    let navbar = document.getElementById('dashboard-menu');
    let a_tags = navbar.getElementsByTagName('a');
    for(let i = 0; i < a_tags.length; i++){
      let file = a_tags[i].href.split('/').pop();
      let file_name = file.split('.')[0];
      if(document.location.href.indexOf(file_name) >= 0){
        a_tags[i].classList.add('active');
      }
    }
  }
  setActive();
</script>

<!-- Session Timeout -->
<script src="../scripts/session_timeout.js"></script>
<?php if (isset($_SESSION['adminLogin']) && $_SESSION['adminLogin'] == true): ?>
<script>
  initSessionTimeout({
    checkUrl:   'ajax/admin_session_check.php',
    logoutUrl:  'admin_logout.php',
    checkEvery: 60,
  });
</script>
<?php endif; ?>