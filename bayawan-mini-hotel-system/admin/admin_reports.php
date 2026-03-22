<?php // bayawan-mini-hotel-system/admin/admin_reports.php

require('includes/admin_essentials.php');
require('includes/admin_configuration.php');
adminOnly();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Reports</title>
    <?php require('includes/admin_links.php'); ?>
    <style>
        .report-card { transition: box-shadow 0.2s; }
        .report-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,0.1) !important; }
        .stat-box { border-left: 4px solid; }
        .stat-box.teal   { border-color: #2ec1ac; }
        .stat-box.green  { border-color: #198754; }
        .stat-box.red    { border-color: #dc3545; }
        .stat-box.yellow { border-color: #ffc107; }
        .export-btn { min-width: 110px; }
        .table-report th { background-color: #1a1a2e; color: white; }
    </style>
</head>
<body class="bg-light">

<?php require('includes/admin_header.php'); ?>

<div id="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 p-4 overflow-hidden">
                <h3 class="mb-4">REPORTS</h3>

                <!-- ── Date Range Filter ── -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="bi bi-funnel me-2"></i>Filter by Date Range
                        </h5>
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Date From</label>
                                <input type="date" id="date_from" class="form-control shadow-none">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Date To</label>
                                <input type="date" id="date_to" class="form-control shadow-none">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Quick Select</label>
                                <select id="quick_select" class="form-select shadow-none" onchange="apply_quick_select(this.value)">
                                    <option value="">-- Select --</option>
                                    <option value="7">Last 7 days</option>
                                    <option value="30">Last 30 days</option>
                                    <option value="90">Last 90 days</option>
                                    <option value="365">Last 1 year</option>
                                    <option value="all">All time</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button onclick="generate_reports()" class="btn custom-bg text-white shadow-none w-100">
                                    <i class="bi bi-bar-chart-line me-1"></i> Generate Reports
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Loading indicator ── -->
                <div id="reports-loading" class="text-center py-5 d-none">
                    <div class="spinner-border text-secondary" role="status"></div>
                    <p class="mt-2 text-muted">Generating reports...</p>
                </div>

                <!-- ── Reports Container ── -->
                <div id="reports-container" class="d-none">

                    <!-- ─────────────────────────────────────────── -->
                    <!-- 1. BOOKINGS SUMMARY                         -->
                    <!-- ─────────────────────────────────────────── -->
                    <div class="card border-0 shadow-sm mb-4 report-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-journal-check me-2 text-primary"></i>
                                    Bookings Summary
                                </h5>
                                <div class="d-flex gap-2">
                                    <button onclick="export_report('bookings_summary','pdf')"
                                            class="btn btn-danger btn-sm shadow-none export-btn">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                                    </button>
                                    <button onclick="export_report('bookings_summary','excel')"
                                            class="btn btn-success btn-sm shadow-none export-btn">
                                        <i class="bi bi-file-earmark-excel me-1"></i> Excel
                                    </button>
                                </div>
                            </div>

                            <!-- Stat boxes -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <div class="bg-white rounded p-3 stat-box teal shadow-sm">
                                        <p class="text-muted small mb-1">Total Bookings</p>
                                        <h3 class="mb-0" id="bs_total">0</h3>
                                        <small class="text-muted" id="bs_total_amt">₱0</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="bg-white rounded p-3 stat-box green shadow-sm">
                                        <p class="text-muted small mb-1">Active Bookings</p>
                                        <h3 class="mb-0" id="bs_active">0</h3>
                                        <small class="text-muted" id="bs_active_amt">₱0</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="bg-white rounded p-3 stat-box red shadow-sm">
                                        <p class="text-muted small mb-1">Cancelled</p>
                                        <h3 class="mb-0" id="bs_cancelled">0</h3>
                                        <small class="text-muted" id="bs_cancelled_amt">₱0</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="bg-white rounded p-3 stat-box yellow shadow-sm">
                                        <p class="text-muted small mb-1">Payment Failed</p>
                                        <h3 class="mb-0" id="bs_failed">0</h3>
                                        <small class="text-muted">—</small>
                                    </div>
                                </div>
                            </div>

                            <!-- Bookings table -->
                            <div class="table-responsive">
                                <table class="table table-hover border table-report">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Order ID</th>
                                            <th>Guest</th>
                                            <th>Room</th>
                                            <th>Check-in</th>
                                            <th>Check-out</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bs_table"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ─────────────────────────────────────────── -->
                    <!-- 2. REVENUE BY DATE RANGE                    -->
                    <!-- ─────────────────────────────────────────── -->
                    <div class="card border-0 shadow-sm mb-4 report-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-cash-stack me-2 text-success"></i>
                                    Revenue by Date Range
                                </h5>
                                <div class="d-flex gap-2">
                                    <button onclick="export_report('revenue','pdf')"
                                            class="btn btn-danger btn-sm shadow-none export-btn">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                                    </button>
                                    <button onclick="export_report('revenue','excel')"
                                            class="btn btn-success btn-sm shadow-none export-btn">
                                        <i class="bi bi-file-earmark-excel me-1"></i> Excel
                                    </button>
                                </div>
                            </div>

                            <!-- Revenue stat boxes -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="bg-white rounded p-3 stat-box teal shadow-sm">
                                        <p class="text-muted small mb-1">Total Revenue</p>
                                        <h3 class="mb-0" id="rev_total">₱0</h3>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="bg-white rounded p-3 stat-box green shadow-sm">
                                        <p class="text-muted small mb-1">Average per Booking</p>
                                        <h3 class="mb-0" id="rev_avg">₱0</h3>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="bg-white rounded p-3 stat-box yellow shadow-sm">
                                        <p class="text-muted small mb-1">Highest Single Booking</p>
                                        <h3 class="mb-0" id="rev_max">₱0</h3>
                                    </div>
                                </div>
                            </div>

                            <!-- Revenue breakdown table -->
                            <div class="table-responsive">
                                <table class="table table-hover border table-report">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Date</th>
                                            <th>Order ID</th>
                                            <th>Room</th>
                                            <th>Guest</th>
                                            <th>Nights</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody id="rev_table"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- ─────────────────────────────────────────── -->
                    <!-- 3. OCCUPANCY RATE                           -->
                    <!-- ─────────────────────────────────────────── -->
                    <div class="card border-0 shadow-sm mb-4 report-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-door-open me-2 text-info"></i>
                                    Occupancy Rate
                                </h5>
                                <div class="d-flex gap-2">
                                    <button onclick="export_report('occupancy','pdf')"
                                            class="btn btn-danger btn-sm shadow-none export-btn">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                                    </button>
                                    <button onclick="export_report('occupancy','excel')"
                                            class="btn btn-success btn-sm shadow-none export-btn">
                                        <i class="bi bi-file-earmark-excel me-1"></i> Excel
                                    </button>
                                </div>
                            </div>

                            <!-- Overall occupancy -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="bg-white rounded p-3 stat-box teal shadow-sm">
                                        <p class="text-muted small mb-1">Overall Occupancy Rate</p>
                                        <h3 class="mb-0" id="occ_overall">0%</h3>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="bg-white rounded p-3 stat-box green shadow-sm">
                                        <p class="text-muted small mb-1">Total Booked Nights</p>
                                        <h3 class="mb-0" id="occ_booked_nights">0</h3>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="bg-white rounded p-3 stat-box yellow shadow-sm">
                                        <p class="text-muted small mb-1">Total Available Nights</p>
                                        <h3 class="mb-0" id="occ_available_nights">0</h3>
                                    </div>
                                </div>
                            </div>

                            <!-- Per-room occupancy table -->
                            <div class="table-responsive">
                                <table class="table table-hover border table-report">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Room Name</th>
                                            <th>Quantity</th>
                                            <th>Available Nights</th>
                                            <th>Booked Nights</th>
                                            <th>Occupancy Rate</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="occ_table"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- end reports-container -->

                <!-- ── Empty state ── -->
                <div id="reports-empty" class="text-center py-5">
                    <i class="bi bi-bar-chart-line" style="font-size:4rem;color:#ccc;"></i>
                    <h5 class="mt-3 text-muted">Select a date range and click Generate Reports</h5>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require('includes/admin_scripts.php'); ?>
<script src="scripts/admin_reports.js"></script>

</body>
</html>