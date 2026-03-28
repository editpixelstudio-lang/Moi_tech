<?php
/**
 * Get All Transactions API
 * UZRS MOI Collection System
 */

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['user_id'];
// $computerNumber = isset($_SESSION['computer_number']) ? $_SESSION['computer_number'] : '';

// if (empty($computerNumber)) {
//     echo json_encode(['success' => false, 'message' => 'Computer number not set']);
//     exit();
// }

$conn = getDBConnection();

// Filters
$month = isset($_GET['month']) ? intval($_GET['month']) : 0;
$year = isset($_GET['year']) ? intval($_GET['year']) : 0;
$type = isset($_GET['type']) ? $_GET['type'] : 'all'; // all, income, expense

$dateFilter = "";
if ($month > 0 && $year > 0) {
    $startDate = "$year-$month-01";
    $endDate = date("Y-m-t", strtotime($startDate));
    $dateFilter = " AND date BETWEEN '$startDate' AND '$endDate'";
}

// Build Query
$sql = "
SELECT * FROM (
    (SELECT 
        'income' as type,
        c.id, 
        c.collection_date as date, 
        c.total_amount as amount, 
        TRIM(CONCAT(COALESCE(c.initial_name,''), ' ', c.name1, ' ', COALESCE(c.name2,''))) as name,
        c.location as place,
        f.function_name as description
     FROM collections c
     JOIN functions f ON c.function_id = f.id
     WHERE c.user_id = ?)
    UNION ALL
    (SELECT 
        'expense' as type,
        e.id, 
        e.expense_date as date, 
        e.amount, 
        e.to_name as name, 
        e.place as place, 
        e.function_name as description
     FROM expenses e
     WHERE e.user_id = ?)
) AS combined_transactions
WHERE 1=1
";

if ($type === 'income') {
    $sql .= " AND type = 'income'";
} elseif ($type === 'expense') {
    $sql .= " AND type = 'expense'";
}

if (!empty($dateFilter)) {
    $sql .= $dateFilter;
}

$sql .= " ORDER BY date DESC, id DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("ii", $userId, $userId);
$stmt->execute();
$result = $stmt->get_result();

$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

// Handle JSON encoding with UTF-8 support
$jsonFlags = 0;
if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
    $jsonFlags |= JSON_INVALID_UTF8_SUBSTITUTE;
}
if (defined('JSON_UNESCAPED_UNICODE')) {
    $jsonFlags |= JSON_UNESCAPED_UNICODE;
}

$jsonOutput = json_encode(['success' => true, 'transactions' => $transactions, 'debug_count' => count($transactions)], $jsonFlags);

if ($jsonOutput === false) {
    echo json_encode(['success' => false, 'message' => 'JSON Error: ' . json_last_error_msg()]);
} else {
    echo $jsonOutput;
}

$stmt->close();
closeDBConnection($conn);
?>
