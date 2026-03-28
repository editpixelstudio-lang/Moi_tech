<?php
/**
 * Update Function API (Admin)
 * Updates function information (Super Admin only)
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
    if (!isset($data['id']) || !isset($data['function_name']) || !isset($data['function_date']) || !isset($data['place'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $functionId = (int)$data['id'];
    $functionName = trim($data['function_name']);
    $functionDate = $data['function_date'];
    $place = trim($data['place']);
    $functionDetails = isset($data['function_details']) ? trim($data['function_details']) : '';
    
    $conn = getDBConnection();
    
    // Check if function exists
    $stmt = $conn->prepare("SELECT id FROM functions WHERE id = ?");
    $stmt->bind_param("i", $functionId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Function not found']);
        $stmt->close();
        closeDBConnection($conn);
        exit;
    }
    $stmt->close();
    
    // Update function
    $stmt = $conn->prepare("UPDATE functions SET function_name = ?, function_date = ?, place = ?, function_details = ?, is_synced = 0 WHERE id = ?");
    $stmt->bind_param("ssssi", $functionName, $functionDate, $place, $functionDetails, $functionId);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Function updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update function');
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
