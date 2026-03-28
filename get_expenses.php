<?php
/**
 * Get Expenses API
 * UZRS MOI Collection System
 */

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['user_id'];
$conn = getDBConnection();

$sql = "SELECT * FROM expenses WHERE user_id = ? ORDER BY expense_date DESC, created_at DESC";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$expenses = [];
while ($row = $result->fetch_assoc()) {
    $expenses[] = $row;
}

echo json_encode(['success' => true, 'expenses' => $expenses]);

$stmt->close();
closeDBConnection($conn);
?>
