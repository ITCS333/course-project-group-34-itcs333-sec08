<?php
// Discussion API database connector (no direct output)
$host = 'localhost';
$db   = 'course';
$user = 'admin';
$pass = 'password123';

// Provide getDBConnection() only if it's not already defined elsewhere
if (!function_exists('getDBConnection')) {
    function getDBConnection() {
        global $host, $db, $user, $pass;

        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        return new PDO($dsn, $user, $pass, $options);
    }
}

// Note: Do NOT echo or print here  APIs must not send output before headers/session.
?>
