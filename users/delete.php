<?php
// users/delete.php - Delete User Module (Admin Only)
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$userId = (int)($_GET['id'] ?? 0);

if ($userId > 0) {
    if ($userId == $_SESSION['user_id']) {
        setFlash('danger', 'You cannot delete your own logged-in account.');
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = :id LIMIT 1");
            $stmt->execute(['id' => $userId]);
            setFlash('success', 'User account #' . $userId . ' has been removed.');
        } catch (PDOException $e) {
            setFlash('danger', 'Error deleting user: ' . $e->getMessage());
        }
    }
} else {
    setFlash('danger', 'Invalid user ID.');
}

header('Location: ' . baseUrl('users/index.php'));
exit;
