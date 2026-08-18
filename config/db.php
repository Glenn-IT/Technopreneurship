<?php
// config/db.php - Database connection configuration for Water Billing System (ramos_db)

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ramos_db');
define('DB_CHARSET', 'utf8mb4');

function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Attempt to connect without dbname and create it if missing
            try {
                $rootDsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
                $tmpPdo = new PDO($rootDsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $tmpPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                // Now reconnect
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $ex) {
                die("<div style='font-family:sans-serif; padding:20px; background:#ffebee; color:#c62828; border-radius:8px; margin:20px;'>
                    <h3>Database Connection Error</h3>
                    <p>Could not connect to database <strong>" . DB_NAME . "</strong> on <strong>" . DB_HOST . "</strong>.</p>
                    <p>Details: " . htmlspecialchars($e->getMessage()) . "</p>
                    <p><em>Please ensure MySQL is running in XAMPP and import <code>schema.sql</code>.</em></p>
                </div>");
            }
        }
    }
    return $pdo;
}

$pdo = getDBConnection();

// Auto migration: Ensure Security Questions columns exist in users table
try {
    $checkCols = $pdo->query("SHOW COLUMNS FROM users LIKE 'security_question'");
    if (!$checkCols->fetch()) {
        $pdo->exec("ALTER TABLE users 
            ADD COLUMN security_question VARCHAR(255) DEFAULT NULL,
            ADD COLUMN security_answer VARCHAR(255) DEFAULT NULL");
    }
} catch (PDOException $e) {
    // Suppress if table does not exist yet during initial setup
}

// Auto migration: Rename bills table to tblaprilyn
try {
    $checkTbl = $pdo->query("SHOW TABLES LIKE 'tblaprilyn'");
    if (!$checkTbl->fetch()) {
        $checkBills = $pdo->query("SHOW TABLES LIKE 'bills'");
        if ($checkBills->fetch()) {
            $pdo->exec("RENAME TABLE bills TO tblaprilyn");
        }
    }
} catch (PDOException $e) {
    // Ignore migration error
}

// Auto migration: Create activity_logs table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `activity_logs` (
        `log_id`      INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id`     INT UNSIGNED DEFAULT NULL,
        `username`    VARCHAR(100) NOT NULL DEFAULT '',
        `full_name`   VARCHAR(200) NOT NULL DEFAULT '',
        `action`      ENUM('login','logout') NOT NULL,
        `ip_address`  VARCHAR(45) DEFAULT NULL,
        `user_agent`  VARCHAR(300) DEFAULT NULL,
        `logged_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id  (`user_id`),
        INDEX idx_action   (`action`),
        INDEX idx_logged_at(`logged_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) {
    // Ignore if already exists
}
