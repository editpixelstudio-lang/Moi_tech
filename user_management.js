/**
 * User Management JavaScript
 * Handles CRUD operations for user management
 */

let allUsers = [];
let currentDeleteUserId = null;

// DOM Elements
const usersTableBody = document.getElementById('usersTableBody');
const searchInput = document.getElementById('searchInput');
const roleFilter = document.getElementById('roleFilter');
const statusFilter = document.getElementById('statusFilter');
const editModal = document.getElementById('editModal');
const deleteModal = document.getElementById('deleteModal');
const editUserForm = document.getElementById('editUserForm');

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadUsers();
    setupEventListeners();
});

// Setup Event Listeners
function setupEventListeners() {
    // Search and filters
    searchInput.addEventListener('input', filterUsers);
    roleFilter.addEventListener('change', filterUsers);
    statusFilter.addEventListener('change', filterUsers);
    
    // Edit modal
    document.querySelector('#editModal .close').addEventListener('click', closeEditModal);
    document.getElementById('cancelEdit').addEventListener('click', closeEditModal);
    editUserForm.addEventListener('submit', handleEditSubmit);
    
    // Delete modal
    document.querySelector('#deleteModal .close').addEventListener('click', closeDeleteModal);
    document.getElementById('cancelDelete').addEventListener('click', closeDeleteModal);
    document.getElementById('confirmDelete').addEventListener('click', handleDelete);
    
    // Close modals on outside click
    window.addEventListener('click', function(event) {
        if (event.target === editModal) {
            closeEditModal();
        }
        if (event.target === deleteModal) {
            closeDeleteModal();
        }
    });
}

// Load Users from API
async function loadUsers() {
    try {
        const response = await fetch('api/get_users.php');
        const data = await response.json();
        
        if (data.success) {
            allUsers = data.users;
            displayUsers(allUsers);
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('Failed to load users: ' + error.message);
    }
}

// Display Users in Table
function displayUsers(users) {
    if (users.length === 0) {
        usersTableBody.innerHTML = '<tr><td colspan="8" class="no-users">No users found</td></tr>';
        return;
    }
    
    const html = users.map(user => `
        <tr>
            <td>${user.id}</td>
            <td>${escapeHtml(user.full_name)}</td>
            <td>${user.email ? escapeHtml(user.email) : '-'}</td>
            <td>${escapeHtml(user.phone)}</td>
            <td><span class="role-badge ${user.role}">${formatRole(user.role)}</span></td>
            <td><span class="status-badge ${user.is_active ? 'active' : 'inactive'}">${user.is_active ? 'Active' : 'Inactive'}</span></td>
            <td>${formatDate(user.created_at)}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-edit" onclick="openEditModal(${user.id})">✏️ Edit</button>
                    <button class="btn btn-delete" onclick="openDeleteModal(${user.id}, '${escapeHtml(user.full_name)}')">🗑️ Delete</button>
                </div>
            </td>
        </tr>
    `).join('');
    
    usersTableBody.innerHTML = html;
}

// Filter Users
function filterUsers() {
    const searchTerm = searchInput.value.toLowerCase();
    const role = roleFilter.value;
    const status = statusFilter.value;
    
    let filtered = allUsers.filter(user => {
        const matchesSearch = !searchTerm || 
            user.full_name.toLowerCase().includes(searchTerm) ||
            (user.email && user.email.toLowerCase().includes(searchTerm)) ||
            user.phone.includes(searchTerm);
        
        const matchesRole = !role || user.role === role;
        const matchesStatus = status === '' || user.is_active === (status === '1');
        
        return matchesSearch && matchesRole && matchesStatus;
    });
    
    displayUsers(filtered);
}

// Open Edit Modal
function openEditModal(userId) {
    const user = allUsers.find(u => u.id === userId);
    if (!user) return;
    
    document.getElementById('editUserId').value = user.id;
    document.getElementById('editFullName').value = user.full_name;
    document.getElementById('editEmail').value = user.email || '';
    document.getElementById('editPhone').value = user.phone;
    document.getElementById('editRole').value = user.role;
    document.getElementById('editStatus').value = user.is_active ? '1' : '0';
    document.getElementById('editPassword').value = '';
    
    editModal.classList.add('show');
}

// Close Edit Modal
function closeEditModal() {
    editModal.classList.remove('show');
    editUserForm.reset();
}

// Handle Edit Form Submit
async function handleEditSubmit(e) {
    e.preventDefault();
    
    const userId = document.getElementById('editUserId').value;
    const fullName = document.getElementById('editFullName').value.trim();
    const email = document.getElementById('editEmail').value.trim();
    const phone = document.getElementById('editPhone').value.trim();
    const role = document.getElementById('editRole').value;
    const isActive = document.getElementById('editStatus').value;
    const password = document.getElementById('editPassword').value;
    
    if (!fullName || !phone) {
        alert('Full name and phone are required');
        return;
    }
    
    const data = {
        id: parseInt(userId),
        full_name: fullName,
        email: email || null,
        phone: phone,
        role: role,
        is_active: parseInt(isActive)
    };
    
    if (password) {
        data.password = password;
    }
    
    try {
        const response = await fetch('api/update_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess('User updated successfully');
            closeEditModal();
            await loadUsers();
        } else {
            showError(result.message);
        }
    } catch (error) {
        showError('Failed to update user: ' + error.message);
    }
}

// Open Delete Modal
function openDeleteModal(userId, userName) {
    currentDeleteUserId = userId;
    document.getElementById('deleteUserName').textContent = userName;
    deleteModal.classList.add('show');
}

// Close Delete Modal
function closeDeleteModal() {
    deleteModal.classList.remove('show');
    currentDeleteUserId = null;
}

// Handle Delete
async function handleDelete() {
    if (!currentDeleteUserId) return;
    
    try {
        const response = await fetch('api/delete_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: currentDeleteUserId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess('User deleted successfully');
            closeDeleteModal();
            await loadUsers();
        } else {
            showError(result.message);
        }
    } catch (error) {
        showError('Failed to delete user: ' + error.message);
    }
}

// Utility Functions
function formatRole(role) {
    return role.replace('_', ' ').split(' ').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ');
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB') + ' ' + date.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showSuccess(message) {
    alert('✓ ' + message);
}

function showError(message) {
    alert('✗ ' + message);
}
