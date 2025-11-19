<?php
// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'school_management');

// Create connection
function getDBConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            // Try alternative connection with explicit port
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, 3306);
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error . "<br>Please ensure XAMPP MySQL/MariaDB service is running.");
            }
        }
        
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (Exception $e) {
        die("Database connection error: " . $e->getMessage() . "<br><br>Troubleshooting steps:<br>1. Make sure XAMPP is running<br>2. Start MySQL/MariaDB from XAMPP Control Panel<br>3. Check if port 3306 is not blocked<br>4. Verify database 'school_management' exists in phpMyAdmin");
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL configuration
define('BASE_URL', 'http://localhost/school-management-system');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

$upload_dirs = ['profiles', 'notes', 'reports', 'clubs', 'news'];
foreach ($upload_dirs as $dir) {
    $path = UPLOAD_PATH . $dir;
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }
}
?>
