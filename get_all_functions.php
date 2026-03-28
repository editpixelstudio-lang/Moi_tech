<?php
/**
 * Get All Functions API
 * Returns list of all functions with user details (Super Admin only)
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
    $date = isset($_GET['date']) ? $_GET['date'] : '';
    $userId = isset($_GET['user_id']) ? $_GET['user_id'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Build query with user information
    $query = "SELECT f.*, u.full_name as user_name, u.phone as user_phone 
              FROM functions f 
              LEFT JOIN users u ON f.user_id = u.id 
              WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($date)) {
        $query .= " AND f.function_date = ?";
        $params[] = $date;
        $types .= "s";
    }
    
    if (!empty($userId)) {
        $query .= " AND f.user_id = ?";
        $params[] = (int)$userId;
        $types .= "i";
    }
    
    if (!empty($search)) {
        $query .= " AND (f.function_name LIKE ? OR f.place LIKE ? OR u.full_name LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "sss";
    }
    
    $query .= " ORDER BY f.function_date DESC, f.created_at DESC";
    
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $functions = [];
    while ($row = $result->fetch_assoc()) {
        $functions[] = [
            'id' => $row['id'],
            'function_name' => $row['function_name'],
            'function_date' => $row['function_date'],
            'place' => $row['place'],
            'function_details' => $row['function_details'],
            'user_id' => $row['user_id'],
            'user_name' => $row['user_name'],
            'user_phone' => $row['user_phone'],
            'computer_number' => $row['computer_number'],
            'created_at' => $row['created_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'functions' => $functions
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
