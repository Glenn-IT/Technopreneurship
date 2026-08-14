<?php
// register.php - User Registration Module
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . baseUrl('dashboard.php'));
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username        = trim($_POST['username'] ?? '');
    $fullName        = trim($_POST['full_name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
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
    } else {
        try {
            // Check if username or email already exists
            $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :u OR email = :e LIMIT 1");
            $checkStmt->execute(['u' => $username, 'e' => $email]);
            if ($checkStmt->fetch()) {
                $error = 'Username or Email is already registered.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $insertStmt = $pdo->prepare("INSERT INTO users (username, full_name, email, password, role, status) VALUES (:u, :f, :e, :p, 'staff', 'active')");
                $insertStmt->execute([
                    'u' => $username,
                    'f' => $fullName,
                    'e' => $email,
                    'p' => $hashedPassword
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
    <title>Register User - Ramos Water Billing System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css'); ?>">
</head>
<body class="auth-body">

<div class="auth-card" style="max-width: 480px;">
    <div class="auth-brand">
        <div class="auth-icon">
            <i data-feather="user-plus"></i>
        </div>
        <h2 class="auth-title">Create Account</h2>
        <p class="auth-subtitle">Register for system access</p>
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
            <label for="username" class="form-label-custom">Username</label>
            <input type="text" name="username" id="username" class="form-control-custom" 
                   placeholder="e.g. jsmith" required value="<?= sanitize($_POST['username'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label for="full_name" class="form-label-custom">Full Name</label>
            <input type="text" name="full_name" id="full_name" class="form-control-custom" 
                   placeholder="e.g. John Smith" required value="<?= sanitize($_POST['full_name'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label for="email" class="form-label-custom">Email Address</label>
            <input type="email" name="email" id="email" class="form-control-custom" 
                   placeholder="e.g. jsmith@ramoswater.com" required value="<?= sanitize($_POST['email'] ?? ''); ?>">
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <label for="password" class="form-label-custom">Password</label>
                <input type="password" name="password" id="password" class="form-control-custom" placeholder="••••••••" required>
            </div>
            <div class="col-md-6 mb-4">
                <label for="confirm_password" class="form-label-custom">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control-custom" placeholder="••••••••" required>
            </div>
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
