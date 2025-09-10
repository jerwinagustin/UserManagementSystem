<?php
// get_users.php - Retrieve all users from database
include '../config.php';

// Set content type to JSON
header('Content-Type: application/json');

try {
    // SQL query to get all users
    $sql = "SELECT UserID, Username, Email, Status, CreatedAt FROM users ORDER BY CreatedAt DESC";

    $result = $conn->query($sql);

    if ($result) {
        $users = array();

        while ($row = $result->fetch_assoc()) {
            $users[] = array(
                'UserID' => (int) $row['UserID'],
                'Username' => $row['Username'],
                'Email' => $row['Email'],
                'Status' => $row['Status'],
                'CreatedAt' => $row['CreatedAt']
            );
        }

        // Return successful response
        echo json_encode(array(
            'success' => true,
            'message' => 'Users retrieved successfully',
            'users' => $users,
            'total' => count($users)
        ));

    } else {
        throw new Exception("Error executing query: " . $conn->error);
    }

} catch (Exception $e) {
    // Return error response
    echo json_encode(array(
        'success' => false,
        'message' => 'Error retrieving users: ' . $e->getMessage(),
        'users' => array(),
        'total' => 0
    ));
}

// Close connection
$conn->close();
?>