<?php
// logout.php - Logout handler
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_ACTIVE) {
    session_unset();
    session_destroy();
}

session_start();
setFlash('info', 'You have been successfully logged out.');
header('Location: ' . baseUrl('login.php'));
exit;
