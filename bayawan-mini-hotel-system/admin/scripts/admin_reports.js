/* bayawan-mini-hotel-system/admin/scripts/admin_reports.js */
let current_date_from = '';
let current_date_to   = '';

// ── Quick select presets ──────────────────────────────────────────────
function apply_quick_select(val) {
    if (!val) return;

    const today = new Date();
    const fmt   = d => d.toISOString().split('T')[0];

    document.getElementById('date_to').value = fmt(today);

    if (val === 'all') {
        document.getElementById('date_from').value = '';
        document.getElementById('date_to').value   = '';
    } else {
        const from = new Date();
        from.setDate(from.getDate() - parseInt(val));
        document.getElementById('date_from').value = fmt(from);
    }
}

// ── Generate all three reports ────────────────────────────────────────
function generate_reports() {
    current_date_from = document.getElementById('date_from').value;
    current_date_to   = document.getElementById('date_to').value;

    document.getElementById('reports-empty').classList.add('d-none');
    document.getElementById('reports-container').classList.add('d-none');
    document.getElementById('reports-loading').classList.remove('d-none');

    Promise.all([
        fetch_report('get_bookings_summary'),
        fetch_report('get_revenue'),
        fetch_report('get_occupancy'),
    ])
    .then(([bs, rev, occ]) => {
        render_bookings_summary(bs);
        render_revenue(rev);
        render_occupancy(occ);

        document.getElementById('reports-loading').classList.add('d-none');
        document.getElementById('reports-container').classList.remove('d-none');
    })
    .catch(err => {
        console.error('Report error:', err);
        document.getElementById('reports-loading').classList.add('d-none');
        show_alert('error', 'Failed to generate reports. Please try again.');
    });
}

// ── Generic AJAX fetch ────────────────────────────────────────────────
function fetch_report(action) {
    return fetch('ajax/admin_reports.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: action + '=1'
            + '&date_from=' + encodeURIComponent(current_date_from)
            + '&date_to='   + encodeURIComponent(current_date_to),
    })
    .then(r => r.text())
    .then(text => {
        const raw = text.substring(text.indexOf('{'));
        return JSON.parse(raw);
    });
}

// ── Money formatter ───────────────────────────────────────────────────
function money(n) {
    return '₱' + parseFloat(n || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2, maximumFractionDigits: 2
    });
}

// ─────────────────────────────────────────────────────────────────────
//  RENDER: Bookings Summary
// ─────────────────────────────────────────────────────────────────────
function render_bookings_summary(data) {
    document.getElementById('bs_total').textContent        = data.total;
    document.getElementById('bs_total_amt').textContent    = money(data.total_amt);
    document.getElementById('bs_active').textContent       = data.active;
    document.getElementById('bs_active_amt').textContent   = money(data.active_amt);
    document.getElementById('bs_cancelled').textContent    = data.cancelled;
    document.getElementById('bs_cancelled_amt').textContent = money(data.cancelled_amt);
    document.getElementById('bs_failed').textContent       = data.failed;

    let html = '';
    if (!data.rows || data.rows.length === 0) {
        html = '<tr><td colspan="8" class="text-center text-muted">No data found for this period.</td></tr>';
    } else {
        data.rows.forEach((r, idx) => {
            const badge = r.booking_status === 'booked'
                ? `<span class="badge bg-success">${r.booking_status}</span>`
                : r.booking_status === 'cancelled'
                    ? `<span class="badge bg-danger">${r.booking_status}</span>`
                    : `<span class="badge bg-warning text-dark">${r.booking_status}</span>`;

            const checkin  = new Date(r.check_in  + 'T00:00:00').toLocaleDateString('en-PH');
            const checkout = new Date(r.check_out + 'T00:00:00').toLocaleDateString('en-PH');

            html += `<tr>
                <td>${idx + 1}</td>
                <td><small>${r.order_id}</small></td>
                <td>${r.user_name}</td>
                <td>${r.room_name}</td>
                <td>${checkin}</td>
                <td>${checkout}</td>
                <td>${money(r.trans_amt)}</td>
                <td>${badge}</td>
            </tr>`;
        });
    }
    document.getElementById('bs_table').innerHTML = html;
}

// ─────────────────────────────────────────────────────────────────────
//  RENDER: Revenue
// ─────────────────────────────────────────────────────────────────────
function render_revenue(data) {
    document.getElementById('rev_total').textContent = money(data.total_rev);
    document.getElementById('rev_avg').textContent   = money(data.avg_rev);
    document.getElementById('rev_max').textContent   = money(data.max_rev);

    let html = '';
    if (!data.rows || data.rows.length === 0) {
        html = '<tr><td colspan="7" class="text-center text-muted">No revenue data for this period.</td></tr>';
    } else {
        data.rows.forEach((r, idx) => {
            const date = new Date(r.datentime).toLocaleDateString('en-PH');
            html += `<tr>
                <td>${idx + 1}</td>
                <td>${date}</td>
                <td><small>${r.order_id}</small></td>
                <td>${r.room_name}</td>
                <td>${r.user_name}</td>
                <td>${r.nights}</td>
                <td>${money(r.trans_amt)}</td>
            </tr>`;
        });
    }
    document.getElementById('rev_table').innerHTML = html;
}

// ─────────────────────────────────────────────────────────────────────
//  RENDER: Occupancy
// ─────────────────────────────────────────────────────────────────────
function render_occupancy(data) {
    document.getElementById('occ_overall').textContent         = data.overall_rate + '%';
    document.getElementById('occ_booked_nights').textContent   = data.total_booked;
    document.getElementById('occ_available_nights').textContent = data.total_available;

    let html = '';
    if (!data.rooms || data.rooms.length === 0) {
        html = '<tr><td colspan="7" class="text-center text-muted">No rooms found.</td></tr>';
    } else {
        data.rooms.forEach((r, idx) => {
            const badge_class = r.occupancy_rate >= 70
                ? 'bg-success'
                : r.occupancy_rate >= 40 ? 'bg-warning text-dark' : 'bg-danger';

            const bar_width = Math.min(r.occupancy_rate, 100);

            html += `<tr>
                <td>${idx + 1}</td>
                <td>${r.name}</td>
                <td>${r.quantity}</td>
                <td>${r.available_nights}</td>
                <td>${r.booked_nights}</td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="progress flex-grow-1" style="height:8px;">
                            <div class="progress-bar ${badge_class}"
                                 style="width:${bar_width}%"></div>
                        </div>
                        <span class="badge ${badge_class}">${r.occupancy_rate}%</span>
                    </div>
                </td>
                <td><span class="badge ${badge_class}">${
                    r.occupancy_rate >= 70 ? 'High' : r.occupancy_rate >= 40 ? 'Medium' : 'Low'
                }</span></td>
            </tr>`;
        });
    }
    document.getElementById('occ_table').innerHTML = html;
}

// ─────────────────────────────────────────────────────────────────────
//  EXPORT
// ─────────────────────────────────────────────────────────────────────
function export_report(report_type, format) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'ajax/admin_reports.php';
    form.target = '_blank';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const fields = {
        ['export_' + format]: '1',
        report_type:           report_type,
        date_from:             current_date_from,
        date_to:               current_date_to,
        csrf_token:            csrfToken,
    };

    Object.entries(fields).forEach(([name, value]) => {
        const input = document.createElement('input');
        input.type  = 'hidden';
        input.name  = name;
        input.value = value;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}