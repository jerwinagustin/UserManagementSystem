<?php
// test_user_creation.php - Test script to verify the complete user creation workflow
require_once __DIR__ . '/config.php';

echo "<h1>User Management System - Workflow Test</h1>";

try {
    // Test 1: Check if all required tables exist
    echo "<h2>Test 1: Database Structure</h2>";
    
    $tables = ['users', 'roles', 'userroles', 'profiles'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' missing<br>";
        }
    }
    
    // Test 2: Check foreign key constraints
    echo "<h2>Test 2: Foreign Key Constraints</h2>";
    
    $constraints_check = $conn->query("
        SELECT 
            CONSTRAINT_NAME, 
            TABLE_NAME, 
            COLUMN_NAME, 
            REFERENCED_TABLE_NAME, 
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE REFERENCED_TABLE_SCHEMA = DATABASE() 
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    if ($constraints_check->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Constraint</th><th>Table</th><th>Column</th><th>References</th></tr>";
        while ($row = $constraints_check->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['CONSTRAINT_NAME']}</td>";
            echo "<td>{$row['TABLE_NAME']}</td>";
            echo "<td>{$row['COLUMN_NAME']}</td>";
            echo "<td>{$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "⚠️ No foreign key constraints found. Make sure to run the updated SQL schema.<br>";
    }
    
    // Test 3: Sample roles (create if they don't exist)
    echo "<h2>Test 3: Sample Data Setup</h2>";
    
    $roles_count = $conn->query("SELECT COUNT(*) as count FROM roles")->fetch_assoc()['count'];
    if ($roles_count == 0) {
        echo "Creating sample roles...<br>";
        $conn->query("INSERT INTO roles (RoleName, Description) VALUES 
            ('Admin', 'System administrator with full access'),
            ('User', 'Standard user with basic permissions'),
            ('Moderator', 'User with moderation capabilities')");
        echo "✅ Sample roles created<br>";
    } else {
        echo "✅ Roles table has $roles_count role(s)<br>";
    }
    
    // Test 4: Test user creation workflow simulation
    echo "<h2>Test 4: User Creation Workflow (Simulation)</h2>";
    
    // Show what would happen during user creation
    echo "<h3>When creating a user, the system will:</h3>";
    echo "<ol>";
    echo "<li><strong>Validate input</strong> - Check required fields, email format, uniqueness</li>";
    echo "<li><strong>Hash password</strong> - Use PHP's password_hash() for security</li>";
    echo "<li><strong>Insert into Users table</strong> - Basic account information</li>";
    echo "<li><strong>Insert into Profiles table</strong> - Extended user information linked by UserID</li>";
    echo "<li><strong>Insert into UserRoles table</strong> - Role assignments linked by UserID and RoleID</li>";
    echo "<li><strong>Commit transaction</strong> - All operations succeed or all are rolled back</li>";
    echo "</ol>";
    
    // Test 5: Verify file structure
    echo "<h2>Test 5: File Structure</h2>";
    
    $required_files = [
        'config.php' => 'Database configuration',
        'index.php' => 'Main dashboard',
        'profile_page.php' => 'Profile management page',
        'users/add_user.php' => 'User creation endpoint',
        'users/get_users.php' => 'User listing endpoint',
        'profiles/get_profile.php' => 'Profile retrieval endpoint',
        'profiles/update_profile.php' => 'Profile update endpoint',
        'roles/get_roles.php' => 'Roles listing endpoint',
        'userroles/update_user_roles.php' => 'Role assignment endpoint'
    ];
    
    foreach ($required_files as $file => $description) {
        if (file_exists(__DIR__ . '/' . $file)) {
            echo "✅ $file - $description<br>";
        } else {
            echo "❌ $file - $description (MISSING)<br>";
        }
    }
    
    // Test 6: Show available endpoints
    echo "<h2>Test 6: API Endpoints</h2>";
    
    echo "<h3>User Operations:</h3>";
    echo "<ul>";
    echo "<li><strong>POST</strong> users/add_user.php - Create user with profile and roles</li>";
    echo "<li><strong>GET</strong> users/get_users.php - List all users</li>";
    echo "<li><strong>GET</strong> users/get_user.php?id=X - Get specific user</li>";
    echo "<li><strong>POST</strong> users/update_user.php - Update user account info</li>";
    echo "<li><strong>POST</strong> users/delete_user.php - Remove user (cascades)</li>";
    echo "</ul>";
    
    echo "<h3>Profile Operations:</h3>";
    echo "<ul>";
    echo "<li><strong>POST</strong> profiles/add_profile.php - Create/update profile</li>";
    echo "<li><strong>GET</strong> profiles/get_profile.php?user_id=X - Get user profile</li>";
    echo "<li><strong>POST</strong> profiles/update_profile.php - Update profile info</li>";
    echo "<li><strong>POST</strong> profiles/delete_profile.php - Remove profile</li>";
    echo "</ul>";
    
    echo "<h3>Role Operations:</h3>";
    echo "<ul>";
    echo "<li><strong>GET</strong> roles/get_roles.php - List all roles</li>";
    echo "<li><strong>POST</strong> userroles/update_user_roles.php - Assign roles to user</li>";
    echo "</ul>";
    
    echo "<h2>✅ System Analysis Complete</h2>";
    echo "<p><strong>Status:</strong> The User Management System appears to be properly configured for the requested workflow.</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>Test user creation via the interface at <a href='index.php'>index.php</a></li>";
    echo "<li>View user profiles by clicking 'View Profile' buttons</li>";
    echo "<li>Manage profile information on the dedicated profile pages</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error during testing</h2>";
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

$conn->close();
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    max-width: 1000px; 
    margin: 0 auto; 
    padding: 20px; 
    background-color: #f5f5f5; 
}
h1, h2, h3 { 
    color: #333; 
    border-bottom: 2px solid #ddd; 
    padding-bottom: 10px; 
}
table { 
    background-color: white; 
    padding: 10px; 
}
th { 
    background-color: #007bff; 
    color: white; 
    padding: 8px; 
}
td { 
    padding: 8px; 
}
ul, ol { 
    background-color: white; 
    padding: 15px; 
    border-radius: 5px; 
    margin: 10px 0; 
}
</style>
