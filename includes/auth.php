<?php
// includes/auth.php - Authentication & Access Control Handlers

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../config/db.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('danger', 'Please log in to access this page.');
        header('Location: ' . baseUrl('login.php'));
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        setFlash('danger', 'Access denied. Administrator privileges required.');
        header('Location: ' . baseUrl('dashboard.php'));
        exit;
    }
}

function currentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'user_id'   => $_SESSION['user_id'] ?? 0,
        'username'  => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? 'User',
        'email'     => $_SESSION['email'] ?? '',
        'role'      => $_SESSION['user_role'] ?? 'staff'
    ];
}
