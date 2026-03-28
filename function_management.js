/**
 * Function Management JavaScript
 * Handles CRUD operations for function management
 */

let allFunctions = [];
let allUsers = [];
let currentDeleteFunctionId = null;
let isEditSubmitting = false;

// DOM Elements
const functionsTableBody = document.getElementById('functionsTableBody');
const searchInput = document.getElementById('searchInput');
const dateFilter = document.getElementById('dateFilter');
const userFilter = document.getElementById('userFilter');
const viewModal = document.getElementById('viewModal');
const editModal = document.getElementById('editModal');
const deleteModal = document.getElementById('deleteModal');
const editFunctionForm = document.getElementById('editFunctionForm');

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadFunctions();
    loadUsers();
    setupEventListeners();
});

// Setup Event Listeners
function setupEventListeners() {
    // Search and filters
    searchInput.addEventListener('input', filterFunctions);
    dateFilter.addEventListener('change', filterFunctions);
    userFilter.addEventListener('change', filterFunctions);
    
    // View modal
    document.querySelector('#viewModal .close').addEventListener('click', closeViewModal);
    document.getElementById('closeView').addEventListener('click', closeViewModal);
    
    // Edit modal
    document.querySelector('#editModal .close').addEventListener('click', closeEditModal);
    document.getElementById('cancelEdit').addEventListener('click', closeEditModal);
    editFunctionForm.addEventListener('submit', handleEditSubmit);
    
    // Delete modal
    document.querySelector('#deleteModal .close').addEventListener('click', closeDeleteModal);
    document.getElementById('cancelDelete').addEventListener('click', closeDeleteModal);
    document.getElementById('confirmDelete').addEventListener('click', handleDelete);
    
    // Close modals on outside click
    window.addEventListener('click', function(event) {
        if (event.target === viewModal) closeViewModal();
        if (event.target === editModal) closeEditModal();
        if (event.target === deleteModal) closeDeleteModal();
    });
}

// Load Functions from API
async function loadFunctions() {
    try {
        const response = await fetch('api/get_all_functions.php');
        const data = await response.json();
        
        if (data.success) {
            allFunctions = data.functions;
            displayFunctions(allFunctions);
        } else {
            showError(data.message);
        }
    } catch (error) {
        showError('Failed to load functions: ' + error.message);
    }
}

// Load Users for Filter
async function loadUsers() {
    try {
        const response = await fetch('api/get_users.php');
        const data = await response.json();
        
        if (data.success) {
            allUsers = data.users;
            populateUserFilter(allUsers);
        }
    } catch (error) {
        console.error('Failed to load users for filter:', error);
    }
}

// Populate User Filter Dropdown
function populateUserFilter(users) {
    userFilter.innerHTML = '<option value="">All Users</option>';
    users.forEach(user => {
        const option = document.createElement('option');
        option.value = user.id;
        option.textContent = `${user.full_name} (${user.phone})`;
        userFilter.appendChild(option);
    });
}

// Display Functions in Table
function displayFunctions(functions) {
    if (functions.length === 0) {
        functionsTableBody.innerHTML = '<tr><td colspan="8" class="no-users">No functions found</td></tr>';
        return;
    }
    
    const html = functions.map(func => `
        <tr>
            <td>${func.id}</td>
            <td><strong>${escapeHtml(func.function_name)}</strong></td>
            <td>${formatDate(func.function_date)}</td>
            <td>${escapeHtml(func.place)}</td>
            <td>${escapeHtml(func.user_name || 'N/A')}<br><small>${func.user_phone || ''}</small></td>
            <td>${func.computer_number || '-'}</td>
            <td>${formatDateTime(func.created_at)}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn btn-edit" onclick="openViewModal(${func.id})" style="background: linear-gradient(135deg, #3498db, #2980b9);">👁️ View</button>
                    <button class="btn btn-edit" onclick="openEditModal(${func.id})">✏️ Edit</button>
                    <button class="btn btn-delete" onclick="openDeleteModal(${func.id}, '${escapeHtml(func.function_name)}')">🗑️ Delete</button>
                </div>
            </td>
        </tr>
    `).join('');
    
    functionsTableBody.innerHTML = html;
}

// Filter Functions
function filterFunctions() {
    const searchTerm = searchInput.value.toLowerCase();
    const selectedDate = dateFilter.value;
    const selectedUser = userFilter.value;
    
    let filtered = allFunctions.filter(func => {
        const matchesSearch = !searchTerm || 
            func.function_name.toLowerCase().includes(searchTerm) ||
            func.place.toLowerCase().includes(searchTerm) ||
            (func.user_name && func.user_name.toLowerCase().includes(searchTerm));
        
        const matchesDate = !selectedDate || func.function_date === selectedDate;
        const matchesUser = !selectedUser || func.user_id == selectedUser;
        
        return matchesSearch && matchesDate && matchesUser;
    });
    
    displayFunctions(filtered);
}

// Open View Modal
function openViewModal(functionId) {
    const func = allFunctions.find(f => f.id === functionId);
    if (!func) return;
    
    const viewBody = document.getElementById('viewModalBody');
    viewBody.innerHTML = `
        <div style="line-height: 1.8;">
            <p><strong>📝 Function Name:</strong> ${escapeHtml(func.function_name)}</p>
            <p><strong>📅 Date:</strong> ${formatDate(func.function_date)}</p>
            <p><strong>📍 Place:</strong> ${escapeHtml(func.place)}</p>
            <p><strong>👤 User:</strong> ${escapeHtml(func.user_name || 'N/A')} ${func.user_phone ? '(' + func.user_phone + ')' : ''}</p>
            <p><strong>💻 Computer #:</strong> ${func.computer_number || 'Not assigned'}</p>
            <p><strong>🕒 Created:</strong> ${formatDateTime(func.created_at)}</p>
            ${func.function_details ? `<div style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;"><strong>📋 Details:</strong><p style="margin-top: 8px;">${escapeHtml(func.function_details).replace(/\n/g, '<br>')}</p></div>` : ''}
        </div>
    `;
    
    viewModal.classList.add('show');
}

// Close View Modal
function closeViewModal() {
    viewModal.classList.remove('show');
}

// Open Edit Modal
function openEditModal(functionId) {
    const func = allFunctions.find(f => f.id === functionId);
    if (!func) return;
    
    document.getElementById('editFunctionId').value = func.id;
    document.getElementById('editFunctionName').value = func.function_name;
    document.getElementById('editFunctionDate').value = func.function_date;
    document.getElementById('editPlace').value = func.place;
    document.getElementById('editFunctionDetails').value = func.function_details || '';
    
    editModal.classList.add('show');
}

// Close Edit Modal
function closeEditModal() {
    editModal.classList.remove('show');
    editFunctionForm.reset();
}

// Handle Edit Form Submit
async function handleEditSubmit(e) {
    e.preventDefault();
    
    // Prevent double-submit
    if (isEditSubmitting) return;
    isEditSubmitting = true;
    
    const submitBtn = editFunctionForm.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = '⏳ Saving...';
    
    const functionId = document.getElementById('editFunctionId').value;
    const functionName = document.getElementById('editFunctionName').value.trim();
    const functionDate = document.getElementById('editFunctionDate').value;
    const place = document.getElementById('editPlace').value.trim();
    const functionDetails = document.getElementById('editFunctionDetails').value.trim();
    
    if (!functionName || !functionDate || !place) {
        alert('Function name, date, and place are required');
        isEditSubmitting = false;
        submitBtn.disabled = false;
        submitBtn.textContent = '✓ Save Changes';
        return;
    }
    
    const data = {
        id: parseInt(functionId),
        function_name: functionName,
        function_date: functionDate,
        place: place,
        function_details: functionDetails
    };
    
    try {
        const response = await fetch('api/update_function_admin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess('Function updated successfully');
            closeEditModal();
            await loadFunctions();
        } else {
            showError(result.message);
        }
    } catch (error) {
        showError('Failed to update function: ' + error.message);
    } finally {
        isEditSubmitting = false;
        submitBtn.disabled = false;
        submitBtn.textContent = '✓ Save Changes';
    }
}

// Open Delete Modal
function openDeleteModal(functionId, functionName) {
    currentDeleteFunctionId = functionId;
    document.getElementById('deleteFunctionName').textContent = functionName;
    deleteModal.classList.add('show');
}

// Close Delete Modal
function closeDeleteModal() {
    deleteModal.classList.remove('show');
    currentDeleteFunctionId = null;
}

// Handle Delete
async function handleDelete() {
    if (!currentDeleteFunctionId) return;
    
    try {
        const response = await fetch('api/delete_function_admin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: currentDeleteFunctionId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess('Function deleted successfully');
            closeDeleteModal();
            await loadFunctions();
        } else {
            showError(result.message);
        }
    } catch (error) {
        showError('Failed to delete function: ' + error.message);
    }
}

// Utility Functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB');
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB') + ' ' + date.toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
}

function escapeHtml(text) {
    if (!text) return '';
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
