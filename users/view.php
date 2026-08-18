<?php
// users/view.php - View User Account (Read-Only, Admin Only)
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$userId = (int)($_GET['id'] ?? 0);

if ($userId <= 0) {
    setFlash('danger', 'Invalid user ID.');
    header('Location: ' . baseUrl('users/index.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $u = $stmt->fetch();

    if (!$u) {
        setFlash('danger', 'User not found.');
        header('Location: ' . baseUrl('users/index.php'));
        exit;
    }
} catch (PDOException $e) {
    setFlash('danger', 'Database error: ' . $e->getMessage());
    header('Location: ' . baseUrl('users/index.php'));
    exit;
}

// Fetch last login/logout from activity logs
try {
    $lastLoginStmt = $pdo->prepare(
        "SELECT logged_at, ip_address FROM activity_logs WHERE user_id = :id AND action = 'login' ORDER BY logged_at DESC LIMIT 1"
    );
    $lastLoginStmt->execute(['id' => $userId]);
    $lastLogin = $lastLoginStmt->fetch();

    $lastLogoutStmt = $pdo->prepare(
        "SELECT logged_at FROM activity_logs WHERE user_id = :id AND action = 'logout' ORDER BY logged_at DESC LIMIT 1"
    );
    $lastLogoutStmt->execute(['id' => $userId]);
    $lastLogout = $lastLogoutStmt->fetch();

    $totalLoginsStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE user_id = :id AND action = 'login'");
    $totalLoginsStmt->execute(['id' => $userId]);
    $totalLogins = (int)$totalLoginsStmt->fetchColumn();
} catch (PDOException $e) {
    $lastLogin  = null;
    $lastLogout = null;
    $totalLogins = 0;
}

$isAdmin  = $u['role'] === 'admin';
$isActive = $u['status'] === 'active';
$isSelf   = $u['user_id'] == $_SESSION['user_id'];

$pageTitle = 'View User — ' . ($u['full_name'] ?? '');
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card-box">

            <!-- Header -->
            <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
                <div>
                    <h3 class="m-0" style="font-size:1.2rem; font-weight:700;">
                        User Account <span style="color:var(--primary);">#<?= $u['user_id']; ?></span>
                    </h3>
                    <p class="text-muted m-0" style="font-size:0.85rem;">Read-only profile view</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= baseUrl('users/edit.php?id=' . $u['user_id']); ?>" class="btn-primary-custom">
                        <i data-feather="edit-2"></i> Edit
                    </a>
                    <a href="<?= baseUrl('users/index.php'); ?>" class="btn-secondary-custom">
                        <i data-feather="arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <?= getFlash(); ?>

            <!-- Profile Banner -->
            <div class="uv-profile-banner mb-4">
                <div class="uv-avatar <?= $isAdmin ? 'uv-avatar-admin' : 'uv-avatar-staff'; ?>">
                    <?= strtoupper(substr($u['full_name'] ?: $u['username'], 0, 1)); ?>
                </div>
                <div class="uv-profile-info">
                    <div class="uv-profile-name">
                        <?= sanitize($u['full_name']); ?>
                        <?php if ($isSelf): ?>
                            <span class="badge bg-secondary ms-1" style="font-size:0.7rem;">You</span>
                        <?php endif; ?>
                    </div>
                    <div class="uv-profile-meta">
                        <span class="uv-role-badge <?= $isAdmin ? 'uv-role-admin' : 'uv-role-staff'; ?>">
                            <i data-feather="<?= $isAdmin ? 'shield' : 'user'; ?>" style="width:11px;height:11px;"></i>
                            <?= $isAdmin ? 'Administrator' : 'Staff'; ?>
                        </span>
                        <span class="uv-status-badge <?= $isActive ? 'uv-status-active' : 'uv-status-inactive'; ?>">
                            <i data-feather="<?= $isActive ? 'check-circle' : 'x-circle'; ?>" style="width:11px;height:11px;"></i>
                            <?= $isActive ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                </div>
                <div class="uv-login-count">
                    <div class="uv-login-num"><?= number_format($totalLogins); ?></div>
                    <div class="uv-login-lbl">Total Logins</div>
                </div>
            </div>

            <!-- Detail Grid -->
            <div class="uv-detail-grid">

                <div class="uv-detail-section">
                    <div class="uv-section-title">
                        <i data-feather="user"></i> Account Information
                    </div>
                    <div class="uv-detail-row">
                        <span class="uv-label">Full Name</span>
                        <span class="uv-value"><?= sanitize($u['full_name']); ?></span>
                    </div>
                    <div class="uv-detail-row">
                        <span class="uv-label">Username</span>
                        <span class="uv-value"><code><?= sanitize($u['username']); ?></code></span>
                    </div>
                    <div class="uv-detail-row">
                        <span class="uv-label">Email Address</span>
                        <span class="uv-value"><?= sanitize($u['email']); ?></span>
                    </div>
                </div>

                <div class="uv-detail-section">
                    <div class="uv-section-title">
                        <i data-feather="shield"></i> Access & Status
                    </div>
                    <div class="uv-detail-row">
                        <span class="uv-label">System Role</span>
                        <span class="uv-value">
                            <span class="uv-role-badge <?= $isAdmin ? 'uv-role-admin' : 'uv-role-staff'; ?>">
                                <?= $isAdmin ? 'Administrator' : 'Staff'; ?>
                            </span>
                        </span>
                    </div>
                    <div class="uv-detail-row">
                        <span class="uv-label">Account Status</span>
                        <span class="uv-value">
                            <span class="uv-status-badge <?= $isActive ? 'uv-status-active' : 'uv-status-inactive'; ?>">
                                <?= $isActive ? 'Active' : 'Inactive'; ?>
                            </span>
                        </span>
                    </div>
                    <div class="uv-detail-row">
                        <span class="uv-label">Registered On</span>
                        <span class="uv-value">
                            <?= isset($u['created_at']) ? date('M j, Y', strtotime($u['created_at'])) : '—'; ?>
                        </span>
                    </div>
                </div>

                <div class="uv-detail-section">
                    <div class="uv-section-title">
                        <i data-feather="log-in"></i> Last Login
                    </div>
                    <?php if ($lastLogin): ?>
                    <div class="uv-detail-row">
                        <span class="uv-label">Date & Time</span>
                        <span class="uv-value">
                            <div><?= date('M j, Y', strtotime($lastLogin['logged_at'])); ?></div>
                            <div style="font-size:0.78rem;color:#64748b;"><?= date('g:i:s A', strtotime($lastLogin['logged_at'])); ?></div>
                        </span>
                    </div>
                    <div class="uv-detail-row">
                        <span class="uv-label">IP Address</span>
                        <span class="uv-value">
                            <code style="background:#e0f2fe;color:#0369a1;padding:2px 6px;border-radius:4px;font-size:0.8rem;">
                                <?= sanitize($lastLogin['ip_address'] ?? '—'); ?>
                            </code>
                        </span>
                    </div>
                    <?php else: ?>
                    <div class="uv-detail-row">
                        <span class="uv-label">Last Login</span>
                        <span class="uv-value text-muted">No login recorded yet</span>
                    </div>
                    <?php endif; ?>
                    <div class="uv-detail-row">
                        <span class="uv-label">Last Logout</span>
                        <span class="uv-value">
                            <?php if ($lastLogout): ?>
                                <div><?= date('M j, Y', strtotime($lastLogout['logged_at'])); ?></div>
                                <div style="font-size:0.78rem;color:#64748b;"><?= date('g:i:s A', strtotime($lastLogout['logged_at'])); ?></div>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <div class="uv-detail-section">
                    <div class="uv-section-title">
                        <i data-feather="activity"></i> Session Activity
                    </div>
                    <div class="uv-detail-row">
                        <span class="uv-label">Total Logins</span>
                        <span class="uv-value"><strong><?= number_format($totalLogins); ?></strong></span>
                    </div>
                    <div class="uv-detail-row">
                        <span class="uv-label">User ID</span>
                        <span class="uv-value"><strong>#<?= $u['user_id']; ?></strong></span>
                    </div>
                </div>

            </div>

            <!-- Footer Actions -->
            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <?php if (!$isSelf): ?>
                <a href="<?= baseUrl('users/delete.php?id=' . $u['user_id']); ?>"
                   class="btn-action-delete btn-delete-confirm"
                   data-item="User account '<?= sanitize($u['username']); ?>'"
                   title="Delete User">
                    <i data-feather="trash-2"></i> Delete
                </a>
                <?php endif; ?>
                <a href="<?= baseUrl('users/edit.php?id=' . $u['user_id']); ?>" class="btn-primary-custom">
                    <i data-feather="edit-2"></i> Edit Account
                </a>
            </div>

        </div>
    </div>
</div>

<style>
/* ── Profile Banner ── */
.uv-profile-banner {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.2rem 1.4rem;
}
.uv-avatar {
    width: 52px; height: 52px;
    border-radius: 50%;
    font-size: 1.2rem;
    font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.uv-avatar-admin { background: #ede9fe; color: #7c3aed; border: 2px solid #ddd6fe; }
.uv-avatar-staff { background: #dbeafe; color: #1d4ed8; border: 2px solid #bfdbfe; }

.uv-profile-info   { flex: 1; min-width: 0; }
.uv-profile-name   { font-size: 1rem; font-weight: 700; color: #0f172a; }
.uv-profile-meta   { display: flex; gap: 6px; margin-top: 5px; flex-wrap: wrap; }

.uv-login-count    { text-align: center; flex-shrink: 0; }
.uv-login-num      { font-size: 1.5rem; font-weight: 800; color: var(--primary, #4f46e5); line-height: 1; }
.uv-login-lbl      { font-size: 0.72rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; margin-top: 2px; }

/* ── Role & Status Badges ── */
.uv-role-badge, .uv-status-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px;
    font-size: 0.74rem; font-weight: 700; letter-spacing: 0.02em;
}
.uv-role-admin   { background: #ede9fe; color: #7c3aed; border: 1px solid #ddd6fe; }
.uv-role-staff   { background: #dbeafe; color: #1d4ed8; border: 1px solid #bfdbfe; }
.uv-status-active   { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
.uv-status-inactive { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }

/* ── Detail Grid ── */
.uv-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
@media (max-width: 576px) { .uv-detail-grid { grid-template-columns: 1fr; } }

.uv-detail-section {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 1.1rem 1.2rem;
}
.uv-section-title {
    font-size: 0.75rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.06em;
    color: var(--primary, #4f46e5);
    margin-bottom: 0.85rem;
    display: flex; align-items: center; gap: 5px;
}
.uv-section-title svg { width: 13px; height: 13px; }

.uv-detail-row {
    display: flex; justify-content: space-between;
    align-items: flex-start; gap: 0.5rem;
    padding: 0.4rem 0;
    border-bottom: 1px dashed #e2e8f0;
}
.uv-detail-row:last-child { border-bottom: none; padding-bottom: 0; }

.uv-label { font-size: 0.8rem; color: #64748b; font-weight: 500; white-space: nowrap; flex-shrink: 0; }
.uv-value { font-size: 0.87rem; color: #1e293b; font-weight: 500; text-align: right; }

/* ── Delete btn fix ── */
.btn-action-delete {
    display: inline-flex; align-items: center; gap: 0.35rem;
    padding: 0.45rem 0.9rem; border-radius: 7px;
    font-size: 0.82rem; font-weight: 600; cursor: pointer;
    text-decoration: none; border: 1px solid #fca5a5;
    background: #fff1f2; color: #dc2626;
    transition: background 0.18s, border-color 0.18s;
}
.btn-action-delete:hover { background: #fee2e2; border-color: #f87171; color: #b91c1c; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
