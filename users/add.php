<?php
// users/add.php - Register User (Admin Side)
$pageTitle = 'Register New System User';
require_once __DIR__ . '/../includes/header.php';
requireAdmin();

$error = '';

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
    } elseif (empty($username) || empty($fullName) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        try {
            $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :u OR email = :e LIMIT 1");
            $checkStmt->execute(['u' => $username, 'e' => $email]);
            if ($checkStmt->fetch()) {
                $error = 'Username or Email is already taken.';
            } else {
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, full_name, email, password, role, status) VALUES (:u, :f, :e, :p, :r, :s)");
                $stmt->execute([
                    'u' => $username,
                    'f' => $fullName,
                    'e' => $email,
                    'p' => $hashed,
                    'r' => $role,
                    's' => $status
                ]);

                setFlash('success', 'User account ' . htmlspecialchars($username) . ' created successfully.');
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
                    <h3 class="m-0" style="font-size:1.2rem; font-weight:700;">Register User Account</h3>
                    <p class="text-muted m-0" style="font-size:0.85rem;">Create a new staff or admin user for the system</p>
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
                    <input type="text" name="username" id="username" class="form-control-custom" required value="<?= sanitize($_POST['username'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label for="full_name" class="form-label-custom">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" id="full_name" class="form-control-custom" required value="<?= sanitize($_POST['full_name'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label-custom">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" id="email" class="form-control-custom" required value="<?= sanitize($_POST['email'] ?? ''); ?>">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label-custom">Initial Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" id="password" class="form-control-custom" required placeholder="••••••••">
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="role" class="form-label-custom">System Role <span class="text-danger">*</span></label>
                        <select name="role" id="role" class="form-select-custom" required>
                            <option value="staff" <?= (($_POST['role'] ?? 'staff') === 'staff') ? 'selected' : ''; ?>>Staff</option>
                            <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Administrator</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="status" class="form-label-custom">Account Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select-custom" required>
                            <option value="active" <?= (($_POST['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?= (($_POST['status'] ?? '') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="<?= baseUrl('users/index.php'); ?>" class="btn-secondary-custom">Cancel</a>
                    <button type="submit" class="btn-primary-custom">
                        <i data-feather="check-circle"></i> Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
