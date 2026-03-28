<?php
/**
 * Update User API
 * Updates user information (Super Admin only)
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

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($data['id']) || !isset($data['full_name']) || !isset($data['phone']) || !isset($data['role']) || !isset($data['is_active'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $userId = (int)$data['id'];
    $fullName = trim($data['full_name']);
    $email = isset($data['email']) ? trim($data['email']) : null;
    $phone = trim($data['phone']);
    $role = $data['role'];
    $isActive = (int)$data['is_active'];
    $password = isset($data['password']) && !empty($data['password']) ? $data['password'] : null;
    
    // Validate role
    if (!in_array($role, ['user', 'admin', 'super_admin'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid role']);
        exit;
    }
    
    // Validate email format if provided
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    $conn = getDBConnection();
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        $stmt->close();
        closeDBConnection($conn);
        exit;
    }
    $stmt->close();
    
    // Check if phone is already used by another user
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
    $stmt->bind_param("si", $phone, $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Phone number already in use']);
        $stmt->close();
        closeDBConnection($conn);
        exit;
    }
    $stmt->close();
    
    // Check if email is already used by another user (if email is provided)
    if (!empty($email)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $userId);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email already in use']);
            $stmt->close();
            closeDBConnection($conn);
            exit;
        }
        $stmt->close();
    }
    
    // Update user
    if ($password !== null) {
        // Update with new password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?, is_active = ?, password = ?, is_synced = 0 WHERE id = ?");
        $stmt->bind_param("ssssisi", $fullName, $email, $phone, $role, $isActive, $hashedPassword, $userId);
    } else {
        // Update without changing password
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?, is_active = ?, is_synced = 0 WHERE id = ?");
        $stmt->bind_param("sssiii", $fullName, $email, $phone, $role, $isActive, $userId);
    }
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'User updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update user');
    }
    
    $stmt->close();
    closeDBConnection($conn);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
