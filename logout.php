<?php
// logout.php - Logout handler
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

if (session_status() === PHP_SESSION_ACTIVE) {
    // Capture user info before destroying session
    $logUserId   = $_SESSION['user_id']   ?? null;
    $logUsername = $_SESSION['username']  ?? '';
    $logFullName = $_SESSION['full_name'] ?? '';

    // Record logout activity
    if ($logUserId) {
        try {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR']
                ?? $_SERVER['REMOTE_ADDR']
                ?? 'unknown';
            $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300);
            $logStmt = $pdo->prepare(
                "INSERT INTO activity_logs (user_id, username, full_name, action, ip_address, user_agent)
                 VALUES (:uid, :uname, :fname, 'logout', :ip, :ua)"
            );
            $logStmt->execute([
                'uid'   => $logUserId,
                'uname' => $logUsername,
                'fname' => $logFullName,
                'ip'    => $ip,
                'ua'    => $ua,
            ]);
        } catch (PDOException $logErr) { /* non-fatal */ }
    }

    session_unset();
    session_destroy();
}

session_start();
setFlash('info', 'You have been successfully logged out.');
header('Location: ' . baseUrl('login.php'));
exit;
