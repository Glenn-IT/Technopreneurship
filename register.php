<?php
// register.php - User Registration Module
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . baseUrl('dashboard.php'));
    exit;
}

$error = '';
$questions = getSecurityQuestions();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username        = trim($_POST['username'] ?? '');
    $fullName        = trim($_POST['full_name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $securityQuestion = trim($_POST['security_question'] ?? '');
    $securityAnswer   = trim($_POST['security_answer'] ?? '');
    $csrfToken       = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (empty($username) || empty($fullName) || empty($email) || empty($password)) {
        $error = 'All required fields must be filled out.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (empty($securityQuestion) || empty($securityAnswer)) {
        $error = 'Please select a security question from the combo box and enter your answer.';
    } else {
        try {
            // Check if username or email already exists
            $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :u OR email = :e LIMIT 1");
            $checkStmt->execute(['u' => $username, 'e' => $email]);
            if ($checkStmt->fetch()) {
                $error = 'Username or Email is already registered.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $insertStmt = $pdo->prepare("INSERT INTO users (username, full_name, email, password, role, status, security_question, security_answer) VALUES (:u, :f, :e, :p, 'staff', 'active', :q, :a)");
                $insertStmt->execute([
                    'u' => $username,
                    'f' => $fullName,
                    'e' => $email,
                    'p' => $hashedPassword,
                    'q' => $securityQuestion,
                    'a' => $securityAnswer
                ]);

                setFlash('success', 'Registration successful! You can now log in with your credentials.');
                header('Location: ' . baseUrl('login.php'));
                exit;
            }
        } catch (PDOException $ex) {
            $error = 'Database error: ' . $ex->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register User - Water Billing System for Sta. Barbara, Piat Cagayan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css'); ?>">
</head>
<body class="auth-body py-5">

<div class="auth-card" style="max-width: 540px;">
    <div class="auth-brand">
        <div class="auth-icon">
            <i data-feather="user-plus"></i>
        </div>
        <h2 class="auth-title">Create Account</h2>
        <p class="auth-subtitle">Register for system access</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
            <i data-feather="alert-circle" class="me-2 flex-shrink-0"></i>
            <div><?= sanitize($error); ?></div>
        </div>
    <?php endif; ?>

    <form action="" method="POST">
        <?= csrfField(); ?>

        <div class="mb-3">
            <label for="username" class="form-label-custom">Username <span class="text-danger">*</span></label>
            <input type="text" name="username" id="username" class="form-control-custom" 
                   required value="<?= sanitize($_POST['username'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label for="full_name" class="form-label-custom">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="full_name" id="full_name" class="form-control-custom" 
                   required value="<?= sanitize($_POST['full_name'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label for="email" class="form-label-custom">Email Address <span class="text-danger">*</span></label>
            <input type="email" name="email" id="email" class="form-control-custom" 
                   required value="<?= sanitize($_POST['email'] ?? ''); ?>">
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="password" class="form-label-custom">Password <span class="text-danger">*</span></label>
                <div class="password-input-wrapper">
                    <input type="password" name="password" id="password" class="form-control-custom" required>
                    <button type="button" class="toggle-password-btn" data-target="password" title="Show/Hide Password">
                        <i data-feather="eye"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <label for="confirm_password" class="form-label-custom">Confirm Password <span class="text-danger">*</span></label>
                <div class="password-input-wrapper">
                    <input type="password" name="confirm_password" id="confirm_password" class="form-control-custom" required>
                    <button type="button" class="toggle-password-btn" data-target="confirm_password" title="Show/Hide Password">
                        <i data-feather="eye"></i>
                    </button>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <h5 class="fw-bold mb-3" style="font-size:0.98rem; color:var(--primary);">Security Question Setup (For Password Recovery)</h5>

        <div class="mb-3">
            <label for="security_question" class="form-label-custom">Select Security Question (Combo Box) <span class="text-danger">*</span></label>
            <select name="security_question" id="security_question" class="form-select-custom" required>
                <option value="">-- Choose a Security Question --</option>
                <?php foreach ($questions as $id => $qText): ?>
                    <option value="<?= sanitize($qText); ?>" <?= (($_POST['security_question'] ?? '') === $qText) ? 'selected' : ''; ?>>
                        <?= sanitize($qText); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-4">
            <label for="security_answer" class="form-label-custom">Security Answer <span class="text-danger">*</span></label>
            <input type="text" name="security_answer" id="security_answer" class="form-control-custom" required value="<?= sanitize($_POST['security_answer'] ?? ''); ?>">
        </div>

        <button type="submit" class="btn-primary-custom w-100 justify-content-center py-2 mb-3">
            <i data-feather="check-circle"></i> Register Account
        </button>
    </form>

    <div class="text-center mt-3">
        <span class="text-muted" style="font-size:0.88rem;">Already registered?</span>
        <a href="<?= baseUrl('login.php'); ?>" class="font-weight-semibold text-decoration-none ms-1" style="color:var(--primary);">Sign In</a>
    </div>
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
