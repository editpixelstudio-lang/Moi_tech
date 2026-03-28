<?php
/**
 * Set Computer Number API
 * UZRS MOI Collection System
 */

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once '../includes/session.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$computerNumber = isset($data['computer_number']) ? trim($data['computer_number']) : '';

// Standardize: If numeric, pad to 3 digits (e.g. "1" -> "001")
if (is_numeric($computerNumber)) {
    $computerNumber = str_pad($computerNumber, 3, '0', STR_PAD_LEFT);
}

if (empty($computerNumber)) {
    echo json_encode([
        'success' => false,
        'message' => 'Computer number is required'
    ]);
    exit();
}

// Set session variable
$_SESSION['computer_number'] = $computerNumber;

echo json_encode([
    'success' => true,
    'message' => 'Computer number set successfully',
    'computer_number' => $computerNumber
]);
?>
