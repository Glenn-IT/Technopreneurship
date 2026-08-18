<?php
// register.php - User Registration Module (Staff + Admin tabs)
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . baseUrl('dashboard.php'));
    exit;
}

$error     = '';
$activeTab = $_POST['active_tab'] ?? $_GET['tab'] ?? 'staff';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $activeTab = $_POST['active_tab'] ?? 'staff';

    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $username        = trim($_POST['username'] ?? '');
        $fullName        = trim($_POST['full_name'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $password        = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Role is determined solely by which tab was submitted
        $role = ($activeTab === 'admin') ? 'admin' : 'staff';

        if (empty($username) || empty($fullName) || empty($email) || empty($password)) {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please provide a valid email address.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            try {
                $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :u OR email = :e LIMIT 1");
                $checkStmt->execute(['u' => $username, 'e' => $email]);
                if ($checkStmt->fetch()) {
                    $error = 'Username or Email is already registered.';
                } else {
                    $hashed = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, full_name, email, password, role, status)
                                          VALUES (:u, :f, :e, :p, :r, 'active')");
                    $stmt->execute(['u' => $username, 'f' => $fullName, 'e' => $email,
                                    'p' => $hashed, 'r' => $role]);

                    setFlash('success', 'Account created successfully! You can now sign in.');
                    header('Location: ' . baseUrl('login.php'));
                    exit;
                }
            } catch (PDOException $ex) {
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
    <title>Create Account - Water Billing System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
    <script src="https://unpkg.com/feather-icons"></script>
    <link rel="stylesheet" href="<?= baseUrl('assets/css/style.css'); ?>">
</head>
<body class="auth-body py-5">

<div class="auth-card" style="max-width:520px;">

    <!-- Brand -->
    <div class="auth-brand">
        <div class="auth-icon">
            <i data-feather="user-plus"></i>
        </div>
        <h2 class="auth-title">Create Account</h2>
        <p class="auth-subtitle">Register for system access</p>
    </div>

    <!-- Tab Switcher -->
    <div class="reg-tabs mb-4">
        <button type="button" class="reg-tab-btn <?= ($activeTab === 'staff') ? 'active' : ''; ?>" data-tab="staff">
            <i data-feather="user" style="width:14px;height:14px;"></i> Staff
        </button>
        <button type="button" class="reg-tab-btn <?= ($activeTab === 'admin') ? 'active' : ''; ?>" data-tab="admin">
            <i data-feather="shield" style="width:14px;height:14px;"></i> Admin
        </button>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
            <i data-feather="alert-circle" class="me-2 flex-shrink-0"></i>
            <div><?= sanitize($error); ?></div>
        </div>
    <?php endif; ?>

    <!-- ── TAB: Staff ─────────────────────────────────────────────────────── -->
    <div id="tabPanelStaff" class="reg-tab-panel <?= ($activeTab === 'staff') ? 'active' : ''; ?>">
        <div class="role-badge role-badge-staff mb-4">
            <i data-feather="user" style="width:13px;height:13px;"></i>
            Registering as <strong>Staff</strong>
        </div>

        <form action="" method="POST">
            <?= csrfField(); ?>
            <input type="hidden" name="active_tab" value="staff">

            <div class="mb-3">
                <label for="s_username" class="form-label-custom">Username <span class="text-danger">*</span></label>
                <input type="text" name="username" id="s_username" class="form-control-custom"
                       required autocomplete="username"
                       value="<?= ($activeTab === 'staff') ? sanitize($_POST['username'] ?? '') : ''; ?>">
            </div>

            <div class="mb-3">
                <label for="s_full_name" class="form-label-custom">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="full_name" id="s_full_name" class="form-control-custom"
                       required
                       value="<?= ($activeTab === 'staff') ? sanitize($_POST['full_name'] ?? '') : ''; ?>">
            </div>

            <div class="mb-3">
                <label for="s_email" class="form-label-custom">Email Address <span class="text-danger">*</span></label>
                <input type="email" name="email" id="s_email" class="form-control-custom"
                       required autocomplete="email"
                       value="<?= ($activeTab === 'staff') ? sanitize($_POST['email'] ?? '') : ''; ?>">
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="s_password" class="form-label-custom">Password <span class="text-danger">*</span></label>
                    <div class="password-input-wrapper">
                        <input type="password" name="password" id="s_password" class="form-control-custom" required>
                        <button type="button" class="toggle-password-btn" data-target="s_password" title="Show/Hide">
                            <i data-feather="eye"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="s_confirm" class="form-label-custom">Confirm Password <span class="text-danger">*</span></label>
                    <div class="password-input-wrapper">
                        <input type="password" name="confirm_password" id="s_confirm" class="form-control-custom" required>
                        <button type="button" class="toggle-password-btn" data-target="s_confirm" title="Show/Hide">
                            <i data-feather="eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-primary-custom w-100 justify-content-center py-2 mb-3">
                <i data-feather="check-circle"></i> Register as Staff
            </button>
        </form>
    </div>

    <!-- ── TAB: Admin ─────────────────────────────────────────────────────── -->
    <div id="tabPanelAdmin" class="reg-tab-panel <?= ($activeTab === 'admin') ? 'active' : ''; ?>">
        <div class="role-badge role-badge-admin mb-4">
            <i data-feather="shield" style="width:13px;height:13px;"></i>
            Registering as <strong>Administrator</strong>
        </div>

        <form action="" method="POST">
            <?= csrfField(); ?>
            <input type="hidden" name="active_tab" value="admin">

            <div class="mb-3">
                <label for="a_username" class="form-label-custom">Username <span class="text-danger">*</span></label>
                <input type="text" name="username" id="a_username" class="form-control-custom"
                       required autocomplete="username"
                       value="<?= ($activeTab === 'admin') ? sanitize($_POST['username'] ?? '') : ''; ?>">
            </div>

            <div class="mb-3">
                <label for="a_full_name" class="form-label-custom">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="full_name" id="a_full_name" class="form-control-custom"
                       required
                       value="<?= ($activeTab === 'admin') ? sanitize($_POST['full_name'] ?? '') : ''; ?>">
            </div>

            <div class="mb-3">
                <label for="a_email" class="form-label-custom">Email Address <span class="text-danger">*</span></label>
                <input type="email" name="email" id="a_email" class="form-control-custom"
                       required autocomplete="email"
                       value="<?= ($activeTab === 'admin') ? sanitize($_POST['email'] ?? '') : ''; ?>">
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="a_password" class="form-label-custom">Password <span class="text-danger">*</span></label>
                    <div class="password-input-wrapper">
                        <input type="password" name="password" id="a_password" class="form-control-custom" required>
                        <button type="button" class="toggle-password-btn" data-target="a_password" title="Show/Hide">
                            <i data-feather="eye"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="a_confirm" class="form-label-custom">Confirm Password <span class="text-danger">*</span></label>
                    <div class="password-input-wrapper">
                        <input type="password" name="confirm_password" id="a_confirm" class="form-control-custom" required>
                        <button type="button" class="toggle-password-btn" data-target="a_confirm" title="Show/Hide">
                            <i data-feather="eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-primary-custom w-100 justify-content-center py-2 mb-3"
                    style="background:var(--primary); filter:hue-rotate(220deg);">
                <i data-feather="check-circle"></i> Register as Admin
            </button>
        </form>
    </div>

    <div class="text-center mt-3">
        <span class="text-muted" style="font-size:0.88rem;">Already have an account?</span>
        <a href="<?= baseUrl('login.php'); ?>" class="font-weight-semibold text-decoration-none ms-1" style="color:var(--primary);">Sign In</a>
    </div>
</div>

<style>
/* ── Tab Switcher ── */
.reg-tabs {
    display: flex;
    background: #f1f5f9;
    border-radius: 10px;
    padding: 4px;
    gap: 4px;
}
.reg-tab-btn {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 9px 14px;
    border-radius: 7px;
    font-size: 0.84rem;
    font-weight: 600;
    border: none;
    background: transparent;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s ease;
}
.reg-tab-btn:hover { background: #e2e8f0; color: #334155; }
.reg-tab-btn.active {
    background: #ffffff;
    color: var(--primary, #4f46e5);
    box-shadow: 0 1px 5px rgba(0,0,0,0.10);
}

/* ── Tab Panels ── */
.reg-tab-panel         { display: none; }
.reg-tab-panel.active  { display: block; }

/* ── Role Badges ── */
.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    width: 100%;
    justify-content: center;
}
.role-badge-staff {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
}
.role-badge-admin {
    background: #faf5ff;
    color: #7c3aed;
    border: 1px solid #ddd6fe;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= baseUrl('assets/js/main.js'); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof feather !== 'undefined') feather.replace();

    document.querySelectorAll('.reg-tab-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const tab = this.dataset.tab;
            const panelId = 'tabPanel' + tab.charAt(0).toUpperCase() + tab.slice(1);

            document.querySelectorAll('.reg-tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.reg-tab-panel').forEach(p => p.classList.remove('active'));

            this.classList.add('active');
            document.getElementById(panelId).classList.add('active');

            feather.replace();
        });
    });
});
</script>
</body>
</html>
