<?php
/**
 * Database Connection File
 * 
 * This script establishes a PDO connection to the MySQL database.
 * The file is included in index.php to allow database interaction.
 * 
 * Database Name: main
 * Host: localhost
 * User: root
 * Password: (empty for XAMPP)
 */

$host = "localhost";
$dbname = "main";   
$username = "root";
$password = "";     // XAMPP default

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed",
        "error" => $e->getMessage()
    ]);
    exit;
}
?>
