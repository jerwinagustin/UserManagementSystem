<?php
// add_profile.php - Create or upsert a user profile (1:1 with users)
include '../config.php';

// Set content type to JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Only POST requests allowed');
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['UserID'])) {
    json_response(false, 'UserID is required');
    exit;
}

try {
    $userId = (int)$input['UserID'];
    $fullName = isset($input['FullName']) ? sanitize_input($input['FullName']) : '';
    $address = isset($input['Address']) ? sanitize_input($input['Address']) : null;
    $phone = isset($input['PhoneNumber']) ? sanitize_input($input['PhoneNumber']) : null;
    $dob = isset($input['DateOfBirth']) ? sanitize_input($input['DateOfBirth']) : null; // Expect YYYY-MM-DD
    $avatar = isset($input['Avatar']) ? sanitize_input($input['Avatar']) : null; // Path or URL

    if ($fullName === '') {
        throw new Exception('FullName is required');
    }

    // Validate DOB format if provided
    if ($dob && !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $dob)) {
        throw new Exception('DateOfBirth must be in YYYY-MM-DD format');
    }

    // Ensure user exists
    $checkUser = $conn->prepare('SELECT UserID FROM users WHERE UserID = ?');
    $checkUser->bind_param('i', $userId);
    $checkUser->execute();
    $resUser = $checkUser->get_result();
    if ($resUser->num_rows === 0) {
        throw new Exception('User not found');
    }
    $checkUser->close();

    // Check if profile already exists -> upsert style logic
    $checkProfile = $conn->prepare('SELECT ProfileID FROM profiles WHERE UserID = ?');
    $checkProfile->bind_param('i', $userId);
    $checkProfile->execute();
    $resProf = $checkProfile->get_result();
    $existing = $resProf->num_rows > 0;
    $profileId = null;
    $checkProfile->close();

    if ($existing) {
        // Update existing profile
        $stmt = $conn->prepare('UPDATE profiles SET FullName=?, Address=?, PhoneNumber=?, DateOfBirth=?, Avatar=? WHERE UserID=?');
        if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);
        $stmt->bind_param('sssssi', $fullName, $address, $phone, $dob, $avatar, $userId);
        if (!$stmt->execute()) throw new Exception('Execute failed: ' . $stmt->error);
        $stmt->close();
        // Get ProfileID
        $getPid = $conn->prepare('SELECT ProfileID FROM profiles WHERE UserID=?');
        $getPid->bind_param('i', $userId);
        $getPid->execute();
        $profileId = $getPid->get_result()->fetch_assoc()['ProfileID'];
        $getPid->close();
        $actionMsg = 'Profile updated successfully';
    } else {
        $stmt = $conn->prepare('INSERT INTO profiles (UserID, FullName, Address, PhoneNumber, DateOfBirth, Avatar) VALUES (?,?,?,?,?,?)');
        if (!$stmt) throw new Exception('Prepare failed: ' . $conn->error);
        $stmt->bind_param('isssss', $userId, $fullName, $address, $phone, $dob, $avatar);
        if (!$stmt->execute()) throw new Exception('Execute failed: ' . $stmt->error);
        $profileId = $conn->insert_id;
        $stmt->close();
        $actionMsg = 'Profile created successfully';
    }

    json_response(true, $actionMsg, [
        'ProfileID' => (int)$profileId,
        'UserID' => $userId,
        'FullName' => $fullName,
        'Address' => $address,
        'PhoneNumber' => $phone,
        'DateOfBirth' => $dob,
        'Avatar' => $avatar
    ]);
} catch (Exception $e) {
    json_response(false, $e->getMessage());
}

$conn->close();
?>
