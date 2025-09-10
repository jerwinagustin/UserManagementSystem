<?php
// add_user.php - Add new user to database with profile
include '../config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Only POST requests allowed');
    exit;
}

// Get JSON input and guard invalid JSON
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
    json_response(false, 'Invalid JSON payload: ' . json_last_error_msg());
    exit;
}

// Validate required fields
if (!isset($input['username']) || !isset($input['email']) || !isset($input['password']) || !isset($input['fullName'])) {
    json_response(false, 'Username, email, password, and full name are required');
    exit;
}

// Validate roleId if provided
if (isset($input['roleId']) && !empty($input['roleId']) && !is_numeric($input['roleId'])) {
    json_response(false, 'Invalid role ID');
    exit;
}

try {
    // Start transaction for atomicity
    $conn->autocommit(FALSE);
    
    // Sanitize inputs
    $username = sanitize_input($input['username']);
    $email = sanitize_input($input['email']);
    $password = $input['password']; // Don't sanitize password before hashing
    $status = isset($input['status']) ? sanitize_input($input['status']) : 'Active';
    
    // Profile fields - provide defaults for NOT NULL columns
    $fullName = sanitize_input($input['fullName']);
    $address = isset($input['address']) && !empty($input['address']) ? sanitize_input($input['address']) : '';
    $phoneNumber = isset($input['phoneNumber']) && !empty($input['phoneNumber']) ? sanitize_input($input['phoneNumber']) : '';
    $dateOfBirth = isset($input['dateOfBirth']) && !empty($input['dateOfBirth']) ? sanitize_input($input['dateOfBirth']) : '';
    $avatar = isset($input['avatar']) && !empty($input['avatar']) ? sanitize_input($input['avatar']) : '';
    $roleId = isset($input['roleId']) && !empty($input['roleId']) ? (int)$input['roleId'] : null;

    // Validation
    if (strlen($username) < 3) {
        throw new Exception("Username must be at least 3 characters long");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    if (strlen($password) < 6) {
        throw new Exception("Password must be at least 6 characters long");
    }

    if (strlen($fullName) < 2) {
        throw new Exception("Full name must be at least 2 characters long");
    }

    if (!in_array($status, ['Active', 'Inactive'])) {
        $status = 'Active';
    }

    // Validate date of birth format if provided
    if ($dateOfBirth && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOfBirth)) {
        throw new Exception('Date of birth must be in YYYY-MM-DD format');
    }

    // Check if username already exists
    $check_username = $conn->prepare("SELECT UserID FROM users WHERE Username = ?");
    $check_username->bind_param("s", $username);
    $check_username->execute();
    $result = $check_username->get_result();

    if ($result->num_rows > 0) {
        throw new Exception("Username already exists");
    }
    $check_username->close();

    // Check if email already exists
    $check_email = $conn->prepare("SELECT UserID FROM users WHERE Email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $result = $check_email->get_result();

    if ($result->num_rows > 0) {
        throw new Exception("Email already exists");
    }
    $check_email->close();

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $sql = "INSERT INTO users (Username, Email, PasswordHash, Status, CreatedAt) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssss", $username, $email, $password_hash, $status);

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $new_user_id = $conn->insert_id;
    $stmt->close();

    // Create profile for the new user
    $profile_sql = "INSERT INTO profiles (UserID, FullName, Address, PhoneNumber, DateOfBirth, Avatar) VALUES (?, ?, ?, ?, ?, ?)";
    $profile_stmt = $conn->prepare($profile_sql);
    
    if (!$profile_stmt) {
        throw new Exception("Profile prepare failed: " . $conn->error);
    }
    
    $profile_stmt->bind_param("isssss", $new_user_id, $fullName, $address, $phoneNumber, $dateOfBirth, $avatar);
    
    if (!$profile_stmt->execute()) {
        throw new Exception("Profile creation failed: " . $profile_stmt->error);
    }
    
    $profile_id = $conn->insert_id;
    $profile_stmt->close();

    // Assign role if provided
    $rolesAssigned = 0;
    if ($roleId !== null) {
        // Verify the role exists
        $role_check = $conn->prepare("SELECT RoleID FROM roles WHERE RoleID = ?");
        $role_check->bind_param("i", $roleId);
        $role_check->execute();
        $role_result = $role_check->get_result();
        
        if ($role_result->num_rows > 0) {
            $role_sql = "INSERT INTO userroles (UserID, RoleID) VALUES (?, ?)";
            $role_stmt = $conn->prepare($role_sql);
            
            if (!$role_stmt) {
                throw new Exception("Role assignment prepare failed: " . $conn->error);
            }
            
            $role_stmt->bind_param("ii", $new_user_id, $roleId);
            if (!$role_stmt->execute()) {
                throw new Exception("Role assignment failed: " . $role_stmt->error);
            }
            $role_stmt->close();
            $rolesAssigned = 1;
        } else {
            throw new Exception("Invalid role ID: Role does not exist");
        }
        $role_check->close();
    }

    // Commit transaction
    $conn->commit();

    // Log the activity
    log_activity($new_user_id, 'User Created', "New user account and profile created: $username");

    // Return success response
    json_response(true, 'User and profile created successfully', array(
        'UserID' => $new_user_id,
        'ProfileID' => $profile_id,
        'Username' => $username,
        'Email' => $email,
        'FullName' => $fullName,
        'Status' => $status,
        'RolesAssigned' => $rolesAssigned
    ));

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    json_response(false, $e->getMessage());
} finally {
    // Restore autocommit
    $conn->autocommit(TRUE);
}

$conn->close();
?>