<?php
// profile_page.php - User Profile Management Page
require_once __DIR__ . '/config.php';

// Get user_id from URL parameter
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

if (!$user_id) {
    die("Error: user_id parameter is required. Usage: profile_page.php?user_id=123");
}

// Fetch user and profile data
$user_data = null;
$profile_data = null;
$user_roles = [];

try {
    // Get user basic information
    $user_stmt = $conn->prepare("SELECT UserID, Username, Email, Status, CreatedAt FROM users WHERE UserID = ?");
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    
    if ($user_result->num_rows === 0) {
        die("Error: User with ID $user_id not found.");
    }
    
    $user_data = $user_result->fetch_assoc();
    $user_stmt->close();
    
    // Get profile information (with LEFT JOIN in case profile doesn't exist yet)
    $profile_stmt = $conn->prepare("
        SELECT p.ProfileID, p.FullName, p.Address, p.PhoneNumber, p.DateOfBirth, p.Avatar, p.UpdatedAt 
        FROM profiles p 
        WHERE p.UserID = ?
    ");
    $profile_stmt->bind_param("i", $user_id);
    $profile_stmt->execute();
    $profile_result = $profile_stmt->get_result();
    
    if ($profile_result->num_rows > 0) {
        $profile_data = $profile_result->fetch_assoc();
    }
    $profile_stmt->close();
    
    // Get user roles
    $roles_stmt = $conn->prepare("
        SELECT r.RoleID, r.RoleName, r.Description 
        FROM userroles ur 
        JOIN roles r ON ur.RoleID = r.RoleID 
        WHERE ur.UserID = ?
    ");
    $roles_stmt->bind_param("i", $user_id);
    $roles_stmt->execute();
    $roles_result = $roles_stmt->get_result();
    
    while ($role = $roles_result->fetch_assoc()) {
        $user_roles[] = $role;
    }
    $roles_stmt->close();
    
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - <?php echo htmlspecialchars($user_data['Username']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h3 {
            color: #007bff;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .field-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .field {
            margin-bottom: 10px;
        }
        .field label {
            display: block;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .field input, .field textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #545b62;
        }
        .roles-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .role-badge {
            background-color: #e7f3ff;
            color: #0066cc;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            border: 1px solid #b3d9ff;
        }
        .no-profile {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .status-active {
            color: #28a745;
            font-weight: bold;
        }
        .status-inactive {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>User Profile</h1>
            <p><strong>User ID:</strong> <?php echo $user_data['UserID']; ?></p>
        </div>

        <div class="section">
            <h3>Account Information</h3>
            <div class="field-group">
                <div class="field">
                    <label>Username:</label>
                    <input type="text" value="<?php echo htmlspecialchars($user_data['Username']); ?>" readonly>
                </div>
                <div class="field">
                    <label>Email:</label>
                    <input type="email" value="<?php echo htmlspecialchars($user_data['Email']); ?>" readonly>
                </div>
                <div class="field">
                    <label>Status:</label>
                    <span class="<?php echo $user_data['Status'] === 'Active' ? 'status-active' : 'status-inactive'; ?>">
                        <?php echo htmlspecialchars($user_data['Status']); ?>
                    </span>
                </div>
                <div class="field">
                    <label>Created:</label>
                    <input type="text" value="<?php echo htmlspecialchars($user_data['CreatedAt']); ?>" readonly>
                </div>
            </div>
        </div>

        <div class="section">
            <h3>User Roles</h3>
            <?php if (!empty($user_roles)): ?>
                <div class="roles-list">
                    <?php foreach ($user_roles as $role): ?>
                        <span class="role-badge" title="<?php echo htmlspecialchars($role['Description']); ?>">
                            <?php echo htmlspecialchars($role['RoleName']); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>No roles assigned to this user.</p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h3>Profile Information</h3>
            <?php if ($profile_data): ?>
                <div id="profile-form">
                    <div class="field-group">
                        <div class="field">
                            <label for="fullName">Full Name:</label>
                            <input type="text" id="fullName" value="<?php echo htmlspecialchars($profile_data['FullName']); ?>">
                        </div>
                        <div class="field">
                            <label for="phoneNumber">Phone Number:</label>
                            <input type="text" id="phoneNumber" value="<?php echo htmlspecialchars($profile_data['PhoneNumber'] ?? ''); ?>">
                        </div>
                        <div class="field">
                            <label for="dateOfBirth">Date of Birth:</label>
                            <input type="date" id="dateOfBirth" value="<?php echo htmlspecialchars($profile_data['DateOfBirth'] ?? ''); ?>">
                        </div>
                        <div class="field">
                            <label for="avatar">Avatar URL:</label>
                            <input type="url" id="avatar" value="<?php echo htmlspecialchars($profile_data['Avatar'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="field">
                        <label for="address">Address:</label>
                        <textarea id="address" rows="3"><?php echo htmlspecialchars($profile_data['Address'] ?? ''); ?></textarea>
                    </div>
                    <p><small><strong>Last Updated:</strong> <?php echo htmlspecialchars($profile_data['UpdatedAt']); ?></small></p>
                </div>
            <?php else: ?>
                <div class="no-profile">
                    <p><strong>No profile found for this user.</strong></p>
                    <p>A profile will be created when you save profile information.</p>
                </div>
                <div id="profile-form">
                    <div class="field-group">
                        <div class="field">
                            <label for="fullName">Full Name:</label>
                            <input type="text" id="fullName" value="">
                        </div>
                        <div class="field">
                            <label for="phoneNumber">Phone Number:</label>
                            <input type="text" id="phoneNumber" value="">
                        </div>
                        <div class="field">
                            <label for="dateOfBirth">Date of Birth:</label>
                            <input type="date" id="dateOfBirth" value="">
                        </div>
                        <div class="field">
                            <label for="avatar">Avatar URL:</label>
                            <input type="url" id="avatar" value="">
                        </div>
                    </div>
                    <div class="field">
                        <label for="address">Address:</label>
                        <textarea id="address" rows="3"></textarea>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="section">
            <button class="btn" onclick="saveProfile()">Save Profile</button>
            <?php if ($profile_data): ?>
                <button class="btn btn-secondary" onclick="deleteProfile()">Delete Profile</button>
            <?php endif; ?>
            <a href="index.php" class="btn btn-secondary">Back to User List</a>
        </div>
    </div>

    <script>
        const userId = <?php echo $user_id; ?>;
        const hasProfile = <?php echo $profile_data ? 'true' : 'false'; ?>;

        async function saveProfile() {
            const formData = {
                UserID: userId,
                FullName: document.getElementById('fullName').value,
                Address: document.getElementById('address').value,
                PhoneNumber: document.getElementById('phoneNumber').value,
                DateOfBirth: document.getElementById('dateOfBirth').value,
                Avatar: document.getElementById('avatar').value
            };

            if (!formData.FullName.trim()) {
                alert('Full Name is required');
                return;
            }

            try {
                const endpoint = hasProfile ? 'profiles/update_profile.php' : 'profiles/add_profile.php';
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();
                
                if (result.success) {
                    alert(result.message);
                    location.reload(); // Refresh the page to show updated data
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Network error: ' + error.message);
            }
        }

        async function deleteProfile() {
            if (!confirm('Are you sure you want to delete this profile? This action cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch('profiles/delete_profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ UserID: userId })
                });

                const result = await response.json();
                
                if (result.success) {
                    alert(result.message);
                    location.reload(); // Refresh the page to show no profile state
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Network error: ' + error.message);
            }
        }
    </script>
</body>
</html>
