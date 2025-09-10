<?php
// delete_user.php - Delete user from database
include '../config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Allow DELETE or POST for broader client compatibility
if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
    json_response(false, 'Only POST or DELETE requests allowed');
    exit;
}

$userIdInput = null;
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Support /delete_user.php?UserID=123 for simple clients
    if (isset($_GET['UserID'])) { $userIdInput = (int)$_GET['UserID']; }
    else {
        // Some clients send JSON body with DELETE
        $raw = file_get_contents('php://input');
        if ($raw) {
            $decoded = json_decode($raw, true);
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                json_response(false, 'Invalid JSON payload: ' . json_last_error_msg());
                exit;
            }
            if (isset($decoded['UserID'])) { $userIdInput = (int)$decoded['UserID']; }
        }
    }
} else { // POST
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw, true);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        json_response(false, 'Invalid JSON payload: ' . json_last_error_msg());
        exit;
    }
    if (isset($decoded['UserID'])) { $userIdInput = (int)$decoded['UserID']; }
}

if (!$userIdInput) {
    json_response(false, 'UserID is required');
    exit;
}

try {
    $user_id = $userIdInput;

    // Check if user exists and get user details for logging
    $check_user = $conn->prepare("SELECT Username, Email FROM users WHERE UserID = ?");
    $check_user->bind_param("i", $user_id);
    $check_user->execute();
    $result = $check_user->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("User not found");
    }

    $user_data = $result->fetch_assoc();
    $username = $user_data['Username'];
    $email = $user_data['Email'];
    $check_user->close();

    // Begin transaction for safe deletion
    $conn->begin_transaction();

    try {
        // Optional: Delete related data first (profiles, user roles, etc.)
        // This prevents foreign key constraint errors if they exist

        // Delete user profiles if profiles table exists
        $delete_profiles = $conn->prepare("DELETE FROM profiles WHERE UserID = ?");
        if ($delete_profiles) {
            $delete_profiles->bind_param("i", $user_id);
            $delete_profiles->execute();
            $delete_profiles->close();
        }

    // Delete user roles if userroles table exists
    $delete_user_roles = $conn->prepare("DELETE FROM userroles WHERE UserID = ?");
        if ($delete_user_roles) {
            $delete_user_roles->bind_param("i", $user_id);
            $delete_user_roles->execute();
            $delete_user_roles->close();
        }

        // Log the deletion activity before deleting the user
        log_activity($user_id, 'User Deleted', "User account deleted: $username ($email)");

        // Delete the main user record
        $delete_user = $conn->prepare("DELETE FROM users WHERE UserID = ?");
        $delete_user->bind_param("i", $user_id);

        if (!$delete_user->execute()) {
            throw new Exception("Failed to delete user: " . $delete_user->error);
        }

        $affected_rows = $delete_user->affected_rows;
        $delete_user->close();

        if ($affected_rows === 0) {
            throw new Exception("No user was deleted");
        }

        // Commit the transaction
        $conn->commit();

        json_response(true, 'User deleted successfully', array(
            'deletedUserID' => $user_id,
            'deletedUsername' => $username,
            'deletedEmail' => $email
        ));

    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    json_response(false, $e->getMessage());
}

// Close connection
$conn->close();
?>