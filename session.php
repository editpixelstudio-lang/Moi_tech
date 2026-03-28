<?php
/**
 * Session Handler
 * UZRS MOI Collection System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// AUTOMATIC LOGOUT DISABLED - Only manual logout is applicable
// Set session timeout (30 minutes)
// define('SESSION_TIMEOUT', 1800);

// Check session timeout
// if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
//     session_unset();
//     session_destroy();
//     session_start();
// }

// $_SESSION['last_activity'] = time();
