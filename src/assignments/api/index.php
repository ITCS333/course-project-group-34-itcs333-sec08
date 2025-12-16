<?php
session_start();
/**
 * Assignment Management API
 * 
 * This is a RESTful API that handles all CRUD operations for course assignments
 * and their associated discussion comments.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: assignments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - title (VARCHAR(200))
 *   - description (TEXT)
 *   - due_date (DATE)
 *   - files (TEXT)
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - assignment_id (VARCHAR(50), FOREIGN KEY)
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve assignment(s) or comment(s)
 *   - POST: Create a new assignment or comment
 *   - PUT: Update an existing assignment
 *   - DELETE: Delete an assignment or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// HEADERS AND CORS CONFIGURATION
// ============================================================================

// TODO: Set Content-Type header to application/json
header('Content-Type: application/json');


// TODO: Set CORS headers to allow cross-origin requests
header('Access-Control-Allow-Origin: *');


// TODO: Handle preflight OPTIONS request
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


// ============================================================================
// DATABASE CONNECTION
// ============================================================================

// TODO: Include the database connection class
include 'connect.php';


// TODO: Create database connection
try {
    $db = getDBConnection();
} catch (PDOException $e) {
    sendResponse(['error' => 'Database connection failed: ' . $e->getMessage()], 500);
    exit();
}


// TODO: Set PDO to throw exceptions on errors



// ============================================================================
// REQUEST PARSING
// ============================================================================

// TODO: Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];


// TODO: Get the request body for POST and PUT requests
$data = [];
if ($method === 'POST' || $method === 'PUT') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendResponse(['error' => 'Invalid JSON data'], 400);
        exit();
    }
}


// TODO: Parse query parameters
$resource = $_GET['resource'] ?? null;
$id = $_GET['id'] ?? null;
$assignmentId = $_GET['assignment_id'] ?? null;
$search = $_GET['search'] ?? null;
$sort = $_GET['sort'] ??;
$order = $_GET['order'] ??;





// ============================================================================
// ASSIGNMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all assignments
 * Method: GET
 * Endpoint: ?resource=assignments
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, due_date, created_at)
 *   - order: Optional sort order (asc or desc, default: asc)
 * 
 * Response: JSON array of assignment objects
 */

function getAllAssignments($db) {
    // TODO: Start building the SQL query
    $sql = "SELECT * FROM assignments";
    
    
    // TODO: Check if 'search' query parameter exists in $_GET
    $whereConditions = [];
    $params = [];
    $search = $_GET['search'] ?? null;
    $sort = $_GET['sort'] ?? null;
    
    if ($search) {
        $whereConditions[] = "(title LIKE :search OR description LIKE :search)";
        $params[':search'] = "%$search%";
    }
        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }
        $orderBy = 'created_at';
        $orderDir = 'ASC';
    
        
            

    
    
    
    // TODO: Check if 'sort' and 'order' query parameters exist
    $allowedSortFields = ['title', 'due_date', 'created_at'];
    $allowedOrders = ['asc', 'desc'];
    if ($sort && in_array($sort, $allowedSortFields)) {
        $orderBy = $sort;
    }
    if (validateAllowedValue($sort, $allowedSortFields) && validateAllowedValue($order, $allowedOrders)) {
        $sql .= " ORDER BY $sort $order";
    } else {
        $sql .= " ORDER BY created_at DESC";
    }
    try {
        
        $stmt = $db->prepare($sql);

        
        if ($search) {
            $stmt->bindValue(':search', $search, PDO::PARAM_STR);
        }

        
        $stmt->execute();

        
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

       
        foreach ($assignments as &$assignment) {
            if (!empty($assignment['files'])) {
                $assignment['files'] = json_decode($assignment['files'], true) ?? [];
            } else {
                $assignment['files'] = [];
            }
        }

       
        sendResponse($assignments);

    } catch (PDOException $e) {
        sendResponse(['error' => 'Failed to fetch assignments: ' . $e->getMessage()], 500);
    }
    
}


/**
 * Function: Get a single assignment by ID
 * Method: GET
 * Endpoint: ?resource=assignments&id={assignment_id}
 * 
 * Query Parameters:
 *   - id: The assignment ID (required)
 * 
 * Response: JSON object with assignment details
 */
function getAssignmentById($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    if (empty($assignmentId))
    {
        sendResponse(['error' => 'Assignment ID is required'], 400);
        return;
    }
    
    
    
    
    
    // TODO: Prepare SQL query to select assignment by id
    $sql = "SELECT * FROM assignments WHERE id = :id";
    stmt = $db->prepare($sql);
    
    
    // TODO: Bind the :id parameter
    stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);
    
    
    // TODO: Execute the statement
    stmt->execute();
    
    
    // TODO: Fetch the result as associative array
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
   
    
    
    // TODO: Check if assignment was found
    if (!$assignment) {
        sendResponse(['error' => 'Assignment not found'], 404);
        return;
    }
    // TODO: Decode the 'files' field from JSON to array
    if (!empty($assignment['files'])) {
        $assignment['files'] = json_decode($assignment['files'], true) ?? [];
    } else {
        $assignment['files'] = [];
    }
    
    
    
    
    
    
    
    
    // TODO: Return success response with assignment data
    
    sendResponse($assignment);
}


/**
 * Function: Create a new assignment
 * Method: POST
 * Endpoint: ?resource=assignments
 * 
 * Required JSON Body:
 *   - title: Assignment title (required)
 *   - description: Assignment description (required)
 *   - due_date: Due date in YYYY-MM-DD format (required)
 *   - files: Array of file URLs/paths (optional)
 * 
 * Response: JSON object with created assignment data
 */
function createAssignment($db, $data) {
    // TODO: Validate required fields
    $requiredFields = ['title', 'description', 'due_date'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            sendResponse(['error' => "$field is required"], 400);
            return;
        }
    }
    
    
    // TODO: Sanitize input data
    $title = sanitizeInput($data['title']);
    $description = sanitizeInput($data['description']);
    $dueDate = sanitizeInput($data['due_date']);
    
    
    // TODO: Validate due_date format
    
    if (!validateDate($dueDate)) {
        sendResponse(['error' => 'Invalid due_date format. Use YYYY-MM-DD'], 400);
        return;
    }
    
    // TODO: Generate a unique assignment ID
    
    
    // TODO: Handle the 'files' field
    $files = isset($data['files']) && is_array($data['files']) ? $data['files'] : [];
    $filesJson = json_encode($files);
    
    // TODO: Prepare INSERT query
    $sql = "INSERT INTO assignments (title, description, due_date, files, created_at, updated_at) 
            VALUES (:title, :description, :due_date, :files, NOW(), NOW())";
    $stmt = $db->prepare($sql);
    
    // TODO: Bind all parameters
    
    $stmt->bindValue(':title', $title, PDO::PARAM_STR);
    $stmt->bindValue(':description', $description, PDO::PARAM_STR);
    $stmt->bindValue(':due_date', $dueDate, PDO::PARAM_STR);
    $stmt->bindValue(':files', $filesJson, PDO::PARAM_STR);
    // TODO: Execute the statement
    
    $stmt->execute();
    // TODO: Check if insert was successful
    if ($stmt->rowCount() > 0) {
        $lastId = $db->lastInsertId();}
    $sql = "SELECT * FROM assignments WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $lastId, PDO::PARAM_INT);
    $stmt->execute();
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // TODO: If insert failed, return 500 error
    if (!empty($assignment['files'])) {
            $assignment['files'] = json_decode($assignment['files'], true) ?? [];
        } else {
            $assignment['files'] = [];
        }

        sendResponse(['message' => 'Assignment created successfully', 'assignment' => $assignment], 201);
    } else {
        sendResponse(['error' => 'Failed to create assignment'], 500);
    }
}


/**
 * Function: Update an existing assignment
 * Method: PUT
 * Endpoint: ?resource=assignments
 * 
 * Required JSON Body:
 *   - id: Assignment ID (required, to identify which assignment to update)
 *   - title: Updated title (optional)
 *   - description: Updated description (optional)
 *   - due_date: Updated due date (optional)
 *   - files: Updated files array (optional)
 * 
 * Response: JSON object with success status
 */
function updateAssignment($db, $data) {
    // TODO: Validate that 'id' is provided in $data
    if (!isset($data['id']) || empty($data['id'])) {
        sendResponse(['error' => 'Assignment ID is required'], 400);
        return;
    }
    
    
    
    // TODO: Store assignment ID in variable
    $assignmentId = $data['id'];
    
    // TODO: Check if assignment exists
    try{
        $sql = "SELECT * FROM assignments WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);
        $stmt->execute();
        $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$assignment) {
            sendResponse(['error' => 'Assignment not found'], 404);
            return;
        }
        
    }
    
    // TODO: Build UPDATE query dynamically based on provided fields
        $setClauses = [];
        $params = [':id' => $assignmentId];
    // TODO: Check which fields are provided and add to SET clause
    if (isset($data['title']) && !empty(trim($data['title']))) {
        $setClauses[] = "title = :title";
        $params[':title'] = sanitizeInput($data['title']);
    }

    if (isset($data['description']) && !empty(trim($data['description']))) {
        $setClauses[] = "description = :description";
        $params[':description'] = sanitizeInput($data['description']);
    }

    if (isset($data['due_date'])) {
        $dueDate = sanitizeInput($data['due_date']);
        if (validateDate($dueDate)) {
            $setClauses[] = "due_date = :due_date";
            $params[':due_date'] = $dueDate;
        } else {
            sendResponse(['error' => 'Invalid due_date format. Use YYYY-MM-DD'], 400);
            return;
        }
    }

    if (isset($data['files']) && is_array($data['files'])) {
        $setClauses[] = "files = :files";
        $params[':files'] = json_encode($data['files']);
    }
    
    // TODO: If no fields to update (besides updated_at), return 400 error
    if (empty($setClauses)) {
        sendResponse(['error' => 'No fields to update'], 400);
        return;
    }
    
    // TODO: Add updated_at timestamp to SET clause
    $setClauses[] = "updated_at = NOW()";
    
    // TODO: Complete the UPDATE query
     $sql = "UPDATE assignments SET " . implode(', ', $setClauses) . " WHERE id = :id";
    
    // TODO: Prepare the statement
    $stmt = $db->prepare($sql);
    
    // TODO: Bind all parameters dynamically
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    
    // TODO: Execute the statement
    $stmt->execute();
    
    // TODO: Check if update was successful
    if ($stmt->rowCount() > 0){
        
    }
    $sql = "SELECT * FROM assignments WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);
    $stmt->execute();
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    
    // TODO: If no rows affected, return appropriate message
    if (!empty($assignment['files'])) {
        $assignment['files'] = json_decode($assignment['files'], true) ?? [];
    } else {
        $assignment['files'] = [];
    }
    sendResponse(['message' => 'Assignment updated successfully', 'assignment' => $assignment]);
        } else {
            sendResponse(['message' => 'No changes made to the assignment']);
        }

    } catch (PDOException $e) {
        sendResponse(['error' => 'Failed to update assignment: ' . $e->getMessage()], 500);
    }
}


/**
 * Function: Delete an assignment
 * Method: DELETE
 * Endpoint: ?resource=assignments&id={assignment_id}
 * 
 * Query Parameters:
 *   - id: Assignment ID (required)
 * 
 * Response: JSON object with success status
 */
function deleteAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    if (empty($assignmentId)) {
        sendResponse(['error' => 'Assignment ID is required'], 400);
        return;
    }
    
    // TODO: Check if assignment exists
    try{
        $sql = "SELECT id FROM assignments WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            sendResponse(['error' => 'Assignment not found'], 404);
            return;
        }
    // TODO: Delete associated comments first (due to foreign key constraint)
        $sql = "DELETE FROM comments WHERE assignment_id = :assignment_id";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':assignment_id', $assignmentId, PDO::PARAM_INT);
        $stmt->execute();
    
    // TODO: Prepare DELETE query for assignment
        $sql = "DELETE FROM assignments WHERE id = :id";
        $stmt = $db->prepare($sql);
        
    // TODO: Bind the :id parameter
        $stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);
    
    // TODO: Execute the statement
    
        
        $stmt->execute();

    // TODO: Check if delete was successful
        if ($stmt->rowCount() > 0) {
        sendResponse(['message' => 'Assignment and associated comments deleted successfully']);
    
    // TODO: If delete failed, return 500 error
            else {
                        sendResponse(['error' => 'Failed to delete assignment'], 500);
                    }

                } catch (PDOException $e) {
                    sendResponse(['error' => 'Failed to delete assignment: ' . $e->getMessage()], 500);
                }
            }
}


// ============================================================================
// COMMENT CRUD FUNCTIONS
// ============================================================================

/**
 * Function: Get all comments for a specific assignment
 * Method: GET
 * Endpoint: ?resource=comments&assignment_id={assignment_id}
 * 
 * Query Parameters:
 *   - assignment_id: The assignment ID (required)
 * 
 * Response: JSON array of comment objects
 */
function getCommentsByAssignment($db, $assignmentId) {
    // TODO: Validate that $assignmentId is provided and not empty
    if (empty($assignmentId)) {
        sendResponse(['error' => 'Assignment ID is required'], 400);
        return;
    }
    try{
    
    // TODO: Prepare SQL query to select all comments for the assignment
        $sql = "SELECT * FROM comments WHERE assignment_id = :assignment_id ORDER BY created_at DESC";
        $stmt = $db->prepare($sql);
    
    // TODO: Bind the :assignment_id parameter
        $stmt->bindValue(':assignment_id', $assignmentId, PDO::PARAM_INT);
    
    // TODO: Execute the statement
        $stmt->execute();
    
    // TODO: Fetch all results as associative array
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // TODO: Return success response with comments data
        sendResponse($comments);

        } catch (PDOException $e) {
            sendResponse(['error' => 'Failed to fetch comments: ' . $e->getMessage()], 500);
        }
}


/**
 * Function: Create a new comment
 * Method: POST
 * Endpoint: ?resource=comments
 * 
 * Required JSON Body:
 *   - assignment_id: Assignment ID (required)
 *   - author: Comment author name (required)
 *   - text: Comment content (required)
 * 
 * Response: JSON object with created comment data
 */
function createComment($db, $data) {
    // TODO: Validate required fields
    $requiredFields = ['assignment_id', 'author', 'text'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            sendResponse(['error' => "$field is required"], 400);
            return;
        }
    }
    
    // TODO: Sanitize input data
    $assignmentId = sanitizeInput($data['assignment_id']);
    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);
    
    // TODO: Validate that text is not empty after trimming
    if (empty(trim($text))) {
        sendResponse(['error' => 'Comment text cannot be empty'], 400);
        return;
    }

    try {
    
    // TODO: Verify that the assignment exists
        $sql = "SELECT id FROM assignments WHERE id = :assignment_id";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':assignment_id', $assignmentId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            sendResponse(['error' => 'Assignment not found'], 404);
            return;
        }
    
    // TODO: Prepare INSERT query for comment
    $sql = "INSERT INTO comments (assignment_id, author, text, created_at) VALUES (:assignment_id, :author, :text, NOW())";
        $stmt = $db->prepare($sql);
    
    // TODO: Bind all parameters
    $stmt->bindValue(':assignment_id', $assignmentId, PDO::PARAM_INT);
    
    // TODO: Execute the statement
     $stmt->execute();
    
    // TODO: Get the ID of the inserted comment
        $lastId = $db->lastInsertId();
    
    // TODO: Return success response with created comment data
        $sql = "SELECT * FROM comments WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $lastId, PDO::PARAM_INT);
            $stmt->execute();
            $comment = $stmt->fetch(PDO::FETCH_ASSOC);

            sendResponse(['message' => 'Comment created successfully', 'comment' => $comment], 201);

        } catch (PDOException $e) {
            sendResponse(['error' => 'Failed to create comment: ' . $e->getMessage()], 500);
        }
}


/**
 * Function: Delete a comment
 * Method: DELETE
 * Endpoint: ?resource=comments&id={comment_id}
 * 
 * Query Parameters:
 *   - id: Comment ID (required)
 * 
 * Response: JSON object with success status
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that $commentId is provided and not empty
    if (empty($commentId)) {
        sendResponse(['error' => 'Comment ID is required'], 400);
        return;
    }

    try {
    
    // TODO: Check if comment exists
        $sql = "SELECT id FROM comments WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $commentId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            sendResponse(['error' => 'Comment not found'], 404);
            return;
        }
    
    // TODO: Prepare DELETE query
        $sql = "DELETE FROM comments WHERE id = :id";
        $stmt = $db->prepare($sql);
        
    
    // TODO: Bind the :id parameter
        $stmt->bindValue(':id', $commentId, PDO::PARAM_INT);
    
    // TODO: Execute the statement
        $stmt->execute();
    
    // TODO: Check if delete was successful
        if ($stmt->rowCount() > 0) {
        sendResponse(['message' => 'Comment deleted successfully']);}
    
    // TODO: If delete failed, return 500 error
        else {
                sendResponse(['error' => 'Failed to delete comment'], 500);
            }

        } catch (PDOException $e) {
            sendResponse(['error' => 'Failed to delete comment: ' . $e->getMessage()], 500);
        }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Get the 'resource' query parameter to determine which resource to access
    
    if (!$resource) {
        sendResponse(['error' => 'Resource parameter is required'], 400);
        exit();
    }
    // TODO: Route based on HTTP method and resource type
    
    if ($method === 'GET') {
        // TODO: Handle GET requests
        
        if ($resource === 'assignments') {
            // TODO: Check if 'id' query parameter exists
            if ($id) {
                getAssignmentById($db, $id);
            } else {
                getAllAssignments($db, $search, $sort, $order);
            }
        } elseif ($resource === 'comments') {
            // TODO: Check if 'assignment_id' query parameter exists
            if ($assignmentId) {
            getCommentsByAssignment($db, $assignmentId);
        }else {
                sendResponse(['error' => 'assignment_id parameter is required for comments'], 400);
            } else {
            // TODO: Invalid resource, return 400 error
            sendResponse(['error' => 'Invalid resource type'], 400);
        }
        
    } elseif ($method === 'POST') {
        // TODO: Handle POST requests (create operations)
        
        if ($resource === 'assignments') {
            // TODO: Call createAssignment($db, $data)
            createAssignment($db, $data);
        } elseif ($resource === 'comments') {
            // TODO: Call createComment($db, $data)
            createComment($db, $data);
        } else {
            // TODO: Invalid resource, return 400 error
            sendResponse(['error' => 'Invalid resource type'], 400);
        }
        
    } elseif ($method === 'PUT') {
        // TODO: Handle PUT requests (update operations)
        
        if ($resource === 'assignments') {
            // TODO: Call updateAssignment($db, $data)
            updateAssignment($db, $data);
        } else {
            // TODO: PUT not supported for other resources
            sendResponse(['error' => 'PUT method not supported for this resource'], 405);
        }
        
    } elseif ($method === 'DELETE') {
        // TODO: Handle DELETE requests
        
        if ($resource === 'assignments') {
            // TODO: Get 'id' from query parameter or request body
            $deleteId = $id ?? ($data['id'] ?? null);
            if (!$deleteId) {
                sendResponse(['error' => 'Assignment ID is required'], 400);
                exit();
            }
            deleteAssignment($db, $deleteId);
        } elseif ($resource === 'comments') {
            // TODO: Get comment 'id' from query parameter
            if (!$id) {
                sendResponse(['error' => 'Comment ID is required'], 400);
                exit();
            }
            deleteComment($db, $id);
        } else {
            // TODO: Invalid resource, return 400 error
            sendResponse(['error' => 'Invalid resource type'], 400);
        }
        
    } else {
        // TODO: Method not supported
        sendResponse(['error' => 'Method not supported'], 405);
    }
    }

}
 catch (PDOException $e) {
    // TODO: Handle database errors
    sendResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
    } 


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response and exit
 * 
 * @param array $data - Data to send as JSON
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    http_response_code($statusCode);
    
    // TODO: Ensure data is an array
    if (!is_array($data)) {
        $data = ['data' => $data];
    }
    
    // TODO: Echo JSON encoded data
    echo json_encode($data, JSON_PRETTY_PRINT);
    
    // TODO: Exit to prevent further execution
     exit();
}


/**
 * Helper function to sanitize string input
 * 
 * @param string $data - Input data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace from beginning and end
    $data = trim($data);
    
    // TODO: Remove HTML and PHP tags
    $data = strip_tags($data);
    
    // TODO: Convert special characters to HTML entities
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // TODO: Return the sanitized data
    return $data;
}


/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDate($date) {
    // TODO: Use DateTime::createFromFormat to validate
    $d = DateTime::createFromFormat('Y-m-d', $date);
    
    // TODO: Return true if valid, false otherwise
    return $d && $d->format('Y-m-d') === $date;
}


/**
 * Helper function to validate allowed values (for sort fields, order, etc.)
 * 
 * @param string $value - Value to validate
 * @param array $allowedValues - Array of allowed values
 * @return bool - True if valid, false otherwise
 */
function validateAllowedValue($value, $allowedValues) {
    // TODO: Check if $value exists in $allowedValues array
    
    $isValid = in_array(strtolower($value), array_map('strtolower', $allowedValues));
    // TODO: Return the result
    return $isValid;
}

?>
