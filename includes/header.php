<?php
// includes/header.php - Global Header & Top Navigation Bar
require_once __DIR__ . '/auth.php';
requireLogin();

$user = currentUser();
$pageTitle = $pageTitle ?? 'Water Billing System for Sta. Barbara, Piat Cagayan';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle); ?> - Sta. Barbara, Piat Cagayan</title>

    <!-- Google Fonts & Feather Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
    <script src="https://unpkg.com/feather-icons"></script>
    
    <!-- Custom Design System -->
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css'); ?>">
</head>
<body>
<div class="app-wrapper">
    <!-- Sidebar Component -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        <!-- Top Navbar -->
        <header class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none" id="sidebarToggle" type="button">
                    <i data-feather="menu"></i>
                </button>
                <h1 class="page-header-title"><?= sanitize($pageTitle); ?></h1>
            </div>
            
            <div class="top-navbar-actions">
                <div class="dropdown">
                    <button class="btn btn-light border-0 d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar" style="width:32px; height:32px; font-size:0.85rem;">
                            <?= strtoupper(substr($user['full_name'], 0, 1)); ?>
                        </div>
                        <span class="d-none d-md-inline font-weight-semibold" style="font-size:0.9rem;">
                            <?= sanitize($user['full_name']); ?>
                        </span>
                        <i data-feather="chevron-down" style="width:16px;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2" href="<?= baseUrl('profile.php'); ?>">
                                <i data-feather="user" style="width:16px;"></i> My Profile
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger d-flex align-items-center gap-2" href="<?= baseUrl('logout.php'); ?>">
                                <i data-feather="log-out" style="width:16px;"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="content-container">
            <?= getFlash(); ?>
