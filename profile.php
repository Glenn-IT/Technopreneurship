<?php
// profile.php - User Profile & Change Password & Security Questions Module
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$user = currentUser();
$errorProfile = '';
$errorPass = '';
$errorSQ = '';
$questions = getSecurityQuestions();

// Fetch fresh user data from DB including security answers
try {
    $freshUserStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :id LIMIT 1");
    $freshUserStmt->execute(['id' => $user['user_id']]);
    $userRecord = $freshUserStmt->fetch();
} catch (PDOException $e) {
    $userRecord = $user;
}

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

// Handle Security Question & Answer Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type']) && $_POST['action_type'] === 'update_sq') {
    $secQuestion = trim($_POST['security_question'] ?? '');
    $secAnswer   = trim($_POST['security_answer'] ?? '');
    $csrfToken   = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        $errorSQ = 'Invalid security token.';
    } elseif (empty($secQuestion) || empty($secAnswer)) {
        $errorSQ = 'Please select a security question from the combo box and enter your answer.';
    } else {
        try {
            $updateSqStmt = $pdo->prepare("UPDATE users SET 
                security_question = :q, security_answer = :a 
                WHERE user_id = :id");
            $updateSqStmt->execute([
                'q'  => $secQuestion,
                'a'  => $secAnswer,
                'id' => $user['user_id']
            ]);

            setFlash('success', 'Your security question and answer have been updated successfully.');
            header('Location: ' . baseUrl('profile.php'));
            exit;
        } catch (PDOException $ex) {
            $errorSQ = 'Database error: ' . $ex->getMessage();
        }
    }
}

$pageTitle = 'My Account Profile';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row">
    <!-- Personal Details Card -->
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
                           required value="<?= sanitize($_POST['full_name'] ?? $userRecord['full_name']); ?>">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label-custom">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control-custom" 
                           required value="<?= sanitize($_POST['email'] ?? $userRecord['email']); ?>">
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
                    <div class="password-input-wrapper">
                        <input type="password" name="current_password" id="current_password" class="form-control-custom" required>
                        <button type="button" class="toggle-password-btn" data-target="current_password" title="Show/Hide Password">
                            <i data-feather="eye"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="new_password" class="form-label-custom">New Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" name="new_password" id="new_password" class="form-control-custom" required>
                        <button type="button" class="toggle-password-btn" data-target="new_password" title="Show/Hide Password">
                            <i data-feather="eye"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label-custom">Confirm New Password</label>
                    <div class="password-input-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control-custom" required>
                        <button type="button" class="toggle-password-btn" data-target="confirm_password" title="Show/Hide Password">
                            <i data-feather="eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary-custom">
                    <i data-feather="lock"></i> Change Password
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Security Question Card (Combo Box) -->
<div class="row">
    <div class="col-12 mb-4">
        <div class="card-box">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                <div>
                    <h3 style="font-size:1.15rem; font-weight:700;" class="m-0">Security Question Setup</h3>
                    <p class="text-muted m-0" style="font-size:0.85rem;">Select your security question from the combo box and enter your answer for password recovery.</p>
                </div>
                <span class="badge <?= (!empty($userRecord['security_answer'])) ? 'bg-success' : 'bg-warning text-dark' ?>">
                    <?= (!empty($userRecord['security_answer'])) ? 'Configured' : 'Setup Required' ?>
                </span>
            </div>

            <?php if (!empty($errorSQ)): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                    <i data-feather="alert-circle" class="me-2"></i>
                    <div><?= sanitize($errorSQ); ?></div>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <?= csrfField(); ?>
                <input type="hidden" name="action_type" value="update_sq">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="security_question" class="form-label-custom">Select Security Question (Combo Box) <span class="text-danger">*</span></label>
                        <select name="security_question" id="security_question" class="form-select-custom" required>
                            <option value="">-- Choose a Security Question --</option>
                            <?php foreach ($questions as $id => $qText): ?>
                                <option value="<?= sanitize($qText); ?>" <?= (($_POST['security_question'] ?? $userRecord['security_question'] ?? '') === $qText) ? 'selected' : ''; ?>>
                                    <?= sanitize($qText); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="security_answer" class="form-label-custom">Security Answer <span class="text-danger">*</span></label>
                        <input type="text" name="security_answer" id="security_answer" class="form-control-custom" required 
                               value="<?= sanitize($_POST['security_answer'] ?? $userRecord['security_answer'] ?? ''); ?>">
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                    <button type="submit" class="btn-primary-custom">
                        <i data-feather="shield"></i> Save Security Question
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

