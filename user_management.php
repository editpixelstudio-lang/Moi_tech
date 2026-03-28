<?php
/**
 * User Management Page
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
    <title>User Management - UZRS MOI Collection</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/user_management.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <h2>UZRS மொய் வசூல் - User Management</h2>
            </div>
            <div class="nav-user">
                <span>Welcome, <?php echo htmlspecialchars($user['name']); ?>! (Super Admin)</span>
                <a href="function_management.php" class="btn-dashboard">🎉 Functions</a>
                <a href="dashboard.php" class="btn-dashboard">Dashboard</a>
                <a href="api/logout.php" class="btn-logout">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <div class="header-title">
                <h1>👥 User Management</h1>
                <p class="subtitle">Manage all users in the system</p>
            </div>
            <div class="header-actions">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search by name, email, or phone..." class="search-input">
                </div>
                <div class="filter-controls">
                    <select id="roleFilter" class="filter-select">
                        <option value="">All Roles</option>
                        <option value="super_admin">🔴 Super Admin</option>
                        <option value="admin">🟠 Admin</option>
                        <option value="user">🔵 User</option>
                    </select>
                    <select id="statusFilter" class="filter-select">
                        <option value="">All Status</option>
                        <option value="1">✓ Active</option>
                        <option value="0">✗ Inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="users-table-container">
            <table class="users-table" id="usersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <tr>
                        <td colspan="8" class="loading">Loading users...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit User</h2>
                <span class="close">&times;</span>
            </div>
            <form id="editUserForm">
                <input type="hidden" id="editUserId">
                <div class="form-group">
                    <label for="editFullName">Full Name *</label>
                    <input type="text" id="editFullName" required>
                </div>
                <div class="form-group">
                    <label for="editEmail">Email</label>
                    <input type="email" id="editEmail">
                </div>
                <div class="form-group">
                    <label for="editPhone">Phone *</label>
                    <input type="text" id="editPhone" required>
                </div>
                <div class="form-group">
                    <label for="editRole">Role *</label>
                    <select id="editRole" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editStatus">Status *</label>
                    <select id="editStatus" required>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editPassword">New Password (leave blank to keep current)</label>
                    <input type="password" id="editPassword" placeholder="Enter new password or leave blank">
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
                <h2>Confirm Delete</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this user?</p>
                <p class="warning-text"><strong id="deleteUserName"></strong></p>
                <p class="warning-text">This action cannot be undone!</p>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="cancelDelete">✗ Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">🗑️ Delete User</button>
            </div>
        </div>
    </div>

    <script src="js/user_management.js"></script>
</body>
</html>
