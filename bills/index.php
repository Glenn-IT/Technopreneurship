<?php
// bills/index.php - View Record & Search/Filter Module
$pageTitle = 'Water Billing Records';
require_once __DIR__ . '/../includes/header.php';

// Search & Filter Parameters
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$monthFilter = trim($_GET['month'] ?? '');

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

$whereSQL = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Fetch distinct billing months for dropdown filter
$monthsStmt = $pdo->query("SELECT DISTINCT billing_month FROM bills ORDER BY billing_month DESC");
$billingMonths = $monthsStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch Matching Bills
try {
    $stmt = $pdo->prepare("SELECT * FROM bills {$whereSQL} ORDER BY bill_id DESC");
    $stmt->execute($params);
    $bills = $stmt->fetchAll();
} catch (PDOException $e) {
    $bills = [];
}
?>

<div class="card-box">
    <!-- Header & Search Toolbar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h3 class="m-0" style="font-size:1.2rem; font-weight:700;">Consumer Water Bills</h3>
            <p class="text-muted m-0" style="font-size:0.85rem;">Manage, search, filter, and export billing statements</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= baseUrl('bills/export.php?' . http_build_query($_GET)); ?>" class="btn-secondary-custom">
                <i data-feather="download"></i> Export CSV/Excel
            </a>
            <a href="<?= baseUrl('bills/add.php'); ?>" class="btn-primary-custom">
                <i data-feather="plus"></i> Add New Record
            </a>
        </div>
    </div>

    <!-- Filter Form Bar -->
    <form method="GET" action="" class="row g-3 align-items-center mb-4 bg-light p-3 rounded">
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i data-feather="search" style="width:16px;"></i></span>
                <input type="text" name="search" id="tableSearchInput" class="form-control border-start-0 ps-0" 
                       placeholder="Search Consumer Name or Meter #" value="<?= sanitize($search); ?>">
            </div>
        </div>

        <div class="col-md-3">
            <select name="status" id="tableStatusFilter" class="form-select-custom">
                <option value="">-- All Payment Status --</option>
                <option value="paid" <?= ($statusFilter === 'paid') ? 'selected' : ''; ?>>Paid</option>
                <option value="unpaid" <?= ($statusFilter === 'unpaid') ? 'selected' : ''; ?>>Unpaid</option>
            </select>
        </div>

        <div class="col-md-3">
            <select name="month" class="form-select-custom">
                <option value="">-- All Billing Months --</option>
                <?php foreach ($billingMonths as $bMonth): ?>
                    <option value="<?= sanitize($bMonth); ?>" <?= ($monthFilter === $bMonth) ? 'selected' : ''; ?>>
                        <?= sanitize($bMonth); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn-primary-custom w-100 justify-content-center">Filter</button>
            <?php if ($search !== '' || $statusFilter !== '' || $monthFilter !== ''): ?>
                <a href="<?= baseUrl('bills/index.php'); ?>" class="btn-secondary-custom" title="Clear Filters">
                    <i data-feather="x"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Table Container -->
    <div class="table-responsive-wrapper">
        <table class="table-custom" id="recordsTable">
            <thead>
                <tr>
                    <th>Bill ID</th>
                    <th>Consumer Name</th>
                    <th>Meter No.</th>
                    <th>Billing Month</th>
                    <th>Consumption (m³)</th>
                    <th>Amount Due</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Remarks</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bills)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-muted py-5">
                            <i data-feather="inbox" style="width:36px; height:36px; opacity:0.4;" class="mb-2"></i><br>
                            No billing records matched your query.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bills as $bill): ?>
                        <tr>
                            <td><strong>#<?= str_pad($bill['bill_id'], 5, '0', STR_PAD_LEFT); ?></strong></td>
                            <td><strong><?= sanitize($bill['consumer_name']); ?></strong></td>
                            <td><code><?= sanitize($bill['meter_number']); ?></code></td>
                            <td><?= sanitize($bill['billing_month']); ?></td>
                            <td><?= number_format($bill['consumption'], 2); ?> m³</td>
                            <td><strong><?= formatMoney($bill['amount_due']); ?></strong></td>
                            <td><?= sanitize($bill['due_date']); ?></td>
                            <td><?= renderStatusBadge($bill['status']); ?></td>
                            <td><small class="text-muted"><?= sanitize($bill['remarks'] ?? '-'); ?></small></td>
                            <td class="text-end">
                                <div class="table-action-btns">
                                    <a href="<?= baseUrl('bills/edit.php?id=' . $bill['bill_id']); ?>" class="btn-action-edit" title="Edit Bill">
                                        <i data-feather="edit-2"></i> Edit
                                    </a>
                                    <a href="<?= baseUrl('bills/delete.php?id=' . $bill['bill_id']); ?>" 
                                       class="btn-action-delete btn-delete-confirm" 
                                       data-item="Bill #<?= $bill['bill_id']; ?> for <?= sanitize($bill['consumer_name']); ?>" title="Delete Bill">
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
