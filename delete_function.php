<?php
/**
 * Delete Function API Endpoint
 * UZRS MOI Collection System
 */

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'விசேஷங்களை நீக்க உள்நுழையவும்'
    ]);
    exit();
}

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'தவறான கோரிக்கை முறை'
    ]);
    exit();
}

// Get POST data
$functionId = isset($_POST['functionId']) ? intval($_POST['functionId']) : 0;

if ($functionId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'தவறான விசேஷ எண்'
    ]);
    exit();
}

// Get current user ID
$userId = $_SESSION['user_id'];

// Create database connection
$conn = getDBConnection();

// Check ownership
$checkStmt = $conn->prepare("SELECT id, remote_id, uuid FROM functions WHERE id = ? AND user_id = ?");

if (!$checkStmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Database prepare error (check): ' . $conn->error
    ]);
    closeDBConnection($conn);
    exit();
}

$checkStmt->bind_param("ii", $functionId, $userId);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows === 0) {
    $checkStmt->close();
    closeDBConnection($conn);
    echo json_encode([
        'success' => false,
        'message' => 'விசேஷம் காணப்படவில்லை அல்லது அனுமதி மறுக்கப்பட்டது'
    ]);
    exit();
}
$functionData = $result->fetch_assoc();
$checkStmt->close();

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

    // Log collections for deletion
    $collStmt = $conn->prepare("SELECT uuid FROM collections WHERE function_id = ?");
    if (!$collStmt) throw new Exception("Prepare error (collStmt): " . $conn->error);
    $collStmt->bind_param("i", $functionId);
    $collStmt->execute();
    $collRes = $collStmt->get_result();
    
    $insDel = $conn->prepare("INSERT INTO deleted_records (table_name, uuid) VALUES (?, ?)");
    if (!$insDel) throw new Exception("Prepare error (insDel): " . $conn->error);
    $tableName = 'collections';
    
    while ($row = $collRes->fetch_assoc()) {
        if (!empty($row['uuid'])) {
            $insDel->bind_param("ss", $tableName, $row['uuid']);
            $insDel->execute();
        }
    }
    $collStmt->close();

    // Log function for deletion
    if (!empty($functionData['uuid'])) {
        $tableName = 'functions';
        $insDel->bind_param("ss", $tableName, $functionData['uuid']);
        $insDel->execute();
    }
    $insDel->close();

    // Delete related collections first
    $delCollStmt = $conn->prepare("DELETE FROM collections WHERE function_id = ?");
    if (!$delCollStmt) throw new Exception("Prepare error (delCollStmt): " . $conn->error);
    $delCollStmt->bind_param("i", $functionId);
    $delCollStmt->execute();
    $delCollStmt->close();

    // Delete function
    $delFuncStmt = $conn->prepare("DELETE FROM functions WHERE id = ? AND user_id = ?");
    if (!$delFuncStmt) throw new Exception("Prepare error (delFuncStmt): " . $conn->error);
    $delFuncStmt->bind_param("ii", $functionId, $userId);
    $delFuncStmt->execute();
    
    if ($delFuncStmt->affected_rows === 0) {
        throw new Exception("Failed to delete function record");
    }
    $delFuncStmt->close();

    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'விசேஷம் மற்றும் தொடர்புடைய அனைத்து தரவுகளும் வெற்றிகரமாக நீக்கப்பட்டன'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'விசேஷத்தை நீக்க முடியவில்லை: ' . $e->getMessage()
    ]);
}

closeDBConnection($conn);
?>
