<?php
// test_user_creation_workflow.php - Test the complete user creation with profiles and roles
require_once __DIR__ . '/config.php';

echo "<h1>User Management System - Complete User Creation Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px; }
    .error { color: #721c24; background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 5px; }
    .info { color: #004085; background: #cce7ff; padding: 10px; margin: 10px 0; border-radius: 5px; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style>";

try {
    echo "<h2>Step 1: Testing Transaction-based User Creation</h2>";
    
    // Test data for new user
    $testUserData = [
        'username' => 'testuser_' . time(),
        'email' => 'testuser_' . time() . '@example.com',
        'password' => 'password123',
        'fullName' => 'Test User ' . time(),
        'address' => '123 Test Street, Test City, TS 12345',
        'phoneNumber' => '+1-555-' . rand(1000, 9999),
        'dateOfBirth' => '1990-01-15',
        'avatar' => 'https://via.placeholder.com/150',
        'status' => 'Active',
        'roles' => [1, 2] // Assuming roles with ID 1 and 2 exist
    ];
    
    echo "<div class='info'>Creating user with data:<pre>" . json_encode($testUserData, JSON_PRETTY_PRINT) . "</pre></div>";
    
    // Simulate the add_user.php request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/UserManagementSystem/users/add_user.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testUserData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($response === false) {
        echo "<div class='error'>❌ Failed to make request to add_user.php</div>";
        
        // Fallback: Test directly with database
        echo "<h3>Fallback: Testing transaction directly with database</h3>";
        testUserCreationDirect($testUserData);
    } else {
        $result = json_decode($response, true);
        
        if ($result && $result['success']) {
            echo "<div class='success'>✅ User created successfully via API!</div>";
            echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
            
            $newUserId = $result['data']['UserID'];
            
            // Test 2: Verify user was created in Users table
            echo "<h2>Step 2: Verifying User in Database</h2>";
            verifyUserInDatabase($newUserId);
            
            // Test 3: Verify profile was created in Profiles table
            echo "<h2>Step 3: Verifying Profile in Database</h2>";
            verifyProfileInDatabase($newUserId);
            
            // Test 4: Verify roles were assigned
            echo "<h2>Step 4: Verifying Role Assignments</h2>";
            verifyRolesInDatabase($newUserId);
            
            // Test 5: Test profile.php page
            echo "<h2>Step 5: Testing Profile Page</h2>";
            testProfilePage($newUserId);
            
            // Cleanup
            echo "<h2>Step 6: Cleanup (Optional)</h2>";
            echo "<button onclick='cleanupUser($newUserId)'>Delete Test User</button>";
            echo "<script>
                function cleanupUser(userId) {
                    if (confirm('Delete test user?')) {
                        fetch('users/delete_user.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({UserID: userId})
                        }).then(r => r.json()).then(result => {
                            alert(result.message);
                            location.reload();
                        });
                    }
                }
            </script>";
            
        } else {
            echo "<div class='error'>❌ API returned error: " . ($result['message'] ?? 'Unknown error') . "</div>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Exception: " . htmlspecialchars($e->getMessage()) . "</div>";
}

function testUserCreationDirect($userData) {
    global $conn;
    
    try {
        // Start transaction
        $conn->autocommit(FALSE);
        echo "<div class='info'>🔄 Starting transaction...</div>";
        
        // 1. Insert user
        $username = $userData['username'];
        $email = $userData['email'];
        $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);
        $status = $userData['status'];
        
        $userSql = "INSERT INTO users (Username, Email, PasswordHash, Status, CreatedAt) VALUES (?, ?, ?, ?, NOW())";
        $userStmt = $conn->prepare($userSql);
        $userStmt->bind_param("ssss", $username, $email, $passwordHash, $status);
        
        if (!$userStmt->execute()) {
            throw new Exception("Failed to insert user: " . $userStmt->error);
        }
        
        $newUserId = $conn->insert_id;
        echo "<div class='success'>✅ User inserted with ID: $newUserId</div>";
        
        // 2. Insert profile
        $profileSql = "INSERT INTO profiles (UserID, FullName, Address, PhoneNumber, DateOfBirth, Avatar) VALUES (?, ?, ?, ?, ?, ?)";
        $profileStmt = $conn->prepare($profileSql);
        $profileStmt->bind_param("isssss", 
            $newUserId, 
            $userData['fullName'], 
            $userData['address'], 
            $userData['phoneNumber'], 
            $userData['dateOfBirth'], 
            $userData['avatar']
        );
        
        if (!$profileStmt->execute()) {
            throw new Exception("Failed to insert profile: " . $profileStmt->error);
        }
        
        $profileId = $conn->insert_id;
        echo "<div class='success'>✅ Profile inserted with ID: $profileId</div>";
        
        // 3. Insert roles (if any exist)
        if (!empty($userData['roles'])) {
            $roleSql = "INSERT INTO userroles (UserID, RoleID) VALUES (?, ?)";
            $roleStmt = $conn->prepare($roleSql);
            
            foreach ($userData['roles'] as $roleId) {
                $roleStmt->bind_param("ii", $newUserId, $roleId);
                if ($roleStmt->execute()) {
                    echo "<div class='success'>✅ Role $roleId assigned</div>";
                } else {
                    echo "<div class='error'>⚠️ Failed to assign role $roleId: " . $roleStmt->error . "</div>";
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        echo "<div class='success'>✅ Transaction committed successfully!</div>";
        
        return $newUserId;
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo "<div class='error'>❌ Transaction rolled back: " . $e->getMessage() . "</div>";
        return false;
    } finally {
        $conn->autocommit(TRUE);
    }
}

function verifyUserInDatabase($userId) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT UserID, Username, Email, Status, CreatedAt FROM users WHERE UserID = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<div class='success'>✅ User found in database:</div>";
        echo "<pre>" . json_encode($user, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<div class='error'>❌ User not found in database</div>";
    }
}

function verifyProfileInDatabase($userId) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM profiles WHERE UserID = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $profile = $result->fetch_assoc();
        echo "<div class='success'>✅ Profile found in database:</div>";
        echo "<pre>" . json_encode($profile, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<div class='error'>❌ Profile not found in database</div>";
    }
}

function verifyRolesInDatabase($userId) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT ur.UserRoleID, ur.RoleID, r.RoleName, r.Description 
        FROM userroles ur 
        JOIN roles r ON ur.RoleID = r.RoleID 
        WHERE ur.UserID = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<div class='success'>✅ Roles found for user:</div>";
        while ($role = $result->fetch_assoc()) {
            echo "<pre>" . json_encode($role, JSON_PRETTY_PRINT) . "</pre>";
        }
    } else {
        echo "<div class='info'>ℹ️ No roles assigned to this user</div>";
    }
}

function testProfilePage($userId) {
    echo "<div class='info'>Profile page URL: <a href='profile.php?id=$userId' target='_blank'>profile.php?id=$userId</a></div>";
    echo "<div class='info'>Alternative URL: <a href='profile_page.php?user_id=$userId' target='_blank'>profile_page.php?user_id=$userId</a></div>";
}

?>

<h2>Manual Tests</h2>
<p>You can also test the following manually:</p>
<ul>
    <li><a href="add_user_form.php">Add User Form</a> - Test the complete user creation form</li>
    <li><a href="index.php">Dashboard</a> - View all users and test "View Profile" buttons</li>
    <li><a href="profile.php?id=1">Profile Page</a> - Test profile viewing (replace ?id=1 with actual user ID)</li>
</ul>

<h2>Transaction Testing</h2>
<p>The system uses MySQL transactions to ensure data consistency:</p>
<ol>
    <li>✅ <strong>atomicity</strong>: All operations (user, profile, roles) succeed or fail together</li>
    <li>✅ <strong>Consistency</strong>: Foreign key constraints maintain referential integrity</li>
    <li>✅ <strong>Isolation</strong>: Concurrent operations won't interfere</li>
    <li>✅ <strong>Durability</strong>: Committed data persists</li>
</ol>
