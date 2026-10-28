<?php
/**
 * Administrator Logout
 * Mount Excel Boarding School
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}
if (!verifyCsrfToken()) {
    http_response_code(403);
    exit('Invalid security token.');
}

if (isAuthenticated()) {
    $admin = currentAdmin();
    logActivity('LOGOUT', "Admin logged out: {$admin['username']}", $admin['id']);
}

// Unset all session variables
$_SESSION = [];

// Destroy session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start fresh session for flash message
session_start();
setFlash('info', 'You have been successfully signed out of the staff portal.');

header('Location: login.php');
exit;
