/* bayawan-mini-hotel-system/admin/scripts/admin_users.js */

// IMPROVEMENT: replaced all alert('type', msg) calls with show_alert()
// to match the renamed PHP function and avoid JS/PHP naming collision.

let current_search = '';
let current_page   = 1;
let search_timer   = null;

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

// IMPROVEMENT: debounced search - waits 300ms after typing stops
function search_user(val) {
    clearTimeout(search_timer);
    search_timer = setTimeout(() => get_users(val, 1), 300);
}

function toggle_status(id, val) {
    let xhr = new XMLHttpRequest();
    xhr.open("POST", "ajax/admin_users.php", true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onload = function () {
        if (this.responseText == 1) {
            show_alert('success', 'Status updated successfully!');
            get_users(current_search, current_page);
        } else {
            show_alert('error', 'Failed to update status.');
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
                show_alert('success', 'User removed successfully!');
                get_users(current_search, current_page);
            } else {
                show_alert('error', 'User removal failed!');
            }
        };

        xhr.send(data);
    }
}

window.onload = function () {
    get_users();
};