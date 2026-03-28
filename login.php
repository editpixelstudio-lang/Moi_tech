<?php
session_start();

/**
 * Login API
 * UZRS MOI Collection System
 */

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once '../includes/session.php';
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

$phone = isset($data['phone']) ? sanitizeInput($data['phone']) : '';
$password = isset($data['password']) ? $data['password'] : '';

// Validation
if (empty($phone) || empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'கைபேசி எண் மற்றும் கடவுச்சொல் தேவை'
    ]);
    exit();
}

if (!validatePhone($phone)) {
    echo json_encode([
        'success' => false,
        'message' => 'தவறான கைபேசி எண் வடிவம்'
    ]);
    exit();
}

// Check credentials
$conn = getDBConnection();

$stmt = $conn->prepare("SELECT id, full_name, email, phone, password, role, is_active FROM users WHERE phone = ?");

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

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'தவறான கைபேசி எண் அல்லது கடவுச்சொல்'
    ]);
    $stmt->close();
    closeDBConnection($conn);
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();
// Connection kept open for potential updates

// Check if user is active
if (!$user['is_active']) {
    echo json_encode([
        'success' => false,
        'message' => 'உங்கள் கணக்கு செயலில் இல்லை. நிர்வாகியை தொடர்பு கொள்ளவும்'
    ]);
    closeDBConnection($conn);
    exit();
}

// Verify password
if (!verifyPassword($password, $user['password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'தவறான கைபேசி எண் அல்லது கடவுச்சொல்'
    ]);
    closeDBConnection($conn);
    exit();
}

closeDBConnection($conn);

// Set session
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['full_name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_phone'] = $user['phone'];
$_SESSION['user_role'] = $user['role'];

echo json_encode([
    'success' => true,
    'message' => 'உள்நுழைவு வெற்றிகரமாக உள்ளது!',
    'user' => [
        'id' => $user['id'],
        'name' => $user['full_name'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'role' => $user['role']
    ]
]);
?>
