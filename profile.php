<?php
// profile.php - User Profile Display and Management Page (as requested in requirements)
require_once __DIR__ . '/config.php';

// Get user ID from URL parameter (supporting both 'id' and 'user_id' for compatibility)
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['user_id']) ? (int)$_GET['user_id'] : null);

if (!$user_id) {
    die("
    <div style='text-align: center; margin-top: 50px; font-family: Arial, sans-serif;'>
        <h2 style='color: #e53e3e;'>Error: Missing User ID</h2>
        <p>Usage: <code>profile.php?id=123</code> or <code>profile.php?user_id=123</code></p>
        <a href='index.php' style='color: #3182ce; text-decoration: none;'>← Back to Dashboard</a>
    </div>
    ");
}

// Fetch user and profile data by joining Users and Profiles tables
$user_data = null;
$profile_data = null;
$user_roles = [];

try {
    // Get user and profile information with JOIN
    $user_profile_stmt = $conn->prepare("
        SELECT 
            u.UserID, u.Username, u.Email, u.Status, u.CreatedAt,
            p.ProfileID, p.FullName, p.Address, p.PhoneNumber, p.DateOfBirth, p.Avatar, p.UpdatedAt
        FROM users u 
        LEFT JOIN profiles p ON u.UserID = p.UserID 
        WHERE u.UserID = ?
    ");
    $user_profile_stmt->bind_param("i", $user_id);
    $user_profile_stmt->execute();
    $result = $user_profile_stmt->get_result();
    
    if ($result->num_rows === 0) {
        die("
        <div style='text-align: center; margin-top: 50px; font-family: Arial, sans-serif;'>
            <h2 style='color: #e53e3e;'>Error: User Not Found</h2>
            <p>User with ID $user_id does not exist.</p>
            <a href='index.php' style='color: #3182ce; text-decoration: none;'>← Back to Dashboard</a>
        </div>
        ");
    }
    
    $combined_data = $result->fetch_assoc();
    
    // Split into user and profile data
    $user_data = [
        'UserID' => $combined_data['UserID'],
        'Username' => $combined_data['Username'],
        'Email' => $combined_data['Email'],
        'Status' => $combined_data['Status'],
        'CreatedAt' => $combined_data['CreatedAt']
    ];
    
    // Only set profile data if profile exists
    if ($combined_data['ProfileID']) {
        $profile_data = [
            'ProfileID' => $combined_data['ProfileID'],
            'FullName' => $combined_data['FullName'],
            'Address' => $combined_data['Address'],
            'PhoneNumber' => $combined_data['PhoneNumber'],
            'DateOfBirth' => $combined_data['DateOfBirth'],
            'Avatar' => $combined_data['Avatar'],
            'UpdatedAt' => $combined_data['UpdatedAt']
        ];
    }
    
    $user_profile_stmt->close();
    
    // Get user roles
    $roles_stmt = $conn->prepare("
        SELECT r.RoleID, r.RoleName, r.Description 
        FROM userroles ur 
        JOIN roles r ON ur.RoleID = r.RoleID 
        WHERE ur.UserID = ?
        ORDER BY r.RoleName
    ");
    $roles_stmt->bind_param("i", $user_id);
    $roles_stmt->execute();
    $roles_result = $roles_stmt->get_result();
    
    while ($role = $roles_result->fetch_assoc()) {
        $user_roles[] = $role;
    }
    $roles_stmt->close();
    
} catch (Exception $e) {
    die("
    <div style='text-align: center; margin-top: 50px; font-family: Arial, sans-serif;'>
        <h2 style='color: #e53e3e;'>Database Error</h2>
        <p>Error fetching user data: " . htmlspecialchars($e->getMessage()) . "</p>
        <a href='index.php' style='color: #3182ce; text-decoration: none;'>← Back to Dashboard</a>
    </div>
    ");
}

// Handle form submissions for CRUD operations
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create_profile') {
            // Create new profile
            $full_name = sanitize_input($_POST['full_name']);
            $address = sanitize_input($_POST['address'] ?? '');
            $phone_number = sanitize_input($_POST['phone_number'] ?? '');
            $date_of_birth = sanitize_input($_POST['date_of_birth'] ?? '');
            $avatar = sanitize_input($_POST['avatar'] ?? '');
            
            if (strlen($full_name) < 2) {
                throw new Exception("Full name must be at least 2 characters long");
            }
            
            // Validate date format if provided
            if ($date_of_birth && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_of_birth)) {
                throw new Exception("Date of birth must be in YYYY-MM-DD format");
            }
            
            $create_stmt = $conn->prepare("
                INSERT INTO profiles (UserID, FullName, Address, PhoneNumber, DateOfBirth, Avatar) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $create_stmt->bind_param("isssss", $user_id, $full_name, $address, $phone_number, $date_of_birth, $avatar);
            
            if ($create_stmt->execute()) {
                $message = "Profile created successfully!";
                $message_type = "success";
                // Refresh page to show new profile
                header("Location: profile.php?id=$user_id&created=1");
                exit;
            } else {
                throw new Exception("Failed to create profile: " . $create_stmt->error);
            }
            
        } elseif ($action === 'update_profile') {
            // Update existing profile
            if (!$profile_data) {
                throw new Exception("Profile does not exist");
            }
            
            $full_name = sanitize_input($_POST['full_name']);
            $address = sanitize_input($_POST['address'] ?? '');
            $phone_number = sanitize_input($_POST['phone_number'] ?? '');
            $date_of_birth = sanitize_input($_POST['date_of_birth'] ?? '');
            $avatar = sanitize_input($_POST['avatar'] ?? '');
            
            if (strlen($full_name) < 2) {
                throw new Exception("Full name must be at least 2 characters long");
            }
            
            // Validate date format if provided
            if ($date_of_birth && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_of_birth)) {
                throw new Exception("Date of birth must be in YYYY-MM-DD format");
            }
            
            $update_stmt = $conn->prepare("
                UPDATE profiles 
                SET FullName = ?, Address = ?, PhoneNumber = ?, DateOfBirth = ?, Avatar = ?, UpdatedAt = NOW()
                WHERE UserID = ?
            ");
            $update_stmt->bind_param("sssssi", $full_name, $address, $phone_number, $date_of_birth, $avatar, $user_id);
            
            if ($update_stmt->execute()) {
                $message = "Profile updated successfully!";
                $message_type = "success";
                // Refresh page to show updated profile
                header("Location: profile.php?id=$user_id&updated=1");
                exit;
            } else {
                throw new Exception("Failed to update profile: " . $update_stmt->error);
            }
            
        } elseif ($action === 'delete_profile') {
            // Delete profile (but keep user account)
            if (!$profile_data) {
                throw new Exception("Profile does not exist");
            }
            
            $delete_stmt = $conn->prepare("DELETE FROM profiles WHERE UserID = ?");
            $delete_stmt->bind_param("i", $user_id);
            
            if ($delete_stmt->execute()) {
                $message = "Profile deleted successfully!";
                $message_type = "success";
                // Refresh page
                header("Location: profile.php?id=$user_id&deleted=1");
                exit;
            } else {
                throw new Exception("Failed to delete profile: " . $delete_stmt->error);
            }
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = "error";
    }
}

// Handle URL messages
if (isset($_GET['created'])) {
    $message = "Profile created successfully!";
    $message_type = "success";
} elseif (isset($_GET['updated'])) {
    $message = "Profile updated successfully!";
    $message_type = "success";
} elseif (isset($_GET['deleted'])) {
    $message = "Profile deleted successfully!";
    $message_type = "success";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - <?php echo htmlspecialchars($user_data['Username']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
        }
        
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .profile-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
            border: 4px solid #667eea;
        }
        
        .avatar-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2em;
            font-weight: bold;
            margin-right: 20px;
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-info h2 {
            color: #2d3748;
            margin-bottom: 5px;
        }
        
        .profile-info p {
            color: #718096;
            margin-bottom: 3px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-item {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .info-item label {
            font-weight: 600;
            color: #4a5568;
            display: block;
            margin-bottom: 5px;
        }
        
        .info-item .value {
            color: #2d3748;
            font-size: 1.1em;
        }
        
        .roles-section {
            margin-bottom: 30px;
        }
        
        .roles-section h3 {
            color: #2d3748;
            margin-bottom: 15px;
        }
        
        .role-tag {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            margin: 5px;
            font-size: 0.9em;
        }
        
        .form-section {
            background: #f7fafc;
            border-radius: 12px;
            padding: 30px;
            margin-top: 30px;
        }
        
        .form-section h3 {
            color: #2d3748;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4a5568;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-right: 10px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
        }
        
        .btn-danger {
            background: #e53e3e;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c53030;
        }
        
        .btn-secondary {
            background: #718096;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4a5568;
        }
        
        .no-profile {
            text-align: center;
            padding: 40px;
            color: #718096;
        }
        
        .no-profile h3 {
            margin-bottom: 15px;
            color: #4a5568;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .avatar,
            .avatar-placeholder {
                margin-right: 0;
                margin-bottom: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo htmlspecialchars($user_data['Username']); ?></h1>
            <p>User Profile Management</p>
        </div>
        
        <div class="content">
            <!-- Back button -->
            <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
            
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <!-- User Account Information -->
            <div class="profile-card">
                <h3 style="color: #2d3748; margin-bottom: 20px;">Account Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Username</label>
                        <div class="value"><?php echo htmlspecialchars($user_data['Username']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Email</label>
                        <div class="value"><?php echo htmlspecialchars($user_data['Email']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Status</label>
                        <div class="value">
                            <span style="color: <?php echo $user_data['Status'] === 'Active' ? '#38a169' : '#e53e3e'; ?>">
                                <?php echo htmlspecialchars($user_data['Status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <label>Member Since</label>
                        <div class="value"><?php echo date('F j, Y', strtotime($user_data['CreatedAt'])); ?></div>
                    </div>
                </div>
                
                <!-- User Roles -->
                <?php if (!empty($user_roles)): ?>
                    <div class="roles-section">
                        <h3>Assigned Roles</h3>
                        <?php foreach ($user_roles as $role): ?>
                            <span class="role-tag" title="<?php echo htmlspecialchars($role['Description']); ?>">
                                <?php echo htmlspecialchars($role['RoleName']); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Profile Information -->
            <?php if ($profile_data): ?>
                <div class="profile-card">
                    <div class="profile-header">
                        <?php if ($profile_data['Avatar']): ?>
                            <img src="<?php echo htmlspecialchars($profile_data['Avatar']); ?>" 
                                 alt="Avatar" class="avatar" 
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="avatar-placeholder" style="display: none;">
                                <?php echo strtoupper(substr($profile_data['FullName'] ?? $user_data['Username'], 0, 1)); ?>
                            </div>
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <?php echo strtoupper(substr($profile_data['FullName'] ?? $user_data['Username'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="profile-info">
                            <h2><?php echo htmlspecialchars($profile_data['FullName']); ?></h2>
                            <p>Profile ID: <?php echo $profile_data['ProfileID']; ?></p>
                            <?php if ($profile_data['UpdatedAt']): ?>
                                <p>Last updated: <?php echo date('F j, Y g:i A', strtotime($profile_data['UpdatedAt'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="info-grid">
                        <?php if ($profile_data['PhoneNumber']): ?>
                            <div class="info-item">
                                <label>Phone Number</label>
                                <div class="value"><?php echo htmlspecialchars($profile_data['PhoneNumber']); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($profile_data['DateOfBirth']): ?>
                            <div class="info-item">
                                <label>Date of Birth</label>
                                <div class="value"><?php echo date('F j, Y', strtotime($profile_data['DateOfBirth'])); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($profile_data['Address']): ?>
                            <div class="info-item" style="grid-column: 1 / -1;">
                                <label>Address</label>
                                <div class="value"><?php echo htmlspecialchars($profile_data['Address']); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Update Profile Form -->
                <div class="form-section">
                    <h3>Update Profile</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($profile_data['FullName']); ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone_number">Phone Number</label>
                                <input type="text" id="phone_number" name="phone_number" 
                                       value="<?php echo htmlspecialchars($profile_data['PhoneNumber'] ?? ''); ?>" 
                                       placeholder="+1 555 0100">
                            </div>
                            
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" 
                                       value="<?php echo htmlspecialchars($profile_data['DateOfBirth'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" 
                                   value="<?php echo htmlspecialchars($profile_data['Address'] ?? ''); ?>" 
                                   placeholder="123 Main St, City, State">
                        </div>
                        
                        <div class="form-group">
                            <label for="avatar">Avatar URL</label>
                            <input type="url" id="avatar" name="avatar" 
                                   value="<?php echo htmlspecialchars($profile_data['Avatar'] ?? ''); ?>" 
                                   placeholder="https://example.com/avatar.jpg">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                        <button type="button" class="btn btn-danger" onclick="deleteProfile()">Delete Profile</button>
                    </form>
                </div>
                
            <?php else: ?>
                <!-- No Profile - Create Form -->
                <div class="no-profile">
                    <h3>No Profile Found</h3>
                    <p>This user doesn't have a profile yet. You can create one below.</p>
                </div>
                
                <div class="form-section">
                    <h3>Create Profile</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="create_profile">
                        
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" required 
                                   placeholder="Enter full name">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone_number">Phone Number</label>
                                <input type="text" id="phone_number" name="phone_number" 
                                       placeholder="+1 555 0100">
                            </div>
                            
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth</label>
                                <input type="date" id="date_of_birth" name="date_of_birth">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" 
                                   placeholder="123 Main St, City, State">
                        </div>
                        
                        <div class="form-group">
                            <label for="avatar">Avatar URL</label>
                            <input type="url" id="avatar" name="avatar" 
                                   placeholder="https://example.com/avatar.jpg">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Create Profile</button>
                    </form>
                </div>
            <?php endif; ?>
            
            <!-- Activity Logs Section -->
            <div class="form-section">
                <h3>📊 Activity Logs</h3>
                <p style="color: #718096; margin-bottom: 20px;">Recent activities for this user</p>
                
                <div id="activityLogsSection">
                    <div id="activityLogsLoading" class="loading" style="display: none; text-align: center; padding: 20px; color: #718096;">
                        ⏳ Loading activity logs...
                    </div>
                    
                    <div id="activityLogsError" style="display: none; background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 10px 0; border: 1px solid #f5c6cb;"></div>
                    
                    <div id="activityLogsTable" style="display: none;">
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6; color: #495057;">Action</th>
                                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6; color: #495057;">Details</th>
                                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6; color: #495057;">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody id="activityLogsTableBody">
                                <!-- Activity logs will be populated here -->
                            </tbody>
                        </table>
                        
                        <div id="activityLogsPagination" style="text-align: center; margin-top: 15px;">
                            <!-- Pagination will be populated here -->
                        </div>
                    </div>
                    
                    <div id="noActivityLogs" style="display: none; text-align: center; padding: 30px; color: #718096;">
                        <i style="font-size: 2em; margin-bottom: 15px; display: block;">📝</i>
                        <h4 style="margin-bottom: 10px; color: #4a5568;">No Activity Logs</h4>
                        <p>No activity logs found for this user.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let currentActivityPage = 1;
        const activityLogsPerPage = 10;
        
        // Load activity logs when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadUserActivityLogs();
        });
        
        function showActivityLoading() {
            document.getElementById('activityLogsLoading').style.display = 'block';
            document.getElementById('activityLogsError').style.display = 'none';
            document.getElementById('activityLogsTable').style.display = 'none';
            document.getElementById('noActivityLogs').style.display = 'none';
        }
        
        function hideActivityLoading() {
            document.getElementById('activityLogsLoading').style.display = 'none';
        }
        
        function showActivityError(message) {
            const errorDiv = document.getElementById('activityLogsError');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            hideActivityLoading();
        }
        
        function loadUserActivityLogs(page = 1) {
            showActivityLoading();
            currentActivityPage = page;
            
            const userId = <?php echo $user_id; ?>;
            
            fetch(`./activitylogs/get_user_logs.php?user_id=${userId}&page=${page}&limit=${activityLogsPerPage}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    hideActivityLoading();
                    if (data.success) {
                        displayActivityLogs(data.data.logs);
                        updateActivityPagination(data.data.pagination);
                    } else {
                        showActivityError(data.message || 'Failed to load activity logs');
                    }
                })
                .catch(error => {
                    hideActivityLoading();
                    console.error('Error loading activity logs:', error);
                    showActivityError('Failed to load activity logs: ' + error.message);
                });
        }
        
        function displayActivityLogs(logs) {
            const tbody = document.getElementById('activityLogsTableBody');
            
            if (logs.length === 0) {
                document.getElementById('activityLogsTable').style.display = 'none';
                document.getElementById('noActivityLogs').style.display = 'block';
                return;
            }
            
            document.getElementById('activityLogsTable').style.display = 'block';
            document.getElementById('noActivityLogs').style.display = 'none';
            
            tbody.innerHTML = logs.map(log => `
                <tr style="border-bottom: 1px solid #dee2e6;">
                    <td style="padding: 12px; font-weight: 600; color: #495057;">
                        ${log.Action}
                    </td>
                    <td style="padding: 12px; color: #6c757d; max-width: 300px; word-wrap: break-word;">
                        ${log.Details || '-'}
                    </td>
                    <td style="padding: 12px; color: #6c757d; font-size: 0.9em;">
                        ${formatActivityTimestamp(log.Timestamp)}
                    </td>
                </tr>
            `).join('');
        }
        
        function updateActivityPagination(pagination) {
            const paginationDiv = document.getElementById('activityLogsPagination');
            
            if (pagination.total_pages <= 1) {
                paginationDiv.style.display = 'none';
                return;
            }
            
            paginationDiv.style.display = 'block';
            
            let paginationHTML = '';
            
            // Previous button
            if (pagination.has_prev) {
                paginationHTML += `<button onclick="loadUserActivityLogs(${currentActivityPage - 1})" style="margin: 0 5px; padding: 8px 12px; border: 2px solid #dee2e6; background: white; color: #495057; border-radius: 6px; cursor: pointer;">‹ Previous</button>`;
            }
            
            // Current page info
            paginationHTML += `<span style="margin: 0 15px; color: #495057;">Page ${pagination.current_page} of ${pagination.total_pages}</span>`;
            
            // Next button
            if (pagination.has_next) {
                paginationHTML += `<button onclick="loadUserActivityLogs(${currentActivityPage + 1})" style="margin: 0 5px; padding: 8px 12px; border: 2px solid #dee2e6; background: white; color: #495057; border-radius: 6px; cursor: pointer;">Next ›</button>`;
            }
            
            paginationDiv.innerHTML = paginationHTML;
        }
        
        function formatActivityTimestamp(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleString();
        }
        
        function deleteProfile() {
            if (confirm('Are you sure you want to delete this profile? This action cannot be undone.\n\nThe user account will remain active, but all profile information will be lost.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="action" value="delete_profile">';
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Auto-hide success messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const successMessages = document.querySelectorAll('.message.success');
            successMessages.forEach(function(message) {
                setTimeout(function() {
                    message.style.opacity = '0';
                    message.style.transition = 'opacity 0.5s';
                    setTimeout(function() {
                        message.style.display = 'none';
                    }, 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>
