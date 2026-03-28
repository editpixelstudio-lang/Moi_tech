<?php
/**
 * Test Connection API
 * Checks if remote cloud database is accessible
 * Used by ConnectionManager for online/offline detection
 */

// Prevent any PHP errors from breaking JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../config/database.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$response = [
    'success' => true,
    'local_connected' => false,
    'remote_connected' => false,
    'timestamp' => time()
];

// Test local connection first
try {
    $localConn = getDBConnection();
    if ($localConn) {
        $response['local_connected'] = true;
        closeDBConnection($localConn);
    }
} catch (Exception $e) {
    $response['local_connected'] = false;
}

// Test remote connection
try {
    $remoteConn = getRemoteDBConnection();
    if ($remoteConn) {
        // Verify connection is actually working with a simple query
        $result = $remoteConn->query("SELECT 1");
        if ($result) {
            $response['remote_connected'] = true;
            $result->close();
        }
        closeDBConnection($remoteConn);
    }
} catch (Exception $e) {
    $response['remote_connected'] = false;
}

echo json_encode($response);
