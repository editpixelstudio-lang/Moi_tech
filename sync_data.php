<?php
session_start();

/**
 * Sync Data API
 * Synchronizes local data to remote server using UUIDs
 */

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);
set_time_limit(0); // Allow long execution time

header('Content-Type: application/json');

require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$localConn = getDBConnection();
$remoteConn = getRemoteDBConnection();

if (!$remoteConn) {
    echo json_encode([
        'success' => false, 
        'message' => 'இணையதள சேவையகத்துடன் இணைக்க முடியவில்லை. இணைய இணைப்பை சரிபார்க்கவும்.'
    ]);
    exit();
}

// Helper to add columns if not exist
function checkAndAddColumns($conn, $table) {
    // Check is_synced
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'is_synced'");
    if ($res->num_rows == 0) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `is_synced` TINYINT(1) DEFAULT 0");
    }
    // Check remote_id
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'remote_id'");
    if ($res->num_rows == 0) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `remote_id` INT(11) DEFAULT NULL");
    }
    // Check uuid
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'uuid'");
    if ($res->num_rows == 0) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `uuid` CHAR(36) NOT NULL DEFAULT ''");
        // Note: We can't easily populate UUIDs on remote if they are empty without logic, 
        // but new inserts will have them.
        $conn->query("ALTER TABLE `$table` ADD INDEX `idx_uuid` (`uuid`)");
    }
}

// Create deleted_records table if not exists
$conn = $localConn;
$res = $conn->query("SHOW TABLES LIKE 'deleted_records'");
if ($res->num_rows == 0) {
    $sql = "CREATE TABLE `deleted_records` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `table_name` VARCHAR(50) NOT NULL,
        `uuid` CHAR(36) NOT NULL,
        `deleted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_uuid` (`uuid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);
}

// Ensure columns exist in LOCAL and REMOTE DB
$tables = ['users', 'functions', 'collections', 'expenses'];
foreach ($tables as $table) {
    checkAndAddColumns($localConn, $table);
    checkAndAddColumns($remoteConn, $table);
}

$stats = [
    'users' => 0,
    'functions' => 0,
    'collections' => 0,
    'expenses' => 0,
    'deleted' => 0
];

$userMap = []; // Local ID -> Remote ID
$functionMap = []; // Local ID -> Remote ID
$collectionMap = []; // Local ID -> Remote ID

try {
    // 0. Sync Deletions
    $deleted = $localConn->query("SELECT * FROM deleted_records");
    while ($del = $deleted->fetch_assoc()) {
        $table = $del['table_name'];
        $uuid = $del['uuid'];
        
        // Delete from remote based on UUID
        // First check if table is valid to prevent SQL injection
        if (in_array($table, $tables)) {
            $stmt = $remoteConn->prepare("DELETE FROM `$table` WHERE uuid = ?");
            $stmt->bind_param("s", $uuid);
            $stmt->execute();
            $stmt->close();
            
            // Also delete from local deleted_records to avoid repeated attempts
            // Or keep it? Better to delete it so we don't grow forever.
            $localConn->query("DELETE FROM deleted_records WHERE id = " . $del['id']);
            $stats['deleted']++;
        }
    }

    // 1. Sync Users
    $users = $localConn->query("SELECT * FROM users");
    while ($user = $users->fetch_assoc()) {
        $localId = $user['id'];
        $uuid = $user['uuid'];
        $phone = $user['phone'];
        $isSynced = $user['is_synced'];
        $remoteId = $user['remote_id'];

        // If we don't have a UUID locally (legacy data), generate one
        if (empty($uuid)) {
            $uuid = uniqid() . '-' . bin2hex(random_bytes(8)); // Simple UUID-like string
            $localConn->query("UPDATE users SET uuid = '$uuid' WHERE id = $localId");
        }

        if ($remoteId) {
            // Already linked
            if ($isSynced == 0) {
                $upd = $remoteConn->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
                $upd->bind_param("ssi", $user['full_name'], $user['phone'], $remoteId);
                $upd->execute();
                $upd->close();
                $localConn->query("UPDATE users SET is_synced = 1 WHERE id = $localId");
                $stats['users']++;
            }
        } else {
            // Not linked, check by UUID first
            $stmt = $remoteConn->prepare("SELECT id FROM users WHERE uuid = ?");
            $stmt->bind_param("s", $uuid);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if ($res->num_rows > 0) {
                $remoteRow = $res->fetch_assoc();
                $remoteId = $remoteRow['id'];
            } else {
                // Check by Phone (fallback for legacy data)
                $stmt->close();
                $stmt = $remoteConn->prepare("SELECT id FROM users WHERE phone = ?");
                $stmt->bind_param("s", $phone);
                $stmt->execute();
                $res = $stmt->get_result();
                
                if ($res->num_rows > 0) {
                    $remoteRow = $res->fetch_assoc();
                    $remoteId = $remoteRow['id'];
                    // Update remote UUID to match local if empty
                    $remoteConn->query("UPDATE users SET uuid = '$uuid' WHERE id = $remoteId AND (uuid = '' OR uuid IS NULL)");
                } else {
                    // Insert
                    $ins = $remoteConn->prepare("INSERT INTO users (full_name, phone, password, uuid) VALUES (?, ?, ?, ?)");
                    $ins->bind_param("ssss", $user['full_name'], $user['phone'], $user['password'], $uuid);
                    $ins->execute();
                    $remoteId = $ins->insert_id;
                    $ins->close();
                    $stats['users']++;
                }
            }
            if (isset($stmt)) $stmt->close();
            
            // Update local
            $localConn->query("UPDATE users SET remote_id = $remoteId, is_synced = 1 WHERE id = $localId");
        }
        $userMap[$localId] = $remoteId;
    }

    // 2. Sync Functions
    $functions = $localConn->query("SELECT * FROM functions");
    while ($func = $functions->fetch_assoc()) {
        $localId = $func['id'];
        $localUserId = $func['user_id'];
        $uuid = $func['uuid'];
        $isSynced = $func['is_synced'];
        $remoteId = $func['remote_id'];
        
        if (!isset($userMap[$localUserId])) continue;
        $remoteUserId = $userMap[$localUserId];

        if (empty($uuid)) {
            $uuid = uniqid() . '-' . bin2hex(random_bytes(8));
            $localConn->query("UPDATE functions SET uuid = '$uuid' WHERE id = $localId");
        }

        if ($remoteId) {
            if ($isSynced == 0) {
                $upd = $remoteConn->prepare("UPDATE functions SET function_name=?, function_date=?, place=?, function_details=? WHERE id=?");
                $upd->bind_param("ssssi", $func['function_name'], $func['function_date'], $func['place'], $func['function_details'], $remoteId);
                $upd->execute();
                $upd->close();
                $localConn->query("UPDATE functions SET is_synced = 1 WHERE id = $localId");
                $stats['functions']++;
            }
        } else {
            // Check by UUID
            $stmt = $remoteConn->prepare("SELECT id FROM functions WHERE uuid = ?");
            $stmt->bind_param("s", $uuid);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if ($res->num_rows > 0) {
                $remoteRow = $res->fetch_assoc();
                $remoteId = $remoteRow['id'];
            } else {
                // Fallback: Check by details
                $stmt->close();
                $stmt = $remoteConn->prepare("SELECT id FROM functions WHERE user_id = ? AND function_name = ? AND function_date = ? AND place = ?");
                $stmt->bind_param("isss", $remoteUserId, $func['function_name'], $func['function_date'], $func['place']);
                $stmt->execute();
                $res = $stmt->get_result();
                
                if ($res->num_rows > 0) {
                    $remoteRow = $res->fetch_assoc();
                    $remoteId = $remoteRow['id'];
                    $remoteConn->query("UPDATE functions SET uuid = '$uuid' WHERE id = $remoteId AND (uuid = '' OR uuid IS NULL)");
                } else {
                    $ins = $remoteConn->prepare("INSERT INTO functions (user_id, function_name, function_date, place, function_details, uuid) VALUES (?, ?, ?, ?, ?, ?)");
                    $ins->bind_param("isssss", $remoteUserId, $func['function_name'], $func['function_date'], $func['place'], $func['function_details'], $uuid);
                    $ins->execute();
                    $remoteId = $ins->insert_id;
                    $ins->close();
                    $stats['functions']++;
                }
            }
            if (isset($stmt)) $stmt->close();
            
            $localConn->query("UPDATE functions SET remote_id = $remoteId, is_synced = 1 WHERE id = $localId");
        }
        $functionMap[$localId] = $remoteId;
    }

    // 3. Sync Collections
    $collections = $localConn->query("SELECT * FROM collections");
    while ($col = $collections->fetch_assoc()) {
        $localId = $col['id'];
        $localFuncId = $col['function_id'];
        $localUserId = $col['user_id'];
        $uuid = $col['uuid'];
        $isSynced = $col['is_synced'];
        $remoteId = $col['remote_id'];
        
        if (!isset($functionMap[$localFuncId]) || !isset($userMap[$localUserId])) continue;
        
        $remoteFuncId = $functionMap[$localFuncId];
        $remoteUserId = $userMap[$localUserId];

        if (empty($uuid)) {
            $uuid = uniqid() . '-' . bin2hex(random_bytes(8));
            $localConn->query("UPDATE collections SET uuid = '$uuid' WHERE id = $localId");
        }

        if ($remoteId) {
            if ($isSynced == 0) {
                $upd = $remoteConn->prepare("UPDATE collections SET 
                    location=?, initial_name=?, name1=?, name2=?, occupation=?, 
                    relationship_priority=?, village_going_to=?, phone=?, customer_number=?, 
                    description=?, total_amount=?, denom_2000=?, denom_500=?, denom_200=?, 
                    denom_100=?, denom_50=?, denom_20=?, denom_10=?, denom_5=?, denom_2=?, denom_1=?, 
                    collection_date=? 
                    WHERE id=?");
                $upd->bind_param("sssssissssiiiiiiiiiiiisi", 
                    $col['location'], $col['initial_name'], $col['name1'], $col['name2'], $col['occupation'],
                    $col['relationship_priority'], $col['village_going_to'], $col['phone'], $col['customer_number'],
                    $col['description'], $col['total_amount'], $col['denom_2000'], $col['denom_500'], $col['denom_200'],
                    $col['denom_100'], $col['denom_50'], $col['denom_20'], $col['denom_10'], $col['denom_5'], $col['denom_2'], $col['denom_1'],
                    $col['collection_date'], $remoteId
                );
                $upd->execute();
                $upd->close();
                $localConn->query("UPDATE collections SET is_synced = 1 WHERE id = $localId");
                $stats['collections']++;
            }
        } else {
            // Check by UUID
            $stmt = $remoteConn->prepare("SELECT id FROM collections WHERE uuid = ?");
            $stmt->bind_param("s", $uuid);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if ($res->num_rows > 0) {
                $remoteRow = $res->fetch_assoc();
                $remoteId = $remoteRow['id'];
            } else {
                // No fallback for collections - if UUID not found, it's new. 
                // We don't want to merge collections based on name/amount as duplicates are valid.
                $ins = $remoteConn->prepare("INSERT INTO collections (
                    function_id, user_id, computer_number, location, initial_name, name1, name2, 
                    occupation, relationship_priority, village_going_to, phone, customer_number, description,
                    total_amount, denom_2000, denom_500, denom_200, denom_100, 
                    denom_50, denom_20, denom_10, denom_5, denom_2, denom_1, 
                    collection_date, uuid
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $ins->bind_param(
                    "iissssssissssdiiiiiiiiiiss",
                    $remoteFuncId, $remoteUserId, $col['computer_number'], $col['location'], $col['initial_name'], $col['name1'], $col['name2'],
                    $col['occupation'], $col['relationship_priority'], $col['village_going_to'], $col['phone'], $col['customer_number'], $col['description'],
                    $col['total_amount'], $col['denom_2000'], $col['denom_500'], $col['denom_200'], $col['denom_100'],
                    $col['denom_50'], $col['denom_20'], $col['denom_10'], $col['denom_5'], $col['denom_2'], $col['denom_1'],
                    $col['collection_date'], $uuid
                );
                $ins->execute();
                $remoteId = $ins->insert_id;
                $ins->close();
                $stats['collections']++;
            }
            if (isset($stmt)) $stmt->close();
            
            $localConn->query("UPDATE collections SET remote_id = $remoteId, is_synced = 1 WHERE id = $localId");
        }
        $collectionMap[$localId] = $remoteId;
    }

    // 4. Sync Expenses
    $expenses = $localConn->query("SELECT * FROM expenses");
    while ($exp = $expenses->fetch_assoc()) {
        $localId = $exp['id'];
        $localUserId = $exp['user_id'];
        $uuid = $exp['uuid'];
        $isSynced = $exp['is_synced'];
        $remoteId = $exp['remote_id'];
        
        if (!isset($userMap[$localUserId])) continue;
        $remoteUserId = $userMap[$localUserId];
        
        $remoteCollectionId = null;
        if (!empty($exp['related_collection_id']) && isset($collectionMap[$exp['related_collection_id']])) {
            $remoteCollectionId = $collectionMap[$exp['related_collection_id']];
        }

        if (empty($uuid)) {
            $uuid = uniqid() . '-' . bin2hex(random_bytes(8));
            $localConn->query("UPDATE expenses SET uuid = '$uuid' WHERE id = $localId");
        }
        
        if ($remoteId) {
            if ($isSynced == 0) {
                $upd = $remoteConn->prepare("UPDATE expenses SET to_name=?, function_name=?, place=?, expense_date=?, amount=?, related_collection_id=? WHERE id=?");
                $upd->bind_param("ssssdii", $exp['to_name'], $exp['function_name'], $exp['place'], $exp['expense_date'], $exp['amount'], $remoteCollectionId, $remoteId);
                $upd->execute();
                $upd->close();
                $localConn->query("UPDATE expenses SET is_synced = 1 WHERE id = $localId");
                $stats['expenses']++;
            }
        } else {
            $stmt = $remoteConn->prepare("SELECT id FROM expenses WHERE uuid = ?");
            $stmt->bind_param("s", $uuid);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if ($res->num_rows > 0) {
                $remoteRow = $res->fetch_assoc();
                $remoteId = $remoteRow['id'];
            } else {
                $ins = $remoteConn->prepare("INSERT INTO expenses (user_id, to_name, function_name, place, expense_date, amount, related_collection_id, uuid) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $ins->bind_param("issssdis", $remoteUserId, $exp['to_name'], $exp['function_name'], $exp['place'], $exp['expense_date'], $exp['amount'], $remoteCollectionId, $uuid);
                $ins->execute();
                $remoteId = $ins->insert_id;
                $ins->close();
                $stats['expenses']++;
            }
            if (isset($stmt)) $stmt->close();
            
            $localConn->query("UPDATE expenses SET remote_id = $remoteId, is_synced = 1 WHERE id = $localId");
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'தரவு ஒத்திசைவு வெற்றிகரமாக முடிந்தது!',
        'stats' => $stats
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'பிழை: ' . $e->getMessage()
    ]);
}

closeDBConnection($localConn);
closeDBConnection($remoteConn);
?>