<?php
// login_tracker.php - Track user login activity
include 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Only POST requests allowed');
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    json_response(false, 'Invalid JSON payload');
    exit;
}

if (!isset($input['username'])) {
    json_response(false, 'Username is required');
    exit;
}

try {
    $username = sanitize_input($input['username']);
    $login_result = isset($input['success']) ? $input['success'] : true;
    $details = isset($input['details']) ? sanitize_input($input['details']) : '';
    
    // Get user ID from username
    $user_stmt = $conn->prepare("SELECT UserID FROM users WHERE Username = ?");
    $user_stmt->bind_param("s", $username);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    if ($user_result->num_rows > 0) {
        $user_data = $user_result->fetch_assoc();
        $user_id = $user_data['UserID'];
        
        if ($login_result) {
            log_activity($user_id, 'Login', "User logged in successfully. $details");
        } else {
            log_activity($user_id, 'Login Failed', "Failed login attempt. $details");
        }
        
        json_response(true, 'Login activity logged');
    } else {
        // Log failed login attempt even for non-existent users (security)
        // Use a generic user ID or create a separate logging mechanism
        json_response(true, 'Login activity noted');
    }
    
    $user_stmt->close();
    
} catch (Exception $e) {
    json_response(false, 'Error logging activity: ' . $e->getMessage());
}

$conn->close();
?>
