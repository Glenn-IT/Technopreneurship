<?php
// reports.php - Financial & Status Reports Module
$pageTitle = 'Billing & Financial Reports';
require_once __DIR__ . '/includes/header.php';

// Filter inputs
$search       = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$monthFilter  = trim($_GET['month'] ?? '');
$dateFrom     = trim($_GET['date_from'] ?? '');
$dateTo       = trim($_GET['date_to'] ?? '');

$whereClauses = [];
$params = [];

if ($search !== '') {
    $whereClauses[] = "(consumer_name LIKE :s1 OR meter_number LIKE :s2)";
    $params['s1'] = "%{$search}%";
    $params['s2'] = "%{$search}%";
}

if ($statusFilter !== '') {
    $whereClauses[] = "status = :status";
    $params['status'] = $statusFilter;
}

if ($monthFilter !== '') {
    $whereClauses[] = "billing_month = :month";
    $params['month'] = $monthFilter;
}

if ($dateFrom !== '') {
    $whereClauses[] = "due_date >= :date_from";
    $params['date_from'] = $dateFrom;
}

if ($dateTo !== '') {
    $whereClauses[] = "due_date <= :date_to";
    $params['date_to'] = $dateTo;
}

$whereSQL = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

try {
    // Distinct Billing Months for Filter Dropdown
    $monthsStmt = $pdo->query("SELECT DISTINCT billing_month FROM tblaprilyn ORDER BY bill_id DESC");
    $distinctMonths = $monthsStmt->fetchAll(PDO::FETCH_COLUMN);

    // KPI Metrics with active filters
    $overallStmt = $pdo->prepare("SELECT 
        COUNT(*) as total_bills, 
        COALESCE(SUM(consumption), 0) as grand_consumption, 
        COALESCE(SUM(amount_due), 0) as grand_amount,
        COALESCE(SUM(CASE WHEN status = 'paid' THEN amount_due ELSE 0 END), 0) as paid_amount,
        COALESCE(SUM(CASE WHEN status = 'unpaid' THEN amount_due ELSE 0 END), 0) as unpaid_amount,
        COALESCE(SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END), 0) as paid_count,
        COALESCE(SUM(CASE WHEN status = 'unpaid' THEN 1 ELSE 0 END), 0) as unpaid_count
        FROM tblaprilyn {$whereSQL}");
    $overallStmt->execute($params);
    $overall = $overallStmt->fetch();

    $grandAmount = (float)($overall['grand_amount'] ?? 0);
    $paidAmount  = (float)($overall['paid_amount'] ?? 0);
    $collectionRate = ($grandAmount > 0) ? round(($paidAmount / $grandAmount) * 100, 1) : 0;

    // Summary by Status
    $statusSummaryStmt = $pdo->prepare("SELECT 
        status, 
        COUNT(*) as total_count, 
        COALESCE(SUM(consumption), 0) as total_consumption, 
        COALESCE(SUM(amount_due), 0) as total_amount 
        FROM tblaprilyn {$whereSQL}
        GROUP BY status");
    $statusSummaryStmt->execute($params);
    $statusSummary = $statusSummaryStmt->fetchAll();

    // Summary by Billing Month
    $monthSummaryStmt = $pdo->prepare("SELECT 
        billing_month, 
        COUNT(*) as total_count, 
        COALESCE(SUM(CASE WHEN status = 'paid' THEN amount_due ELSE 0 END), 0) as paid_amount, 
        COALESCE(SUM(CASE WHEN status = 'unpaid' THEN amount_due ELSE 0 END), 0) as unpaid_amount,
        COALESCE(SUM(amount_due), 0) as total_amount 
        FROM tblaprilyn {$whereSQL}
        GROUP BY billing_month 
        ORDER BY bill_id DESC");
    $monthSummaryStmt->execute($params);
    $monthSummary = $monthSummaryStmt->fetchAll();

    // Filtered Detailed Table (Limit 100 for performance)
    $detailStmt = $pdo->prepare("SELECT * FROM tblaprilyn {$whereSQL} ORDER BY due_date DESC, bill_id DESC LIMIT 100");
    $detailStmt->execute($params);
    $detailedBills = $detailStmt->fetchAll();

} catch (PDOException $e) {
    $distinctMonths = [];
    $statusSummary  = [];
    $monthSummary   = [];
    $detailedBills  = [];
    $overall        = ['total_bills' => 0, 'grand_consumption' => 0, 'grand_amount' => 0, 'paid_amount' => 0, 'unpaid_amount' => 0, 'paid_count' => 0, 'unpaid_count' => 0];
    $collectionRate = 0;
}

// Build query string for export & print links
$queryParams = $_GET;
$exportUrl = baseUrl('bills/export.php') . '?' . http_build_query($queryParams);
$printUrl  = baseUrl('report_print.php') . '?' . http_build_query($queryParams);
?>

<!-- Header Actions -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h3 class="m-0" style="font-size:1.35rem; font-weight:800; color:var(--text-main);">Billing & Financial Analytics</h3>
        <p class="text-muted m-0" style="font-size:0.88rem;">System-wide status reports, revenue collections, and usage analytics</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= $printUrl; ?>" target="_blank" class="btn-secondary-custom">
            <i data-feather="printer"></i> Print Official Report
        </a>
        <a href="<?= $exportUrl; ?>" class="btn-primary-custom">
            <i data-feather="download"></i> Export Filtered CSV
        </a>
    </div>
</div>

<!-- Interactive Report Filters Form -->
<div class="filter-card">
    <form method="GET" action="reports.php" class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label-custom">Search Consumer / Meter</label>
            <input type="text" name="search" class="form-control-custom" placeholder="Consumer name or meter #..." value="<?= sanitize($search); ?>">
        </div>

        <div class="col-md-2">
            <label class="form-label-custom">Billing Month</label>
            <select name="month" class="form-select-custom">
                <option value="">All Months</option>
                <?php foreach ($distinctMonths as $m): ?>
                    <option value="<?= sanitize($m); ?>" <?= ($monthFilter === $m) ? 'selected' : ''; ?>>
                        <?= sanitize($m); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label-custom">Payment Status</label>
            <select name="status" class="form-select-custom">
                <option value="">All Statuses</option>
                <option value="paid" <?= ($statusFilter === 'paid') ? 'selected' : ''; ?>>Paid</option>
                <option value="unpaid" <?= ($statusFilter === 'unpaid') ? 'selected' : ''; ?>>Unpaid</option>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label-custom">Due Date From</label>
            <input type="date" name="date_from" class="form-control-custom" value="<?= sanitize($dateFrom); ?>">
        </div>

        <div class="col-md-2">
            <label class="form-label-custom">Due Date To</label>
            <input type="date" name="date_to" class="form-control-custom" value="<?= sanitize($dateTo); ?>">
        </div>

        <div class="col-md-1 d-flex gap-2">
            <button type="submit" class="btn-primary-custom w-100 justify-content-center" title="Apply Filters">
                <i data-feather="filter"></i>
            </button>
            <?php if ($search || $statusFilter || $monthFilter || $dateFrom || $dateTo): ?>
                <a href="reports.php" class="btn-secondary-custom justify-content-center" title="Reset Filters">
                    <i data-feather="rotate-ccw"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Overall Metric Summary Cards -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon-wrapper stat-icon-blue">
            <i data-feather="droplet"></i>
        </div>
        <div>
            <div class="stat-val"><?= number_format($overall['grand_consumption'] ?? 0, 2); ?> m³</div>
            <div class="stat-lbl">Total Water Volume</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon-wrapper stat-icon-blue" style="background:#e0e7ff; color:#4338ca;">
            <i data-feather="dollar-sign"></i>
        </div>
        <div>
            <div class="stat-val"><?= formatMoney($overall['grand_amount'] ?? 0); ?></div>
            <div class="stat-lbl">Total Billed Revenue</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon-wrapper stat-icon-green">
            <i data-feather="check-circle"></i>
        </div>
        <div>
            <div class="stat-val"><?= formatMoney($overall['paid_amount'] ?? 0); ?></div>
            <div class="stat-lbl">Paid Revenue</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon-wrapper stat-icon-red">
            <i data-feather="alert-circle"></i>
        </div>
        <div>
            <div class="stat-val"><?= formatMoney($overall['unpaid_amount'] ?? 0); ?></div>
            <div class="stat-lbl">Unpaid Balance</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon-wrapper stat-icon-amber">
            <i data-feather="pie-chart"></i>
        </div>
        <div>
            <div class="stat-val"><?= number_format($collectionRate, 1); ?>%</div>
            <div class="stat-lbl">Collection Rate</div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <div class="col-lg-8 mb-3">
        <div class="card-box h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h4 style="font-size:1.05rem; font-weight:700; margin:0;">Monthly Revenue Collection Trend</h4>
                <span class="text-muted" style="font-size:0.8rem;">Paid vs Unpaid Breakdown</span>
            </div>
            <div class="chart-container">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4 mb-3">
        <div class="card-box h-100">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h4 style="font-size:1.05rem; font-weight:700; margin:0;">Payment Status Distribution</h4>
                <span class="text-muted" style="font-size:0.8rem;">Count</span>
            </div>
            <div class="chart-container">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Summary Tables Row -->
<div class="row mb-4">
    <!-- Payment Status Breakdown -->
    <div class="col-lg-5 mb-4">
        <div class="card-box h-100">
            <h4 style="font-size:1.05rem; font-weight:700;" class="mb-3">Report by Status (Paid / Unpaid)</h4>
            <div class="table-responsive-wrapper">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Bills</th>
                            <th>Volume (m³)</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($statusSummary)): ?>
                            <tr><td colspan="4" class="text-center text-muted">No data available</td></tr>
                        <?php else: ?>
                            <?php foreach ($statusSummary as $st): ?>
                                <tr>
                                    <td><?= renderStatusBadge($st['status']); ?></td>
                                    <td><strong><?= number_format($st['total_count']); ?></strong></td>
                                    <td><?= number_format($st['total_consumption'], 2); ?> m³</td>
                                    <td><strong><?= formatMoney($st['total_amount']); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Billing Month Breakdown -->
    <div class="col-lg-7 mb-4">
        <div class="card-box h-100">
            <h4 style="font-size:1.05rem; font-weight:700;" class="mb-3">Report by Billing Cycle (Month)</h4>
            <div class="table-responsive-wrapper">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Billing Month</th>
                            <th>Bills</th>
                            <th>Paid Revenue</th>
                            <th>Unpaid Balance</th>
                            <th>Total Billed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($monthSummary)): ?>
                            <tr><td colspan="5" class="text-center text-muted">No data available</td></tr>
                        <?php else: ?>
                            <?php foreach ($monthSummary as $ms): ?>
                                <tr>
                                    <td><strong><?= sanitize($ms['billing_month']); ?></strong></td>
                                    <td><?= number_format($ms['total_count']); ?></td>
                                    <td class="text-success font-weight-semibold"><?= formatMoney($ms['paid_amount']); ?></td>
                                    <td class="text-danger font-weight-semibold"><?= formatMoney($ms['unpaid_amount']); ?></td>
                                    <td><strong><?= formatMoney($ms['total_amount']); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Filtered Billing Records Table -->
<div class="card-box">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 style="font-size:1.1rem; font-weight:700; margin:0;">Detailed Itemized Records</h4>
            <p class="text-muted m-0" style="font-size:0.83rem;">Showing up to 100 matching bill records based on active filters</p>
        </div>
        <div class="text-muted font-weight-semibold" style="font-size:0.88rem;">
            Matches: <?= count($detailedBills); ?> Bills
        </div>
    </div>

    <div class="table-responsive-wrapper">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Bill ID</th>
                    <th>Consumer Name</th>
                    <th>Meter Number</th>
                    <th>Billing Month</th>
                    <th>Consumption</th>
                    <th>Amount Due</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($detailedBills)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No matching records found for the applied filters.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($detailedBills as $bill): ?>
                        <tr>
                            <td><strong>#<?= sprintf('%04d', $bill['bill_id']); ?></strong></td>
                            <td><strong><?= sanitize($bill['consumer_name']); ?></strong></td>
                            <td><code><?= sanitize($bill['meter_number']); ?></code></td>
                            <td><?= sanitize($bill['billing_month']); ?></td>
                            <td><?= number_format($bill['consumption'], 2); ?> m³</td>
                            <td><strong><?= formatMoney($bill['amount_due']); ?></strong></td>
                            <td><?= date('M d, Y', strtotime($bill['due_date'])); ?></td>
                            <td><?= renderStatusBadge($bill['status']); ?></td>
                            <td class="text-muted" style="font-size:0.85rem;"><?= sanitize($bill['remarks'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js Integration Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Data Preparation for Monthly Bar Chart
    const monthLabels = <?= json_encode(array_column(array_reverse($monthSummary), 'billing_month')); ?>;
    const paidData    = <?= json_encode(array_map('floatval', array_column(array_reverse($monthSummary), 'paid_amount'))); ?>;
    const unpaidData  = <?= json_encode(array_map('floatval', array_column(array_reverse($monthSummary), 'unpaid_amount'))); ?>;

    // Revenue Bar Chart
    const ctxRev = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctxRev, {
        type: 'bar',
        data: {
            labels: monthLabels.length ? monthLabels : ['No Data'],
            datasets: [
                {
                    label: 'Paid Revenue (₱)',
                    data: paidData.length ? paidData : [0],
                    backgroundColor: '#22c55e',
                    borderRadius: 6
                },
                {
                    label: 'Unpaid Balance (₱)',
                    data: unpaidData.length ? unpaidData : [0],
                    backgroundColor: '#ef4444',
                    borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                x: { grid: { display: false } },
                y: { 
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) { return '₱' + value.toLocaleString(); }
                    }
                }
            }
        }
    });

    // Status Doughnut Chart
    const paidCount   = <?= (int)($overall['paid_count'] ?? 0); ?>;
    const unpaidCount = <?= (int)($overall['unpaid_count'] ?? 0); ?>;

    const ctxStat = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStat, {
        type: 'doughnut',
        data: {
            labels: ['Paid Bills', 'Unpaid Bills'],
            datasets: [{
                data: [paidCount, unpaidCount],
                backgroundColor: ['#22c55e', '#ef4444'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
