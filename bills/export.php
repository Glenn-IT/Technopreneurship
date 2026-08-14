<?php
// bills/export.php - Excel / CSV Data Export Module
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

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

try {
    $stmt = $pdo->prepare("SELECT bill_id, consumer_name, meter_number, billing_month, consumption, amount_due, due_date, status, remarks, created_at FROM bills {$whereSQL} ORDER BY bill_id DESC");
    $stmt->execute($params);
    $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = "Water_Bills_Report_" . date('Y-m-d_H-i') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Output CSV Column Headers
    fputcsv($output, ['Bill ID', 'Consumer Name', 'Meter Number', 'Billing Month', 'Consumption (m3)', 'Amount Due (PHP)', 'Due Date', 'Status', 'Remarks', 'Created At']);

    // Output Data Rows
    foreach ($bills as $row) {
        fputcsv($output, [
            $row['bill_id'],
            $row['consumer_name'],
            $row['meter_number'],
            $row['billing_month'],
            number_format($row['consumption'], 2, '.', ''),
            number_format($row['amount_due'], 2, '.', ''),
            $row['due_date'],
            strtoupper($row['status']),
            $row['remarks'],
            $row['created_at']
        ]);
    }

    fclose($output);
    exit;

} catch (PDOException $e) {
    setFlash('danger', 'Failed to generate export file: ' . $e->getMessage());
    header('Location: ' . baseUrl('bills/index.php'));
    exit;
}
