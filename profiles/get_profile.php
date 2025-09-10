<?php
// get_profile.php - Retrieve profile by UserID or ProfileID
include '../config.php';

header('Content-Type: application/json');

// Support both 'user_id' and 'UserID' parameter names for flexibility
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : (isset($_GET['UserID']) ? (int)$_GET['UserID'] : null);
$profileId = isset($_GET['profile_id']) ? (int)$_GET['profile_id'] : (isset($_GET['ProfileID']) ? (int)$_GET['ProfileID'] : null);

if (!$userId && !$profileId) {
    json_response(false, 'Provide user_id/UserID or profile_id/ProfileID');
    exit;
}

try {
    if ($userId) {
        // Join with users table to get complete information including account details and creation date
        $stmt = $conn->prepare('SELECT p.ProfileID, p.UserID, p.FullName, p.Address, p.PhoneNumber, p.DateOfBirth, p.Avatar, p.UpdatedAt, u.Username, u.Email, u.Status, u.CreatedAt FROM profiles p JOIN users u ON p.UserID = u.UserID WHERE p.UserID = ?');
        $stmt->bind_param('i', $userId);
    } else {
        $stmt = $conn->prepare('SELECT p.ProfileID, p.UserID, p.FullName, p.Address, p.PhoneNumber, p.DateOfBirth, p.Avatar, p.UpdatedAt, u.Username, u.Email, u.Status, u.CreatedAt FROM profiles p JOIN users u ON p.UserID = u.UserID WHERE p.ProfileID = ?');
        $stmt->bind_param('i', $profileId);
    }

    if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);

    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        json_response(false, 'Profile not found');
    } else {
        $row = $res->fetch_assoc();
        // Convert to appropriate data types
        $row['ProfileID'] = (int)$row['ProfileID'];
        $row['UserID'] = (int)$row['UserID'];
        
        json_response(true, 'Profile retrieved successfully', $row);
    }
    $stmt->close();
} catch (Exception $e) {
    json_response(false, $e->getMessage());
}

$conn->close();
?>
