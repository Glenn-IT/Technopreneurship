<?php
// index.php - Entry point redirector
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . baseUrl('dashboard.php'));
} else {
    header('Location: ' . baseUrl('login.php'));
}
exit;
