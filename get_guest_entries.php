<?php
/**
 * Get Guest Entries API Endpoint
 * UZRS MOI Collection System
 */

header('Content-Type: application/json');

require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'பயனர் அங்கீகரிக்கப்படவில்லை.'
    ]);
    exit();
}

// Check if request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'message' => 'தவறான கோரிக்கை முறை.'
    ]);
    exit();
}

$userId = $_SESSION['user_id'];
$functionId = isset($_GET['function_id']) ? intval($_GET['function_id']) : 0;

if ($functionId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'தவறான விசேஷ எண்.'
    ]);
    exit();
}

try {
    $conn = getDBConnection();
    
    // Get pending guest entries (computer_number starts with 'GUEST-')
    $stmt = $conn->prepare("
        SELECT id, location, initial_name, name1, name2, occupation, 
               relationship_priority, village_going_to, phone, customer_number, 
               description, total_amount, created_at, computer_number
        FROM collections 
        WHERE function_id = ? 
        AND user_id = ? 
        AND computer_number LIKE 'GUEST-%'
        ORDER BY created_at DESC
    ");
    
    $stmt->bind_param("ii", $functionId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $entries = [];
    while ($row = $result->fetch_assoc()) {
        $entries[] = $row;
    }
    
    $stmt->close();
    closeDBConnection($conn);
    
    echo json_encode([
        'success' => true,
        'entries' => $entries,
        'count' => count($entries)
    ]);
    
} catch (Exception $e) {
    if (isset($conn)) {
        closeDBConnection($conn);
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'பிழை ஏற்பட்டது: ' . $e->getMessage()
    ]);
}
?>
