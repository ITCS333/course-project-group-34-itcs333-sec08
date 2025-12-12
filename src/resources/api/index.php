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
 * Table: comments
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

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
header("Content-Type: application/json");
// Allow cross-origin requests (CORS) if needed
header("Access-Control-Allow-Origin: *");
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
// Allow specific headers (Content-Type, Authorization)
header("Access-Control-Allow-Headers: Content-Type, Authorization");



// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


require_once __DIR__ . '/../config/Database.php';


// TODO: Get the PDO database connection
// Example: $database = new Database();
// Example: $db = $database->getConnection();
$database = new Database();
$db = $database->getConnection();

// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode() with associative array parameter
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true) ?? [];

// TODO: Parse query parameters
// Get 'action', 'id', 'resource_id', 'comment_id' from $_GET
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
    if (!isset($data['title']) || !isset($data['link']) || empty(trim($data['title'])) || empty(trim($data['link']))) {
        sendResponse([
            "success" => false,
            "message" => "Title and link are required"
        ], 400);
    }

    $data["title"] = trim($data["title"]);
    $data["link"] = trim($data["link"]);
    if (isset($data["description"])) {
        $data["description"] = trim($data["description"]);
    } else {
        $data["description"] = "";
    }
    if (!filter_var($data["link"], FILTER_VALIDATE_URL)) {
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
        $values[] = trim($data["title"]);
    }
    if (isset($data["link"]) && !empty(trim($data["link"]))) {
        if (!filter_var($data['link'], FILTER_VALIDATE_URL)) {
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
        $values[] = trim($data["description"]);
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
 
        $query = "DELETE FROM comments WHERE resource_id = ?";
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

/**
 * Function: Get all comments for a specific resource
 * Method: GET with action=comments
 * 
 * Query Parameters:
 *   - resource_id: The resource's database ID (required)
 * 
 * Response:
 *   - success: true/false
 *   - data: Array of comment objects
 */
function getCommentsByResourceId($db, $resourceId)
{
    // TODO: Validate that resource_id is provided and is numeric
    // If not, return error response with 400 status

    // TODO: Prepare SQL query to select comments for the resource
    // SELECT id, resource_id, author, text, created_at 
    // FROM comments 
    // WHERE resource_id = ? 
    // ORDER BY created_at ASC

    // TODO: Bind the resource_id parameter

    // TODO: Execute the query

    // TODO: Fetch all results as an associative array

    // TODO: Return success response with comments data
    // Even if no comments exist, return empty array (not an error)
}


/**
 * Function: Create a new comment
 * Method: POST with action=comment
 * 
 * Required JSON Body:
 *   - resource_id: The resource's database ID (required)
 *   - author: Name of the comment author (required)
 *   - text: Comment text content (required)
 * 
 * Response:
 *   - success: true/false
 *   - message: Success or error message
 *   - id: ID of created comment (on success)
 */
function createComment($db, $data)
{
    // TODO: Validate required fields
    // Check if resource_id, author, and text are provided and not empty
    // If any required field is missing, return error response with 400 status

    // TODO: Validate that resource_id is numeric
    // If not, return error response with 400 status

    // TODO: Check if the resource exists
    // Prepare and execute SELECT query on resources table
    // If resource not found, return error response with 404 status

    // TODO: Sanitize input data
    // Trim whitespace from author and text

    // TODO: Prepare INSERT query
    // INSERT INTO comments (resource_id, author, text) VALUES (?, ?, ?)

    // TODO: Bind parameters
    // Bind resource_id, author, and text

    // TODO: Execute the query

    // TODO: Check if insert was successful
    // If yes, get the last inserted ID using $db->lastInsertId()
    // Return success response with 201 status and the new comment ID
    // If no, return error response with 500 status
}


/**
 * Function: Delete a comment
 * Method: DELETE with action=delete_comment
 * 
 * Query Parameters or JSON Body:
 *   - comment_id: The comment's database ID (required)
 * 
 * Response:
 *   - success: true/false
 *   - message: Success or error message
 */
function deleteComment($db, $commentId)
{
    // TODO: Validate that comment_id is provided and is numeric
    // If not, return error response with 400 status

    // TODO: Check if comment exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status

    // TODO: Prepare DELETE query
    // DELETE FROM comments WHERE id = ?

    // TODO: Bind the comment_id parameter

    // TODO: Execute the query

    // TODO: Check if delete was successful
    // If yes, return success response with 200 status
    // If no, return error response with 500 status
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Route the request based on HTTP method and action parameter

    if ($method === 'GET') {
        // TODO: Check the action parameter to determine which function to call

        // If action is 'comments', get comments for a resource
        // TODO: Check if action === 'comments'
        // Get resource_id from query parameters
        // Call getCommentsByResourceId()

        // If id parameter exists, get single resource
        // TODO: Check if 'id' parameter exists in $_GET
        // Call getResourceById()

        // Otherwise, get all resources
        // TODO: Call getAllResources()

    } elseif ($method === 'POST') {
        // TODO: Check the action parameter to determine which function to call

        // If action is 'comment', create a new comment
        // TODO: Check if action === 'comment'
        // Call createComment()

        // Otherwise, create a new resource
        // TODO: Call createResource()

    } elseif ($method === 'PUT') {
        // TODO: Update a resource
        // Call updateResource()

    } elseif ($method === 'DELETE') {
        // TODO: Check the action parameter to determine which function to call

        // If action is 'delete_comment', delete a comment
        // TODO: Check if action === 'delete_comment'
        // Get comment_id from query parameters or request body
        // Call deleteComment()

        // Otherwise, delete a resource
        // TODO: Get resource id from query parameter or request body
        // Call deleteResource()

    } else {
        // TODO: Return error for unsupported methods
        // Set HTTP status to 405 (Method Not Allowed)
        // Return JSON error message using sendResponse()
    }

} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional, use error_log())
    // Return generic error response with 500 status
    // Do NOT expose detailed error messages to the client in production

} catch (Exception $e) {
    // TODO: Handle general errors
    // Log the error message (optional)
    // Return error response with 500 status
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
    // TODO: Set HTTP response code using http_response_code()

    // TODO: Ensure data is an array
    // If not, wrap it in an array

    // TODO: Echo JSON encoded data
    // Use JSON_PRETTY_PRINT for readability (optional)

    // TODO: Exit to prevent further execution
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
    // TODO: Use filter_var with FILTER_VALIDATE_URL
    // Return true if valid, false otherwise
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data)
{
    // TODO: Trim whitespace using trim()

    // TODO: Strip HTML tags using strip_tags()

    // TODO: Convert special characters using htmlspecialchars()
    // Use ENT_QUOTES to escape both double and single quotes

    // TODO: Return sanitized data
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
    // TODO: Initialize empty array for missing fields

    // TODO: Loop through required fields
    // Check if each field exists in data and is not empty
    // If missing or empty, add to missing fields array

    // TODO: Return result array
    // ['valid' => (count($missing) === 0), 'missing' => $missing]
}

?>