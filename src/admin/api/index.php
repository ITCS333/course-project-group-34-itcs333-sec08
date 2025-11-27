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

// Handle preflight OPTIONS request
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}


require_once __DIR__ . "/../../auth/api/connect.php";
$pdo = getDBConnection();

// Get method & input data
$method = $_SERVER["REQUEST_METHOD"];
$inputJSON = file_get_contents("php://input");
$inputData = json_decode($inputJSON, true) ?? [];


// ============================
// Helper Functions
// ============================

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


// ============================
// GET: ALL STUDENTS
// ============================

function getStudents($db) {

    $hasSearch = isset($_GET["search"]) && $_GET["search"] !== "";
    $searchTerm = "%" . ($_GET["search"] ?? "") . "%";

    $sql = "SELECT student_id, name, email, created_at FROM students";
    
    if ($hasSearch) {
        $sql .= " WHERE name LIKE ? OR student_id LIKE ? OR email LIKE ?";
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


// ============================
// POST: CREATE STUDENT
// ============================

function createStudent($db, $data) {

    if (!isset($data["student_id"], $data["name"], $data["email"], $data["password"])) {
        sendResponse(["success" => false, "message" => "Missing fields"], 400);
    }

    $student_id = sanitizeInput($data["student_id"]);
    $name       = sanitizeInput($data["name"]);
    $email      = sanitizeInput($data["email"]);
    $password   = $data["password"];

    if (!validateEmail($email)) {
        sendResponse(["success" => false, "message" => "Invalid email"], 400);
    }

    // Check duplicates
    $check = $db->prepare("SELECT student_id FROM students WHERE student_id = ? OR email = ?");
    $check->execute([$student_id, $email]);
    if ($check->fetch()) {
        sendResponse(["success" => false, "message" => "Student already exists"], 409);
    }

    // Hash password
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // Insert
    $stmt = $db->prepare("INSERT INTO students (student_id, name, email, password) VALUES (?, ?, ?, ?)");
    $stmt->execute([$student_id, $name, $email, $hashed]);

    sendResponse(["success" => true, "message" => "Student created"], 201);
}


// ============================
// PUT: UPDATE STUDENT
// ============================

function updateStudent($db, $data) {

    if (!isset($data["student_id"])) {
        sendResponse(["success" => false, "message" => "student_id required"], 400);
    }

    $id       = sanitizeInput($data["student_id"]);
    $newName  = isset($data["name"])  ? sanitizeInput($data["name"])  : null;
    $newEmail = isset($data["email"]) ? sanitizeInput($data["email"]) : null;

    // Check if exists
    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        sendResponse(["success" => false, "message" => "Student not found"], 404);
    }

    // Check duplicate email
    if ($newEmail) {
        $dup = $db->prepare("SELECT student_id FROM students WHERE email = ? AND student_id != ?");
        $dup->execute([$newEmail, $id]);
        if ($dup->fetch()) {
            sendResponse(["success" => false, "message" => "Email already in use"], 409);
        }
    }

    // Build update query dynamically
    $sql = "UPDATE students SET ";
    $params = [];

    if ($newName)  { $sql .= "name = ?, ";  $params[] = $newName; }
    if ($newEmail) { $sql .= "email = ?, "; $params[] = $newEmail; }

    $sql = rtrim($sql, ", ") . " WHERE student_id = ?";
    $params[] = $id;

    $update = $db->prepare($sql);
    $update->execute($params);

    sendResponse(["success" => true, "message" => "Student updated"]);
}


// ============================
// DELETE STUDENT
// ============================

function deleteStudent($db, $id) {

    $stmt = $db->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$id]);

    if (!$stmt->fetch()) {
        sendResponse(["success" => false, "message" => "Student not found"], 404);
    }

    $delete = $db->prepare("DELETE FROM students WHERE student_id = ?");
    $delete->execute([$id]);

    sendResponse(["success" => true, "message" => "Student deleted"]);
}


// ============================
// CHANGE ADMIN PASSWORD
// ============================

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

    // Fetch old password
    $stmt = $db->prepare("SELECT password FROM admins WHERE id = ?");
    $stmt->execute([$adminId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        sendResponse(["success" => false, "message" => "User not found"], 404);
    }

    if (!password_verify($current, $user["password"])) {
        sendResponse(["success" => false, "message" => "Incorrect current password"], 401);
    }

    // Update password
    $hashed = password_hash($new, PASSWORD_DEFAULT);

    $up = $db->prepare("UPDATE admins SET password = ? WHERE id = ?");
    $up->execute([$hashed, $adminId]);

    sendResponse(["success" => true, "message" => "Password updated"]);
}



// ============================
// MAIN ROUTER
// ============================

try {

    // All routes in this API require an admin session
    if (!isset($_SESSION["logged_in"]) || $_SESSION["role"] !== "admin") {
        sendResponse(["success" => false, "message" => "Unauthorized"], 401);
    }

    if ($method === "GET") {

        getStudents($pdo);
    }

    elseif ($method === "POST") {

        if (isset($_GET["action"]) && $_GET["action"] === "change_password") {
            changePassword($pdo, $inputData);
        } else {
            createStudent($pdo, $inputData);
        }
    }

    elseif ($method === "PUT") {
        updateStudent($pdo, $inputData);
    }

    elseif ($method === "DELETE") {
        $id = $_GET["student_id"] ?? $inputData["student_id"] ?? null;
        deleteStudent($pdo, $id);
    }

    else {
        sendResponse(["success" => false, "message" => "Method not allowed"], 405);
    }

} catch (Exception $e) {
    sendResponse(["success" => false, "message" => $e->getMessage()], 500);
}

?>
