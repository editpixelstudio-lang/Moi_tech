<?php
/**
 * Get Functions API Endpoint
 * UZRS MOI Collection System
 */

require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

// Set JSON header
header('Content-Type: application/json');

// Disable error display to prevent JSON breakage
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Please login to view functions'
    ]);
    exit();
}

// Get current user ID
$userId = $_SESSION['user_id'];

// Create database connection
$conn = getDBConnection();

// Prepare SQL statement to get user's functions
$stmt = $conn->prepare("SELECT 
        f.id, 
        f.function_name, 
        f.function_date, 
        f.place, 
        f.function_details, 
        f.created_at,
        COALESCE(SUM(c.total_amount), 0) AS total_amount
    FROM functions f
    LEFT JOIN collections c ON c.function_id = f.id
    WHERE f.user_id = ?
    GROUP BY f.id, f.function_name, f.function_date, f.place, f.function_details, f.created_at
    ORDER BY f.function_date DESC, f.created_at DESC");

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Database prepare error: ' . $conn->error
    ]);
    closeDBConnection($conn);
    exit();
}

$stmt->bind_param("i", $userId);

// Execute query
if ($stmt->execute()) {
    $result = $stmt->get_result();
    $functions = [];
    
    while ($row = $result->fetch_assoc()) {
        $functions[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'functions' => $functions,
        'debug_user_id' => $userId,
        'debug_count' => count($functions)
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to retrieve functions: ' . $stmt->error
    ]);
}

// Close connections
$stmt->close();
closeDBConnection($conn);
?>
