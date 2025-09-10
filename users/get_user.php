<?php
// get_user.php - Get individual user data by ID
include '../config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if UserID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    json_response(false, 'UserID parameter is required');
    exit;
}

try {
    $user_id = (int) $_GET['id'];

    // SQL query to get specific user (excluding password hash for security)
    $sql = "SELECT UserID, Username, Email, Status, CreatedAt FROM users WHERE UserID = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Convert UserID to integer
        $user['UserID'] = (int) $user['UserID'];

        json_response(true, 'User retrieved successfully', $user);

    } else {
        json_response(false, 'User not found', null);
    }

    $stmt->close();

} catch (Exception $e) {
    json_response(false, 'Error retrieving user: ' . $e->getMessage());
}

// Close connection
$conn->close();
?>