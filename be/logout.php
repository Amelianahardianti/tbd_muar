<?php
session_start();

// Log logout attempt
if (isset($_SESSION['id_mua'])) {
    error_log("MUA logout: ID " . $_SESSION['id_mua']);
} else if (isset($_SESSION['id_pelanggan'])) {
    error_log("Pelanggan logout: ID " . $_SESSION['id_pelanggan']);
} else {
    error_log("Logout attempt without active session");
}

// Destroy all session data
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// If this is an AJAX request, return JSON
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    exit;
}

// For regular requests, redirect to login page
header("Location: ../fe/login.html");
exit;
?>