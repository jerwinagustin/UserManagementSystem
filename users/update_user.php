<?php
// update_user.php - Update existing user in database
include '../config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Only POST requests allowed');
    exit;
}

// Get JSON input with validation
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
    json_response(false, 'Invalid JSON payload: ' . json_last_error_msg());
    exit;
}

// Validate required fields
if (!isset($input['UserID'])) {
    json_response(false, 'UserID is required');
    exit;
}

try {
    $user_id = (int) $input['UserID'];

    // Check if user exists
    $check_user = $conn->prepare("SELECT Username, Email, Status FROM users WHERE UserID = ?");
    $check_user->bind_param("i", $user_id);
    $check_user->execute();
    $result = $check_user->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("User not found");
    }

    $current_user = $result->fetch_assoc();
    $check_user->close();

    // Begin transaction to group user/profile/roles changes
    $conn->begin_transaction();

    // Build update query dynamically based on provided fields
    $update_fields = array();
    $bind_params = array();
    $bind_types = "";

    // Username update
    if (isset($input['username']) && !empty($input['username'])) {
        $username = sanitize_input($input['username']);

        if (strlen($username) < 3) {
            throw new Exception("Username must be at least 3 characters long");
        }

        // Check if new username already exists (excluding current user)
        $check_username = $conn->prepare("SELECT UserID FROM users WHERE Username = ? AND UserID != ?");
        $check_username->bind_param("si", $username, $user_id);
        $check_username->execute();
        $username_result = $check_username->get_result();

        if ($username_result->num_rows > 0) {
            throw new Exception("Username already exists");
        }
        $check_username->close();

        $update_fields[] = "Username = ?";
        $bind_params[] = $username;
        $bind_types .= "s";
    }

    // Email update
    if (isset($input['email']) && !empty($input['email'])) {
        $email = sanitize_input($input['email']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        // Check if new email already exists (excluding current user)
        $check_email = $conn->prepare("SELECT UserID FROM users WHERE Email = ? AND UserID != ?");
        $check_email->bind_param("si", $email, $user_id);
        $check_email->execute();
        $email_result = $check_email->get_result();

        if ($email_result->num_rows > 0) {
            throw new Exception("Email already exists");
        }
        $check_email->close();

        $update_fields[] = "Email = ?";
        $bind_params[] = $email;
        $bind_types .= "s";
    }

    // Status update
    if (isset($input['status'])) {
        $status = sanitize_input($input['status']);

        if (!in_array($status, ['Active', 'Inactive'])) {
            throw new Exception("Status must be either 'Active' or 'Inactive'");
        }

        $update_fields[] = "Status = ?";
        $bind_params[] = $status;
        $bind_types .= "s";
    }

    // Password update (optional)
    if (isset($input['password']) && !empty($input['password'])) {
        if (strlen($input['password']) < 6) {
            throw new Exception("Password must be at least 6 characters long");
        }

        $password_hash = password_hash($input['password'], PASSWORD_DEFAULT);
        $update_fields[] = "PasswordHash = ?";
        $bind_params[] = $password_hash;
        $bind_types .= "s";
    }

    // Profile fields detection (optional partial)
    $profileFullName = isset($input['fullName']) ? sanitize_input($input['fullName']) : null;
    $profileAddress = isset($input['address']) ? sanitize_input($input['address']) : null;
    $profilePhone = isset($input['phoneNumber']) ? sanitize_input($input['phoneNumber']) : null;
    $profileDob = isset($input['dateOfBirth']) ? sanitize_input($input['dateOfBirth']) : null;
    $profileAvatar = isset($input['avatar']) ? sanitize_input($input['avatar']) : null;
    $profileProvided = ($profileFullName !== null || $profileAddress !== null || $profilePhone !== null || $profileDob !== null || $profileAvatar !== null);

    $rolesProvided = isset($input['roles']) && is_array($input['roles']);

    if (empty($update_fields) && !$profileProvided && !$rolesProvided) {
        throw new Exception("No valid fields provided for update");
    }

    // Add UserID to bind parameters
    $bind_params[] = $user_id;
    $bind_types .= "i";

    // Build and execute update query
    $sql = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE UserID = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param($bind_types, ...$bind_params);

    if ($stmt->execute()) {
        // Upsert profile if provided
        if ($profileProvided) {
            $pchk = $conn->prepare("SELECT ProfileID FROM profiles WHERE UserID = ?");
            $pchk->bind_param("i", $user_id);
            $pchk->execute();
            $pres = $pchk->get_result();
            $hasProfile = $pres->num_rows > 0;
            $pchk->close();

            if ($hasProfile) {
                $pupd = $conn->prepare("UPDATE profiles SET FullName = COALESCE(?, FullName), Address = COALESCE(?, Address), PhoneNumber = COALESCE(?, PhoneNumber), DateOfBirth = COALESCE(?, DateOfBirth), Avatar = COALESCE(?, Avatar) WHERE UserID = ?");
                $pupd->bind_param("sssssi", $profileFullName, $profileAddress, $profilePhone, $profileDob, $profileAvatar, $user_id);
                if (!$pupd->execute()) { throw new Exception('Profile update failed: ' . $pupd->error); }
                $pupd->close();
            } else {
                $pins = $conn->prepare("INSERT INTO profiles (UserID, FullName, Address, PhoneNumber, DateOfBirth, Avatar) VALUES (?, ?, ?, ?, ?, ?)");
                $pins->bind_param("isssss", $user_id, $profileFullName, $profileAddress, $profilePhone, $profileDob, $profileAvatar);
                if (!$pins->execute()) { throw new Exception('Profile insert failed: ' . $pins->error); }
                $pins->close();
            }
        }

        // Replace roles if provided
        if ($rolesProvided) {
            $rolesArr = array_map('intval', $input['roles']);
            $delRoles = $conn->prepare("DELETE FROM userroles WHERE UserID = ?");
            $delRoles->bind_param("i", $user_id);
            if (!$delRoles->execute()) { throw new Exception('Clearing roles failed: ' . $delRoles->error); }
            $delRoles->close();
            if (!empty($rolesArr)) {
                $insRole = $conn->prepare("INSERT INTO userroles (UserID, RoleID) VALUES (?, ?)");
                foreach ($rolesArr as $rid) {
                    $insRole->bind_param("ii", $user_id, $rid);
                    if (!$insRole->execute()) { throw new Exception('Assign role ' . $rid . ' failed: ' . $insRole->error); }
                }
                $insRole->close();
            }
        }

        // Get updated user data
        $get_updated = $conn->prepare("SELECT UserID, Username, Email, Status, CreatedAt FROM users WHERE UserID = ?");
        $get_updated->bind_param("i", $user_id);
        $get_updated->execute();
        $updated_result = $get_updated->get_result();
        $updated_user = $updated_result->fetch_assoc();
        $get_updated->close();

        // Retrieve profile data if exists
        $profileData = null;
        $gp = $conn->prepare("SELECT FullName, Address, PhoneNumber, DateOfBirth, Avatar FROM profiles WHERE UserID = ?");
        $gp->bind_param("i", $user_id);
        $gp->execute();
        $gpr = $gp->get_result();
        if ($gpr->num_rows > 0) { $profileData = $gpr->fetch_assoc(); }
        $gp->close();

        // Retrieve roles
        $rolesData = [];
        $gr = $conn->prepare("SELECT RoleID FROM userroles WHERE UserID = ?");
        $gr->bind_param("i", $user_id);
        $gr->execute();
        $grr = $gr->get_result();
        while ($r = $grr->fetch_assoc()) { $rolesData[] = (int)$r['RoleID']; }
        $gr->close();

        // Log the activity
        $changes = array();
        if (isset($username) && $username !== $current_user['Username']) {
            $changes[] = "username changed to: $username";
        }
        if (isset($email) && $email !== $current_user['Email']) {
            $changes[] = "email changed to: $email";
        }
        if (isset($status) && $status !== $current_user['Status']) {
            $changes[] = "status changed to: $status";
        }
        if (isset($password_hash)) {
            $changes[] = "password updated";
        }

        $change_details = implode(", ", $changes);
        log_activity($user_id, 'User Updated', "User profile updated: $change_details");

        // Commit transaction
        $conn->commit();

        json_response(true, 'User updated successfully', array(
            'UserID' => (int) $updated_user['UserID'],
            'Username' => $updated_user['Username'],
            'Email' => $updated_user['Email'],
            'Status' => $updated_user['Status'],
            'CreatedAt' => $updated_user['CreatedAt'],
            'Profile' => $profileData,
            'Roles' => $rolesData
        ));

    } else {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $stmt->close();

} catch (Exception $e) {
    if ($conn->errno) { $conn->rollback(); }
    json_response(false, $e->getMessage());
}

// Close connection
$conn->close();
?>