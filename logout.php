<?php
// logout.php
// Handle user logout and session cleanup

require_once 'config/database.php';

// Start session
startSecureSession();

// Store user info before destroying session (for logout message)
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';

// Destroy all session data
session_unset();
session_destroy();

// Start new session for logout message
session_start();
$_SESSION['logout_message'] = 'Anda telah berjaya log keluar dari sistem.';

// Redirect to login page
header('Location: login.php');
exit();
?>