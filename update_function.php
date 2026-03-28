<?php
/**
 * Update Function API Endpoint
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
        'message' => 'Please login to update functions'
    ]);
    exit();
}

// Check if POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit();
}

// Get POST data
$functionId = isset($_POST['functionId']) ? intval($_POST['functionId']) : 0;
$functionName = isset($_POST['functionName']) ? sanitizeInput($_POST['functionName']) : '';
$functionDate = isset($_POST['functionDate']) ? sanitizeInput($_POST['functionDate']) : '';
$place = isset($_POST['place']) ? sanitizeInput($_POST['place']) : '';
$functionDetails = isset($_POST['functionDetails']) ? sanitizeInput($_POST['functionDetails']) : '';

// Validation
$errors = [];

if ($functionId <= 0) {
    $errors[] = 'Invalid function ID';
}

if (empty($functionName)) {
    $errors[] = 'Function name is required';
}

if (empty($functionDate)) {
    $errors[] = 'Date is required';
} elseif (!strtotime($functionDate)) {
    $errors[] = 'Invalid date format';
}

if (empty($place)) {
    $errors[] = 'Place is required';
}

// Check for errors
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => implode(', ', $errors)
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
        'message' => 'Function not found or access denied'
    ]);
    exit();
}
$functionData = $result->fetch_assoc();
$checkStmt->close();

// Check for duplicate function (excluding current one)
$dupStmt = $conn->prepare("SELECT id FROM functions WHERE user_id = ? AND function_name = ? AND function_date = ? AND place = ? AND id != ?");

if (!$dupStmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Database prepare error (dup): ' . $conn->error
    ]);
    closeDBConnection($conn);
    exit();
}

$dupStmt->bind_param("isssi", $userId, $functionName, $functionDate, $place, $functionId);
$dupStmt->execute();
$dupStmt->store_result();

if ($dupStmt->num_rows > 0) {
    $dupStmt->close();
    closeDBConnection($conn);
    echo json_encode([
        'success' => false,
        'message' => 'Another function with the same Name, Date, and Place already exists.'
    ]);
    exit();
}
$dupStmt->close();

// Update function
// Set is_synced = 0 so it gets picked up by sync script
$stmt = $conn->prepare("UPDATE functions SET function_name = ?, function_date = ?, place = ?, function_details = ?, is_synced = 0 WHERE id = ? AND user_id = ?");

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Database prepare error (update): ' . $conn->error
    ]);
    closeDBConnection($conn);
    exit();
}

$stmt->bind_param("ssssii", $functionName, $functionDate, $place, $functionDetails, $functionId, $userId);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Function updated successfully!'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update function: ' . $stmt->error
    ]);
}

// Close connections
$stmt->close();
closeDBConnection($conn);
?>
