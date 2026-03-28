<?php
/**
 * Get Function Handover Summary API
 * UZRS MOI Collection System
 * Returns function details with denomination totals for handover
 */

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
    $computerNumber = isset($_SESSION['computer_number']) ? $_SESSION['computer_number'] : '';
    
    // Get function details
    $sql = "SELECT * FROM functions WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("Prepare error: " . $conn->error);
    $stmt->bind_param("i", $functionId);
    $stmt->execute();
    $functionResult = $stmt->get_result();
    
    if ($functionResult->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Function not found.'
        ]);
        $stmt->close();
        closeDBConnection($conn);
        exit();
    }
    
    $function = $functionResult->fetch_assoc();
    $stmt->close();
    
    // Get denomination totals for this function
    $sqlDenom = "
        SELECT 
            COUNT(*) as total_entries,
            SUM(total_amount) as total_amount,
            SUM(denom_500) as total_500,
            SUM(denom_200) as total_200,
            SUM(denom_100) as total_100,
            SUM(denom_50) as total_50,
            SUM(denom_20) as total_20,
            SUM(denom_10) as total_10,
            SUM(denom_5) as total_5,
            SUM(denom_2) as total_2,
            SUM(denom_1) as total_1
        FROM collections 
        WHERE function_id = ?
    ";
    
    $stmtDenom = $conn->prepare($sqlDenom);
    if (!$stmtDenom) throw new Exception("Prepare error: " . $conn->error);
    $stmtDenom->bind_param("i", $functionId);
    $stmtDenom->execute();
    $denomResult = $stmtDenom->get_result();
    $denomTotals = $denomResult->fetch_assoc();
    $stmtDenom->close();
    
    // Get expense totals if exists
    $sqlExpense = "
        SELECT 
            COUNT(*) as total_expenses,
            SUM(amount) as total_expense_amount
        FROM expenses 
        WHERE function_id = ?
    ";
    
    $stmtExp = $conn->prepare($sqlExpense);
    $expenseTotals = ['total_expenses' => 0, 'total_expense_amount' => 0];
    if ($stmtExp) {
        $stmtExp->bind_param("i", $functionId);
        $stmtExp->execute();
        $expResult = $stmtExp->get_result();
        $expenseTotals = $expResult->fetch_assoc();
        $stmtExp->close();
    }
    
    closeDBConnection($conn);
    
    echo json_encode([
        'success' => true,
        'function' => $function,
        'denomination_totals' => [
            'total_entries' => intval($denomTotals['total_entries'] ?? 0),
            'total_amount' => floatval($denomTotals['total_amount'] ?? 0),
            'denom_500' => intval($denomTotals['total_500'] ?? 0),
            'denom_200' => intval($denomTotals['total_200'] ?? 0),
            'denom_100' => intval($denomTotals['total_100'] ?? 0),
            'denom_50' => intval($denomTotals['total_50'] ?? 0),
            'denom_20' => intval($denomTotals['total_20'] ?? 0),
            'denom_10' => intval($denomTotals['total_10'] ?? 0),
            'denom_5' => intval($denomTotals['total_5'] ?? 0),
            'denom_2' => intval($denomTotals['total_2'] ?? 0),
            'denom_1' => intval($denomTotals['total_1'] ?? 0)
        ],
        'expense_totals' => [
            'total_expenses' => intval($expenseTotals['total_expenses'] ?? 0),
            'total_expense_amount' => floatval($expenseTotals['total_expense_amount'] ?? 0)
        ]
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
