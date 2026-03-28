<?php
/**
 * Signup API
 * UZRS MOI Collection System
 */

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'தவறான கோரிக்கை முறை'
    ]);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

$full_name = isset($data['full_name']) ? sanitizeInput($data['full_name']) : '';
$phone = isset($data['phone']) ? sanitizeInput($data['phone']) : '';
$password = isset($data['password']) ? $data['password'] : '';
$confirm_password = isset($data['confirm_password']) ? $data['confirm_password'] : '';

// Validation
$errors = [];

if (empty($full_name)) {
    $errors[] = 'முழு பெயர் தேவை';
}

if (empty($phone)) {
    $errors[] = 'கைபேசி எண் தேவை';
} elseif (!validatePhone($phone)) {
    $errors[] = 'தவறான கைபேசி எண்';
}

if (empty($password)) {
    $errors[] = 'கடவுச்சொல் தேவை';
} elseif (strlen($password) < 6) {
    $errors[] = 'கடவுச்சொல் குறைந்தது 6 எழுத்துக்கள் இருக்க வேண்டும்';
}

if ($password !== $confirm_password) {
    $errors[] = 'கடவுச்சொற்கள் பொருந்தவில்லை';
}

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => implode(', ', $errors)
    ]);
    exit();
}

// Check if phone already exists
$conn = getDBConnection();

$stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Database prepare error: ' . $conn->error
    ]);
    closeDBConnection($conn);
    exit();
}

$stmt->bind_param("s", $phone);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'கைபேசி எண் ஏற்கனவே பதிவு செய்யப்பட்டுள்ளது'
    ]);
    $stmt->close();
    closeDBConnection($conn);
    exit();
}
$stmt->close();

// Insert new user
$hashed_password = hashPassword($password);

// Try with new columns first (uuid, is_synced)
$inserted = false;
try {
    $stmt = $conn->prepare("INSERT INTO users (full_name, phone, password, uuid, is_synced) VALUES (?, ?, ?, UUID(), 0)");
    if ($stmt) {
        $stmt->bind_param("sss", $full_name, $phone, $hashed_password);
        if ($stmt->execute()) {
            $inserted = true;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    // Ignore and try fallback
}

if (!$inserted) {
    // Fallback to legacy insert
    $stmt = $conn->prepare("INSERT INTO users (full_name, phone, password) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("sss", $full_name, $phone, $hashed_password);
        if ($stmt->execute()) {
            $inserted = true;
        }
        $stmt->close();
    }
}

if ($inserted) {
    echo json_encode([
        'success' => true,
        'message' => 'பதிவு வெற்றிகரமாக முடிந்தது! நீங்கள் இப்போது உள்நுழையலாம்.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'பதிவு தோல்வியடைந்தது. மீண்டும் முயற்சிக்கவும்.'
    ]);
}

closeDBConnection($conn);
?>
