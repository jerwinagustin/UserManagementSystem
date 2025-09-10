<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - User Management System</title>
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
            max-width: 1200px;
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
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .content {
            padding: 30px;
        }

        .nav-links {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .nav-links a {
            background: #f8f9fa;
            color: #495057;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 8px;
            border: 2px solid #dee2e6;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }

        .filters h3 {
            margin-bottom: 15px;
            color: #495057;
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #495057;
        }

        .filter-group input, .filter-group select {
            padding: 10px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .filter-group input:focus, .filter-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #f5c6cb;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #c3e6cb;
        }

        .logs-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: top;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .log-action {
            font-weight: 600;
            color: #495057;
        }

        .log-timestamp {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .log-user {
            color: #667eea;
            font-weight: 500;
        }

        .log-details {
            color: #6c757d;
            font-size: 0.9rem;
            max-width: 300px;
            word-wrap: break-word;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .pagination button {
            padding: 8px 12px;
            border: 2px solid #dee2e6;
            background: white;
            color: #495057;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .pagination button:hover:not(:disabled) {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination .current-page {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #dee2e6;
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6c757d;
            font-weight: 500;
        }

        .no-logs {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .no-logs i {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 10px;
            }

            .header {
                padding: 20px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .content {
                padding: 20px;
            }

            .filter-row {
                grid-template-columns: 1fr;
            }

            .table-container {
                overflow-x: auto;
            }

            table {
                min-width: 600px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Activity Logs</h1>
            <p>Monitor and track all system activities</p>
        </div>

        <div class="content">
            <!-- Navigation Links -->
            <div class="nav-links">
                <a href="index.php">🏠 Dashboard</a>
                <a href="add_user_form.php">👤 Add User</a>
                <a href="activity_logs.php" style="background: #667eea; color: white;">📊 Activity Logs</a>
            </div>

            <!-- Stats -->
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number" id="totalLogs">-</div>
                    <div class="stat-label">Total Logs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="todayLogs">-</div>
                    <div class="stat-label">Today's Activities</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="uniqueUsers">-</div>
                    <div class="stat-label">Active Users</div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters">
                <h3>🔍 Filter Logs</h3>
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="userFilter">User ID:</label>
                        <input type="number" id="userFilter" placeholder="Enter User ID">
                    </div>
                    <div class="filter-group">
                        <label for="actionFilter">Action:</label>
                        <select id="actionFilter">
                            <option value="">All Actions</option>
                            <option value="Login">Login</option>
                            <option value="User Created">User Created</option>
                            <option value="User Updated">User Updated</option>
                            <option value="User Deleted">User Deleted</option>
                            <option value="Profile Updated">Profile Updated</option>
                            <option value="Roles Updated">Roles Updated</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="startDate">Start Date:</label>
                        <input type="date" id="startDate">
                    </div>
                    <div class="filter-group">
                        <label for="endDate">End Date:</label>
                        <input type="date" id="endDate">
                    </div>
                </div>
                <div class="filter-actions">
                    <button class="btn btn-primary" onclick="applyFilters()">🔍 Apply Filters</button>
                    <button class="btn btn-secondary" onclick="clearFilters()">🧹 Clear Filters</button>
                    <button class="btn btn-primary" onclick="exportLogs()">📄 Export CSV</button>
                </div>
            </div>

            <!-- Loading and Error Messages -->
            <div id="loading" class="loading" style="display: none;">
                <p>⏳ Loading activity logs...</p>
            </div>

            <div id="error" class="error" style="display: none;"></div>
            <div id="success" class="success" style="display: none;"></div>

            <!-- Logs Table -->
            <div class="logs-table">
                <div class="table-container">
                    <table id="logsTable">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>Timestamp</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="logsTableBody">
                            <!-- Logs will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="pagination" id="pagination" style="display: none;">
                <!-- Pagination will be populated here -->
            </div>

            <!-- No logs message -->
            <div id="noLogs" class="no-logs" style="display: none;">
                <i>📝</i>
                <h3>No Activity Logs Found</h3>
                <p>No activity logs match your current filters.</p>
            </div>
        </div>
    </div>

    <script>
        let currentPage = 1;
        let totalPages = 1;
        const logsPerPage = 20;

        // Load logs on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadActivityLogs();
        });

        function showLoading() {
            document.getElementById('loading').style.display = 'block';
            document.getElementById('error').style.display = 'none';
            document.getElementById('success').style.display = 'none';
            document.getElementById('noLogs').style.display = 'none';
        }

        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }

        function showError(message) {
            const errorDiv = document.getElementById('error');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            hideLoading();
        }

        function showSuccess(message) {
            const successDiv = document.getElementById('success');
            successDiv.textContent = message;
            successDiv.style.display = 'block';
            setTimeout(() => {
                successDiv.style.display = 'none';
            }, 3000);
        }

        function loadActivityLogs(page = 1) {
            showLoading();
            currentPage = page;

            // Build query parameters
            const params = new URLSearchParams({
                page: page,
                limit: logsPerPage
            });

            // Add filters
            const userFilter = document.getElementById('userFilter').value;
            const actionFilter = document.getElementById('actionFilter').value;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            if (userFilter) params.append('user_id', userFilter);
            if (actionFilter) params.append('action', actionFilter);
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);

            fetch(`activitylogs/get_all_logs.php?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success) {
                        displayLogs(data.data.logs);
                        updatePagination(data.data.pagination);
                        updateStats(data.data);
                    } else {
                        showError(data.message || 'Failed to load activity logs');
                    }
                })
                .catch(error => {
                    hideLoading();
                    showError('Network error: ' + error.message);
                });
        }

        function displayLogs(logs) {
            const tbody = document.getElementById('logsTableBody');
            
            if (logs.length === 0) {
                tbody.innerHTML = '';
                document.getElementById('logsTable').style.display = 'none';
                document.getElementById('noLogs').style.display = 'block';
                return;
            }

            document.getElementById('logsTable').style.display = 'table';
            document.getElementById('noLogs').style.display = 'none';

            tbody.innerHTML = logs.map(log => `
                <tr>
                    <td>
                        <div class="log-user">${log.Username}</div>
                        <div style="font-size: 0.8rem; color: #6c757d;">ID: ${log.UserID}</div>
                    </td>
                    <td>
                        <span class="log-action">${log.Action}</span>
                    </td>
                    <td>
                        <div class="log-details">${log.Details || '-'}</div>
                    </td>
                    <td>
                        <div class="log-timestamp">${formatTimestamp(log.Timestamp)}</div>
                    </td>
                    <td>
                        <button class="btn btn-danger" onclick="deleteLog(${log.ActivityLogID})" 
                                style="font-size: 12px; padding: 5px 10px;">🗑️ Delete</button>
                    </td>
                </tr>
            `).join('');
        }

        function updatePagination(pagination) {
            const paginationDiv = document.getElementById('pagination');
            totalPages = pagination.total_pages;
            currentPage = pagination.current_page;

            if (totalPages <= 1) {
                paginationDiv.style.display = 'none';
                return;
            }

            paginationDiv.style.display = 'flex';
            
            let paginationHTML = '';
            
            // Previous button
            paginationHTML += `<button onclick="loadActivityLogs(${currentPage - 1})" ${!pagination.has_prev ? 'disabled' : ''}>‹ Previous</button>`;
            
            // Page numbers
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);
            
            if (startPage > 1) {
                paginationHTML += `<button onclick="loadActivityLogs(1)">1</button>`;
                if (startPage > 2) {
                    paginationHTML += `<span>...</span>`;
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                paginationHTML += `<button onclick="loadActivityLogs(${i})" ${i === currentPage ? 'class="current-page"' : ''}>${i}</button>`;
            }
            
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    paginationHTML += `<span>...</span>`;
                }
                paginationHTML += `<button onclick="loadActivityLogs(${totalPages})">${totalPages}</button>`;
            }
            
            // Next button
            paginationHTML += `<button onclick="loadActivityLogs(${currentPage + 1})" ${!pagination.has_next ? 'disabled' : ''}>Next ›</button>`;
            
            paginationDiv.innerHTML = paginationHTML;
        }

        function updateStats(data) {
            document.getElementById('totalLogs').textContent = data.pagination.total_records;
            
            // Calculate today's logs
            const today = new Date().toISOString().split('T')[0];
            const todayLogs = data.logs.filter(log => log.Timestamp.startsWith(today)).length;
            document.getElementById('todayLogs').textContent = todayLogs;
            
            // Calculate unique users
            const uniqueUsers = new Set(data.logs.map(log => log.UserID)).size;
            document.getElementById('uniqueUsers').textContent = uniqueUsers;
        }

        function formatTimestamp(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleString();
        }

        function applyFilters() {
            currentPage = 1;
            loadActivityLogs(1);
        }

        function clearFilters() {
            document.getElementById('userFilter').value = '';
            document.getElementById('actionFilter').value = '';
            document.getElementById('startDate').value = '';
            document.getElementById('endDate').value = '';
            currentPage = 1;
            loadActivityLogs(1);
        }

        function deleteLog(logId) {
            if (!confirm('Are you sure you want to delete this activity log?')) {
                return;
            }

            fetch('activitylogs/delete_log.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    ActivityLogID: logId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess('Activity log deleted successfully');
                    loadActivityLogs(currentPage);
                } else {
                    showError(data.message || 'Failed to delete activity log');
                }
            })
            .catch(error => {
                showError('Network error: ' + error.message);
            });
        }

        function exportLogs() {
            // Build query parameters for export
            const params = new URLSearchParams();
            
            const userFilter = document.getElementById('userFilter').value;
            const actionFilter = document.getElementById('actionFilter').value;
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            if (userFilter) params.append('user_id', userFilter);
            if (actionFilter) params.append('action', actionFilter);
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);
            params.append('limit', '1000'); // Export more logs

            fetch(`activitylogs/get_all_logs.php?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        downloadCSV(data.data.logs);
                    } else {
                        showError('Failed to export logs: ' + data.message);
                    }
                })
                .catch(error => {
                    showError('Export error: ' + error.message);
                });
        }

        function downloadCSV(logs) {
            const headers = ['User ID', 'Username', 'Action', 'Details', 'Timestamp'];
            const csvContent = [
                headers.join(','),
                ...logs.map(log => [
                    log.UserID,
                    `"${log.Username}"`,
                    `"${log.Action}"`,
                    `"${(log.Details || '').replace(/"/g, '""')}"`,
                    `"${log.Timestamp}"`
                ].join(','))
            ].join('\n');

            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = `activity_logs_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            showSuccess('Logs exported successfully');
        }
    </script>
</body>
</html>
