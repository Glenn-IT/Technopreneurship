<?php
// reports.php - Financial & Status Reports Module
$pageTitle = 'Billing & Payment Reports';
require_once __DIR__ . '/includes/header.php';

try {
    // Summary by Status
    $statusSummaryStmt = $pdo->query("SELECT 
        status, 
        COUNT(*) as total_count, 
        SUM(consumption) as total_consumption, 
        SUM(amount_due) as total_amount 
        FROM bills 
        GROUP BY status");
    $statusSummary = $statusSummaryStmt->fetchAll();

    // Summary by Billing Month
    $monthSummaryStmt = $pdo->query("SELECT 
        billing_month, 
        COUNT(*) as total_count, 
        SUM(CASE WHEN status = 'paid' THEN amount_due ELSE 0 END) as paid_amount, 
        SUM(CASE WHEN status = 'unpaid' THEN amount_due ELSE 0 END) as unpaid_amount,
        SUM(amount_due) as total_amount 
        FROM bills 
        GROUP BY billing_month 
        ORDER BY bill_id DESC");
    $monthSummary = $monthSummaryStmt->fetchAll();

    // Overall Totals
    $overallStmt = $pdo->query("SELECT 
        COUNT(*) as total_bills, 
        SUM(consumption) as grand_consumption, 
        SUM(amount_due) as grand_amount 
        FROM bills");
    $overall = $overallStmt->fetch();

} catch (PDOException $e) {
    $statusSummary = [];
    $monthSummary = [];
    $overall = ['total_bills' => 0, 'grand_consumption' => 0, 'grand_amount' => 0];
}
?>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h3 class="m-0" style="font-size:1.25rem; font-weight:700;">Financial & Usage Summary</h3>
        <p class="text-muted m-0" style="font-size:0.85rem;">System-wide status reports and monthly collection metrics</p>
    </div>
    <a href="<?= baseUrl('bills/export.php'); ?>" class="btn-secondary-custom">
        <i data-feather="download"></i> Download Full Data CSV
    </a>
</div>

<!-- Overall Metrics -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-icon-wrapper stat-icon-blue">
            <i data-feather="droplet"></i>
        </div>
        <div>
            <div class="stat-val"><?= number_format($overall['grand_consumption'] ?? 0, 2); ?> m³</div>
            <div class="stat-lbl">Total Water Consumed</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon-wrapper stat-icon-green">
            <i data-feather="dollar-sign"></i>
        </div>
        <div>
            <div class="stat-val"><?= formatMoney($overall['grand_amount'] ?? 0); ?></div>
            <div class="stat-lbl">Total Billed Amount</div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Payment Status Breakdown -->
    <div class="col-lg-5 mb-4">
        <div class="card-box h-100">
            <h4 style="font-size:1.05rem; font-weight:700;" class="mb-3">Report by Status (Paid / Unpaid)</h4>
            <div class="table-responsive-wrapper">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Bills Count</th>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
