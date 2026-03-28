<?php
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$function_id = isset($_POST['function_id']) ? intval($_POST['function_id']) : 0;
$find_text = isset($_POST['find_text']) ? $_POST['find_text'] : '';
$replace_text = isset($_POST['replace_text']) ? $_POST['replace_text'] : '';
$field = isset($_POST['field']) ? $_POST['field'] : 'all';

if ($function_id <= 0 || $find_text === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$userId = $_SESSION['user_id'];
$computerNumber = isset($_SESSION['computer_number']) ? trim($_SESSION['computer_number']) : '';

if ($computerNumber === '') {
    echo json_encode(['success' => false, 'message' => 'Computer number not set for this session']);
    exit;
}

// Allowed fields to prevent SQL injection
$allowed_fields = ['location', 'name1', 'name2', 'initial_name', 'occupation', 'village_going_to', 'description'];

if ($field === 'all') {
    $fields_to_update = $allowed_fields;
} elseif (in_array($field, $allowed_fields, true)) {
    $fields_to_update = [$field];
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid field']);
    exit;
}

$conn = getDBConnection();
$total_affected = 0;

foreach ($fields_to_update as $col) {
    $sql = "UPDATE collections SET $col = REPLACE($col, ?, ?), is_synced = 0 WHERE function_id = ? AND user_id = ? AND computer_number = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        closeDBConnection($conn);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param('ssiis', $find_text, $replace_text, $function_id, $userId, $computerNumber);
    $stmt->execute();
    $total_affected += $stmt->affected_rows;
    $stmt->close();
}

closeDBConnection($conn);

echo json_encode(['success' => true, 'message' => "Replaced $total_affected occurrences only for this computer."]);
?>
