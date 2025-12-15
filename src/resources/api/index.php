<?php
/**
 * Course Resources API
 * 
 * This is a RESTful API that handles all CRUD operations for course resources 
 * and their associated comments/discussions.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: resources
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - title (VARCHAR(255))
 *   - description (TEXT)
 *   - link (VARCHAR(500))
 *   - created_at (TIMESTAMP)
 * 
 * Table: comments_resource
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - resource_id (INT, FOREIGN KEY references resources.id)
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve resource(s) or comment(s)
 *   - POST: Create a new resource or comment
 *   - PUT: Update an existing resource
 *   - DELETE: Delete a resource or comment
 * 
 * Response Format: JSON
 * 
 * API Endpoints:
 *   Resources:
 *     GET    /api/resources.php                    - Get all resources
 *     GET    /api/resources.php?id={id}           - Get single resource by ID
 *     POST   /api/resources.php                    - Create new resource
 *     PUT    /api/resources.php                    - Update resource
 *     DELETE /api/resources.php?id={id}           - Delete resource
 * 
 *   Comments:
 *     GET    /api/resources.php?resource_id={id}&action=comments  - Get comments for resource
 *     POST   /api/resources.php?action=comment                    - Create new comment
 *     DELETE /api/resources.php?comment_id={id}&action=delete_comment - Delete comment
 */

// ============================================================================
// HEADERS AND INITIALIZATION
// ============================================================================

session_start();
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");




if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


require_once __DIR__ . '/../config/Database.php';

$db = getDBConnection();


$method = $_SERVER['REQUEST_METHOD'];

$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true) ?? [];


$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;
$resourceId = $_GET['resource_id'] ?? null;
$commentId = $_GET['comment_id'] ?? null;




// ============================================================================
// RESOURCE FUNCTIONS
// ============================================================================
function getAllResources($db)
{

    $search = $_GET['search'] ?? null;
    $query = "SELECT id, title,description, link, created_at FROM resources";

    if ($search) {
        $query .= " WHERE title LIKE :search OR description LIKE :search";
    }

    $sort = $_GET['sort'] ?? null;

    if ($sort == 'title') {
        $sort = 'title';
    } else {
        $sort = 'created_at';
    }
    $order = $_GET['order'] ?? null;
    if ($order == 'asc') {
        $order = 'asc';
    } else {
        $order = 'desc';
    }

    $query .= " ORDER BY $sort $order";

    $stmt = $db->prepare($query);

    if ($search) {
        $stmt->bindValue(':search', "%$search%");
    }
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);


    sendResponse([
        "success" => true,
        "data" => $result
    ]);
}



function getResourceById($db, $resourceId)
{

    if (!is_numeric($resourceId)) {
        sendResponse([
            "success" => false,
            "message" => "Invalid or missing resource ID"
        ], 400);
    }

    $stmt = $db->prepare("SELECT id, title, description, link, created_at FROM resources WHERE id = ?");

    $stmt->bindValue(1, $resourceId);

    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        sendResponse([
            "success" => false,
            "message" => "Resource not found"
        ], 404);
    }

    sendResponse([
        "success" => true,
        "data" => $result
    ]);
}



function createResource($db, $data)
{
    $check = validateRequiredFields($data, ['title', 'link']);

    if (!$check['valid']) {
        sendResponse([
            "success" => false,
            "message" => "Title and link are required"
        ], 400);
    }

    $data["title"] = sanitizeInput($data["title"]);
    $data["link"] = trim($data["link"]);
    if (isset($data["description"])) {
        $data["description"] = sanitizeInput($data["description"]);
    } else {
        $data["description"] = "";
    }
    if (!validateUrl($data["link"])) {
        sendResponse([
            "success" => false,
            "message" => "Invalid link"
        ], 400);
    }

    $query = "INSERT INTO resources (title, description, link) VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);

    $stmt->bindValue(1, $data["title"]);
    $stmt->bindValue(2, $data["description"]);
    $stmt->bindValue(3, $data["link"]);

    if ($stmt->execute()) {
        $newId = $db->lastInsertId();
        sendResponse([
            "success" => true,
            "message" => "Resource created successfully",
            "id" => $newId
        ], 201);
    } else {
        sendResponse([
            "success" => false,
            "message" => "Failed to create resource"
        ], 500);
    }
}

function updateResource($db, $data)
{
    if (!isset($data["id"]) || empty(trim($data["id"])) || !is_numeric(trim($data["id"]))) {
        sendResponse([
            "success" => false,
            "message" => "Valid resource ID is required"
        ], 400);
    }

    $query = "SELECT id FROM resources WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindValue(1, $data["id"], PDO::PARAM_INT);
    $stmt->execute();
    $resource = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$resource) {
        sendResponse([
            "success" => false,
            "message" => "Resource not found"
        ], 404);
    }

    $fields = [];
    $values = [];
    if (isset($data["title"]) && !empty(trim($data["title"]))) {
        $fields[] = "title = ?";
        $values[] = sanitizeInput($data["title"]);
    }
    if (isset($data["link"]) && !empty(trim($data["link"]))) {
        if (!validateUrl($data["link"])) {
            sendResponse([
                'success' => false,
                'message' => "Invalid link"
            ], 400);
        }
        $fields[] = "link = ?";
        $values[] = trim($data["link"]);
    }

    if (isset($data["description"])) {
        $fields[] = "description = ?";
        $values[] = sanitizeInput($data["description"]);
    }

    if (empty($fields)) {
        sendResponse([
            "success" => false,
            "message" => "No fields provided to update"
        ], 400);
    }

    $values[] = (int) $data["id"];

    $query = "UPDATE resources SET " . implode(", ", $fields) . " WHERE id = ?";

    $stmt = $db->prepare($query);

    if ($stmt->execute($values)) {
        sendResponse([
            "success" => true,
            "message" => "Resource updated successfully"
        ], 200);
    } else {
        sendResponse([
            "success" => false,
            "message" => "Failed to update resource"
        ], 500);
    }
}


function deleteResource($db, $resourceId)
{

    if (!is_numeric($resourceId)) {
        sendResponse([
            "success" => false,
            "message" => "Invalid ID"
        ], 400);
    }

    $query = "SELECT id FROM resources WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindValue(1, $resourceId, PDO::PARAM_INT);
    $stmt->execute();

    $resource = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resource) {
        sendResponse([
            "success" => false,
            "message" => "Resource not found"
        ], 404);
    }

    $db->beginTransaction();
    try {

        $query = "DELETE FROM comments_resource WHERE resource_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindValue(1, $resourceId, PDO::PARAM_INT);
        $stmt->execute();

        $query = "DELETE FROM resources WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindValue(1, $resourceId, PDO::PARAM_INT);
        $stmt->execute();

        $db->commit();

        sendResponse([
            "success" => true,
            "message" => "Resource deleted successfully"
        ], 200);
        return;

    } catch (Exception $e) {

        $db->rollBack();

        sendResponse([
            "success" => false,
            "message" => "Failed to delete resource"
        ], 500);
    }
}


// ============================================================================
// COMMENT FUNCTIONS
// ============================================================================

function getCommentsByResourceId($db, $resourceId)
{

    if (!is_numeric($resourceId)) {
        sendResponse([
            "success" => false,
            "message" => "Invalid ID"
        ], 400);
    }

    $query = "SELECT id, resource_id, author, text, created_at FROM comments_resource WHERE resource_id = ? ORDER BY created_at ASC";

    $stmt = $db->prepare($query);

    $stmt->bindValue(1, $resourceId, PDO::PARAM_INT);

    $stmt->execute();

    $comment = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse([
        "success" => true,
        "data" => $comment
    ], 200);
}



function createComment($db, $data)
{
    $check = validateRequiredFields($data, ['resource_id', 'author', 'text']);

    if (!$check['valid']) {
        sendResponse([
            'success' => false,
            "message" => "required fields are: resource_id, author, and text"
        ], 400);
    }

    if (!is_numeric($data['resource_id'])) {
        sendResponse([
            'success' => false,
            "message" => "Invalid: id must be numric"
        ], 400);
    }

    $query = "SELECT id FROM resources WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindValue(1, $data["resource_id"], PDO::PARAM_INT);
    $stmt->execute();

    $resource = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resource) {
        sendResponse([
            "success" => false,
            "message" => "Resource not found"
        ], 404);
    }


    $data['author'] = sanitizeInput($data['author']);
    $data['text'] = sanitizeInput($data['text']);

    $query = "INSERT INTO comments_resource (resource_id, author, text) VALUES (?, ?, ?)";

    $stmt = $db->prepare($query);
    $stmt->bindValue(1, $data["resource_id"], PDO::PARAM_INT);
    $stmt->bindValue(2, $data["author"]);
    $stmt->bindValue(3, $data["text"]);

    if ($stmt->execute()) {
        $newId = $db->lastInsertId();
        sendResponse([
            "success" => true,
            "message" => "Comment created successfully",
            "id" => $newId
        ], 201);
    } else {
        sendResponse([
            "success" => false,
            "message" => "Failed to create comment"
        ], 500);
    }
}



function deleteComment($db, $commentId)
{

    if (!is_numeric($commentId)) {
        sendResponse([
            "success" => false,
            "message" => "Comment ID must be provided"
        ], 400);
    }

    $query = "SELECT id FROM comments_resource WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindValue(1, $commentId, PDO::PARAM_INT);
    $stmt->execute();

    $comment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$comment) {
        sendResponse([
            "success" => false,
            "message" => "Comment not found"
        ], 404);
    }

    $query = "DELETE FROM comments_resource WHERE id = ?";
    $stmt = $db->prepare($query);

    $stmt->bindValue(1, $commentId, PDO::PARAM_INT);

    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        sendResponse([
            "success" => true,
            "message" => "Comment deleted successfully"
        ], 200);
    } else {
        sendResponse([
            "success" => false,
            "message" => "Failed to delete comment"
        ], 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {

    if ($method === 'GET') {
        $action = $_GET['action'] ?? null;

        if ($action === 'comments') {
            if (!isset($_GET['resource_id'])) {
                sendResponse([
                    'success' => false,
                    'message' => 'Invalid action'
                ], 400);
            }
            getCommentsByResourceId($db, $_GET['resource_id']);
        } elseif (isset($_GET['id'])) {
            getResourceById($db, $_GET['id']);

        } else {
            getAllResources($db);
        }

    } elseif ($method === 'POST') {

        $action = $_GET['action'] ?? null;

        if ($action === 'comment') {
            createComment($db, $data);
        } else {
            createResource($db, $data);
        }

    } elseif ($method === 'PUT') {

        updateResource($db, $data);

    } elseif ($method === 'DELETE') {

        $action = $_GET['action'] ?? null;

        if ($action === 'delete_comment') {
            if (!isset($_GET['comment_id'])) {
                sendResponse([
                    'success' => false,
                    'message' => 'Invalid action'
                ], 400);
            }
            deleteComment($db, $_GET['comment_id']);
        } elseif (isset($_GET['id'])) {
            deleteResource($db, $_GET['id']);
        }

    } else {
        sendResponse([
            'success' => false,
            'message' => 'Method Not Allowed',
        ], 405);
    }

} catch (PDOException $e) {

    error_log($e->getMessage());

    sendResponse([
        'success' => false,
        'message' => 'Internal Server Error'
    ], 500);

} catch (Exception $e) {
    error_log($e->getMessage());

    sendResponse([
        'success' => false,
        'message' => 'Internal Server Error'
    ], 500);

}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param array $data - Data to send (should include 'success' key)
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);

    if (!is_array($data)) {
        $data = ['data' => $data];
    }

    echo json_encode($data, JSON_PRETTY_PRINT);

    exit;
}


/**
 * Helper function to validate URL format
 * 
 * @param string $url - URL to validate
 * @return bool - True if valid, false otherwise
 */
function validateUrl($url)
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false;

}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data)
{
    $data = trim($data);

    $data = strip_tags($data);

    $data = htmlspecialchars($data, ENT_QUOTES);
    return $data;
}


/**
 * Helper function to validate required fields
 * 
 * @param array $data - Data array to validate
 * @param array $requiredFields - Array of required field names
 * @return array - Array with 'valid' (bool) and 'missing' (array of missing fields)
 */
function validateRequiredFields($data, $requiredFields)
{
    $missing = [];

    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $missing[] = $field;
        }
    }

    return [
        'valid' => (count($missing) === 0),
        'missing' => $missing
    ];
}

?>