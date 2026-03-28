<?php
/**
 * Function Management Page
 * UZRS MOI Collection System
 * Only accessible to Super Admin
 */

require_once 'includes/session.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$user = getCurrentUser();

// Check if user is super admin
if ($user['role'] !== 'super_admin') {
    redirect('dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Function Management - UZRS MOI Collection</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/function_management.css">
    <link rel="stylesheet" href="css/status.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <h2>UZRS மொய் வசூல் - Function Management</h2>
            </div>
            <div class="nav-user">
                <span>Welcome, <?php echo htmlspecialchars($user['name']); ?>! (Super Admin)</span>
                <!-- Connection status indicator -->
                <div id="connectionStatus" class="status-indicator status-offline" title="Connection Status">
                    <span class="status-icon">●</span> <span class="status-text">OFFLINE</span>
                </div>
                <a href="user_management.php" class="btn-dashboard">User Management</a>
                <a href="dashboard.php" class="btn-dashboard">Dashboard</a>
                <a href="api/logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <div class="header-title">
                <h1>🎉 Function Management</h1>
                <p class="subtitle">Manage all functions/events in the system</p>
            </div>
            <div class="header-actions">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search by function name, place, or user..." class="search-input">
                </div>
                <div class="filter-controls">
                    <input type="date" id="dateFilter" class="filter-input" placeholder="Filter by date">
                    <select id="userFilter" class="filter-select">
                        <option value="">All Users</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="functions-table-container">
            <table class="functions-table" id="functionsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Function Name</th>
                        <th>Date</th>
                        <th>Place</th>
                        <th>User</th>
                        <th>Computer #</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="functionsTableBody">
                    <tr>
                        <td colspan="8" class="loading">Loading functions...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- View Details Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📋 Function Details</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body" id="viewModalBody">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="closeView">✗ Close</button>
            </div>
        </div>
    </div>

    <!-- Edit Function Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>✏️ Edit Function</h2>
                <span class="close">&times;</span>
            </div>
            <form id="editFunctionForm">
                <div class="modal-body">
                    <input type="hidden" id="editFunctionId">
                    <div class="form-group">
                        <label for="editFunctionName">Function Name</label>
                        <input type="text" id="editFunctionName" required>
                    </div>
                    <div class="form-group">
                        <label for="editFunctionDate">Function Date</label>
                        <input type="date" id="editFunctionDate" required>
                    </div>
                    <div class="form-group">
                        <label for="editPlace">Place</label>
                        <input type="text" id="editPlace" required>
                    </div>
                    <div class="form-group">
                        <label for="editFunctionDetails">Details</label>
                        <textarea id="editFunctionDetails" rows="4" style="width: 100%; padding: 14px 16px; border: 2px solid var(--border-color); border-radius: var(--radius-md); font-size: 1rem; font-family: inherit; background: #f8f9fa; transition: var(--transition);"></textarea>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="cancelEdit">✗ Cancel</button>
                    <button type="submit" class="btn btn-primary">✓ Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>⚠️ Confirm Delete</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this function/event?</p>
                <p class="warning-text"><strong id="deleteFunctionName"></strong></p>
                <p class="warning-text">⚠️ This will also delete all associated collections and expenses!</p>
                <p class="warning-text">This action cannot be undone!</p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="cancelDelete">✗ Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">🗑️ Delete Function</button>
            </div>
        </div>
    </div>

    <script src="js/connection_manager.js"></script>
    <script src="js/function_management.js"></script>
</body>
</html>
