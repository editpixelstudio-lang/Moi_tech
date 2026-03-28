<?php
/**
 * Get Denomination Summary API Endpoint
 * Returns denomination summary by computer number for a function
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
    $stmt = $conn->prepare("SELECT id, function_name FROM functions WHERE id = ? AND user_id = ?");
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
    $functionRow = $result->fetch_assoc();
    $functionName = $functionRow['function_name'];
    $stmt->close();
    
    // Get denomination summary grouped by computer number
    $stmt = $conn->prepare("
        SELECT 
            computer_number,
            COUNT(*) as transaction_count,
            SUM(total_amount) as total_amount,
            SUM(COALESCE(denom_2000, 0)) as denom_2000,
            SUM(COALESCE(denom_500, 0)) as denom_500,
            SUM(COALESCE(denom_200, 0)) as denom_200,
            SUM(COALESCE(denom_100, 0)) as denom_100,
            SUM(COALESCE(denom_50, 0)) as denom_50,
            SUM(COALESCE(denom_20, 0)) as denom_20,
            SUM(COALESCE(denom_10, 0)) as denom_10,
            SUM(COALESCE(denom_5, 0)) as denom_5,
            SUM(COALESCE(denom_2, 0)) as denom_2,
            SUM(COALESCE(denom_1, 0)) as denom_1
        FROM collections
        WHERE function_id = ?
        GROUP BY computer_number
        ORDER BY computer_number
    ");
    
    if (!$stmt) throw new Exception("Prepare error (summary): " . $conn->error);
    
    $stmt->bind_param("i", $functionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $computerSummaries = [];
    $allComputersTotals = [
        'transaction_count' => 0,
        'total_amount' => 0,
        'denom_2000' => 0,
        'denom_500' => 0,
        'denom_200' => 0,
        'denom_100' => 0,
        'denom_50' => 0,
        'denom_20' => 0,
        'denom_10' => 0,
        'denom_5' => 0,
        'denom_2' => 0,
        'denom_1' => 0
    ];
    
    $currentComputerSummary = null;
    
    while ($row = $result->fetch_assoc()) {
        $computerSummaries[] = $row;
        
        // Add to all computers totals
        $allComputersTotals['transaction_count'] += intval($row['transaction_count']);
        $allComputersTotals['total_amount'] += floatval($row['total_amount']);
        $allComputersTotals['denom_2000'] += intval($row['denom_2000']);
        $allComputersTotals['denom_500'] += intval($row['denom_500']);
        $allComputersTotals['denom_200'] += intval($row['denom_200']);
        $allComputersTotals['denom_100'] += intval($row['denom_100']);
        $allComputersTotals['denom_50'] += intval($row['denom_50']);
        $allComputersTotals['denom_20'] += intval($row['denom_20']);
        $allComputersTotals['denom_10'] += intval($row['denom_10']);
        $allComputersTotals['denom_5'] += intval($row['denom_5']);
        $allComputersTotals['denom_2'] += intval($row['denom_2']);
        $allComputersTotals['denom_1'] += intval($row['denom_1']);
        
        // Check if this is the current computer
        $compNumUnpadded = ltrim($computerNumber, '0');
        if (empty($compNumUnpadded)) $compNumUnpadded = '0';
        
        if ($row['computer_number'] == $computerNumber || $row['computer_number'] == $compNumUnpadded) {
            $currentComputerSummary = $row;
        }
    }
    
    $stmt->close();
    closeDBConnection($conn);
    
    echo json_encode([
        'success' => true,
        'function_name' => $functionName,
        'current_computer' => $computerNumber,
        'current_computer_summary' => $currentComputerSummary,
        'all_computers_summary' => $allComputersTotals,
        'by_computer' => $computerSummaries
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
