<?php
/**
 * Delete Collection API Endpoint
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
        'message' => 'பதிவை நீக்க உள்நுழையவும்'
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
$collectionId = isset($_POST['collection_id']) ? intval($_POST['collection_id']) : 0;

if ($collectionId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'தவறான பதிவு எண்'
    ]);
    exit();
}

// Get current user ID
$userId = $_SESSION['user_id'];

// Create database connection
$conn = getDBConnection();

// Check ownership and get UUID
$checkStmt = $conn->prepare("
    SELECT c.id, c.uuid 
    FROM collections c
    JOIN functions f ON c.function_id = f.id
    WHERE c.id = ? AND f.user_id = ?
");

if (!$checkStmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Database prepare error (check): ' . $conn->error
    ]);
    closeDBConnection($conn);
    exit();
}

$checkStmt->bind_param("ii", $collectionId, $userId);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows === 0) {
    $checkStmt->close();
    closeDBConnection($conn);
    echo json_encode([
        'success' => false,
        'message' => 'பதிவு காணப்படவில்லை அல்லது அனுமதி மறுக்கப்பட்டது'
    ]);
    exit();
}

$collectionData = $result->fetch_assoc();
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

    // Log for sync if UUID exists
    if (!empty($collectionData['uuid'])) {
        $insDel = $conn->prepare("INSERT INTO deleted_records (table_name, uuid) VALUES (?, ?)");
        if (!$insDel) throw new Exception("Prepare error (insDel): " . $conn->error);
        
        $tableName = 'collections';
        $insDel->bind_param("ss", $tableName, $collectionData['uuid']);
        $insDel->execute();
        $insDel->close();
    }

    // Copy to deleted_collections history
    $copySql = "INSERT INTO deleted_collections (
        original_id, function_id, user_id, computer_number, location, initial_name, name1, name2, 
        occupation, relationship_priority, village_going_to, phone, customer_number, description, 
        total_amount, denom_2000, denom_500, denom_200, denom_100, denom_50, denom_20, denom_10, 
        denom_5, denom_2, denom_1, collection_date, created_at, updated_at, updated_by, uuid, 
        is_synced, remote_id, deleted_by
    )
    SELECT 
        id, function_id, user_id, computer_number, location, initial_name, name1, name2, 
        occupation, relationship_priority, village_going_to, phone, customer_number, description, 
        total_amount, denom_2000, denom_500, denom_200, denom_100, denom_50, denom_20, denom_10, 
        denom_5, denom_2, denom_1, collection_date, created_at, updated_at, updated_by, uuid, 
        is_synced, remote_id, ? 
    FROM collections WHERE id = ?";
    
    $copyStmt = $conn->prepare($copySql);
    if ($copyStmt) {
        $copyStmt->bind_param("ii", $userId, $collectionId);
        $copyStmt->execute();
        $copyStmt->close();
    }

    // Delete collection
    $delStmt = $conn->prepare("DELETE FROM collections WHERE id = ?");
    if (!$delStmt) throw new Exception("Prepare error (delStmt): " . $conn->error);
    
    $delStmt->bind_param("i", $collectionId);
    $delStmt->execute();
    
    if ($delStmt->affected_rows === 0) {
        throw new Exception("Failed to delete collection record");
    }
    $delStmt->close();

    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'பதிவு வெற்றிகரமாக நீக்கப்பட்டது'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'பதிவை நீக்க முடியவில்லை: ' . $e->getMessage()
    ]);
}

closeDBConnection($conn);
?>
