<?php
// profile.php - User Profile & Change Password Module
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$user = currentUser();
$errorProfile = '';
$errorPass = '';

// Handle Profile Details Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'update_profile') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        $errorProfile = 'Invalid security token.';
    } elseif (empty($fullName) || empty($email)) {
        $errorProfile = 'Full Name and Email Address are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorProfile = 'Invalid email address.';
    } else {
        try {
            $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :e AND user_id != :id LIMIT 1");
            $checkStmt->execute(['e' => $email, 'id' => $user['user_id']]);
            if ($checkStmt->fetch()) {
                $errorProfile = 'Email address is already in use by another account.';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = :f, email = :e WHERE user_id = :id");
                $stmt->execute(['f' => $fullName, 'e' => $email, 'id' => $user['user_id']]);

                $_SESSION['full_name'] = $fullName;
                $_SESSION['email']     = $email;

                setFlash('success', 'Your profile details have been updated.');
                header('Location: ' . baseUrl('profile.php'));
                exit;
            }
        } catch (PDOException $ex) {
            $errorProfile = 'Database error: ' . $ex->getMessage();
        }
    }
}

// Handle Change Password Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'change_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $csrfToken       = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        $errorPass = 'Invalid security token.';
    } elseif (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $errorPass = 'All password fields are required.';
    } elseif ($newPassword !== $confirmPassword) {
        $errorPass = 'New passwords do not match.';
    } elseif (strlen($newPassword) < 6) {
        $errorPass = 'New password must be at least 6 characters long.';
    } else {
        try {
            $userStmt = $pdo->prepare("SELECT password FROM users WHERE user_id = :id LIMIT 1");
            $userStmt->execute(['id' => $user['user_id']]);
            $dbUser = $userStmt->fetch();

            if ($dbUser && password_verify($currentPassword, $dbUser['password'])) {
                $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $updatePassStmt = $pdo->prepare("UPDATE users SET password = :p WHERE user_id = :id");
                $updatePassStmt->execute(['p' => $newHash, 'id' => $user['user_id']]);

                setFlash('success', 'Your password has been changed successfully.');
                header('Location: ' . baseUrl('profile.php'));
                exit;
            } else {
                $errorPass = 'Current password is incorrect.';
            }
        } catch (PDOException $ex) {
            $errorPass = 'Database error: ' . $ex->getMessage();
        }
    }
}

$pageTitle = 'My Account Profile';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row">
    <!-- Profile Info Card -->
    <div class="col-lg-6 mb-4">
        <div class="card-box h-100">
            <h3 style="font-size:1.15rem; font-weight:700;" class="mb-3">Personal Details</h3>
            
            <?php if (!empty($errorProfile)): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                    <i data-feather="alert-circle" class="me-2"></i>
                    <div><?= sanitize($errorProfile); ?></div>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <?= csrfField(); ?>
                <input type="hidden" name="action_type" value="update_profile">

                <div class="mb-3">
                    <label class="form-label-custom">Username</label>
                    <input type="text" class="form-control-custom bg-light" value="<?= sanitize($user['username']); ?>" readonly>
                    <small class="text-muted">Username cannot be changed.</small>
                </div>

                <div class="mb-3">
                    <label for="full_name" class="form-label-custom">Full Name</label>
                    <input type="text" name="full_name" id="full_name" class="form-control-custom" 
                           required value="<?= sanitize($_POST['full_name'] ?? $user['full_name']); ?>">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label-custom">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control-custom" 
                           required value="<?= sanitize($_POST['email'] ?? $user['email']); ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label-custom">Account Role</label>
                    <div>
                        <span class="badge bg-primary px-3 py-2"><?= strtoupper($user['role']); ?></span>
                    </div>
                </div>

                <button type="submit" class="btn-primary-custom">
                    <i data-feather="save"></i> Update Profile Details
                </button>
            </form>
        </div>
    </div>

    <!-- Change Password Card -->
    <div class="col-lg-6 mb-4">
        <div class="card-box h-100">
            <h3 style="font-size:1.15rem; font-weight:700;" class="mb-3">Change Password</h3>

            <?php if (!empty($errorPass)): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                    <i data-feather="alert-circle" class="me-2"></i>
                    <div><?= sanitize($errorPass); ?></div>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <?= csrfField(); ?>
                <input type="hidden" name="action_type" value="change_password">

                <div class="mb-3">
                    <label for="current_password" class="form-label-custom">Current Password</label>
                    <input type="password" name="current_password" id="current_password" class="form-control-custom" 
                           required placeholder="••••••••">
                </div>

                <div class="mb-3">
                    <label for="new_password" class="form-label-custom">New Password</label>
                    <input type="password" name="new_password" id="new_password" class="form-control-custom" 
                           required placeholder="••••••••">
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label-custom">Confirm New Password</label>
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control-custom" 
                           required placeholder="••••••••">
                </div>

                <button type="submit" class="btn-primary-custom">
                    <i data-feather="lock"></i> Change Password
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
