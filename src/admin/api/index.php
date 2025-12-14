<?php
session_start();

/**
 * Student Management API
 * 
 * A full RESTful API for:
 * - Retrieving students
 * - Adding students
 * - Updating students
 * - Deleting students
 * - Changing passwords
 * 
 * Uses PDO + MySQL (database = main)
 */

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

require_once __DIR__ . "/../../auth/api/connect.php";
$pdo = getDBConnection();

$method = $_SERVER["REQUEST_METHOD"];
$inputJSON = file_get_contents("php://input");
$inputData = json_decode($inputJSON, true) ?? [];

function sendResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function sanitizeInput($str) {
    return htmlspecialchars(strip_tags(trim($str)));
}

function getStudents($db) {
    $hasSearch = isset($_GET["search"]) && $_GET["search"] !== "";
    $searchTerm = "%" . ($_GET["search"] ?? "") . "%";

    $sql = "SELECT id AS student_id, name, email, created_at FROM users WHERE is_admin = 0";
    
    if ($hasSearch) {
        $sql .= " AND (name LIKE ? OR id LIKE ? OR email LIKE ?)";
    }

    $stmt = $db->prepare($sql);

    if ($hasSearch) {
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    } else {
        $stmt->execute();
    }

    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(["success" => true, "data" => $students]);
}

function createStudent($db, $data) {
    if (!isset($data["name"], $data["email"], $data["password"])) {
        sendResponse(["success" => false, "message" => "Missing fields"], 400);
    }

    $name     = sanitizeInput($data["name"]);
    $email    = sanitizeInput($data["email"]);
    $password = $data["password"];

    if (!validateEmail($email)) {
        sendResponse(["success" => false, "message" => "Invalid email"], 400);
    }

    $check = $db->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        sendResponse(["success" => false, "message" => "Student already exists"], 409);
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $db->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, 0)");
    $stmt->execute([$name, $email, $hashed]);

    sendResponse(["success" => true, "message" => "Student created"], 201);
}

function updateStudent($db, $data) {
    if (!isset($data["student_id"])) {
        sendResponse(["success" => false, "message" => "student_id required"], 400);
    }

    $id       = sanitizeInput($data["student_id"]);
    $newName  = isset($data["name"])  ? sanitizeInput($data["name"])  : null;
    $newEmail = isset($data["email"]) ? sanitizeInput($data["email"]) : null;

    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_admin = 0");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        sendResponse(["success" => false, "message" => "Student not found"], 404);
    }

    if ($newEmail) {
        $dup = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $dup->execute([$newEmail, $id]);
        if ($dup->fetch()) {
            sendResponse(["success" => false, "message" => "Email already in use"], 409);
        }
    }

    $sql = "UPDATE users SET ";
    $params = [];

    if ($newName)  { $sql .= "name = ?, ";  $params[] = $newName; }
    if ($newEmail) { $sql .= "email = ?, "; $params[] = $newEmail; }

    $sql = rtrim($sql, ", ") . " WHERE id = ? AND is_admin = 0";
    $params[] = $id;

    $update = $db->prepare($sql);
    $update->execute($params);

    sendResponse(["success" => true, "message" => "Student updated"]);
}

function deleteStudent($db, $id) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND is_admin = 0");
    $stmt->execute([$id]);

    if (!$stmt->fetch()) {
        sendResponse(["success" => false, "message" => "Student not found"], 404);
    }

    $delete = $db->prepare("DELETE FROM users WHERE id = ? AND is_admin = 0");
    $delete->execute([$id]);

    sendResponse(["success" => true, "message" => "Student deleted"]);
}

function changePassword($db, $data) {
    if (!isset($data["current_password"], $data["new_password"])) {
        sendResponse(["success" => false, "message" => "Missing fields"], 400);
    }

    $current = $data["current_password"];
    $new     = $data["new_password"];

    if (strlen($new) < 8) {
        sendResponse(["success" => false, "message" => "New password must be at least 8 characters"], 400);
    }

    if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
        sendResponse(["success" => false, "message" => "Unauthorized"], 401);
    }

    $adminId = $_SESSION["user_id"];

    $stmt = $db->prepare("SELECT password FROM users WHERE id = ? AND is_admin = 1");
    $stmt->execute([$adminId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        sendResponse(["success" => false, "message" => "User not found"], 404);
    }

    if (!password_verify($current, $user["password"])) {
        sendResponse(["success" => false, "message" => "Incorrect current password"], 401);
    }

    $hashed = password_hash($new, PASSWORD_DEFAULT);

    $up = $db->prepare("UPDATE users SET password = ? WHERE id = ? AND is_admin = 1");
    $up->execute([$hashed, $adminId]);

    sendResponse(["success" => true, "message" => "Password updated"]);
}

try {
    if (!isset($_SESSION["logged_in"]) || $_SESSION["role"] !== "admin") {
        sendResponse(["success" => false, "message" => "Unauthorized"], 401);
    }

    if ($method === "GET") {
        getStudents($pdo);
    } elseif ($method === "POST") {
        if (isset($_GET["action"]) && $_GET["action"] === "change_password") {
            changePassword($pdo, $inputData);
        } else {
            createStudent($pdo, $inputData);
        }
    } elseif ($method === "PUT") {
        updateStudent($pdo, $inputData);
    } elseif ($method === "DELETE") {
        $id = $_GET["student_id"] ?? $inputData["student_id"] ?? null;
        deleteStudent($pdo, $id);
    } else {
        sendResponse(["success" => false, "message" => "Method not allowed"], 405);
    }
} catch (PDOException $e) {
    sendResponse(["success" => false, "message" => "Database error"], 500);
} catch (Exception $e) {
    sendResponse(["success" => false, "message" => $e->getMessage()], 500);
}


?>
