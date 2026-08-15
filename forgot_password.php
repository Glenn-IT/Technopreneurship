<?php
// forgot_password.php - Forgot Password with Security Question Combo Box & Answer Verification
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . baseUrl('dashboard.php'));
    exit;
}

$error = '';
$step = 1;
$questions = getSecurityQuestions();

$username         = trim($_POST['username'] ?? '');
$securityQuestion = trim($_POST['security_question'] ?? '');
$securityAnswer   = trim($_POST['security_answer'] ?? '');
$verifiedUserId   = (int)($_POST['verified_user_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Invalid security token. Please try again.';
    } elseif ($action === 'verify_security_question') {
        if (empty($username)) {
            $error = 'Please enter your username.';
        } elseif (empty($securityQuestion)) {
            $error = 'Please select a security question from the combo box.';
        } elseif (empty($securityAnswer)) {
            $error = 'Please enter your answer to the security question.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u AND status = 'active' LIMIT 1");
                $stmt->execute(['u' => $username]);
                $userRecord = $stmt->fetch();

                if (!$userRecord) {
                    $error = 'Username not found or account is inactive.';
                } else {
                    $dbQuestion = trim($userRecord['security_question'] ?? '');
                    $dbAnswer   = strtolower(trim($userRecord['security_answer'] ?? ''));
                    $inputAns   = strtolower($securityAnswer);

                    // Check match
                    $hasRecordedQuestion = !empty($dbQuestion) && !empty($dbAnswer);

                    if ($hasRecordedQuestion) {
                        if ($dbQuestion !== $securityQuestion || $dbAnswer !== $inputAns) {
                            $error = 'Incorrect security question or answer. Please check your credentials.';
                        } else {
                            $step = 2;
                            $verifiedUserId = $userRecord['user_id'];
                        }
                    } else {
                        // User has not configured security question yet - save this question & answer and allow password reset
                        $saveSqStmt = $pdo->prepare("UPDATE users SET security_question = :q, security_answer = :a WHERE user_id = :id");
                        $saveSqStmt->execute(['q' => $securityQuestion, 'a' => $securityAnswer, 'id' => $userRecord['user_id']]);
                        $step = 2;
                        $verifiedUserId = $userRecord['user_id'];
                    }
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'reset_password') {
        $verifiedUserId = (int)($_POST['verified_user_id'] ?? 0);
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($verifiedUserId <= 0) {
            $error = 'Verification session expired. Please verify your security question again.';
            $step = 1;
        } elseif (empty($newPassword) || empty($confirmPassword)) {
            $step = 2;
            $error = 'Please enter your new password and confirmation.';
        } elseif ($newPassword !== $confirmPassword) {
            $step = 2;
            $error = 'Passwords do not match.';
        } elseif (strlen($newPassword) < 6) {
            $step = 2;
            $error = 'Password must be at least 6 characters long.';
        } else {
            try {
                $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
                $updatePassStmt = $pdo->prepare("UPDATE users SET password = :p WHERE user_id = :id");
                $updatePassStmt->execute(['p' => $newHash, 'id' => $verifiedUserId]);

                setFlash('success', 'Your password has been reset successfully! You can now log in with your new password.');
                header('Location: ' . baseUrl('login.php'));
                exit;
            } catch (PDOException $ex) {
                $step = 2;
                $error = 'Database error: ' . $ex->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Water Billing System for Sta. Barbara, Piat Cagayan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css'); ?>">
</head>
<body class="auth-body py-5">

<div class="auth-card" style="max-width: 520px;">
    <div class="auth-brand mb-4">
        <div class="auth-icon">
            <i data-feather="key"></i>
        </div>
        <h2 class="auth-title">Forgot Password</h2>
        <p class="auth-subtitle">Verify your security question to reset your password</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
            <i data-feather="alert-circle" class="me-2 flex-shrink-0"></i>
            <div><?= sanitize($error); ?></div>
        </div>
    <?php endif; ?>

    <?php if ($step === 1): ?>
        <!-- STEP 1: Combo Box Security Question & Answer -->
        <form action="" method="POST">
            <?= csrfField(); ?>
            <input type="hidden" name="action" value="verify_security_question">

            <div class="mb-3">
                <label for="username" class="form-label-custom">Username <span class="text-danger">*</span></label>
                <input type="text" name="username" id="username" class="form-control-custom" 
                       required value="<?= sanitize($username); ?>" autofocus>
            </div>

            <div class="mb-3">
                <label for="security_question" class="form-label-custom">Select Security Question (Combo Box) <span class="text-danger">*</span></label>
                <select name="security_question" id="security_question" class="form-select-custom" required>
                    <option value="">-- Choose a Security Question --</option>
                    <?php foreach ($questions as $id => $qText): ?>
                        <option value="<?= sanitize($qText); ?>" <?= ($securityQuestion === $qText) ? 'selected' : ''; ?>>
                            <?= sanitize($qText); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label for="security_answer" class="form-label-custom">Security Answer <span class="text-danger">*</span></label>
                <input type="text" name="security_answer" id="security_answer" class="form-control-custom" 
                       required value="<?= sanitize($securityAnswer); ?>">
            </div>

            <button type="submit" class="btn-primary-custom w-100 justify-content-center py-2 mb-3">
                <i data-feather="check-circle"></i> Verify Answer
            </button>

            <div class="text-center">
                <a href="<?= baseUrl('login.php'); ?>" class="text-decoration-none text-muted font-weight-semibold" style="font-size:0.88rem;">
                    &larr; Back to Sign In
                </a>
            </div>
        </form>

    <?php else: ?>
        <!-- STEP 2: Show New Password and Confirm Password after correct answer -->
        <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
            <i data-feather="check-circle" class="me-2 flex-shrink-0"></i>
            <div>Identity verified! Please enter your new password below.</div>
        </div>

        <form action="" method="POST">
            <?= csrfField(); ?>
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="verified_user_id" value="<?= $verifiedUserId; ?>">

            <div class="mb-3">
                <label for="new_password" class="form-label-custom">New Password <span class="text-danger">*</span></label>
                <div class="password-input-wrapper">
                    <input type="password" name="new_password" id="new_password" class="form-control-custom" required autofocus>
                    <button type="button" class="toggle-password-btn" data-target="new_password" title="Show/Hide Password">
                        <i data-feather="eye"></i>
                    </button>
                </div>
            </div>

            <div class="mb-4">
                <label for="confirm_password" class="form-label-custom">Confirm Password <span class="text-danger">*</span></label>
                <div class="password-input-wrapper">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control-custom" required>
                    <button type="button" class="toggle-password-btn" data-target="confirm_password" title="Show/Hide Password">
                        <i data-feather="eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-primary-custom w-100 justify-content-center py-2 mb-3">
                <i data-feather="save"></i> Update Password
            </button>

            <div class="text-center">
                <a href="<?= baseUrl('login.php'); ?>" class="text-decoration-none text-muted font-weight-semibold" style="font-size:0.88rem;">
                    Cancel & Return to Sign In
                </a>
            </div>
        </form>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= baseUrl('assets/js/main.js'); ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof feather !== 'undefined') feather.replace();
    });
</script>
</body>
</html>
