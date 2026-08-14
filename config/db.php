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
