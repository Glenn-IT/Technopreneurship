<?php
// login.php - User Login Module
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . baseUrl('dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } elseif (empty($username) || empty($password)) {
        $error = 'Please enter both your username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u AND status = 'active' LIMIT 1");
            $stmt->execute(['u' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Success: store session variables
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                // Record login activity
                try {
                    $ip = $_SERVER['HTTP_X_FORWARDED_FOR']
                        ?? $_SERVER['REMOTE_ADDR']
                        ?? 'unknown';
                    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300);
                    $logStmt = $pdo->prepare(
                        "INSERT INTO activity_logs (user_id, username, full_name, action, ip_address, user_agent)
                         VALUES (:uid, :uname, :fname, 'login', :ip, :ua)"
                    );
                    $logStmt->execute([
                        'uid'   => $user['user_id'],
                        'uname' => $user['username'],
                        'fname' => $user['full_name'],
                        'ip'    => $ip,
                        'ua'    => $ua,
                    ]);
                } catch (PDOException $logErr) { /* non-fatal */ }

                setFlash('success', 'Welcome back, ' . htmlspecialchars($user['full_name']) . '!');
                header('Location: ' . baseUrl('dashboard.php'));
                exit;
            } else {
                $error = 'Invalid credentials or inactive account.';
            }
        } catch (PDOException $e) {
            $error = 'Database error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Water Billing System for Sta. Barbara, Piat Cagayan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css'); ?>">
</head>
<body class="auth-body">

<div class="auth-card" style="max-width: 460px;">
    <div class="auth-brand">
        <div class="auth-icon">
            <i data-feather="droplet"></i>
        </div>
        <h2 class="auth-title" style="font-size:1.25rem;">Water Billing System for Sta. Barbara, Piat Cagayan</h2>
        <p class="auth-subtitle">Sign in to manage records and accounts</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
            <i data-feather="alert-circle" class="me-2"></i>
            <div><?= sanitize($error); ?></div>
        </div>
    <?php endif; ?>

    <?= getFlash(); ?>

    <form action="" method="POST">
        <?= csrfField(); ?>

        <div class="mb-3">
            <label for="username" class="form-label-custom">Username</label>
            <input type="text" name="username" id="username" class="form-control-custom" 
                   required value="<?= sanitize($_POST['username'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="password" class="form-label-custom m-0">Password</label>
                <a href="<?= baseUrl('forgot_password.php'); ?>" class="text-decoration-none font-weight-semibold" style="font-size:0.82rem; color:var(--primary);">Forgot Password?</a>
            </div>
            <div class="password-input-wrapper">
                <input type="password" name="password" id="password" class="form-control-custom" required>
                <button type="button" class="toggle-password-btn" data-target="password" title="Show/Hide Password">
                    <i data-feather="eye"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-primary-custom w-100 justify-content-center py-2 mb-3">
            <i data-feather="log-in"></i> Sign In
        </button>
    </form>

    <div class="text-center mt-3">
        <span class="text-muted" style="font-size:0.88rem;">Don't have an account?</span>
        <a href="<?= baseUrl('register.php'); ?>" class="font-weight-semibold text-decoration-none ms-1" style="color:var(--primary);">Register Now</a>
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
