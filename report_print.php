<?php
// report_print.php - Printable Official Report View
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$user = currentUser();

// Read filter query parameters
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
    // Fetch Summary Metrics
    $summaryStmt = $pdo->prepare("SELECT 
        COUNT(*) as total_bills,
        COALESCE(SUM(consumption), 0) as total_consumption,
        COALESCE(SUM(amount_due), 0) as total_amount,
        COALESCE(SUM(CASE WHEN status = 'paid' THEN amount_due ELSE 0 END), 0) as paid_amount,
        COALESCE(SUM(CASE WHEN status = 'unpaid' THEN amount_due ELSE 0 END), 0) as unpaid_amount,
        COALESCE(SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END), 0) as paid_count,
        COALESCE(SUM(CASE WHEN status = 'unpaid' THEN 1 ELSE 0 END), 0) as unpaid_count
        FROM bills {$whereSQL}");
    $summaryStmt->execute($params);
    $metrics = $summaryStmt->fetch(PDO::FETCH_ASSOC);

    // Fetch Detailed Bills
    $stmt = $pdo->prepare("SELECT * FROM bills {$whereSQL} ORDER BY due_date DESC, bill_id DESC");
    $stmt->execute($params);
    $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $collectionRate = ($metrics['total_amount'] > 0) ? ($metrics['paid_amount'] / $metrics['total_amount']) * 100 : 0;

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Billing & Financial Summary Report - Ramos Water</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1e293b;
            background: #f8fafc;
            padding: 20px;
        }
        .report-paper {
            background: #ffffff;
            max-width: 960px;
            margin: 0 auto;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .report-header {
            border-bottom: 2px solid #0284c7;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .report-title {
            font-size: 1.6rem;
            font-weight: 800;
            color: #0284c7;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .report-sub {
            font-size: 0.9rem;
            color: #64748b;
        }
        .metric-box {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 12px 16px;
            text-align: center;
        }
        .metric-val {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
        }
        .metric-lbl {
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
        }
        .table-report {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            font-size: 0.88rem;
        }
        .table-report th {
            background-color: #0284c7;
            color: #ffffff;
            padding: 8px 12px;
            font-size: 0.8rem;
            text-transform: uppercase;
        }
        .table-report td {
            padding: 8px 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .table-report tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .signature-section {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 260px;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #475569;
            margin-top: 50px;
            padding-top: 6px;
            font-weight: 600;
            font-size: 0.88rem;
        }
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .report-paper {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="no-print d-flex justify-content-between align-items-center max-w-960 mx-auto mb-3" style="max-width:960px;">
    <a href="reports.php" class="btn btn-outline-secondary btn-sm">&larr; Back to Reports</a>
    <button onclick="window.print()" class="btn btn-primary btn-sm">🖨️ Print / Save as PDF</button>
</div>

<div class="report-paper">
    <!-- Header -->
    <div class="report-header d-flex justify-content-between align-items-center">
        <div>
            <div class="report-title">RAMOS WATER UTILITY</div>
            <div class="report-sub">Official Billing & Financial Summary Report</div>
        </div>
        <div class="text-end" style="font-size:0.82rem; color:#64748b;">
            <div><strong>Date Generated:</strong> <?= date('F d, Y h:i A'); ?></div>
            <div><strong>Generated By:</strong> <?= htmlspecialchars($user['full_name']); ?></div>
            <div><strong>System Status:</strong> Active</div>
        </div>
    </div>

    <!-- Active Filters Summary -->
    <div class="mb-4 p-2 bg-light rounded border" style="font-size:0.85rem;">
        <strong>Applied Filters:</strong>
        Status: <span class="badge bg-secondary"><?= $statusFilter ? strtoupper($statusFilter) : 'ALL' ?></span> |
        Month: <span class="badge bg-secondary"><?= $monthFilter ? htmlspecialchars($monthFilter) : 'ALL' ?></span> |
        Date Range: <span class="badge bg-secondary"><?= $dateFrom ? $dateFrom : 'Any' ?> to <?= $dateTo ? $dateTo : 'Any' ?></span> |
        Search: <span class="badge bg-secondary"><?= $search ? htmlspecialchars($search) : 'None' ?></span>
    </div>

    <!-- Key Metrics Grid -->
    <div class="row g-2 mb-4">
        <div class="col-md-2 col-4">
            <div class="metric-box">
                <div class="metric-val"><?= number_format($metrics['total_bills']); ?></div>
                <div class="metric-lbl">Total Bills</div>
            </div>
        </div>
        <div class="col-md-2 col-4">
            <div class="metric-box">
                <div class="metric-val"><?= number_format($metrics['total_consumption'], 2); ?> m³</div>
                <div class="metric-lbl">Volume (m³)</div>
            </div>
        </div>
        <div class="col-md-3 col-4">
            <div class="metric-box">
                <div class="metric-val">₱<?= number_format($metrics['total_amount'], 2); ?></div>
                <div class="metric-lbl">Total Billed</div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="metric-box" style="background:#dcfce7; border-color:#86efac;">
                <div class="metric-val text-success">₱<?= number_format($metrics['paid_amount'], 2); ?></div>
                <div class="metric-lbl text-success">Total Paid</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="metric-box" style="background:#fee2e2; border-color:#fca5a5;">
                <div class="metric-val text-danger">₱<?= number_format($metrics['unpaid_amount'], 2); ?></div>
                <div class="metric-lbl text-danger">Unpaid Arrears</div>
            </div>
        </div>
    </div>

    <!-- Itemized Detailed Bills Table -->
    <h5 class="fw-bold mb-2" style="font-size:1.05rem;">Itemized Billing Records (<?= count($bills); ?> Records)</h5>
    <table class="table-report">
        <thead>
            <tr>
                <th>Bill #</th>
                <th>Consumer Name</th>
                <th>Meter No.</th>
                <th>Month</th>
                <th class="text-end">Usage (m³)</th>
                <th class="text-end">Amount Due</th>
                <th>Due Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($bills)): ?>
                <tr><td colspan="8" class="text-center text-muted">No bill records found matching criteria.</td></tr>
            <?php else: ?>
                <?php foreach ($bills as $b): ?>
                    <tr>
                        <td>#<?= sprintf('%04d', $b['bill_id']); ?></td>
                        <td><strong><?= htmlspecialchars($b['consumer_name']); ?></strong></td>
                        <td><?= htmlspecialchars($b['meter_number']); ?></td>
                        <td><?= htmlspecialchars($b['billing_month']); ?></td>
                        <td class="text-end"><?= number_format($b['consumption'], 2); ?> m³</td>
                        <td class="text-end"><strong>₱<?= number_format($b['amount_due'], 2); ?></strong></td>
                        <td><?= date('M d, Y', strtotime($b['due_date'])); ?></td>
                        <td>
                            <?php if ($b['status'] === 'paid'): ?>
                                <span class="text-success fw-bold">PAID</span>
                            <?php else: ?>
                                <span class="text-danger fw-bold">UNPAID</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Signature Block -->
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line">
                <?= htmlspecialchars($user['full_name']); ?><br>
                <span class="text-muted fw-normal" style="font-size:0.8rem;">Prepared By (<?= ucfirst($user['role']); ?>)</span>
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                Billing Manager / Supervisor<br>
                <span class="text-muted fw-normal" style="font-size:0.8rem;">Approved & Certified Correct</span>
            </div>
        </div>
    </div>
</div>

<script>
    // Automatically open print dialog on page load
    window.addEventListener('load', function() {
        setTimeout(function() { window.print(); }, 500);
    });
</script>

</body>
</html>
