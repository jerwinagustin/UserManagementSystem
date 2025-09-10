<?php
// config.php - Database connection configuration
$servername = "localhost";
$username = "root";  // Default XAMPP username
$password = "";      // Default XAMPP password (usually empty)
$dbname = "usersystem"; // NOTE: Ensure this matches your actual database name. SQL dump uses `users`.

// First connect without specifying DB to allow auto-create if missing
$bootstrapConn = new mysqli($servername, $username, $password);
if ($bootstrapConn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database bootstrap connection failed',
        'error' => $bootstrapConn->connect_error
    ]);
    exit;
}

// Attempt to select DB; if it fails, create it (simple safeguard)
if (!$bootstrapConn->select_db($dbname)) {
    $createDbSql = "CREATE DATABASE `" . $bootstrapConn->real_escape_string($dbname) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
    if ($bootstrapConn->query($createDbSql) === false) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => "Database '$dbname' does not exist and could not be created automatically.",
            'error' => $bootstrapConn->error
        ]);
        exit;
    }
}
$bootstrapConn->close();

// Now connect directly to the database
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => $conn->connect_error
    ]);
    exit;
}

$conn->set_charset("utf8mb4");

// Install global error & exception handlers to avoid HTML error output
if (!function_exists('ums_json_error_handler')) {
    function ums_json_error_handler($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) { return; }
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Internal server error',
            'error' => [
                'type' => 'php_error',
                'severity' => $severity,
                'message' => $message,
                'file' => basename($file),
                'line' => $line
            ]
        ]);
        exit;
    }
    set_error_handler('ums_json_error_handler');

    function ums_json_exception_handler($ex) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Unhandled exception',
            'error' => [
                'type' => 'exception',
                'message' => $ex->getMessage(),
                'file' => basename($ex->getFile()),
                'line' => $ex->getLine()
            ]
        ]);
        exit;
    }
    set_exception_handler('ums_json_exception_handler');

    function ums_json_shutdown_handler() {
        $err = error_get_last();
        if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Fatal error',
                'error' => [
                    'type' => 'shutdown',
                    'severity' => $err['type'],
                    'message' => $err['message'],
                    'file' => basename($err['file']),
                    'line' => $err['line']
                ]
            ]);
        }
    }
    register_shutdown_function('ums_json_shutdown_handler');
}

// Function to sanitize input
function sanitize_input($data)
{
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return $conn->real_escape_string($data);
}

// Function to generate JSON response
function json_response($success, $message, $data = null)
{
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
}

// Function to log activity - Insert activity logs into database
function log_activity($user_id, $action, $details = '')
{
    global $conn;
    
    try {
        // Validate inputs
        if (!$user_id || !$action) {
            return false;
        }
        
        // Check if activitylogs table exists
        $table_check = $conn->query("SHOW TABLES LIKE 'activitylogs'");
        if ($table_check->num_rows === 0) {
            return false; // Table doesn't exist
        }
        
        // Prepare the insert statement
        $stmt = $conn->prepare("INSERT INTO activitylogs (UserID, Action, Details, Timestamp) VALUES (?, ?, ?, NOW())");
        if (!$stmt) {
            return false;
        }
        
        // Sanitize inputs
        $action = sanitize_input($action);
        $details = sanitize_input($details);
        
        $stmt->bind_param("iss", $user_id, $action, $details);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
        
    } catch (Exception $e) {
        // Log error but don't break the main operation
        error_log("Activity log error: " . $e->getMessage());
        return false;
    }
}
?>