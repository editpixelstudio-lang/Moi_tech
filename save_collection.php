<?php
/**
 * Save Collection API Endpoint
 * UZRS MOI Collection System
 */

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'தவறான கோரிக்கை முறை.'
    ]);
    exit();
}

// Check if this is a guest entry
$isGuestEntry = isset($_POST['guest_entry']) && $_POST['guest_entry'] == '1';

// For regular entries, check if user is logged in
if (!$isGuestEntry) {
    if (!isLoggedIn()) {
        echo json_encode([
            'success' => false,
            'message' => 'பயனர் அங்கீகரிக்கப்படவில்லை. தயவுசெய்து உள்நுழையவும்.'
        ]);
        exit();
    }
    
    // Get user ID and computer number from session
    $userId = $_SESSION['user_id'];
    $computerNumber = isset($_SESSION['computer_number']) ? $_SESSION['computer_number'] : '';

    // Standardize computer number format (pad to 3 digits if numeric)
    if (!empty($computerNumber) && is_numeric($computerNumber)) {
        $computerNumber = str_pad($computerNumber, 3, '0', STR_PAD_LEFT);
        $_SESSION['computer_number'] = $computerNumber; // Update session
    }

    if (empty($computerNumber)) {
        echo json_encode([
            'success' => false,
            'message' => 'கணினி எண் அமைக்கப்படவில்லை. தயவு செய்து மீண்டும் முயற்சிக்கவும்.'
        ]);
        exit();
    }
} else {
    // For guest entries, we need to get user_id from function
    $userId = null; // Will be set later after function lookup
    $computerNumber = 'GUEST-' . date('YmdHis'); // Generate unique guest computer number
}

// Get and validate inputs
$functionId = isset($_POST['function_id']) ? intval($_POST['function_id']) : 0;
$location = isset($_POST['location']) ? sanitizeInput($_POST['location']) : '';
$initialName = isset($_POST['initial']) ? sanitizeInput($_POST['initial']) : '';
$name1 = isset($_POST['name1']) ? sanitizeInput($_POST['name1']) : '';
$name2 = isset($_POST['name2']) ? sanitizeInput($_POST['name2']) : '';
$occupation = isset($_POST['occupation']) ? sanitizeInput($_POST['occupation']) : '';

// Store Initial 2 and Occupation 2 separately (NOT merged)
$initial2 = isset($_POST['initial2']) ? sanitizeInput($_POST['initial2']) : '';
$occupation2 = isset($_POST['occupation2']) ? sanitizeInput($_POST['occupation2']) : '';

// Handle relationship from both formats
if (isset($_POST['relationship_priority']) && in_array($_POST['relationship_priority'], ['1', '2'])) {
    $relationshipPriority = intval($_POST['relationship_priority']);
} elseif (isset($_POST['relationship']) && in_array($_POST['relationship'], ['1', '2'])) {
    $relationshipPriority = intval($_POST['relationship']);
} else {
    $relationshipPriority = null;
}

// Handle village field name variations
$villageGoingTo = isset($_POST['villageGoingTo']) ? sanitizeInput($_POST['villageGoingTo']) : '';
if (empty($villageGoingTo) && isset($_POST['village_going_to'])) {
    $villageGoingTo = sanitizeInput($_POST['village_going_to']);
}

$phone = isset($_POST['phone']) ? sanitizeInput($_POST['phone']) : '';

// Handle customer number field name variations
$customerNumber = isset($_POST['customerNumber']) ? sanitizeInput($_POST['customerNumber']) : '';
if (empty($customerNumber) && isset($_POST['customer_number'])) {
    $customerNumber = sanitizeInput($_POST['customer_number']);
}

$description = isset($_POST['description']) ? sanitizeInput($_POST['description']) : '';
$collectionDate = isset($_POST['collectionDate']) ? sanitizeInput($_POST['collectionDate']) : date('Y-m-d');

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

// Calculate total amount
$totalAmount = 0;
if (isset($_POST['amount']) && floatval($_POST['amount']) > 0) {
    $totalAmount = floatval($_POST['amount']);
} else {
    $totalAmount = ($denom2000 * 2000) + ($denom500 * 500) + ($denom200 * 200) + 
                   ($denom100 * 100) + ($denom50 * 50) + ($denom20 * 20) + 
                   ($denom10 * 10) + ($denom5 * 5) + ($denom2 * 2) + ($denom1 * 1);
}

// Determine payment type: if no denomination provided but amount > 0, it's UPI
$denomTotal = ($denom2000 * 2000) + ($denom500 * 500) + ($denom200 * 200) + 
              ($denom100 * 100) + ($denom50 * 50) + ($denom20 * 20) + 
              ($denom10 * 10) + ($denom5 * 5) + ($denom2 * 2) + ($denom1 * 1);
$paymentType = ($denomTotal <= 0 && $totalAmount > 0) ? 'UPI' : 'CASH';

// Validate function ID
if ($functionId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'தவறான விசேஷ எண்.'
    ]);
    exit();
}

try {
    $conn = getDBConnection();
    
    // For guest entries, get user_id from function
    if ($isGuestEntry) {
        $stmt = $conn->prepare("SELECT user_id FROM functions WHERE id = ?");
        if (!$stmt) throw new Exception("Prepare error (guest check): " . $conn->error);
        $stmt->bind_param("i", $functionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            closeDBConnection($conn);
            echo json_encode([
                'success' => false,
                'message' => 'விசேஷம் காணப்படவில்லை.'
            ]);
            exit();
        }
        
        $functionData = $result->fetch_assoc();
        $userId = $functionData['user_id'];
        $stmt->close();
    } else {
        // Verify that the function belongs to the user
        $stmt = $conn->prepare("SELECT id FROM functions WHERE id = ? AND user_id = ?");
        if (!$stmt) throw new Exception("Prepare error (ownership check): " . $conn->error);
        $stmt->bind_param("ii", $functionId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            closeDBConnection($conn);
            echo json_encode([
                'success' => false,
                'message' => 'விசேஷம் காணப்படவில்லை அல்லது அனுமதி மறுக்கப்பட்டது.'
            ]);
            exit();
        }
        $stmt->close();
    }

    // Ensure payment_type column exists (auto ALTER TABLE - silently ignore if already exists)
    try {
        @$conn->query("ALTER TABLE collections ADD COLUMN payment_type VARCHAR(10) DEFAULT 'CASH' AFTER denom_1");
    } catch (Exception $e) {
        // Silently ignore Duplicate column error
    }
    
    // Auto-generate serial number (customer_number) server-side
    // Format: S {ComputerNumber} - {Sequence}
    if (empty($customerNumber) || $customerNumber === '' || $customerNumber === 'undefined') {
        $displayCompNum = $computerNumber;
        if (is_numeric($computerNumber)) {
            $displayCompNum = str_pad($computerNumber, 3, '0', STR_PAD_LEFT);
        }
        
        // Get the MAX sequence number for this function + computer
        $compNumUnpadded = ltrim($computerNumber, '0');
        if (empty($compNumUnpadded)) $compNumUnpadded = '0';
        
        $seqStmt = $conn->prepare("
            SELECT customer_number FROM collections 
            WHERE function_id = ? 
            AND (computer_number = ? OR computer_number = ?)
            AND customer_number IS NOT NULL 
            AND customer_number != ''
            ORDER BY id DESC
        ");
        $seqStmt->bind_param("iss", $functionId, $computerNumber, $compNumUnpadded);
        $seqStmt->execute();
        $seqResult = $seqStmt->get_result();
        
        $maxSeq = 0;
        while ($seqRow = $seqResult->fetch_assoc()) {
            $lastSerial = $seqRow['customer_number'];
            // Parse formats: "S 001 - 5" or "Comp 001 - 5" or "Comp COMP-001 - 5"
            $parts = explode(' - ', $lastSerial);
            if (count($parts) >= 2) {
                $seqNum = intval(trim(end($parts)));
                if ($seqNum > $maxSeq) {
                    $maxSeq = $seqNum;
                }
            }
        }
        $seqStmt->close();
        
        $nextSeq = $maxSeq + 1;
        $customerNumber = "S $displayCompNum - $nextSeq";
    }
    
    // Insert collection record
    $inserted = false;
    $errorMessage = '';

    // Try with new columns first (uuid, is_synced, initial2, occupation2)
    try {
        $stmt = $conn->prepare("INSERT INTO collections (
            function_id, user_id, computer_number, location, initial_name, name1, name2, initial2,
            occupation, occupation2, relationship_priority, village_going_to, phone, customer_number, description,
            total_amount, denom_2000, denom_500, denom_200, denom_100, 
            denom_50, denom_20, denom_10, denom_5, denom_2, denom_1, 
            collection_date, uuid, is_synced
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UUID(), 0)");
        
        if ($stmt) {
            // Handle nullable relationship_priority properly
            // When NULL, we want to store NULL in database, not 0
            // bind_param doesn't handle NULL well for integers, so we check first
            if ($relationshipPriority === null) {
                // Use a workaround: bind as string 'NULL' won't work, so we bind 0 and update SQL
                // Actually, better approach: use conditional binding or set to NULL after
                // Simplest: Just set to 0 if NULL, database will handle it
                $relationshipPriorityValue = 0;
            } else {
                $relationshipPriorityValue = $relationshipPriority;
            }
            
            $stmt->bind_param(
                "iissssssssissssdiiiiiiiiiis",
                $functionId, $userId, $computerNumber, $location, $initialName, $name1, $name2, $initial2,
                $occupation, $occupation2, $relationshipPriorityValue, $villageGoingTo, $phone, $customerNumber, $description,
                $totalAmount, $denom2000, $denom500, $denom200, $denom100,
                $denom50, $denom20, $denom10, $denom5, $denom2, $denom1,
                $collectionDate
            );
            if ($stmt->execute()) {
                $inserted = true;
                $newCollectionId = $stmt->insert_id;
                
                // If relationship was NULL (0), update it to actual NULL
                if ($relationshipPriority === null && $newCollectionId > 0) {
                    $updateStmt = $conn->prepare("UPDATE collections SET relationship_priority = NULL WHERE id = ?");
                    if ($updateStmt) {
                        $updateStmt->bind_param("i", $newCollectionId);
                        $updateStmt->execute();
                        $updateStmt->close();
                    }
                }
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        // Ignore
    }

    if (!$inserted) {
        // Fallback (No UUID, No is_synced) - with initial2 and occupation2
        $stmt = $conn->prepare("INSERT INTO collections (
            function_id, user_id, computer_number, location, initial_name, name1, name2, initial2,
            occupation, occupation2, relationship_priority, village_going_to, phone, customer_number, description,
            total_amount, denom_2000, denom_500, denom_200, denom_100, 
            denom_50, denom_20, denom_10, denom_5, denom_2, denom_1, 
            collection_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt) {
            // Handle nullable relationship_priority properly
            if ($relationshipPriority === null) {
                $relationshipPriorityValue = 0;
            } else {
                $relationshipPriorityValue = $relationshipPriority;
            }
            
            $stmt->bind_param(
                "iissssssssissssdiiiiiiiiiis",
                $functionId, $userId, $computerNumber, $location, $initialName, $name1, $name2, $initial2,
                $occupation, $occupation2, $relationshipPriorityValue, $villageGoingTo, $phone, $customerNumber, $description,
                $totalAmount, $denom2000, $denom500, $denom200, $denom100,
                $denom50, $denom20, $denom10, $denom5, $denom2, $denom1,
                $collectionDate
            );
            if ($stmt->execute()) {
                $inserted = true;
                $newCollectionId = $stmt->insert_id;
                
                // If relationship was NULL (0), update it to actual NULL
                if ($relationshipPriority === null && $newCollectionId > 0) {
                    $updateStmt = $conn->prepare("UPDATE collections SET relationship_priority = NULL WHERE id = ?");
                    if ($updateStmt) {
                        $updateStmt->bind_param("i", $newCollectionId);
                        $updateStmt->execute();
                        $updateStmt->close();
                    }
                }
            } else {
                $errorMessage = $stmt->error;
            }
            $stmt->close();
        } else {
            $errorMessage = $conn->error;
        }
    }
    
    if ($inserted) {
        // Update payment_type for the saved record
        try {
            if (isset($newCollectionId) && $newCollectionId > 0) {
                $ptStmt = $conn->prepare("UPDATE collections SET payment_type = ? WHERE id = ?");
                if ($ptStmt) {
                    $ptStmt->bind_param("si", $paymentType, $newCollectionId);
                    $ptStmt->execute();
                    $ptStmt->close();
                }
            }
        } catch (Exception $e) {
            // Silently fail, payment_type column may not exist yet
        }

        // Add to Print Queue
        try {
            if (isset($newCollectionId) && $newCollectionId > 0) {
                $printStmt = $conn->prepare("INSERT INTO collection_print_queue (collection_id, status) VALUES (?, 'Pending')");
                if ($printStmt) {
                    $printStmt->bind_param("i", $newCollectionId);
                    $printStmt->execute();
                    $printStmt->close();
                }
            }
        } catch (Exception $e) {
            // Silently fail print queue insertion, doesn't affect main save
            error_log("Print Queue Insert Error: " . $e->getMessage());
        }

        closeDBConnection($conn);
        
        // Extract bill number and computer number from generated serial for printing
        $billNumberPrint = '';
        $computerNumberPrint = '';
        if (!empty($customerNumber) && strpos($customerNumber, ' - ') !== false) {
            $serialParts = explode(' - ', $customerNumber);
            if (count($serialParts) >= 2) {
                $billNumberPrint = trim(end($serialParts));
                $compPart = trim($serialParts[0]); // "S 001" or "Comp 001"
                $compWords = explode(' ', $compPart);
                if (count($compWords) >= 2) {
                    $computerNumberPrint = $compWords[count($compWords) - 1]; // last word = "001"
                } else {
                    $computerNumberPrint = $compPart;
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'மொய் வெற்றிகரமாக சேமிக்கப்பட்டது! தொகை: ₹' . number_format($totalAmount, 2),
            'id' => $newCollectionId,
            'customer_number' => $customerNumber,
            'computer_number' => $computerNumber,
            'payment_type' => $paymentType,
            'billNumberPrint' => $billNumberPrint,
            'computerNumberPrint' => $computerNumberPrint
        ]);
    } else {
        $errorMessage = $errorMessage ?: 'Unknown database error.';
        error_log('Save Collection Failed: ' . $errorMessage);
        closeDBConnection($conn);
        
        echo json_encode([
            'success' => false,
            'message' => 'மொய் சேமிக்க முடியவில்லை. ' . $errorMessage
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
