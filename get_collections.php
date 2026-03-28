<?php
/**
 * Get Collections API Endpoint
 * UZRS MOI Collection System
 */

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'User not authenticated. Please log in.'
    ]);
    exit();
}

// Get user ID and computer number from session
$userId = $_SESSION['user_id'];
$computerNumber = isset($_SESSION['computer_number']) ? $_SESSION['computer_number'] : '';

if (empty($computerNumber)) {
    echo json_encode([
        'success' => false,
        'message' => 'Computer number not set in session'
    ]);
    exit();
}

// Get function ID from query parameter
$functionId = isset($_GET['function_id']) ? intval($_GET['function_id']) : 0;

if ($functionId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid function ID.'
    ]);
    exit();
}

try {
    $conn = getDBConnection();
    
    // Verify that the function belongs to the user
    $stmt = $conn->prepare("SELECT id FROM functions WHERE id = ? AND user_id = ?");
    if (!$stmt) throw new Exception("Prepare error (ownership): " . $conn->error);
    $stmt->bind_param("ii", $functionId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        closeDBConnection($conn);
        echo json_encode([
            'success' => false,
            'message' => 'Function not found or access denied.'
        ]);
        exit();
    }
    $stmt->close();
    
    // Get collections ONLY for this specific function and computer
    // Handle both padded (001) and non-padded (1) formats for backward compatibility
    $computerNumberUnpadded = ltrim($computerNumber, '0'); // Remove leading zeros
    if (empty($computerNumberUnpadded)) $computerNumberUnpadded = '0'; // Handle "000" case
    
    $stmt = $conn->prepare("
        SELECT * 
        FROM collections
        WHERE function_id = ? AND (computer_number = ? OR computer_number = ?)
        ORDER BY collection_date DESC, created_at DESC
    ");
    
    if (!$stmt) throw new Exception("Prepare error (collections): " . $conn->error);
    
    $stmt->bind_param("iss", $functionId, $computerNumber, $computerNumberUnpadded);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Debug log
    error_log("get_collections.php: Requested function_id=$functionId, user_id=$userId, rows=" . $result->num_rows);
    
    $collections = [];
    while ($row = $result->fetch_assoc()) {
        $collections[] = $row;
    }
    
    $stmt->close();
    closeDBConnection($conn);
    
    echo json_encode([
        'success' => true,
        'collections' => $collections
    ]);
    
} catch (Exception $e) {
    if (isset($conn)) {
        closeDBConnection($conn);
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
?>
