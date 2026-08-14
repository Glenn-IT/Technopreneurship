<?php
// users/edit.php - Edit User Module (Admin Only)
$pageTitle = 'Edit User Account';
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$userId = (int)($_GET['id'] ?? 0);
$error = '';

if ($userId <= 0) {
    setFlash('danger', 'Invalid user ID.');
    header('Location: ' . baseUrl('users/index.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :id LIMIT 1");
    $stmt->execute(['id' => $userId]);
    $userRecord = $stmt->fetch();

    if (!$userRecord) {
        setFlash('danger', 'User not found.');
        header('Location: ' . baseUrl('users/index.php'));
        exit;
    }
} catch (PDOException $e) {
    setFlash('danger', 'Database error: ' . $e->getMessage());
    header('Location: ' . baseUrl('users/index.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = trim($_POST['role'] ?? 'staff');
    $status   = trim($_POST['status'] ?? 'active');
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (empty($username) || empty($fullName) || empty($email)) {
        $error = 'Username, Full Name, and Email are required.';
    } else {
        try {
            // Check unique constraints except for current user
            $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE (username = :u OR email = :e) AND user_id != :id LIMIT 1");
            $checkStmt->execute(['u' => $username, 'e' => $email, 'id' => $userId]);
            if ($checkStmt->fetch()) {
                $error = 'Username or Email is already used by another account.';
            } else {
                if (!empty($password)) {
                    $hashed = password_hash($password, PASSWORD_BCRYPT);
                    $updateStmt = $pdo->prepare("UPDATE users SET username = :u, full_name = :f, email = :e, password = :p, role = :r, status = :s WHERE user_id = :id");
                    $updateStmt->execute([
                        'u' => $username, 'f' => $fullName, 'e' => $email, 'p' => $hashed, 'r' => $role, 's' => $status, 'id' => $userId
                    ]);
                } else {
                    $updateStmt = $pdo->prepare("UPDATE users SET username = :u, full_name = :f, email = :e, role = :r, status = :s WHERE user_id = :id");
                    $updateStmt->execute([
                        'u' => $username, 'f' => $fullName, 'e' => $email, 'r' => $role, 's' => $status, 'id' => $userId
                    ]);
                }

                setFlash('success', 'User account ' . htmlspecialchars($username) . ' updated successfully.');
                header('Location: ' . baseUrl('users/index.php'));
                exit;
            }
        } catch (PDOException $ex) {
            $error = 'Database error: ' . $ex->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card-box">
            <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
                <div>
                    <h3 class="m-0" style="font-size:1.2rem; font-weight:700;">Edit User Account #<?= $userRecord['user_id']; ?></h3>
                    <p class="text-muted m-0" style="font-size:0.85rem;">Modify account details and access roles</p>
                </div>
                <a href="<?= baseUrl('users/index.php'); ?>" class="btn-secondary-custom">
                    <i data-feather="arrow-left"></i> Back to Users
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                    <i data-feather="alert-circle" class="me-2"></i>
                    <div><?= sanitize($error); ?></div>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <?= csrfField(); ?>

                <div class="mb-3">
                    <label for="username" class="form-label-custom">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" id="username" class="form-control-custom" required 
                           value="<?= sanitize($_POST['username'] ?? $userRecord['username']); ?>">
                </div>

                <div class="mb-3">
                    <label for="full_name" class="form-label-custom">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" id="full_name" class="form-control-custom" required 
                           value="<?= sanitize($_POST['full_name'] ?? $userRecord['full_name']); ?>">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label-custom">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" id="email" class="form-control-custom" required 
                           value="<?= sanitize($_POST['email'] ?? $userRecord['email']); ?>">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label-custom">New Password (leave blank to keep current)</label>
                    <input type="password" name="password" id="password" class="form-control-custom" placeholder="••••••••">
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="role" class="form-label-custom">System Role <span class="text-danger">*</span></label>
                        <select name="role" id="role" class="form-select-custom" required>
                            <option value="staff" <?= (($_POST['role'] ?? $userRecord['role']) === 'staff') ? 'selected' : ''; ?>>Staff</option>
                            <option value="admin" <?= (($_POST['role'] ?? $userRecord['role']) === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="status" class="form-label-custom">Account Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select-custom" required>
                            <option value="active" <?= (($_POST['status'] ?? $userRecord['status']) === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?= (($_POST['status'] ?? $userRecord['status']) === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="<?= baseUrl('users/index.php'); ?>" class="btn-secondary-custom">Cancel</a>
                    <button type="submit" class="btn-primary-custom">
                        <i data-feather="save"></i> Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
