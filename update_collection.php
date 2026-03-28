<?php
/**
 * Update Collection API Endpoint
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
        'message' => 'பயனர் அங்கீகரிக்கப்படவில்லை. தயவுசெய்து உள்நுழையவும்.'
    ]);
    exit();
}

// Check if it's a POST request
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
        'message' => 'கணினி எண் அமைக்கப்படவில்லை'
    ]);
    exit();
}

$collectionId = isset($_POST['collection_id']) ? intval($_POST['collection_id']) : 0;

if ($collectionId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'தவறான மொய் எண்.'
    ]);
    exit();
}

// Get form data
$location = isset($_POST['location']) ? trim($_POST['location']) : '';
$initial = isset($_POST['initial']) ? trim($_POST['initial']) : '';
$name1 = isset($_POST['name1']) ? trim($_POST['name1']) : '';
$name2 = isset($_POST['name2']) ? trim($_POST['name2']) : '';
$occupation = isset($_POST['occupation']) ? trim($_POST['occupation']) : '';

// Store Initial 2 and Occupation 2 separately (NOT merged)
$initial2 = isset($_POST['initial2']) ? trim($_POST['initial2']) : '';
$occupation2 = isset($_POST['occupation2']) ? trim($_POST['occupation2']) : '';

$relationshipPriority = isset($_POST['relationship_priority']) && in_array($_POST['relationship_priority'], ['1', '2']) ? intval($_POST['relationship_priority']) : null;
$village = isset($_POST['villageGoingTo']) ? trim($_POST['villageGoingTo']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$customerNumber = isset($_POST['customerNumber']) ? trim($_POST['customerNumber']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0.0;

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

if ($amount <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'தவறான தொகை.'
    ]);
    exit();
}

try {
    $conn = getDBConnection();
    
    // Verify the collection belongs to this computer
    $verifyStmt = $conn->prepare("SELECT id, remote_id, uuid FROM collections WHERE id = ? AND computer_number = ?");
    $verifyStmt->bind_param("is", $collectionId, $computerNumber);
    $verifyStmt->execute();
    $verifyResult = $verifyStmt->get_result();
    
    if ($verifyResult->num_rows === 0) {
        $verifyStmt->close();
        closeDBConnection($conn);
        echo json_encode([
            'success' => false,
            'message' => 'இந்த கணினியில் இந்த பதிவை திருத்த முடியாது.'
        ]);
        exit();
    }
    $collectionData = $verifyResult->fetch_assoc();
    $verifyStmt->close();

    // Update query with initial2 and occupation2
    // Set is_synced = 0
    $sql = "UPDATE collections SET 
            location = ?, 
            initial_name = ?, 
            name1 = ?, 
            name2 = ?, 
            initial2 = ?,
            occupation = ?, 
            occupation2 = ?,
            relationship_priority = ?,
            village_going_to = ?, 
            phone = ?, 
            customer_number = ?, 
            description = ?, 
            total_amount = ?,
            denom_2000 = ?, denom_500 = ?, denom_200 = ?, denom_100 = ?, 
            denom_50 = ?, denom_20 = ?, denom_10 = ?, denom_5 = ?, denom_2 = ?, denom_1 = ?,
            updated_by = ?,
            updated_at = NOW(),
            is_synced = 0
            WHERE id = ?";
            
    // Handle nullable relationship_priority properly
    if ($relationshipPriority === null) {
        $relationshipPriorityValue = 0;
    } else {
        $relationshipPriorityValue = $relationshipPriority;
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssissssdiiiiiiiiiiii", 
        $location, 
        $initial, 
        $name1, 
        $name2, 
        $initial2,
        $occupation, 
        $occupation2,
        $relationshipPriorityValue,
        $village, 
        $phone, 
        $customerNumber, 
        $description, 
        $amount,
        $denom2000, $denom500, $denom200, $denom100,
        $denom50, $denom20, $denom10, $denom5, $denom2, $denom1,
        $userId,
        $collectionId
    );
    
    if ($stmt->execute()) {
        // If relationship was NULL (0), update it to actual NULL
        if ($relationshipPriority === null && $collectionId > 0) {
            $updateStmt = $conn->prepare("UPDATE collections SET relationship_priority = NULL WHERE id = ?");
            if ($updateStmt) {
                $updateStmt->bind_param("i", $collectionId);
                $updateStmt->execute();
                $updateStmt->close();
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'மொய் வெற்றிகரமாக புதுப்பிக்கப்பட்டது.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'மொய் புதுப்பிக்க முடியவில்லை: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
    closeDBConnection($conn);
    
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
