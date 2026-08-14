<?php
// includes/functions.php - Utility helper functions for Water Billing System

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (ob_get_level() === 0) {
    ob_start();
}

/**
 * Sanitize output text for XSS prevention
 */
function sanitize($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Base URL helper function
 */
function baseUrl($path = '') {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Determine base folder dynamically
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = dirname($scriptName);
    
    // Adjust if in subfolder like /bills or /users
    if (str_ends_with($dir, '/bills') || str_ends_with($dir, '\bills') || str_ends_with($dir, '/users') || str_ends_with($dir, '\users')) {
        $dir = dirname($dir);
    }
    
    $base = rtrim($protocol . $host . $dir, '/\\');
    return $base . '/' . ltrim($path, '/');
}

/**
 * CSRF Token Generator & Validator
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
}

/**
 * Session Flash Messaging System
 */
function setFlash($type, $message) {
    $_SESSION['flash_msg'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash_msg'])) {
        $flash = $_SESSION['flash_msg'];
        unset($_SESSION['flash_msg']);
        $type = sanitize($flash['type']);
        $msg = sanitize($flash['message']);
        
        $icon = match($type) {
            'success' => 'check-circle',
            'danger'  => 'alert-circle',
            'warning' => 'alert-triangle',
            default   => 'info'
        };

        return "<div class='alert alert-{$type} alert-dismissible fade show d-flex align-items-center mb-4' role='alert'>
                    <i class='feather-{$icon} me-2'></i>
                    <div>{$msg}</div>
                    <button type='button' class='btn-close ms-auto' data-bs-dismiss='alert' aria-label='Close'></button>
                </div>";
    }
    return '';
}

/**
 * Format currency in Philippine Peso (₱) or standard decimal
 */
function formatMoney($amount) {
    return '₱' . number_format((float)$amount, 2, '.', ',');
}

/**
 * Render Payment Status Badge
 */
function renderStatusBadge($status) {
    $statusLower = strtolower(trim($status));
    if ($statusLower === 'paid') {
        return '<span class="badge badge-paid"><i class="feather-check-circle me-1"></i> Paid</span>';
    } else {
        return '<span class="badge badge-unpaid"><i class="feather-clock me-1"></i> Unpaid</span>';
    }
}
