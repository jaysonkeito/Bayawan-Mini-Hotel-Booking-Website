/* bayawan-mini-hotel-system/admin/scripts/admin_booking_records.js */

let current_page = 1;
let search_timer = null;

function get_bookings(search = '', page = 1) {
    current_page = page;

    let xhr = new XMLHttpRequest();
    xhr.open("POST", "ajax/admin_booking_records.php", true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onload = function () {
        try {
            const data = JSON.parse(this.responseText);
            document.getElementById('table-data').innerHTML       = data.table_data;
            document.getElementById('table-pagination').innerHTML = data.pagination;

            // IMPROVEMENT: show "Showing X–Y of Z records" info
            const infoEl = document.getElementById('records-info');
            if (infoEl) infoEl.textContent = data.total_info || '';
        } catch(e) {
            console.error('get_bookings parse error:', this.responseText);
        }
    };

    xhr.send(
        'get_bookings=1' +
        '&search=' + encodeURIComponent(search) +
        '&page='   + page
    );
}

// IMPROVEMENT: debounce search so it doesn't fire on every keystroke
function debounced_search(val) {
    clearTimeout(search_timer);
    search_timer = setTimeout(() => get_bookings(val, 1), 300);
}

function change_page(page) {
    get_bookings(document.getElementById('search_input').value, page);
}

function download(id) {
    window.location.href = 'admin_generate_pdf.php?gen_pdf&id=' + id;
}

window.onload = function () {
    get_bookings();
};