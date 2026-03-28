<?php
/**
 * Get Collection Details API Endpoint
 * UZRS MOI Collection System
 */

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

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

// Get collection ID from query parameter
$collectionId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($collectionId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid collection ID.'
    ]);
    exit();
}

try {
    $conn = getDBConnection();
    $computerNumber = isset($_SESSION['computer_number']) ? $_SESSION['computer_number'] : '';
    
    if (empty($computerNumber)) {
        echo json_encode([
            'success' => false,
            'message' => 'Computer number not set'
        ]);
        exit();
    }
    
    // Fetch collection details along with updater info, filtered by computer
    $sql = "
        SELECT c.*, u.full_name as updated_by_name 
        FROM collections c
        LEFT JOIN users u ON c.updated_by = u.id
        WHERE c.id = ? AND c.computer_number = ?
    ";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("Prepare error: " . $conn->error);
    $stmt->bind_param("is", $collectionId, $computerNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Collection not found.'
        ]);
        $stmt->close();
        closeDBConnection($conn);
        exit();
    }
    
    $collection = $result->fetch_assoc();
    
    $stmt->close();
    closeDBConnection($conn);
    
    echo json_encode([
        'success' => true,
        'collection' => $collection
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
