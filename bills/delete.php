<?php
// bills/delete.php - Delete Record Module
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$billId = (int)($_GET['id'] ?? 0);

if ($billId > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM tblaprilyn WHERE bill_id = :id LIMIT 1");
        $stmt->execute(['id' => $billId]);
        setFlash('success', 'Water bill record #' . $billId . ' has been deleted.');
    } catch (PDOException $e) {
        setFlash('danger', 'Error deleting record: ' . $e->getMessage());
    }
} else {
    setFlash('danger', 'Invalid bill ID.');
}

header('Location: ' . baseUrl('bills/index.php'));
exit;
