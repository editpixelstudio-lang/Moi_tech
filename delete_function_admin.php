<?php
/**
 * Delete Function API (Admin)
 * Deletes a function and all associated data (Super Admin only)
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
        echo json_encode(['success' => false, 'message' => 'Function ID is required']);
        exit;
    }
    
    $functionId = (int)$data['id'];
    
    $conn = getDBConnection();
    
    // Check if function exists
    $stmt = $conn->prepare("SELECT id, function_name FROM functions WHERE id = ?");
    $stmt->bind_param("i", $functionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Function not found']);
        $stmt->close();
        closeDBConnection($conn);
        exit;
    }
    
    $functionData = $result->fetch_assoc();
    $stmt->close();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Ensure deleted_records table exists
        $conn->query("CREATE TABLE IF NOT EXISTS `deleted_records` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `table_name` VARCHAR(50) NOT NULL,
            `uuid` CHAR(36) NOT NULL,
            `deleted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_uuid` (`uuid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $insDel = $conn->prepare("INSERT INTO deleted_records (table_name, uuid) VALUES (?, ?)");

        // Log expenses for deletion
        $expStmt = $conn->prepare("SELECT uuid FROM expenses WHERE function_id = ?");
        $expStmt->bind_param("i", $functionId);
        $expStmt->execute();
        $expRes = $expStmt->get_result();
        $tableName = 'expenses';
        while ($row = $expRes->fetch_assoc()) {
            if (!empty($row['uuid'])) {
                $insDel->bind_param("ss", $tableName, $row['uuid']);
                $insDel->execute();
            }
        }
        $expStmt->close();

        // Log collections for deletion
        $collStmt = $conn->prepare("SELECT uuid FROM collections WHERE function_id = ?");
        $collStmt->bind_param("i", $functionId);
        $collStmt->execute();
        $collRes = $collStmt->get_result();
        $tableName = 'collections';
        while ($row = $collRes->fetch_assoc()) {
            if (!empty($row['uuid'])) {
                $insDel->bind_param("ss", $tableName, $row['uuid']);
                $insDel->execute();
            }
        }
        $collStmt->close();

        // Log function for deletion
        // Need to fetch UUID first if not in functionData
        if (!isset($functionData['uuid'])) {
             $uuidStmt = $conn->prepare("SELECT uuid FROM functions WHERE id = ?");
             $uuidStmt->bind_param("i", $functionId);
             $uuidStmt->execute();
             $uuidRes = $uuidStmt->get_result();
             if ($uuidRow = $uuidRes->fetch_assoc()) {
                 $functionData['uuid'] = $uuidRow['uuid'];
             }
             $uuidStmt->close();
        }

        if (!empty($functionData['uuid'])) {
            $tableName = 'functions';
            $insDel->bind_param("ss", $tableName, $functionData['uuid']);
            $insDel->execute();
        }
        $insDel->close();

        // Delete associated expenses first
        $stmt = $conn->prepare("DELETE FROM expenses WHERE function_id = ?");
        $stmt->bind_param("i", $functionId);
        $stmt->execute();
        $stmt->close();
        
        // Delete associated collections
        $stmt = $conn->prepare("DELETE FROM collections WHERE function_id = ?");
        $stmt->bind_param("i", $functionId);
        $stmt->execute();
        $stmt->close();
        
        // Delete the function
        $stmt = $conn->prepare("DELETE FROM functions WHERE id = ?");
        $stmt->bind_param("i", $functionId);
        $stmt->execute();
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Function and all associated data deleted successfully',
            'deleted_function' => $functionData['function_name']
        ]);
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        throw $e;
    }
    
    closeDBConnection($conn);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
