<?php
// dashboard.php - Main Application Dashboard
$pageTitle = 'Dashboard Overview';
require_once __DIR__ . '/includes/header.php';

// Fetch Statistics
try {
    $totalBills   = $pdo->query("SELECT COUNT(*) FROM bills")->fetchColumn() ?: 0;
    $totalPaid    = $pdo->query("SELECT COUNT(*) FROM bills WHERE status = 'paid'")->fetchColumn() ?: 0;
    $totalUnpaid  = $pdo->query("SELECT COUNT(*) FROM bills WHERE status = 'unpaid'")->fetchColumn() ?: 0;
    
    $revenuePaid  = $pdo->query("SELECT SUM(amount_due) FROM bills WHERE status = 'paid'")->fetchColumn() ?: 0.00;
    $revenuePending = $pdo->query("SELECT SUM(amount_due) FROM bills WHERE status = 'unpaid'")->fetchColumn() ?: 0.00;

    // Fetch 5 Latest Records
    $recentStmt = $pdo->query("SELECT * FROM bills ORDER BY bill_id DESC LIMIT 5");
    $recentBills = $recentStmt->fetchAll();
} catch (PDOException $e) {
    $totalBills = $totalPaid = $totalUnpaid = 0;
    $revenuePaid = $revenuePending = 0.00;
    $recentBills = [];
}
?>

<!-- Statistics Overview -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-wrapper stat-icon-blue">
            <i data-feather="file-text"></i>
        </div>
        <div>
            <div class="stat-val"><?= number_format($totalBills); ?></div>
            <div class="stat-lbl">Total Billing Records</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon-wrapper stat-icon-green">
            <i data-feather="check-circle"></i>
        </div>
        <div>
            <div class="stat-val"><?= formatMoney($revenuePaid); ?></div>
            <div class="stat-lbl">Total Revenue Collected (<?= $totalPaid; ?> Paid)</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon-wrapper stat-icon-red">
            <i data-feather="alert-circle"></i>
        </div>
        <div>
            <div class="stat-val"><?= formatMoney($revenuePending); ?></div>
            <div class="stat-lbl">Pending Unpaid Balance (<?= $totalUnpaid; ?> Bills)</div>
        </div>
    </div>
</div>

<!-- Dashboard Quick Actions & Recent Table -->
<div class="row">
    <div class="col-12">
        <div class="card-box">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h3 class="m-0" style="font-size:1.15rem; font-weight:700;">Recent Billing Records</h3>
                    <p class="text-muted m-0" style="font-size:0.85rem;">Latest water meter consumption and status</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= baseUrl('bills/add.php'); ?>" class="btn-primary-custom">
                        <i data-feather="plus"></i> Add New Record
                    </a>
                    <a href="<?= baseUrl('bills/index.php'); ?>" class="btn-secondary-custom">
                        <i data-feather="list"></i> View All Records
                    </a>
                </div>
            </div>

            <div class="table-responsive-wrapper">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Bill #</th>
                            <th>Consumer Name</th>
                            <th>Meter No.</th>
                            <th>Billing Month</th>
                            <th>Consumption</th>
                            <th>Amount Due</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentBills)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No water bill records found. <a href="<?= baseUrl('bills/add.php'); ?>">Add one now</a>.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentBills as $bill): ?>
                                <tr>
                                    <td><strong>#<?= str_pad($bill['bill_id'], 5, '0', STR_PAD_LEFT); ?></strong></td>
                                    <td><strong><?= sanitize($bill['consumer_name']); ?></strong></td>
                                    <td><code><?= sanitize($bill['meter_number']); ?></code></td>
                                    <td><?= sanitize($bill['billing_month']); ?></td>
                                    <td><?= number_format($bill['consumption'], 2); ?> m³</td>
                                    <td><strong><?= formatMoney($bill['amount_due']); ?></strong></td>
                                    <td><?= sanitize($bill['due_date']); ?></td>
                                    <td><?= renderStatusBadge($bill['status']); ?></td>
                                    <td class="text-end">
                                        <div class="table-action-btns">
                                            <a href="<?= baseUrl('bills/edit.php?id=' . $bill['bill_id']); ?>" class="btn-action-edit" title="Edit Bill">
                                                <i data-feather="edit-2"></i> Edit
                                            </a>
                                            <a href="<?= baseUrl('bills/delete.php?id=' . $bill['bill_id']); ?>" class="btn-action-delete btn-delete-confirm" data-item="Bill #<?= $bill['bill_id']; ?>" title="Delete Bill">
                                                <i data-feather="trash-2"></i> Delete
                                            </a>
                                        </div>
                                    </td>
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
