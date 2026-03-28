<?php
/**
 * Get Deleted Collections API
 * UZRS MOI Collection System
 */

require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$functionId = isset($_GET['function_id']) ? intval($_GET['function_id']) : 0;

if ($functionId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid Function ID']);
    exit();
}

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Verify function ownership
$checkStmt = $conn->prepare("SELECT id FROM functions WHERE id = ? AND user_id = ?");
$checkStmt->bind_param("ii", $functionId, $userId);
$checkStmt->execute();
if ($checkStmt->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Function not found or access denied']);
    exit();
}
$checkStmt->close();

// Fetch deleted records
$sql = "SELECT * FROM deleted_collections WHERE function_id = ? ORDER BY deleted_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $functionId);
$stmt->execute();
$result = $stmt->get_result();

$deletedRecords = [];
while ($row = $result->fetch_assoc()) {
    $deletedRecords[] = $row;
}

$stmt->close();
closeDBConnection($conn);

echo json_encode(['success' => true, 'data' => $deletedRecords]);
?>
