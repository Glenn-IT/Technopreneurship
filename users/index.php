<?php
// users/index.php - User Management Module (Admin Only)
$pageTitle = 'Manage System Users';
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

try {
    $stmt = $pdo->query("SELECT user_id, username, full_name, email, role, status, created_at FROM users ORDER BY user_id ASC");
    $usersList = $stmt->fetchAll();
} catch (PDOException $e) {
    $usersList = [];
}
?>

<div class="card-box">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h3 class="m-0" style="font-size:1.2rem; font-weight:700;">System Users</h3>
            <p class="text-muted m-0" style="font-size:0.85rem;">Manage user accounts, administrative privileges, and system access</p>
        </div>
        <a href="<?= baseUrl('users/add.php'); ?>" class="btn-primary-custom">
            <i data-feather="user-plus"></i> Register New User
        </a>
    </div>

    <div class="table-responsive-wrapper">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Email Address</th>
                    <th>Role</th>
                    <th>Account Status</th>
                    <th>Registered Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($usersList)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No users found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($usersList as $u): ?>
                        <tr>
                            <td><strong>#<?= $u['user_id']; ?></strong></td>
                            <td>
                                <strong><?= sanitize($u['full_name']); ?></strong>
                                <?php if ($u['user_id'] == $_SESSION['user_id']): ?>
                                    <span class="badge bg-secondary ms-1">You</span>
                                <?php endif; ?>
                            </td>
                            <td><code><?= sanitize($u['username']); ?></code></td>
                            <td><?= sanitize($u['email']); ?></td>
                            <td>
                                <span class="badge <?= ($u['role'] === 'admin') ? 'bg-primary' : 'bg-info'; ?>">
                                    <?= strtoupper($u['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= ($u['status'] === 'active') ? 'bg-success' : 'bg-danger'; ?>">
                                    <?= ucfirst($u['status']); ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y', strtotime($u['created_at'])); ?></td>
                            <td class="text-end">
                                <div class="table-action-btns">
                                    <a href="<?= baseUrl('users/view.php?id=' . $u['user_id']); ?>" class="btn-action-view" title="View User">
                                        <i data-feather="eye"></i> View
                                    </a>
                                    <a href="<?= baseUrl('users/edit.php?id=' . $u['user_id']); ?>" class="btn-action-edit" title="Edit User">
                                        <i data-feather="edit-2"></i> Edit
                                    </a>
                                    <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                                        <a href="<?= baseUrl('users/delete.php?id=' . $u['user_id']); ?>" 
                                           class="btn-action-delete btn-delete-confirm" 
                                           data-item="User account '<?= sanitize($u['username']); ?>'" title="Delete User">
                                            <i data-feather="trash-2"></i> Delete
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
