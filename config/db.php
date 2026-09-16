<?php
// config/db.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ekservice_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('LINE_LIFF_ID', '2011627827-2s8sq190');
define('LINE_CHANNEL_ID', '2011627827');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helper: เริ่ม session ถ้ายังไม่มี
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}