<?php
/**
 * Database Configuration File
 * UZRS MOI Collection System
 */

// Determine environment
$is_remote = isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'uzrssoft.com') !== false);

if ($is_remote) {
    // Production Database credentials
    define('DB_HOST', 'localhost');
    define('DB_USER', 'uzrssa40_vvs-moi-tech');
    define('DB_PASS', 'uzrssa40_vvs-moi-tech');
    define('DB_NAME', 'uzrssa40_vvs-moi-tech');
} else {
    // Local Database credentials
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'uzrs_moi_vvs');
}

// Determine if we're already on the remote server
$is_on_remote_server = isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'uzrssoft.com') !== false);

// Remote Database credentials
// Use 'localhost' if we're on the remote server, otherwise use the domain
define('REMOTE_DB_HOST', $is_on_remote_server ? 'localhost' : 'www.uzrssoft.com');
define('REMOTE_DB_USER', 'uzrssa40_vvs-moi-tech');
define('REMOTE_DB_PASS', 'uzrssa40_vvs-moi-tech');
define('REMOTE_DB_NAME', 'uzrssa40_vvs-moi-tech');

// Create connection
function getDBConnection() {
    try {
        // Set connection timeout
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // Check connection
        if ($conn->connect_error) {
            throw new Exception($conn->connect_error);
        }
        
        // Set charset to utf8mb4
        $conn->set_charset("utf8mb4");
        
        // Set connection timeout (5 seconds)
        $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
        
        return $conn;
    } catch (Exception $e) {
        die(json_encode([
            'success' => false,
            'message' => 'Database connection failed: ' . $e->getMessage()
        ]));
    }
}

// Create remote connection
function getRemoteDBConnection() {
    try {
        // Suppress warnings for connection errors to handle them gracefully
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        
        // Set connection timeout to 5 seconds
        $conn = mysqli_init();
        if (!$conn) {
            return null;
        }
        
        $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
        $conn->options(MYSQLI_OPT_READ_TIMEOUT, 10);
        
        @$conn->real_connect(REMOTE_DB_HOST, REMOTE_DB_USER, REMOTE_DB_PASS, REMOTE_DB_NAME);
        
        // Check connection
        if ($conn->connect_error) {
            return null;
        }
        
        // Set charset to utf8mb4
        $conn->set_charset("utf8mb4");
        
        return $conn;
    } catch (Exception $e) {
        return null;
    }
}

// Close connection
function closeDBConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}
