<?php
/**
 * Create Function API Endpoint
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
        'message' => 'விசேஷங்களை உருவாக்க உள்நுழையவும்'
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
$functionName = isset($_POST['functionName']) ? sanitizeInput($_POST['functionName']) : '';
$functionDate = isset($_POST['functionDate']) ? sanitizeInput($_POST['functionDate']) : '';
$place = isset($_POST['place']) ? sanitizeInput($_POST['place']) : '';
$functionDetails = isset($_POST['functionDetails']) ? sanitizeInput($_POST['functionDetails']) : '';

// Validation
$errors = [];

if (empty($functionName)) {
    $errors[] = 'விசேஷத்தின் பெயர் தேவை';
}

if (empty($functionDate)) {
    $errors[] = 'தேதி தேவை';
} elseif (!strtotime($functionDate)) {
    $errors[] = 'தவறான தேதி வடிவம்';
}

if (empty($place)) {
    $errors[] = 'இடம் தேவை';
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

// Check for duplicate function (Same Name, Date, and Place for this user)
$checkStmt = $conn->prepare("SELECT id FROM functions WHERE user_id = ? AND function_name = ? AND function_date = ? AND place = ?");

if (!$checkStmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Database prepare error (check): ' . $conn->error
    ]);
    closeDBConnection($conn);
    exit();
}

$checkStmt->bind_param("isss", $userId, $functionName, $functionDate, $place);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    $checkStmt->close();
    closeDBConnection($conn);
    echo json_encode([
        'success' => false,
        'message' => 'இதே பெயர், தேதி மற்றும் இடத்தில் ஏற்கனவே ஒரு விசேஷம் உள்ளது.'
    ]);
    exit();
}
$checkStmt->close();

// Generate UUID
$uuid = uniqid() . '-' . bin2hex(random_bytes(8));

// Prepare SQL statement
// is_synced defaults to 0, remote_id defaults to NULL
$stmt = $conn->prepare("INSERT INTO functions (function_name, function_date, place, function_details, user_id, uuid, is_synced) VALUES (?, ?, ?, ?, ?, ?, 0)");

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Database prepare error (insert): ' . $conn->error
    ]);
    closeDBConnection($conn);
    exit();
}

$stmt->bind_param("ssssis", $functionName, $functionDate, $place, $functionDetails, $userId, $uuid);

// Execute query
if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'விசேஷம் வெற்றிகரமாக உருவாக்கப்பட்டது!',
        'functionId' => $stmt->insert_id
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'விசேஷத்தை உருவாக்க முடியவில்லை: ' . $stmt->error
    ]);
}

// Close connections
$stmt->close();
closeDBConnection($conn);
?>
