<?php
/**
 * Delete User API
 * Deletes a user from the system (Super Admin only)
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

// Only accept POST or DELETE requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }
    
    $userId = (int)$data['id'];
    
    // Prevent super admin from deleting themselves
    if ($userId === $currentUser['id']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
        exit;
    }
    
    $conn = getDBConnection();
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        $stmt->close();
        closeDBConnection($conn);
        exit;
    }
    
    $userToDelete = $result->fetch_assoc();
    $stmt->close();
    
    // Ensure deleted_records table exists
    $conn->query("CREATE TABLE IF NOT EXISTS `deleted_records` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `table_name` VARCHAR(50) NOT NULL,
        `uuid` CHAR(36) NOT NULL,
        `deleted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_uuid` (`uuid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Log user for deletion
    // Need to fetch UUID first
    $uuidStmt = $conn->prepare("SELECT uuid FROM users WHERE id = ?");
    $uuidStmt->bind_param("i", $userId);
    $uuidStmt->execute();
    $uuidRes = $uuidStmt->get_result();
    if ($uuidRow = $uuidRes->fetch_assoc()) {
        if (!empty($uuidRow['uuid'])) {
            $insDel = $conn->prepare("INSERT INTO deleted_records (table_name, uuid) VALUES (?, ?)");
            $tableName = 'users';
            $insDel->bind_param("ss", $tableName, $uuidRow['uuid']);
            $insDel->execute();
            $insDel->close();
        }
    }
    $uuidStmt->close();

    // Delete user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully',
            'deleted_user' => $userToDelete['full_name']
        ]);
    } else {
        throw new Exception('Failed to delete user');
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
