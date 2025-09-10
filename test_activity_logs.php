<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs Testing - User Management System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            margin: 0;
        }

        .container {
            max-width: 1000px;
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

        .content {
            padding: 30px;
        }

        .test-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }

        .test-section h3 {
            color: #495057;
            margin-bottom: 15px;
        }

        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin: 5px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #28a745;
        }

        .btn-danger {
            background: #dc3545;
        }

        .result {
            margin-top: 15px;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }

        .result.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .result.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .result.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .nav-links {
            margin-bottom: 20px;
        }

        .nav-links a {
            background: #f8f9fa;
            color: #495057;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 8px;
            margin-right: 10px;
            border: 2px solid #dee2e6;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .test-input {
            width: 100%;
            padding: 10px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Activity Logs Testing</h1>
            <p>Test and verify the Activity Logs implementation</p>
        </div>

        <div class="content">
            <!-- Navigation -->
            <div class="nav-links">
                <a href="index.php">🏠 Dashboard</a>
                <a href="activity_logs.php">📊 Activity Logs Page</a>
                <a href="add_user_form.php">👤 Add User</a>
            </div>

            <!-- Test 1: Basic Logging Function -->
            <div class="test-section">
                <h3>🔧 Test 1: Basic Logging Function</h3>
                <p>Test the log_activity function directly</p>
                <input type="number" id="testUserId" class="test-input" placeholder="User ID (e.g., 1)" value="1">
                <input type="text" id="testAction" class="test-input" placeholder="Action (e.g., Test Action)" value="Test Action">
                <input type="text" id="testDetails" class="test-input" placeholder="Details (e.g., Testing logging functionality)" value="Testing logging functionality">
                <button class="btn" onclick="testLogActivity()">🔬 Test Log Activity</button>
                <div id="testLogResult" class="result info" style="display: none;"></div>
            </div>

            <!-- Test 2: API Endpoints -->
            <div class="test-section">
                <h3>📡 Test 2: API Endpoints</h3>
                <p>Test the Activity Logs API endpoints</p>
                <button class="btn" onclick="testGetAllLogs()">📋 Test Get All Logs</button>
                <button class="btn" onclick="testGetUserLogs()">👤 Test Get User Logs (User ID: 1)</button>
                <button class="btn" onclick="testGetUserLogsWithPagination()">📄 Test Pagination</button>
                <div id="testApiResult" class="result info" style="display: none;"></div>
            </div>

            <!-- Test 3: Frontend Integration -->
            <div class="test-section">
                <h3>🎨 Test 3: Frontend Integration</h3>
                <p>Test the frontend components</p>
                <button class="btn" onclick="testDashboardTab()">🏠 Open Dashboard Activity Tab</button>
                <button class="btn" onclick="testFullActivityPage()">📊 Open Full Activity Page</button>
                <button class="btn" onclick="testProfileActivitySection()">👤 Test Profile Activity Section</button>
                <div id="testFrontendResult" class="result info" style="display: none;"></div>
            </div>

            <!-- Test 4: User Operations Logging -->
            <div class="test-section">
                <h3>⚡ Test 4: User Operations Logging</h3>
                <p>Test that user operations create appropriate logs</p>
                <button class="btn btn-success" onclick="createTestUser()">✅ Create Test User (Should Log)</button>
                <button class="btn" onclick="updateTestUser()">✏️ Update Test User (Should Log)</button>
                <button class="btn btn-danger" onclick="deleteTestUser()">❌ Delete Test User (Should Log)</button>
                <div id="testOperationsResult" class="result info" style="display: none;"></div>
            </div>

            <!-- Test 5: Login Tracking -->
            <div class="test-section">
                <h3>🔐 Test 5: Login Tracking</h3>
                <p>Test login activity tracking</p>
                <input type="text" id="loginUsername" class="test-input" placeholder="Username" value="testuser">
                <button class="btn btn-success" onclick="testSuccessfulLogin()">✅ Simulate Successful Login</button>
                <button class="btn btn-danger" onclick="testFailedLogin()">❌ Simulate Failed Login</button>
                <div id="testLoginResult" class="result info" style="display: none;"></div>
            </div>

            <!-- Test 6: Verification -->
            <div class="test-section">
                <h3>✅ Test 6: Verification</h3>
                <p>Verify all logs were created correctly</p>
                <button class="btn" onclick="verifyRecentLogs()">🔍 Check Recent Activity Logs</button>
                <button class="btn" onclick="countTotalLogs()">📊 Count Total Logs</button>
                <button class="btn btn-danger" onclick="cleanupTestLogs()">🧹 Cleanup Test Logs</button>
                <div id="testVerificationResult" class="result info" style="display: none;"></div>
            </div>
        </div>
    </div>

    <script>
        let testUserId = null;

        function showResult(elementId, content, type = 'info') {
            const element = document.getElementById(elementId);
            element.textContent = content;
            element.className = `result ${type}`;
            element.style.display = 'block';
        }

        function formatResponse(response) {
            return JSON.stringify(response, null, 2);
        }

        // Test 1: Basic Logging Function
        function testLogActivity() {
            const userId = document.getElementById('testUserId').value;
            const action = document.getElementById('testAction').value;
            const details = document.getElementById('testDetails').value;

            if (!userId || !action) {
                showResult('testLogResult', 'Please provide User ID and Action', 'error');
                return;
            }

            // Since we can't directly call PHP function from JS, we'll create a test endpoint
            fetch('login_tracker.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username: 'testuser', // This would normally be dynamic
                    success: true,
                    details: `${action}: ${details}`
                })
            })
            .then(response => response.json())
            .then(data => {
                showResult('testLogResult', `Login tracker test:\n${formatResponse(data)}`, data.success ? 'success' : 'error');
            })
            .catch(error => {
                showResult('testLogResult', `Error testing log activity: ${error.message}`, 'error');
            });
        }

        // Test 2: API Endpoints
        function testGetAllLogs() {
            fetch('activitylogs/get_all_logs.php?limit=5')
                .then(response => response.json())
                .then(data => {
                    showResult('testApiResult', `Get All Logs (limit 5):\n${formatResponse(data)}`, data.success ? 'success' : 'error');
                })
                .catch(error => {
                    showResult('testApiResult', `Error testing get all logs: ${error.message}`, 'error');
                });
        }

        function testGetUserLogs() {
            const userId = document.getElementById('testUserId').value || 1;
            fetch(`activitylogs/get_user_logs.php?user_id=${userId}&limit=3`)
                .then(response => response.json())
                .then(data => {
                    showResult('testApiResult', `Get User Logs (User ${userId}, limit 3):\n${formatResponse(data)}`, data.success ? 'success' : 'error');
                })
                .catch(error => {
                    showResult('testApiResult', `Error testing get user logs: ${error.message}`, 'error');
                });
        }

        function testGetUserLogsWithPagination() {
            fetch('activitylogs/get_all_logs.php?page=1&limit=2')
                .then(response => response.json())
                .then(data => {
                    showResult('testApiResult', `Pagination Test (page 1, limit 2):\n${formatResponse(data)}`, data.success ? 'success' : 'error');
                })
                .catch(error => {
                    showResult('testApiResult', `Error testing pagination: ${error.message}`, 'error');
                });
        }

        // Test 3: Frontend Integration
        function testDashboardTab() {
            showResult('testFrontendResult', 'Opening main dashboard...', 'info');
            window.open('index.php', '_blank');
            setTimeout(() => {
                showResult('testFrontendResult', 'Dashboard opened. Click on "Activity Logs" tab to test integration.', 'success');
            }, 1000);
        }

        function testFullActivityPage() {
            showResult('testFrontendResult', 'Opening full activity logs page...', 'info');
            window.open('activity_logs.php', '_blank');
            setTimeout(() => {
                showResult('testFrontendResult', 'Activity logs page opened. Test filtering and pagination features.', 'success');
            }, 1000);
        }

        function testProfileActivitySection() {
            const userId = document.getElementById('testUserId').value || 1;
            showResult('testFrontendResult', `Opening profile page for user ${userId}...`, 'info');
            window.open(`profile.php?id=${userId}`, '_blank');
            setTimeout(() => {
                showResult('testFrontendResult', 'Profile page opened. Scroll down to see Activity Logs section.', 'success');
            }, 1000);
        }

        // Test 4: User Operations Logging
        function createTestUser() {
            const testUserData = {
                username: `testuser_${Date.now()}`,
                email: `test_${Date.now()}@example.com`,
                password: 'password123',
                fullName: 'Test User Activity Logs',
                status: 'Active',
                address: '123 Test Street',
                phoneNumber: '+1234567890',
                dateOfBirth: '1990-01-01',
                avatar: '',
                roleId: 1 // Assuming role 1 exists
            };

            fetch('users/add_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(testUserData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    testUserId = data.data.UserID;
                    showResult('testOperationsResult', `Test user created successfully!\nUser ID: ${testUserId}\nThis should have created an activity log entry.\n\n${formatResponse(data)}`, 'success');
                } else {
                    showResult('testOperationsResult', `Error creating test user:\n${formatResponse(data)}`, 'error');
                }
            })
            .catch(error => {
                showResult('testOperationsResult', `Network error creating test user: ${error.message}`, 'error');
            });
        }

        function updateTestUser() {
            if (!testUserId) {
                showResult('testOperationsResult', 'Please create a test user first', 'error');
                return;
            }

            const updateData = {
                UserID: testUserId,
                profile: {
                    FullName: 'Updated Test User Name',
                    Address: '456 Updated Street'
                }
            };

            fetch('users/update_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(updateData)
            })
            .then(response => response.json())
            .then(data => {
                showResult('testOperationsResult', `Test user update result:\n${formatResponse(data)}`, data.success ? 'success' : 'error');
            })
            .catch(error => {
                showResult('testOperationsResult', `Error updating test user: ${error.message}`, 'error');
            });
        }

        function deleteTestUser() {
            if (!testUserId) {
                showResult('testOperationsResult', 'Please create a test user first', 'error');
                return;
            }

            if (!confirm(`Are you sure you want to delete test user ${testUserId}?`)) {
                return;
            }

            fetch('users/delete_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    UserID: testUserId
                })
            })
            .then(response => response.json())
            .then(data => {
                showResult('testOperationsResult', `Test user deletion result:\n${formatResponse(data)}`, data.success ? 'success' : 'error');
                if (data.success) {
                    testUserId = null;
                }
            })
            .catch(error => {
                showResult('testOperationsResult', `Error deleting test user: ${error.message}`, 'error');
            });
        }

        // Test 5: Login Tracking
        function testSuccessfulLogin() {
            const username = document.getElementById('loginUsername').value;
            
            fetch('login_tracker.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username: username,
                    success: true,
                    details: 'Login test from activity logs testing page'
                })
            })
            .then(response => response.json())
            .then(data => {
                showResult('testLoginResult', `Successful login tracking:\n${formatResponse(data)}`, data.success ? 'success' : 'error');
            })
            .catch(error => {
                showResult('testLoginResult', `Error testing successful login: ${error.message}`, 'error');
            });
        }

        function testFailedLogin() {
            const username = document.getElementById('loginUsername').value;
            
            fetch('login_tracker.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username: username,
                    success: false,
                    details: 'Failed login test from activity logs testing page'
                })
            })
            .then(response => response.json())
            .then(data => {
                showResult('testLoginResult', `Failed login tracking:\n${formatResponse(data)}`, data.success ? 'success' : 'error');
            })
            .catch(error => {
                showResult('testLoginResult', `Error testing failed login: ${error.message}`, 'error');
            });
        }

        // Test 6: Verification
        function verifyRecentLogs() {
            fetch('activitylogs/get_all_logs.php?limit=10')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const recentLogs = data.data.logs.slice(0, 5);
                        showResult('testVerificationResult', `Recent Activity Logs (last 5):\n${formatResponse(recentLogs)}`, 'success');
                    } else {
                        showResult('testVerificationResult', `Error getting recent logs:\n${formatResponse(data)}`, 'error');
                    }
                })
                .catch(error => {
                    showResult('testVerificationResult', `Error verifying logs: ${error.message}`, 'error');
                });
        }

        function countTotalLogs() {
            fetch('activitylogs/get_all_logs.php?limit=1')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const totalLogs = data.data.pagination.total_records;
                        showResult('testVerificationResult', `Total Activity Logs in Database: ${totalLogs}`, 'success');
                    } else {
                        showResult('testVerificationResult', `Error counting logs:\n${formatResponse(data)}`, 'error');
                    }
                })
                .catch(error => {
                    showResult('testVerificationResult', `Error counting logs: ${error.message}`, 'error');
                });
        }

        function cleanupTestLogs() {
            showResult('testVerificationResult', 'Cleanup test logs is not implemented in this demo.\nIn a production system, you would:\n1. Delete specific test log entries\n2. Or truncate the activity logs table\n3. Be very careful with cleanup operations!', 'info');
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Activity Logs Testing Page Loaded');
        });
    </script>
</body>
</html>
