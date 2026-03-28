<?php
/**
 * Approve Guest Entry API Endpoint
 * UZRS MOI Collection System - Updates guest entry with denomination and computer number
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

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'தவறான கோரிக்கை முறை.'
    ]);
    exit();
}

$userId = $_SESSION['user_id'];
$computerNumber = isset($_SESSION['computer_number']) ? $_SESSION['computer_number'] : '';

if (empty($computerNumber)) {
    echo json_encode([
        'success' => false,
        'message' => 'கணினி எண் அமைக்கப்படவில்லை.'
    ]);
    exit();
}

// Get inputs
$collectionId = isset($_POST['collection_id']) ? intval($_POST['collection_id']) : 0;

// Denominations
$denom2000 = isset($_POST['denom2000']) ? intval($_POST['denom2000']) : 0;
$denom500 = isset($_POST['denom500']) ? intval($_POST['denom500']) : 0;
$denom200 = isset($_POST['denom200']) ? intval($_POST['denom200']) : 0;
$denom100 = isset($_POST['denom100']) ? intval($_POST['denom100']) : 0;
$denom50 = isset($_POST['denom50']) ? intval($_POST['denom50']) : 0;
$denom20 = isset($_POST['denom20']) ? intval($_POST['denom20']) : 0;
$denom10 = isset($_POST['denom10']) ? intval($_POST['denom10']) : 0;
$denom5 = isset($_POST['denom5']) ? intval($_POST['denom5']) : 0;
$denom2 = isset($_POST['denom2']) ? intval($_POST['denom2']) : 0;
$denom1 = isset($_POST['denom1']) ? intval($_POST['denom1']) : 0;

if ($collectionId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'தவறான பதிவு எண்.'
    ]);
    exit();
}

try {
    $conn = getDBConnection();
    
    // Verify that the entry belongs to the user and is a guest entry
    $stmt = $conn->prepare("
        SELECT total_amount, computer_number 
        FROM collections 
        WHERE id = ? AND user_id = ? AND computer_number LIKE 'GUEST-%'
    ");
    $stmt->bind_param("ii", $collectionId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        closeDBConnection($conn);
        echo json_encode([
            'success' => false,
            'message' => 'பதிவு காணப்படவில்லை அல்லது அனுமதி மறுக்கப்பட்டது.'
        ]);
        exit();
    }
    
    $entry = $result->fetch_assoc();
    $totalAmount = $entry['total_amount'];
    $stmt->close();
    
    // Calculate entered amount from denominations
    $enteredAmount = ($denom2000 * 2000) + ($denom500 * 500) + ($denom200 * 200) + 
                     ($denom100 * 100) + ($denom50 * 50) + ($denom20 * 20) + 
                     ($denom10 * 10) + ($denom5 * 5) + ($denom2 * 2) + ($denom1 * 1);
    
    // Verify amount matches
    if (abs($enteredAmount - $totalAmount) > 0.01) {
        closeDBConnection($conn);
        echo json_encode([
            'success' => false,
            'message' => 'உள்ளிடப்பட்ட தொகை பொருந்தவில்லை. தேவை: ₹' . number_format($totalAmount, 2) . ', உள்ளிடப்பட்டது: ₹' . number_format($enteredAmount, 2)
        ]);
        exit();
    }
    
    // Update the entry with denominations and computer number
    $stmt = $conn->prepare("
        UPDATE collections 
        SET computer_number = ?,
            denom_2000 = ?, denom_500 = ?, denom_200 = ?, denom_100 = ?,
            denom_50 = ?, denom_20 = ?, denom_10 = ?, denom_5 = ?, 
            denom_2 = ?, denom_1 = ?,
            updated_at = CURRENT_TIMESTAMP,
            updated_by = ?,
            is_synced = 0
        WHERE id = ? AND user_id = ?
    ");
    
    $stmt->bind_param(
        "siiiiiiiiiiiii",
        $computerNumber,
        $denom2000, $denom500, $denom200, $denom100,
        $denom50, $denom20, $denom10, $denom5,
        $denom2, $denom1,
        $userId,
        $collectionId, $userId
    );
    
    if ($stmt->execute()) {
        $stmt->close();
        closeDBConnection($conn);
        
        echo json_encode([
            'success' => true,
            'message' => 'விருந்தினர் பதிவு வெற்றிகரமாக ஏற்றுக்கொள்ளப்பட்டது!'
        ]);
    } else {
        $stmt->close();
        closeDBConnection($conn);
        
        echo json_encode([
            'success' => false,
            'message' => 'பதிவை புதுப்பிக்க முடியவில்லை.'
        ]);
    }
    
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
