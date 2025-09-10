<?php
// index.php - Integrated front-end + PHP for User Management System (Users Table focused)
require_once __DIR__ . '/config.php';

// Pre-fetch basic user stats & list (server-side render fallback)
$totalUsers = 0;
$activeUsers = 0;
$todayActivity = 0;
$totalRoles = 0; // roles/activity placeholders
$users = [];

try {
    // Users list
    $res = $conn->query("SELECT UserID, Username, Email, Status, CreatedAt FROM users ORDER BY CreatedAt DESC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $users[] = $row;
        }
        $totalUsers = count($users);
    }
    // Active users count
    $res2 = $conn->query("SELECT COUNT(*) AS c FROM users WHERE Status='Active'");
    if ($res2) {
        $activeUsers = (int) $res2->fetch_assoc()['c'];
    }
    // Today activity (if table exists)
    if ($conn->query("SHOW TABLES LIKE 'activity_logs'")->num_rows === 1) {
        $res3 = $conn->query("SELECT COUNT(*) AS a FROM activity_logs WHERE DATE(Timestamp)=CURDATE()");
        if ($res3) {
            $todayActivity = (int) $res3->fetch_assoc()['a'];
        }
    }
    // Total roles (if roles table exists)
    if ($conn->query("SHOW TABLES LIKE 'roles'")->num_rows === 1) {
        $res4 = $conn->query("SELECT COUNT(*) AS r FROM roles");
        if ($res4) {
            $totalRoles = (int) $res4->fetch_assoc()['r'];
        }
    }
} catch (Throwable $e) {
    // Silent fail for initial render; JS will refresh via AJAX
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>User Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, .95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, .1);
            border: 1px solid rgba(255, 255, 255, .18);
        }

        h1 {
            color: #4a5568;
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #718096;
            font-size: 1.1em;
        }

        .nav-tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .tab-btn {
            background: rgba(255, 255, 255, .9);
            border: none;
            padding: 15px 25px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: .3s;
            color: #4a5568;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .1);
            min-width: 120px;
        }

        .tab-btn:hover {
            background: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, .15);
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            transform: translateY(-2px);
        }

        .tab-content {
            display: none;
            background: rgba(255, 255, 255, .95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, .1);
            border: 1px solid rgba(255, 255, 255, .18);
        }

        .tab-content.active {
            display: block;
            animation: fadeIn .3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4a5568;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 16px;
            transition: .3s;
            background: rgba(255, 255, 255, .9);
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, .1);
            transform: translateY(-1px);
        }

        .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: .3s;
            margin-right: 10px;
            margin-bottom: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, .3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #718096, #4a5568);
        }

        .btn-danger {
            background: linear-gradient(135deg, #e53e3e, #c53030);
        }

        .btn-success {
            background: linear-gradient(135deg, #38a169, #2f855a);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .1);
        }

        .data-table th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        .data-table td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .data-table tr:hover {
            background: rgba(102, 126, 234, .05);
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-active {
            background: #c6f6d5;
            color: #2f855a;
        }

        .status-inactive {
            background: #fed7d7;
            color: #c53030;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, .5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: #fff;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .3);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }

        .close:hover {
            color: #000;
        }

        .search-bar {
            margin-bottom: 20px;
        }

        .search-bar input {
            width: 100%;
            padding: 15px 20px;
            border-radius: 25px;
            border: 2px solid #e2e8f0;
            font-size: 16px;
            background: rgba(255, 255, 255, .9);
        }

        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .btn-small {
            padding: 8px 15px;
            font-size: 14px;
            border-radius: 8px;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, .95);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, .1);
            border: 1px solid rgba(255, 255, 255, .18);
            transition: transform .3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #718096;
            font-weight: 600;
        }

        @media (max-width:768px) {
            .container {
                padding: 10px;
            }

            .nav-tabs {
                justify-content: center;
            }

            .tab-btn {
                flex: 1;
                min-width: auto;
                text-align: center;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .data-table {
                font-size: 14px;
            }

            .data-table th,
            .data-table td {
                padding: 10px 5px;
            }

            .action-buttons {
                flex-direction: column;
            }
        }

        .php-placeholder {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
            color: #6c757d;
            font-style: italic;
        }

        .flash {
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .flash-success {
            background: #c6f6d5;
            color: #22543d;
        }

        .flash-error {
            background: #fed7d7;
            color: #822727;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>User Management System</h1>
            <p class="subtitle">Integrated Users Table (PHP + JS)</p>
        </div>

        <div class="nav-tabs">
            <button class="tab-btn active" onclick="showTab('dashboard', event)">Dashboard</button>
            <button class="tab-btn" onclick="showTab('users', event)">Users</button>
            <button class="tab-btn" onclick="showTab('roles', event)">Roles</button>
            <button class="tab-btn" onclick="showTab('activitylogs', event)">Activity Logs</button>
        </div>

        <div id="dashboard" class="tab-content active">
            <h2>System Dashboard</h2>
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number" id="totalUsers"><?php echo (int) $totalUsers; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="activeUsers"><?php echo (int) $activeUsers; ?></div>
                    <div class="stat-label">Active Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="totalRoles"><?php echo (int) $totalRoles; ?></div>
                    <div class="stat-label">Total Roles</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="todayActivity"><?php echo (int) $todayActivity; ?></div>
                    <div class="stat-label">Today's Activity</div>
                </div>
            </div>
        </div>

        <div id="users" class="tab-content">
            <div
                style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap;">
                <h2>User Management</h2>
                <button class="btn" onclick="showModal('addUserModal')">Add New User</button>
            </div>
            <div class="search-bar">
                <input type="text" id="userSearch"
                    placeholder="Search users by name, email, or username..." 
                    oninput="filterUsers()" 
                    onkeyup="filterUsers()"
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" />
            </div>
            <table class="data-table" id="usersTable">
                <thead>
                    <tr>
                        <th>UserID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                        <th>Profile</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center;">No users found</td>
                        </tr>
                    <?php else:
                        foreach ($users as $u): ?>
                            <tr data-user-id="<?php echo (int) $u['UserID']; ?>">
                                <td><?php echo (int) $u['UserID']; ?></td>
                                <td><?php echo htmlspecialchars($u['Username']); ?></td>
                                <td><?php echo htmlspecialchars($u['Email']); ?></td>
                                <td><span class="status-badge status-<?php echo strtolower($u['Status']) === 'active' ? 'active' : 'inactive'; ?>"><?php echo htmlspecialchars($u['Status']); ?></span></td>
                                <td><?php echo htmlspecialchars($u['CreatedAt']); ?></td>
                                <td class="action-buttons">
                                    <button class="btn btn-small btn-secondary" onclick="editUser(<?php echo (int) $u['UserID']; ?>)">Edit</button>
                                    <button class="btn btn-small btn-success" onclick="manageUserRoles(<?php echo (int) $u['UserID']; ?>)">Roles</button>
                                    <button class="btn btn-small btn-danger" onclick="deleteUserReal(<?php echo (int) $u['UserID']; ?>)">Delete</button>
                                </td>
                                <td><button class="btn btn-small" style="background:linear-gradient(135deg,#805ad5,#6b46c1)" onclick='viewUserProfile(<?php echo (int) $u['UserID']; ?>)'>View Profile</button></td>
                            </tr>
                        <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <div id="roles" class="tab-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap;">
                <h2>Role Management</h2>
                <button class="btn" onclick="showModal('addRoleModal')">Add New Role</button>
            </div>
            <div class="search-bar">
                <input type="text" id="roleSearch" 
                    placeholder="Search roles..." 
                    oninput="filterRoles()" 
                    onkeyup="filterRoles()"
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" />
            </div>
            <table class="data-table" id="rolesTable">
                <thead>
                    <tr>
                        <th>RoleID</th>
                        <th>Role Name</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="rolesTableBody">
                    <tr><td colspan="4" style="text-align:center;">Loading...</td></tr>
                </tbody>
            </table>
        </div>

        <div id="activitylogs" class="tab-content">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap;">
                <h2>📊 Activity Logs</h2>
                <a href="activity_logs.php" class="btn" style="text-decoration: none;">View Full Activity Logs</a>
            </div>
            
            <div class="search-bar">
                <input type="text" id="activitySearch" 
                    placeholder="Search activities..." 
                    oninput="filterActivityLogs()" 
                    onkeyup="filterActivityLogs()"
                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;" />
            </div>
            
            <div style="margin-bottom: 20px;">
                <label for="activityUserFilter" style="margin-right: 10px; font-weight: 600;">Filter by User:</label>
                <select id="activityUserFilter" onchange="loadActivityLogs()" style="padding: 8px; border: 1px solid #ddd; border-radius: 5px; margin-right: 15px;">
                    <option value="">All Users</option>
                </select>
                
                <label for="activityActionFilter" style="margin-right: 10px; font-weight: 600;">Filter by Action:</label>
                <select id="activityActionFilter" onchange="loadActivityLogs()" style="padding: 8px; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="">All Actions</option>
                    <option value="Login">Login</option>
                    <option value="User Created">User Created</option>
                    <option value="User Updated">User Updated</option>
                    <option value="User Deleted">User Deleted</option>
                    <option value="Profile Updated">Profile Updated</option>
                    <option value="Roles Updated">Roles Updated</option>
                </select>
            </div>
            
            <table class="data-table" id="activityLogsTable">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody id="activityLogsTableBody">
                    <tr><td colspan="4" style="text-align:center;">Loading activity logs...</td></tr>
                </tbody>
            </table>
            
            <div id="activityLogsPagination" style="text-align: center; margin-top: 20px;">
                <!-- Pagination will be populated here -->
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('addUserModal')">&times;</span>
            <h2>Add New User</h2>
            <form id="addUserForm">
                <div class="form-row">
                    <div class="form-group"><label for="username">Username:</label><input type="text" id="username"
                            name="username" required /></div>
                    <div class="form-group"><label for="email">Email:</label><input type="email" id="email" name="email"
                            required /></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label for="password">Password:</label><input type="password" id="password"
                            name="password" required /></div>
                    <div class="form-group"><label for="fullName">Full Name:</label><input type="text" id="fullName"
                            name="fullName" required /></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label for="status">Status:</label><select id="status" name="status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select></div>
                    <div class="form-group"><label for="roleId">Role:</label><select id="roleId" name="roleId" required>
                            <option value="">Select a role...</option>
                        </select></div>
                </div>
                <button type="submit" class="btn">Add User</button>
                <button type="button" class="btn btn-secondary" onclick="hideModal('addUserModal')">Cancel</button>
            </form>
            <div class="php-placeholder">
                <p><strong>Form posts via fetch to:</strong> add_user.php</p>
            </div>
        </div>
    </div>

    <!-- Add Role Modal -->
    <div id="addRoleModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('addRoleModal')">&times;</span>
            <h2>Add New Role</h2>
            <form id="addRoleForm">
                <div class="form-group">
                    <label for="roleName">Role Name:</label>
                    <input type="text" id="roleName" name="roleName" required />
                </div>
                <div class="form-group">
                    <label for="roleDescription">Description:</label>
                    <textarea id="roleDescription" name="roleDescription" rows="3" placeholder="Optional"></textarea>
                </div>
                <button type="submit" class="btn">Add Role</button>
                <button type="button" class="btn btn-secondary" onclick="hideModal('addRoleModal')">Cancel</button>
            </form>
            <div class="php-placeholder"><p><strong>Form posts via fetch to:</strong> add_role.php</p></div>
        </div>
    </div>

    <!-- Manage User Roles Modal -->
    <div id="userRolesModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('userRolesModal')">&times;</span>
            <h2>Manage Roles for <span id="userRolesUsername"></span></h2>
            <form id="userRolesForm">
                <input type="hidden" id="rolesUserID" />
                <div id="rolesCheckboxContainer" style="display:grid; gap:10px; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); margin:20px 0;"></div>
                <div style="margin-top:10px;">
                    <button type="submit" class="btn">Save Roles</button>
                    <button type="button" class="btn btn-secondary" onclick="hideModal('userRolesModal')">Close</button>
                </div>
            </form>
            <div class="php-placeholder"><p>Uses <strong>get_user_roles.php</strong> & <strong>update_user_roles.php</strong></p></div>
        </div>
    </div>

    <!-- User Profile Modal -->
    <div id="userProfileModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="hideModal('userProfileModal')">&times;</span>
            <h2>User Profile: <span id="profileUsername"></span></h2>
            <form id="userProfileForm">
                <input type="hidden" id="profileUserID" />
                <div class="form-group">
                    <label for="profileFullName">Full Name <span style="color:#e53e3e;">*</span></label>
                    <input type="text" id="profileFullName" required />
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="profilePhone">Phone Number</label>
                        <input type="text" id="profilePhone" placeholder="+1 555 0100" />
                    </div>
                    <div class="form-group">
                        <label for="profileDOB">Date of Birth</label>
                        <input type="date" id="profileDOB" />
                    </div>
                </div>
                <div class="form-group">
                    <label for="profileAddress">Address</label>
                    <input type="text" id="profileAddress" placeholder="123 Main St" />
                </div>
                <div class="form-group">
                    <label for="profileAvatar">Avatar URL / Path</label>
                    <input type="text" id="profileAvatar" placeholder="/uploads/avatars/user1.png" />
                    <div style="margin-top:10px; text-align:center;">
                        <img id="profileAvatarPreview" src="" alt="Avatar Preview" style="max-width:120px; border-radius:60px; display:none; box-shadow:0 4px 12px rgba(0,0,0,.15);" />
                    </div>
                </div>
                <div id="profileFlash" style="display:none;"></div>
                <div style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;">
                    <button type="submit" class="btn">Save Profile</button>
                    <button type="button" class="btn btn-secondary" onclick="hideModal('userProfileModal')">Close</button>
                    <button type="button" id="deleteProfileBtn" class="btn btn-danger" style="display:none;" onclick="deleteProfile()">Delete Profile</button>
                </div>
            </form>
            <div class="php-placeholder"><p>Endpoints: <strong>get_profile.php</strong>, <strong>add_profile.php</strong>, <strong>delete_profile.php</strong></p></div>
        </div>
    </div>

    <script>
    function showTab(tabName, ev) { 
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active')); 
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active')); 
        document.getElementById(tabName).classList.add('active'); 
        ev.target.classList.add('active'); 
        
        if (tabName === 'roles') { 
            loadRoles(); 
        } else if (tabName === 'users') {
            // Clear user search when switching to users tab
            document.getElementById('userSearch').value = '';
            filterUsers();
        } else if (tabName === 'activitylogs') {
            loadActivityLogs(1);
        }
    }
        function showModal(id) { 
            document.getElementById(id).style.display = 'block'; 
            if (id === 'addUserModal') {
                loadRolesForDropdown();
            }
        }
        
        function loadRolesForDropdown() {
            fetch('roles/get_roles.php')
                .then(r => r.json())
                .then(data => {
                    const roleSelect = document.getElementById('roleId');
                    roleSelect.innerHTML = '<option value="">Select a role...</option>';
                    
                    if (data.success && data.roles && data.roles.length > 0) {
                        data.roles.forEach(role => {
                            const option = document.createElement('option');
                            option.value = role.RoleID;
                            option.textContent = role.RoleName + (role.Description ? ` - ${role.Description}` : '');
                            roleSelect.appendChild(option);
                        });
                    }
                })
                .catch(err => {
                    console.error('Error loading roles for dropdown:', err);
                });
        }
        function hideModal(id) { document.getElementById(id).style.display = 'none'; }
        window.onclick = function (e) { document.querySelectorAll('.modal').forEach(m => { if (e.target === m) { m.style.display = 'none'; } }); };

        function filterUsers() { 
            const term = document.getElementById('userSearch').value.toLowerCase().trim(); 
            const rows = document.querySelectorAll('#usersTableBody tr');
            let visibleCount = 0;
            
            rows.forEach(r => { 
                // Skip rows that are "no data" messages
                if (r.cells.length === 1 && r.textContent.includes('No users found')) {
                    r.style.display = 'none';
                    return;
                }
                
                const text = r.textContent.toLowerCase(); 
                const isVisible = !term || text.includes(term);
                r.style.display = isVisible ? '' : 'none'; 
                if (isVisible) visibleCount++;
            });
            
            // Show "no results" message if no matches
            const tbody = document.getElementById('usersTableBody');
            const noResultsRow = tbody.querySelector('.no-search-results');
            
            if (term && visibleCount === 0) {
                if (!noResultsRow) {
                    const tr = document.createElement('tr');
                    tr.className = 'no-search-results';
                    tr.innerHTML = '<td colspan="7" style="text-align:center; color:#718096;">No users match your search</td>';
                    tbody.appendChild(tr);
                }
            } else if (noResultsRow) {
                noResultsRow.remove();
            }
        }

        // Load users (AJAX refresh)
    function loadUsers() { fetch('users/get_users.php').then(r => r.json()).then(data => { if (!data.success) return; const tbody = document.getElementById('usersTableBody'); tbody.innerHTML = ''; if (data.users.length === 0) { tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;">No users found</td></tr>'; return; } data.users.forEach(u => { const safeName = JSON.stringify(u.Username); const tr = document.createElement('tr'); tr.dataset.userId = u.UserID; tr.innerHTML = `<td>${u.UserID}</td><td>${escapeHtml(u.Username)}</td><td>${escapeHtml(u.Email)}</td><td><span class=\"status-badge status-${u.Status.toLowerCase() === 'active' ? 'active' : 'inactive'}\">${escapeHtml(u.Status)}</span></td><td>${escapeHtml(u.CreatedAt)}</td><td class=\"action-buttons\"><button class='btn btn-small btn-secondary' onclick='editUser(${u.UserID})'>Edit</button><button class='btn btn-small btn-success' onclick='manageUserRoles(${u.UserID})'>Roles</button><button class='btn btn-small btn-danger' onclick='deleteUserReal(${u.UserID})'>Delete</button></td><td><button class='btn btn-small' style=\"background:linear-gradient(135deg,#805ad5,#6b46c1)\" onclick='viewUserProfile(${u.UserID})'>View Profile</button></td>`; tbody.appendChild(tr); }); updateDashboardStats(); }).catch(err => console.log('Load users error', err)); }

        // Profile Page Redirection
        function viewUserProfile(userID) {
            // Redirect to the dedicated profile page (updated to use profile.php?id=USERID as requested)
            window.location.href = 'profile.php?id=' + userID;
        }

        // Profile Management
    function openProfileModal(userID, username) {
            // Reset form
            document.getElementById('userProfileForm').reset();
            document.getElementById('profileUserID').value = userID;
            document.getElementById('profileUsername').textContent = username;
            document.getElementById('deleteProfileBtn').style.display = 'none';
            flashProfile('', false, true);
        updateAvatarPreview('');
        flashProfile('Loading profile...', false);
            // Fetch existing profile
            fetch('profiles/get_profile.php?user_id=' + userID).then(r => r.json()).then(res => {
                if (res.success && res.data) {
                    const p = res.data;
                    document.getElementById('profileFullName').value = p.FullName || '';
                    document.getElementById('profilePhone').value = p.PhoneNumber || '';
                    document.getElementById('profileDOB').value = p.DateOfBirth || '';
                    document.getElementById('profileAddress').value = p.Address || '';
                    document.getElementById('profileAvatar').value = p.Avatar || '';
            updateAvatarPreview(p.Avatar || '');
                    document.getElementById('deleteProfileBtn').style.display = 'inline-block';
            flashProfile('Profile loaded.', false);
                } else {
                    // Profile not found -> new profile (FullName required)
            flashProfile('No profile yet. Create one now.', false);
                }
            }).catch(()=>{});
            showModal('userProfileModal');
        }

        function flashProfile(msg, error=false, clearOnly=false){
            const box = document.getElementById('profileFlash');
            if (clearOnly){ box.style.display='none'; box.textContent=''; return; }
            if (!msg){ box.style.display='none'; return; }
            box.textContent = msg;
            box.className = error ? 'flash flash-error' : 'flash flash-success';
            box.style.display='block';
        }

        document.getElementById('userProfileForm').addEventListener('submit', function(e){
            e.preventDefault();
            const payload = {
                UserID: parseInt(document.getElementById('profileUserID').value,10),
                FullName: document.getElementById('profileFullName').value.trim(),
                PhoneNumber: document.getElementById('profilePhone').value.trim() || undefined,
                DateOfBirth: document.getElementById('profileDOB').value || undefined,
                Address: document.getElementById('profileAddress').value.trim() || undefined,
                Avatar: document.getElementById('profileAvatar').value.trim() || undefined
            };
            if (!payload.FullName){ flashProfile('Full Name is required', true); return; }
            fetch('profiles/add_profile.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) })
                .then(r=>r.json()).then(res=>{
                    if (res.success){
                        flashProfile(res.message, false);
                        updateAvatarPreview(payload.Avatar || '');
                        document.getElementById('deleteProfileBtn').style.display='inline-block';
                    } else {
                        flashProfile(res.message, true);
                    }
                }).catch(err=> flashProfile('Error: '+err, true));
        });

        function deleteProfile(){
            if (!confirm('Delete this profile?')) return;
            const userID = parseInt(document.getElementById('profileUserID').value,10);
            fetch('profiles/delete_profile.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ UserID: userID }) })
                .then(r=>r.json()).then(res=>{
                    if (res.success){
                        flashProfile('Profile deleted', false);
                        updateAvatarPreview('');
                        document.getElementById('userProfileForm').reset();
                        document.getElementById('deleteProfileBtn').style.display='none';
                    } else {
                        flashProfile(res.message, true);
                    }
                }).catch(err=> flashProfile('Error: '+err, true));
        }

    function updateDashboardStats() { fetch('dashboard_stats.php').then(r => r.json()).then(d => { if (!d.success) return; const s = d.stats; document.getElementById('totalUsers').textContent = s.totalUsers; document.getElementById('activeUsers').textContent = s.activeUsers; if (document.getElementById('totalRoles')) document.getElementById('totalRoles').textContent = s.totalRoles || 0; document.getElementById('todayActivity').textContent = s.todayActivity; }).catch(() => { }); }

    function updateAvatarPreview(val){ const img = document.getElementById('profileAvatarPreview'); if (!img) return; if (val){ img.src=val; img.style.display='inline-block'; } else { img.src=''; img.style.display='none'; } }
    document.getElementById('profileAvatar') && document.getElementById('profileAvatar').addEventListener('input', function(){ updateAvatarPreview(this.value.trim()); });

    // Roles
    function loadRoles() { fetch('roles/get_roles.php').then(r => r.json()).then(data => { const tbody = document.getElementById('rolesTableBody'); if (!data.success) { tbody.innerHTML = `<tr><td colspan='4' style='text-align:center;color:#c53030;'>${escapeHtml(data.message)}</td></tr>`; return; } if (data.roles.length === 0) { tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">No roles found</td></tr>'; return; } tbody.innerHTML = ''; data.roles.forEach(role => { const tr = document.createElement('tr'); tr.dataset.roleId = role.RoleID; tr.innerHTML = `<td>${role.RoleID}</td><td>${escapeHtml(role.RoleName)}</td><td>${escapeHtml(role.Description || '')}</td><td class='action-buttons'><button class='btn btn-small btn-secondary' onclick='editRole(${role.RoleID})'>Edit</button><button class='btn btn-small btn-danger' onclick='deleteRoleReal(${role.RoleID})'>Delete</button></td>`; tbody.appendChild(tr); }); filterRoles(); updateDashboardStats(); }).catch(err => { console.log('Load roles error', err); }); }

    function filterRoles() { 
        const term = (document.getElementById('roleSearch').value || '').toLowerCase().trim(); 
        const rows = document.querySelectorAll('#rolesTableBody tr');
        let visibleCount = 0;
        
        rows.forEach(r => { 
            // Skip rows that are "no data" messages
            if (r.cells.length === 1 && (r.textContent.includes('No roles found') || r.textContent.includes('No roles match'))) {
                r.style.display = 'none';
                return;
            }
            
            const isVisible = !term || r.textContent.toLowerCase().includes(term);
            r.style.display = isVisible ? '' : 'none'; 
            if (isVisible) visibleCount++;
        });
        
        // Show "no results" message if no matches
        const tbody = document.getElementById('rolesTableBody');
        const noResultsRow = tbody.querySelector('.no-search-results');
        
        if (term && visibleCount === 0) {
            if (!noResultsRow) {
                const tr = document.createElement('tr');
                tr.className = 'no-search-results';
                tr.innerHTML = '<td colspan="4" style="text-align:center; color:#718096;">No roles match your search</td>';
                tbody.appendChild(tr);
            }
        } else if (noResultsRow) {
            noResultsRow.remove();
        }
    }

    document.getElementById('addRoleForm').addEventListener('submit', function (e) { e.preventDefault(); const payload = { roleName: this.roleName.value.trim(), description: this.roleDescription.value.trim() }; fetch('roles/add_role.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) }).then(r => r.json()).then(res => { if (res.success) { hideModal('addRoleModal'); this.reset(); loadRoles(); } else { alert(res.message); } }).catch(err => alert('Error: ' + err)); });

    function editRole(id) { fetch('roles/get_role.php?id=' + id).then(r => r.json()).then(d => { if (!d.success) { alert('Role not found'); return; } const role = d.data; const newName = prompt('Update Role Name', role.RoleName); if (newName === null || newName.trim() === '' || newName === role.RoleName) return; const newDesc = prompt('Update Description', role.Description || ''); fetch('roles/update_role.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ RoleID: role.RoleID, roleName: newName.trim(), description: newDesc ? newDesc.trim() : '' }) }).then(r => r.json()).then(res => { if (res.success) { loadRoles(); } else { alert(res.message); } }); }); }

    function deleteRoleReal(id) { if (!confirm('Delete role #' + id + '?')) return; fetch('roles/delete_role.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ RoleID: id }) }).then(r => r.json()).then(res => { if (res.success) { loadRoles(); } else { alert(res.message); } }); }

        // User Role Assignment
        function manageUserRoles(userID) {
            fetch('userroles/get_user_roles.php?user_id=' + userID).then(r => r.json()).then(res => {
                if (!res.success) { alert(res.message); return; }
                const data = res.data;
                document.getElementById('rolesUserID').value = data.user.UserID;
                document.getElementById('userRolesUsername').textContent = data.user.Username;
                const container = document.getElementById('rolesCheckboxContainer');
                container.innerHTML = '';
                if (!data.roles || data.roles.length === 0) {
                    container.innerHTML = '<p style="grid-column:1/-1; text-align:center;">No roles defined.</p>';
                } else {
                    data.roles.forEach(r => {
                        const id = 'role_cb_' + r.RoleID;
                        const div = document.createElement('div');
                        div.innerHTML = `<label style='display:flex; gap:8px; align-items:flex-start;'><input type='checkbox' id='${id}' value='${r.RoleID}' ${r.assigned ? 'checked' : ''}/> <span><strong>${escapeHtml(r.RoleName)}</strong><br/><small>${escapeHtml(r.Description || '')}</small></span></label>`;
                        container.appendChild(div);
                    });
                }
                showModal('userRolesModal');
            });
        }

        document.getElementById('userRolesForm').addEventListener('submit', function(e){
            e.preventDefault();
            const userID = parseInt(document.getElementById('rolesUserID').value, 10);
            const roleIDs = Array.from(document.querySelectorAll('#rolesCheckboxContainer input[type=checkbox]:checked')).map(cb => parseInt(cb.value, 10));
            fetch('userroles/update_user_roles.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ UserID: userID, roleIDs }) }).then(r=>r.json()).then(res => { if(res.success){ hideModal('userRolesModal'); updateDashboardStats(); } else { alert(res.message); } });
        });

        function editUser(id) { fetch('users/get_user.php?id=' + id).then(r => r.json()).then(d => { if (!d.success) { alert('User not found'); return; } const u = d.data; const newEmail = prompt('Update Email for ' + u.Username, u.Email); if (!newEmail || newEmail === u.Email) return; fetch('users/update_user.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ UserID: u.UserID, email: newEmail }) }).then(r => r.json()).then(res => { if (res.success) { loadUsers(); } else { alert(res.message); } }); }); }

        function deleteUserReal(id) { if (!confirm('Delete user #' + id + '?')) return; fetch('users/delete_user.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ UserID: id }) }).then(r => r.json()).then(res => { if (res.success) { loadUsers(); } else { alert(res.message); } }); }

        // Add user form -> JSON to add_user.php
        document.getElementById('addUserForm').addEventListener('submit', function (e) { 
            e.preventDefault(); 
            const payload = { 
                username: this.username.value.trim(), 
                email: this.email.value.trim(), 
                password: this.password.value, 
                fullName: this.fullName.value.trim(),
                status: this.status.value,
                roleId: this.roleId.value ? parseInt(this.roleId.value) : null
            }; 
            fetch('users/add_user.php', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify(payload) 
            }).then(r => r.json()).then(res => { 
                if (res.success) { 
                    hideModal('addUserModal'); 
                    this.reset(); 
                    loadUsers(); 
                    alert('User added successfully!');
                } else { 
                    alert(res.message); 
                } 
            }).catch(err => alert('Error: ' + err)); 
        });

        function escapeHtml(str) { return str.replace(/[&<>'"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[c])); }

        // Activity Logs Functions
        let currentActivityPage = 1;
        const activityLogsPerPage = 20;

        function loadActivityLogs(page = 1) {
            currentActivityPage = page;
            
            const userFilter = document.getElementById('activityUserFilter').value;
            const actionFilter = document.getElementById('activityActionFilter').value;
            
            const params = new URLSearchParams({
                page: page,
                limit: activityLogsPerPage
            });
            
            if (userFilter) params.append('user_id', userFilter);
            if (actionFilter) params.append('action', actionFilter);
            
            fetch(`activitylogs/get_all_logs.php?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayActivityLogs(data.data.logs);
                        updateActivityLogsPagination(data.data.pagination);
                        populateUserFilter(data.data.logs);
                    } else {
                        document.getElementById('activityLogsTableBody').innerHTML = 
                            `<tr><td colspan="4" style="text-align:center; color: red;">Error: ${data.message}</td></tr>`;
                    }
                })
                .catch(error => {
                    document.getElementById('activityLogsTableBody').innerHTML = 
                        `<tr><td colspan="4" style="text-align:center; color: red;">Network error: ${error.message}</td></tr>`;
                });
        }

        function displayActivityLogs(logs) {
            const tbody = document.getElementById('activityLogsTableBody');
            
            if (logs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; color: #718096;">No activity logs found</td></tr>';
                return;
            }
            
            tbody.innerHTML = logs.map(log => `
                <tr>
                    <td>
                        <strong>${escapeHtml(log.Username)}</strong><br>
                        <small style="color: #718096;">ID: ${log.UserID}</small>
                    </td>
                    <td>
                        <span style="font-weight: 600; color: #4a5568;">${escapeHtml(log.Action)}</span>
                    </td>
                    <td style="max-width: 300px; word-wrap: break-word;">
                        ${log.Details ? escapeHtml(log.Details) : '-'}
                    </td>
                    <td>
                        <small style="color: #718096;">${formatTimestamp(log.Timestamp)}</small>
                    </td>
                </tr>
            `).join('');
        }

        function updateActivityLogsPagination(pagination) {
            const paginationDiv = document.getElementById('activityLogsPagination');
            
            if (pagination.total_pages <= 1) {
                paginationDiv.innerHTML = '';
                return;
            }
            
            let paginationHTML = '';
            
            // Previous button
            if (pagination.has_prev) {
                paginationHTML += `<button class="btn btn-small" onclick="loadActivityLogs(${currentActivityPage - 1})">‹ Previous</button>`;
            }
            
            // Current page info
            paginationHTML += `<span style="margin: 0 15px; color: #495057;">Page ${pagination.current_page} of ${pagination.total_pages}</span>`;
            
            // Next button
            if (pagination.has_next) {
                paginationHTML += `<button class="btn btn-small" onclick="loadActivityLogs(${currentActivityPage + 1})">Next ›</button>`;
            }
            
            paginationDiv.innerHTML = paginationHTML;
        }

        function populateUserFilter(logs) {
            const userFilter = document.getElementById('activityUserFilter');
            const currentValue = userFilter.value;
            
            // Get unique users from logs
            const users = [...new Map(logs.map(log => [log.UserID, { id: log.UserID, name: log.Username }])).values()];
            
            // Clear and repopulate options
            userFilter.innerHTML = '<option value="">All Users</option>';
            users.forEach(user => {
                const option = document.createElement('option');
                option.value = user.id;
                option.textContent = user.name;
                if (user.id == currentValue) option.selected = true;
                userFilter.appendChild(option);
            });
        }

        function filterActivityLogs() {
            // For now, just reload - in a full implementation you'd filter the displayed results
            loadActivityLogs(1);
        }

        function formatTimestamp(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleString();
        }

    document.addEventListener('DOMContentLoaded', function () { 
        console.log('Integrated User & Role Management loaded'); 
        updateDashboardStats(); 
        loadUsers(); // Load users on page load
    });
    </script>
</body>

</html>