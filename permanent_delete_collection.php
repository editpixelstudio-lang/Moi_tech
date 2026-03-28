<?php
/**
 * Permanent Delete Collection API
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$historyId = isset($_POST['history_id']) ? intval($_POST['history_id']) : 0;

if ($historyId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid History ID']);
    exit();
}

$conn = getDBConnection();
$userId = $_SESSION['user_id'];

// Verify ownership via function_id in deleted_collections
// We need to make sure the user owns the function associated with this deleted record
$checkStmt = $conn->prepare("
    SELECT dc.history_id 
    FROM deleted_collections dc
    JOIN functions f ON dc.function_id = f.id
    WHERE dc.history_id = ? AND f.user_id = ?
");
$checkStmt->bind_param("ii", $historyId, $userId);
$checkStmt->execute();

if ($checkStmt->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Record not found or access denied']);
    exit();
}
$checkStmt->close();

// Permanent Delete
$delStmt = $conn->prepare("DELETE FROM deleted_collections WHERE history_id = ?");
$delStmt->bind_param("i", $historyId);

if ($delStmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Permanently deleted']);
} else {
    echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $conn->error]);
}

$delStmt->close();
closeDBConnection($conn);
?>
