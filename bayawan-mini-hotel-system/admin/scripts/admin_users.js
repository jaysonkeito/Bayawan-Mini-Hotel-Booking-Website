/* bayawan-mini-hotel-system/admin/scripts/admin_users.js */

let current_search = '';
let current_page   = 1;

function get_users(search = '', page = 1) {
  current_search = search;
  current_page   = page;

  let xhr = new XMLHttpRequest();
  xhr.open("POST", "ajax/admin_users.php", true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

  xhr.onload = function () {
    try {
      const raw  = this.responseText.substring(this.responseText.indexOf('{'));
      const data = JSON.parse(raw);
      document.getElementById('users-data').innerHTML       = data.table_data;
      document.getElementById('users-pagination').innerHTML = data.pagination;
    } catch(e) {
      console.error('get_users parse error:', this.responseText);
    }
  };

  xhr.send(
    'get_users=1' +
    '&search=' + encodeURIComponent(search) +
    '&page='   + page
  );
}

function search_user(username) {
  get_users(username, 1);
}

function toggle_status(id, val) {
  let xhr = new XMLHttpRequest();
  xhr.open("POST", "ajax/admin_users.php", true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

  xhr.onload = function () {
    if (this.responseText == 1) {
      alert('success', 'Status toggled!');
      get_users(current_search, current_page);
    } else {
      alert('error', 'Server Down!');
    }
  };

  xhr.send('toggle_status=' + id + '&value=' + val);
}

function remove_user(user_id) {
  if (confirm("Are you sure you want to remove this user?")) {
    let data = new FormData();
    data.append('user_id',     user_id);
    data.append('remove_user', '');

    let xhr = new XMLHttpRequest();
    xhr.open("POST", "ajax/admin_users.php", true);

    xhr.onload = function () {
      if (this.responseText == 1) {
        alert('success', 'User Removed!');
        get_users(current_search, current_page);
      } else {
        alert('error', 'User removal failed!');
      }
    };

    xhr.send(data);
  }
}

window.onload = function () {
  get_users();
};