<?php
// update_profile.php - Partial update for an existing profile (by UserID or ProfileID)
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

$userId = isset($input['UserID']) ? (int)$input['UserID'] : null;
$profileId = isset($input['ProfileID']) ? (int)$input['ProfileID'] : null;

if (!$userId && !$profileId) {
    json_response(false, 'Provide UserID or ProfileID');
    exit;
}

try {
    // Determine target profile
    if ($profileId) {
        $q = $conn->prepare('SELECT ProfileID, UserID FROM profiles WHERE ProfileID = ?');
        $q->bind_param('i', $profileId);
    } else {
        $q = $conn->prepare('SELECT ProfileID, UserID FROM profiles WHERE UserID = ?');
        $q->bind_param('i', $userId);
    }
    $q->execute();
    $res = $q->get_result();
    if ($res->num_rows === 0) throw new Exception('Profile not found');
    $profileRow = $res->fetch_assoc();
    $profileId = (int)$profileRow['ProfileID'];
    $userId = (int)$profileRow['UserID'];
    $q->close();

    $fields = [];
    $params = [];
    $types = '';

    $map = [
        'FullName' => 's',
        'Address' => 's',
        'PhoneNumber' => 's',
        'DateOfBirth' => 's', // Expect YYYY-MM-DD format
        'Avatar' => 's'
    ];

    foreach ($map as $key => $t) {
        if (array_key_exists($key, $input)) {
            // Validate FullName is not empty if provided
            if ($key === 'FullName' && trim($input[$key]) === '') {
                throw new Exception('FullName cannot be empty');
            }
            
            // Validate DateOfBirth format if provided
            if ($key === 'DateOfBirth' && $input[$key] && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $input[$key])) {
                throw new Exception('DateOfBirth must be in YYYY-MM-DD format');
            }
            
            $fields[] = "$key = ?";
            $params[] = $input[$key] ? sanitize_input($input[$key]) : null;
            $types .= $t;
        }
    }

    if (empty($fields)) {
        json_response(false, 'No updatable fields provided');
        exit;
    }

    $sql = 'UPDATE profiles SET ' . implode(', ', $fields) . ' WHERE ProfileID = ?';
    $types .= 'i';
    $params[] = $profileId;

    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);

    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) throw new Exception('Execute failed: ' . $stmt->error);

    $stmt->close();
    
    // Log the activity
    log_activity($userId, 'Profile Updated', 'User profile information was updated');

    // Return full updated record with user information
    $g = $conn->prepare('SELECT p.ProfileID, p.UserID, p.FullName, p.Address, p.PhoneNumber, p.DateOfBirth, p.Avatar, p.UpdatedAt, u.Username, u.Email, u.Status FROM profiles p JOIN users u ON p.UserID = u.UserID WHERE p.ProfileID = ?');
    $g->bind_param('i', $profileId);
    $g->execute();
    $row = $g->get_result()->fetch_assoc();
    $g->close();

    $row['ProfileID'] = (int)$row['ProfileID'];
    $row['UserID'] = (int)$row['UserID'];

    json_response(true, 'Profile updated successfully', $row);
} catch (Exception $e) {
    json_response(false, $e->getMessage());
}

$conn->close();
?>
