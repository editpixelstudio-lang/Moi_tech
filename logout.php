<?php
/**
 * Logout API
 * UZRS MOI Collection System
 */

require_once '../includes/session.php';

// Destroy session
session_unset();
session_destroy();

// Redirect to login page
header('Location: ../login.php');
exit();
?>
