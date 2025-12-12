<?php
session_start();

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

require_once __DIR__ . "/connect.php";

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!isset($data["email"]) || !isset($data["password"])) {
    echo json_encode(["success" => false, "message" => "Missing credentials"]);
    exit;
}

$email = trim($data["email"]);
$password = $data["password"];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email"]);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(["success" => false, "message" => "Password too short"]);
    exit;
}

try {
    $pdo = getDBConnection();

    $stmt = $pdo->prepare("SELECT id, name, email, password, is_admin FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user["password"])) {
        echo json_encode(["success" => false, "message" => "Invalid email or password"]);
        exit;
    }

    $role = ($user["is_admin"] == 1) ? "admin" : "student";

    $_SESSION["logged_in"] = true;
    $_SESSION["user_id"] = $user["id"];
    $_SESSION["user_name"] = $user["name"];
    $_SESSION["user_email"] = $user["email"];
    $_SESSION["role"] = $role;

    echo json_encode([
        "success" => true,
        "message" => "Login successful",
        "user" => [
            "id" => $user["id"],
            "name" => $user["name"],
            "email" => $user["email"],
            "role" => $role
        ]
    ]);

} catch (PDOException $e) {
    error_log("LOGIN ERROR: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Server error"]);
}
?>
