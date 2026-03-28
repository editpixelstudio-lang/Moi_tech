<?php
/**
 * Search Collections API - Autocomplete with Online/Offline Support
 * Returns matching collection records for autocomplete
 * Dynamically switches between cloud and local database based on connectivity
 */

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user = getCurrentUser();
$userId = $user['id'];

// Get search query and field
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$field = isset($_GET['field']) ? trim($_GET['field']) : '';

if (empty($query) || empty($field)) {
    echo json_encode(['success' => true, 'results' => []]);
    exit;
}

// Get contextual filters
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$initial = isset($_GET['initial']) ? trim($_GET['initial']) : '';
$name1 = isset($_GET['name1']) ? trim($_GET['name1']) : '';
$name2 = isset($_GET['name2']) ? trim($_GET['name2']) : '';
$isOnline = isset($_GET['is_online']) && $_GET['is_online'] === 'true';

// ============================================================================
// HELPER FUNCTION: Execute Search Query
// ============================================================================
function executeSearch($conn, $query, $field, $location, $initial, $name1, $name2, $useCache = false) {
    // Prefix matching for search
    $searchTerm = mb_strtolower($query, 'UTF-8') . "%";
    
    $whereConditions = [];
    $params = [];
    $types = "";
    
    // Determine search columns based on field
    $searchCols = [];
    switch ($field) {
        case 'location': $searchCols = ['location']; break;
        case 'village': $searchCols = ['village_going_to']; break;
        case 'initial': $searchCols = ['initial_name']; break;
        case 'name1': $searchCols = ['name1']; break;
        case 'name2': $searchCols = ['name2']; break;
        case 'occupation': $searchCols = ['occupation']; break;
        case 'phone': $searchCols = ['phone']; break;
        case 'customerNo': $searchCols = ['customer_number']; break;
        case 'description': $searchCols = ['description']; break;
        default: $searchCols = ['initial_name', 'name1', 'name2', 'occupation', 'phone', 'customer_number', 'village_going_to', 'location']; break;
    }
    
    // Build OR conditions for search columns
    $orConditions = [];
    foreach ($searchCols as $col) {
        $orConditions[] = "LOWER($col) LIKE ?";
        $params[] = $searchTerm;
        $types .= "s";
    }
    $whereConditions[] = "(" . implode(" OR ", $orConditions) . ")";
    
    // Add contextual filters
    if ($field !== 'location' && !empty($location)) {
        $whereConditions[] = "location = ?";
        $params[] = $location; 
        $types .= "s";
    }
    if ($field === 'name1' && !empty($initial)) {
        $whereConditions[] = "initial_name = ?";
        $params[] = $initial; 
        $types .= "s";
    }
    if ($field === 'name2' && !empty($name1)) {
        if (!empty($initial)) { 
            $whereConditions[] = "initial_name = ?"; 
            $params[] = $initial; 
            $types .= "s"; 
        }
        $whereConditions[] = "name1 = ?"; 
        $params[] = $name1; 
        $types .= "s";
    }
    if ($field === 'occupation' && !empty($name2)) {
        if (!empty($initial)) { 
            $whereConditions[] = "initial_name = ?"; 
            $params[] = $initial; 
            $types .= "s"; 
        }
        if (!empty($name1)) { 
            $whereConditions[] = "name1 = ?"; 
            $params[] = $name1; 
            $types .= "s"; 
        }
        $whereConditions[] = "name2 = ?"; 
        $params[] = $name2; 
        $types .= "s";
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    // Build SQL based on field type
    if ($field === 'location' || $field === 'village' || $field === 'occupation') {
        $colName = ($field === 'location') ? 'location' : (($field === 'village') ? 'village_going_to' : 'occupation');
        
        $sql = "SELECT DISTINCT $colName FROM collections WHERE $whereClause";
        if ($useCache) {
            $sql .= " UNION SELECT DISTINCT $colName FROM suggestion_cache WHERE $whereClause";
        }
        $sql .= " LIMIT 15";
    } else {
        $cols = "customer_number, phone, initial_name, name1, name2, occupation, village_going_to, location, description";
        $sql = "SELECT DISTINCT $cols FROM collections WHERE $whereClause";
        if ($useCache) {
            $sql .= " UNION SELECT DISTINCT $cols FROM suggestion_cache WHERE $whereClause";
        }
        $sql .= " LIMIT 15";
    }
    
    // If using UNION, duplicate params for the second query
    if ($useCache) {
        $types .= $types;
        $params = array_merge($params, $params);
    }
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $output = [];
    while ($row = $result->fetch_assoc()) {
        if ($field === 'location' || $field === 'village' || $field === 'occupation') {
            $val = ($field === 'location') ? ($row['location'] ?? '') : (($field === 'village') ? ($row['village_going_to'] ?? '') : ($row['occupation'] ?? ''));
            $val = $val ?: '';
            if (!empty($val)) {
                $output[] = [$field => $val, 'display' => $val];
            }
        } else {
            $displayParts = [];
            if (!empty($row['initial_name'])) $displayParts[] = $row['initial_name'];
            if (!empty($row['name1'])) $displayParts[] = $row['name1'];
            if (!empty($row['name2'])) $displayParts[] = $row['name2'];
            if (!empty($row['occupation'])) $displayParts[] = $row['occupation'];
            $mainDisplay = implode('-', $displayParts);
            
            // Context parts removed as per user request (just name and details)
            /*
            $contextParts = [];
            if (!empty($row['phone'])) $contextParts[] = '📞' . $row['phone'];
            if (!empty($row['village_going_to'])) $contextParts[] = '🏘️' . $row['village_going_to'];
            if (!empty($row['customer_number'])) $contextParts[] = '#' . $row['customer_number'];
            */
            
            $fullDisplay = $mainDisplay;
            /*
            if (!empty($contextParts)) {
                $fullDisplay .= ' (' . implode(' • ', $contextParts) . ')';
            }
            */
            
            $output[] = [
                'customerNumber' => $row['customer_number'] ?? '',
                'phone' => $row['phone'] ?? '',
                'initial' => $row['initial_name'] ?? '',
                'name1' => $row['name1'] ?? '',
                'name2' => $row['name2'] ?? '',
                'occupation' => $row['occupation'] ?? '',
                'village' => $row['village_going_to'] ?? '',
                'location' => $row['location'] ?? '',
                'description' => $row['description'] ?? '',
                'display' => $fullDisplay
            ];
        }
    }
    $stmt->close();
    return $output;
}

// 1. Initialize Local Connection (always needed for cache)
$localConn = getDBConnection();

// 2. Ensure Cache Table Exists (for offline functionality)
$localConn->query("CREATE TABLE IF NOT EXISTS `suggestion_cache` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `customer_number` VARCHAR(50),
    `phone` VARCHAR(20),
    `initial_name` VARCHAR(50),
    `name1` VARCHAR(100),
    `name2` VARCHAR(100),
    `occupation` VARCHAR(100),
    `village_going_to` VARCHAR(100),
    `location` VARCHAR(100),
    `description` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_location` (`location`),
    INDEX `idx_name` (`name1`),
    INDEX `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$results = [];

// Local Search - Use collections table only
$results = executeSearch($localConn, $query, $field, $location, $initial, $name1, $name2, false);

// Deduplicate results based on display value
$seen = [];
$dedupedResults = [];
foreach ($results as $item) {
    $key = $item['display'] ?? '';
    if (!empty($key) && !isset($seen[$key])) {
        $seen[$key] = true;
        $dedupedResults[] = $item;
    }
}

closeDBConnection($localConn);
echo json_encode(['success' => true, 'results' => $dedupedResults, 'source' => 'local']);
exit;
