<?php
// activity_logs.php - Activity Logs Module
$pageTitle = 'Activity Logs';
require_once __DIR__ . '/includes/header.php';

// Only admin can access
if (($user['role'] ?? '') !== 'admin') {
    setFlash('danger', 'Access denied. Administrator privileges required.');
    header('Location: ' . baseUrl('dashboard.php'));
    exit;
}

// Fetch paginated log records
try {
    $perPage     = 25;
    $currentPage = max(1, (int)($_GET['page'] ?? 1));
    $offset      = ($currentPage - 1) * $perPage;

    $totalRecords = (int)$pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
    $totalPages   = max(1, (int)ceil($totalRecords / $perPage));

    $logsStmt = $pdo->prepare(
        "SELECT * FROM activity_logs ORDER BY logged_at DESC LIMIT {$perPage} OFFSET {$offset}"
    );
    $logsStmt->execute();
    $logs = $logsStmt->fetchAll();

} catch (PDOException $e) {
    $logs = [];
    $totalRecords = 0;
    $totalPages   = 1;
}

function pagUrl($p) {
    return 'activity_logs.php?page=' . $p;
}
?>

<!-- Page Header -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h3 class="m-0" style="font-size:1.35rem; font-weight:800; color:var(--text-main);">Activity Logs</h3>
        <p class="text-muted m-0" style="font-size:0.88rem;">System-wide login and logout history of all users</p>
    </div>
    <span class="text-muted" style="font-size:0.82rem;">
        <i data-feather="clock" style="width:13px;height:13px;vertical-align:-2px;"></i>
        <?= date('F j, Y — g:i A'); ?>
    </span>
</div>

<!-- Session History Table -->
<div class="card-box">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 style="font-size:1.05rem; font-weight:700; margin:0;">Session History</h4>
            <p class="text-muted m-0" style="font-size:0.82rem;">
                Showing <?= number_format(count($logs)); ?> of <?= number_format($totalRecords); ?> records
            </p>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="text-muted" style="font-size:0.82rem;">
            Page <?= $currentPage; ?> of <?= $totalPages; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="table-responsive-wrapper">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>#</th>
                    <th>User</th>
                    <th>Username</th>
                    <th>Action</th>
                    <th>IP Address</th>
                    <th>Browser / Device</th>
                    <th>Date &amp; Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i data-feather="inbox" style="width:36px;height:36px;opacity:0.4;" class="mb-2"></i><br>
                            No activity logs yet. Logs will appear after users log in or out.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <?php
                        $isLogin = $log['action'] === 'login';
                        $ua      = $log['user_agent'] ?? '';

                        $browser = 'Unknown';
                        if (str_contains($ua, 'Edg'))                                      $browser = 'Edge';
                        elseif (str_contains($ua, 'Chrome') && !str_contains($ua, 'Edg')) $browser = 'Chrome';
                        elseif (str_contains($ua, 'Firefox'))                              $browser = 'Firefox';
                        elseif (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome')) $browser = 'Safari';
                        elseif (str_contains($ua, 'OPR') || str_contains($ua, 'Opera'))   $browser = 'Opera';

                        $device = 'Desktop';
                        if (preg_match('/Mobile|Android|iPhone|iPad/i', $ua)) $device = 'Mobile';
                        ?>
                        <tr>
                            <td><code style="font-size:0.78rem;">#<?= str_pad($log['log_id'], 4, '0', STR_PAD_LEFT); ?></code></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="log-avatar <?= $isLogin ? 'log-avatar-green' : 'log-avatar-red'; ?>">
                                        <?= strtoupper(substr($log['full_name'] ?: $log['username'], 0, 1)); ?>
                                    </div>
                                    <strong style="font-size:0.88rem;"><?= sanitize($log['full_name']); ?></strong>
                                </div>
                            </td>
                            <td><code style="font-size:0.82rem;"><?= sanitize($log['username']); ?></code></td>
                            <td>
                                <?php if ($isLogin): ?>
                                    <span class="log-badge log-badge-login">
                                        <i data-feather="log-in" style="width:11px;height:11px;"></i> Login
                                    </span>
                                <?php else: ?>
                                    <span class="log-badge log-badge-logout">
                                        <i data-feather="log-out" style="width:11px;height:11px;"></i> Logout
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code style="font-size:0.8rem;color:#0369a1;background:#e0f2fe;padding:2px 6px;border-radius:4px;">
                                    <?= sanitize($log['ip_address'] ?? '—'); ?>
                                </code>
                            </td>
                            <td>
                                <span class="log-device-chip">
                                    <i data-feather="<?= $device === 'Mobile' ? 'smartphone' : 'monitor'; ?>" style="width:11px;height:11px;"></i>
                                    <?= $browser; ?> &middot; <?= $device; ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-size:0.85rem;font-weight:600;color:var(--text-main);">
                                    <?= date('M j, Y', strtotime($log['logged_at'])); ?>
                                </div>
                                <div style="font-size:0.78rem;color:#64748b;">
                                    <?= date('g:i:s A', strtotime($log['logged_at'])); ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="d-flex justify-content-center align-items-center gap-1 mt-4">
        <?php if ($currentPage > 1): ?>
            <a href="<?= pagUrl(1); ?>" class="log-page-btn" title="First">&laquo;</a>
            <a href="<?= pagUrl($currentPage - 1); ?>" class="log-page-btn">&lsaquo; Prev</a>
        <?php endif; ?>

        <?php
        $startPage = max(1, $currentPage - 2);
        $endPage   = min($totalPages, $currentPage + 2);
        for ($p = $startPage; $p <= $endPage; $p++): ?>
            <a href="<?= pagUrl($p); ?>" class="log-page-btn <?= ($p === $currentPage) ? 'log-page-active' : ''; ?>">
                <?= $p; ?>
            </a>
        <?php endfor; ?>

        <?php if ($currentPage < $totalPages): ?>
            <a href="<?= pagUrl($currentPage + 1); ?>" class="log-page-btn">Next &rsaquo;</a>
            <a href="<?= pagUrl($totalPages); ?>" class="log-page-btn" title="Last">&raquo;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
.log-avatar {
    width: 30px; height: 30px;
    border-radius: 50%;
    font-size: 0.75rem;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.log-avatar-green { background: #dcfce7; color: #16a34a; }
.log-avatar-red   { background: #fee2e2; color: #dc2626; }

.log-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}
.log-badge-login  { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
.log-badge-logout { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }

.log-device-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.78rem;
    color: #64748b;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 2px 8px;
}

.log-page-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 34px;
    height: 34px;
    padding: 0 8px;
    border-radius: 7px;
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none;
    color: #475569;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    transition: all 0.15s ease;
}
.log-page-btn:hover  { background: var(--primary, #4f46e5); color: #fff; border-color: var(--primary, #4f46e5); }
.log-page-active     { background: var(--primary, #4f46e5); color: #fff !important; border-color: var(--primary, #4f46e5); }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
