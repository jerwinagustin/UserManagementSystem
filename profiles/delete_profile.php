<?php
// delete_profile.php - Remove a profile (User record stays unless separately deleted)
include '../config.php';

// Set content type to JSON
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

$profileId = isset($input['ProfileID']) ? (int)$input['ProfileID'] : null;
$userId = isset($input['UserID']) ? (int)$input['UserID'] : null;

if (!$profileId && !$userId) {
    json_response(false, 'Provide ProfileID or UserID');
    exit;
}

try {
    // First, get the profile information for logging
    if ($profileId) {
        $getStmt = $conn->prepare('SELECT UserID, FullName FROM profiles WHERE ProfileID = ?');
        $getStmt->bind_param('i', $profileId);
    } else {
        $getStmt = $conn->prepare('SELECT ProfileID, FullName FROM profiles WHERE UserID = ?');
        $getStmt->bind_param('i', $userId);
    }
    
    $getStmt->execute();
    $result = $getStmt->get_result();
    
    if ($result->num_rows === 0) {
        json_response(false, 'Profile not found');
        exit;
    }
    
    $profileData = $result->fetch_assoc();
    $targetUserId = $profileId ? $profileData['UserID'] : $userId;
    $targetProfileId = $userId ? $profileData['ProfileID'] : $profileId;
    $fullName = $profileData['FullName'];
    $getStmt->close();

    // Delete the profile
    if ($profileId) {
        $stmt = $conn->prepare('DELETE FROM profiles WHERE ProfileID = ?');
        $stmt->bind_param('i', $profileId);
    } else {
        $stmt = $conn->prepare('DELETE FROM profiles WHERE UserID = ?');
        $stmt->bind_param('i', $userId);
    }

    if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);

    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected === 0) {
        json_response(false, 'Profile could not be deleted');
    } else {
        // Log the activity
        log_activity($targetUserId, 'Profile Deleted', "Profile for $fullName was deleted");
        
        json_response(true, 'Profile deleted successfully', ['deleted' => $affected, 'ProfileID' => $targetProfileId]);
    }

} catch (Exception $e) {
    json_response(false, $e->getMessage());
}

$conn->close();
?>
