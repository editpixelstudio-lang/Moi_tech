<?php
/**
 * Save Expense API
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$userId = $_SESSION['user_id'];
$toName = isset($_POST['to_name']) ? sanitizeInput($_POST['to_name']) : '';
$functionName = isset($_POST['function_name']) ? sanitizeInput($_POST['function_name']) : '';
$place = isset($_POST['place']) ? sanitizeInput($_POST['place']) : '';
$date = isset($_POST['expense_date']) ? sanitizeInput($_POST['expense_date']) : date('Y-m-d');
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
$relatedCollectionId = isset($_POST['related_collection_id']) && !empty($_POST['related_collection_id']) ? intval($_POST['related_collection_id']) : null;

if (empty($toName) || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'பெயர் மற்றும் தொகை அவசியம்']);
    exit();
}

$conn = getDBConnection();

$inserted = false;
$errorMessage = '';

// Try with new columns
try {
    $stmt = $conn->prepare("INSERT INTO expenses (user_id, to_name, function_name, place, expense_date, amount, related_collection_id, uuid, is_synced) VALUES (?, ?, ?, ?, ?, ?, ?, UUID(), 0)");
    if ($stmt) {
        $stmt->bind_param("issssdi", $userId, $toName, $functionName, $place, $date, $amount, $relatedCollectionId);
        if ($stmt->execute()) {
            $inserted = true;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    // Ignore
}

if (!$inserted) {
    // Fallback
    $stmt = $conn->prepare("INSERT INTO expenses (user_id, to_name, function_name, place, expense_date, amount, related_collection_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("issssdi", $userId, $toName, $functionName, $place, $date, $amount, $relatedCollectionId);
        if ($stmt->execute()) {
            $inserted = true;
        } else {
            $errorMessage = $stmt->error;
        }
        $stmt->close();
    } else {
        $errorMessage = $conn->error;
    }
}

if ($inserted) {
    echo json_encode(['success' => true, 'message' => 'சேமிக்கப்பட்டது']);
} else {
    echo json_encode(['success' => false, 'message' => 'சேமிக்க முடியவில்லை: ' . $errorMessage]);
}
closeDBConnection($conn);
?>
