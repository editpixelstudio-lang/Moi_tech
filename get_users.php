<?php
/**
 * Get All Users API
 * Returns list of all users (Super Admin only)
 */

header('Content-Type: application/json');
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$currentUser = getCurrentUser();

// Check if user is super admin
if ($currentUser['role'] !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

try {
    $conn = getDBConnection();
    
    // Get filter parameters
    $role = isset($_GET['role']) ? $_GET['role'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Build query
    $query = "SELECT id, full_name, email, phone, role, is_active, created_at FROM users WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($role)) {
        $query .= " AND role = ?";
        $params[] = $role;
        $types .= "s";
    }
    
    if ($status !== '') {
        $query .= " AND is_active = ?";
        $params[] = (int)$status;
        $types .= "i";
    }
    
    if (!empty($search)) {
        $query .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "sss";
    }
    
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'id' => $row['id'],
            'full_name' => $row['full_name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'role' => $row['role'],
            'is_active' => (bool)$row['is_active'],
            'created_at' => $row['created_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
    
    $stmt->close();
    closeDBConnection($conn);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
