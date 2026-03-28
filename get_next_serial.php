<?php
/**
 * Get Next Serial Number API
 * Returns the next serial number in format: S [ComputerNo] - [Sequence]
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$functionId = isset($_GET['function_id']) ? intval($_GET['function_id']) : 0;
$computerNumber = isset($_SESSION['computer_number']) ? $_SESSION['computer_number'] : '';

if ($functionId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid function ID']);
    exit;
}

if (empty($computerNumber)) {
    // If no computer number, maybe default or error? 
    // User requested "Comp 001", implies 3 digits? 
    // If session doesn't have it, we can't do much. 
    // But save_collection uses it.
    $computerNumber = '001'; // Default fallback?
}

try {
    $conn = getDBConnection();
    
    // Get the MAX sequence number for this function and computer
    // Handle both padded (001) and non-padded (1) formats
    $computerNumberUnpadded = ltrim($computerNumber, '0');
    if (empty($computerNumberUnpadded)) $computerNumberUnpadded = '0';
    
    // Fetch ALL non-empty customer_numbers for this function+computer to find MAX sequence
    $stmt = $conn->prepare("SELECT customer_number FROM collections WHERE function_id = ? AND (computer_number = ? OR computer_number = ?) AND customer_number IS NOT NULL AND customer_number != '' ORDER BY id DESC");
    $stmt->bind_param("iss", $functionId, $computerNumber, $computerNumberUnpadded);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $maxSeq = 0; // Default if no records found
    
    while ($row = $result->fetch_assoc()) {
        $lastSerial = $row['customer_number'];
        // Handle formats: "S 001 - 5", "Comp 001 - 5", "Comp COMP-001 - 5"
        $parts = explode(' - ', $lastSerial);
        if (count($parts) >= 2) {
            $seqNum = intval(trim(end($parts)));
            if ($seqNum > $maxSeq) {
                $maxSeq = $seqNum;
            }
        }
    }
    
    $nextSeq = $maxSeq + 1;
    
    // Ensure computer number has at least 3 chars or is what user expects
    if (is_numeric($computerNumber)) {
       $displayCompNum = str_pad($computerNumber, 3, '0', STR_PAD_LEFT);
    } else {
       $displayCompNum = $computerNumber;
    }
    
    $serialString = "S $displayCompNum - $nextSeq";
    
    $stmt->close();
    closeDBConnection($conn);
    
    echo json_encode([
        'success' => true,
        'serial' => $serialString,
        'sequence' => $nextSeq,
        'computer_number' => $computerNumber
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
