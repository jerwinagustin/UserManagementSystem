<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User - User Management System</title>
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
            max-width: 800px;
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
        
        .content {
            padding: 40px;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section h3 {
            color: #2d3748;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 10px;
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
        
        .required {
            color: #e53e3e;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1em;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
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
        
        .btn-secondary {
            background: #718096;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4a5568;
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
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Add New User</h1>
            <p>Create a new user account with profile information</p>
        </div>
        
        <div class="content">
            <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
            
            <div id="message"></div>
            
            <form id="addUserForm">
                <div class="form-section">
                    <h3>Account Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Username <span class="required">*</span></label>
                            <input type="text" id="username" name="username" required 
                                   placeholder="Enter username" minlength="3">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" required 
                                   placeholder="user@example.com">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password <span class="required">*</span></label>
                            <input type="password" id="password" name="password" required 
                                   placeholder="Enter password" minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Profile Information</h3>
                    
                    <div class="form-group">
                        <label for="fullName">Full Name <span class="required">*</span></label>
                        <input type="text" id="fullName" name="fullName" required 
                               placeholder="Enter full name" minlength="2">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phoneNumber">Phone Number</label>
                            <input type="text" id="phoneNumber" name="phoneNumber" 
                                   placeholder="+1 555 0100">
                        </div>
                        
                        <div class="form-group">
                            <label for="dateOfBirth">Date of Birth</label>
                            <input type="date" id="dateOfBirth" name="dateOfBirth">
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
                </div>
                
                <div class="form-section">
                    <h3>Role Assignment</h3>
                    <div class="form-group">
                        <label for="roleId">Role <span class="required">*</span></label>
                        <select id="roleId" name="roleId" required>
                            <option value="">Select a role...</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Create User</button>
                <button type="reset" class="btn btn-secondary">Reset Form</button>
            </form>
        </div>
    </div>
    
    <script>
        // Load available roles
        function loadRoles() {
            fetch('roles/get_roles.php')
                .then(response => response.json())
                .then(data => {
                    const roleSelect = document.getElementById('roleId');
                    
                    if (data.success && data.data && data.data.length > 0) {
                        // Clear existing options except the first one
                        roleSelect.innerHTML = '<option value="">Select a role...</option>';
                        
                        data.data.forEach(role => {
                            const option = document.createElement('option');
                            option.value = role.RoleID;
                            option.textContent = role.RoleName + (role.Description ? ` - ${role.Description}` : '');
                            roleSelect.appendChild(option);
                        });
                    } else {
                        roleSelect.innerHTML = '<option value="">No roles available</option>';
                    }
                })
                .catch(error => {
                    console.error('Error loading roles:', error);
                    const roleSelect = document.getElementById('roleId');
                    roleSelect.innerHTML = '<option value="">Error loading roles</option>';
                });
        }
        
        // Handle form submission
        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {};
            
            // Convert form data to object
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            
            // Convert roleId to integer if provided
            if (data.roleId) {
                data.roleId = parseInt(data.roleId);
            }
            
            // Show loading message
            showMessage('Creating user...', 'info');
            
            // Submit data
            fetch('users/add_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showMessage(result.message, 'success');
                    document.getElementById('addUserForm').reset();
                    // Optionally redirect to the new user's profile
                    setTimeout(() => {
                        window.location.href = `profile.php?id=${result.data.UserID}`;
                    }, 2000);
                } else {
                    showMessage(result.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('An error occurred while creating the user.', 'error');
            });
        });
        
        function showMessage(text, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.innerHTML = `<div class="message ${type}">${text}</div>`;
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(() => {
                    messageDiv.innerHTML = '';
                }, 5000);
            }
        }
        
        // Load roles when page loads
        document.addEventListener('DOMContentLoaded', loadRoles);
    </script>
</body>
</html>
