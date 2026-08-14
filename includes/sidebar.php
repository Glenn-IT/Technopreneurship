<?php
// includes/sidebar.php - Navigation Sidebar Component
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));
$user = currentUser();
?>
<aside class="app-sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand-icon">
            <i data-feather="droplet"></i>
        </div>
        <div>
            <div class="sidebar-brand-text">Ramos Water</div>
            <div class="sidebar-brand-sub">Billing System</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Main Navigation</div>
        
        <a href="<?= baseUrl('dashboard.php'); ?>" 
           class="sidebar-link <?= ($currentPage === 'dashboard.php') ? 'active' : ''; ?>">
            <i data-feather="grid"></i>
            <span>Dashboard</span>
        </a>

        <a href="<?= baseUrl('bills/index.php'); ?>" 
           class="sidebar-link <?= ($currentDir === 'bills' || $currentPage === 'bills.php') ? 'active' : ''; ?>">
            <i data-feather="file-text"></i>
            <span>Water Bills</span>
        </a>

        <a href="<?= baseUrl('reports.php'); ?>" 
           class="sidebar-link <?= ($currentPage === 'reports.php') ? 'active' : ''; ?>">
            <i data-feather="pie-chart"></i>
            <span>Reports</span>
        </a>

        <?php if (($user['role'] ?? '') === 'admin'): ?>
        <div class="nav-section-title">Administration</div>
        <a href="<?= baseUrl('users/index.php'); ?>" 
           class="sidebar-link <?= ($currentDir === 'users') ? 'active' : ''; ?>">
            <i data-feather="users"></i>
            <span>Manage Users</span>
        </a>
        <?php endif; ?>

        <div class="nav-section-title">Account</div>
        <a href="<?= baseUrl('profile.php'); ?>" 
           class="sidebar-link <?= ($currentPage === 'profile.php') ? 'active' : ''; ?>">
            <i data-feather="user"></i>
            <span>My Profile</span>
        </a>

        <a href="<?= baseUrl('logout.php'); ?>" class="sidebar-link text-danger mt-2">
            <i data-feather="log-out"></i>
            <span>Logout</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-profile-badge">
            <div class="user-avatar">
                <?= strtoupper(substr($user['full_name'] ?? 'U', 0, 1)); ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?= sanitize($user['full_name'] ?? ''); ?></div>
                <div class="user-role"><?= sanitize($user['role'] ?? 'Staff'); ?></div>
            </div>
        </div>
    </div>
</aside>
