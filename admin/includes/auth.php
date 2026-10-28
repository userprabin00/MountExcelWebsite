<?php
/**
 * Admin Authentication & Session Guard
 * Mount Excel Boarding School
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

/**
 * Check if current user is authenticated as an admin
 */
function isAuthenticated() {
    return !empty($_SESSION['admin_logged_in']) && !empty($_SESSION['admin_id']);
}

/**
 * Require authentication or redirect to login
 */
function requireAuth() {
    if (!isAuthenticated()) {
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
        $_SESSION['intended_url'] = $currentUrl;
        header('Location: login.php');
        exit;
    }

    // Session inactivity timeout (default: 2 hours)
    $lifetime = max(60, (int)env('SESSION_LIFETIME', 7200));
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $lifetime)) {
        // Session expired
        session_unset();
        session_destroy();
        session_start();
        setFlash('warning', 'Your session has timed out due to inactivity. Please log in again.');
        header('Location: login.php');
        exit;
    }
    $_SESSION['last_activity'] = time();

    try {
        $stmt = getDB()->prepare("SELECT role, is_active FROM admins WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $_SESSION['admin_id']]);
        $adminState = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Admin session validation failed: ' . $e->getMessage());
        $adminState = false;
    }
    if (!$adminState || (int)$adminState['is_active'] !== 1) {
        $_SESSION = [];
        session_destroy();
        session_start();
        setFlash('error', 'Your account is unavailable. Please contact an administrator.');
        header('Location: login.php');
        exit;
    }
    $_SESSION['admin_role'] = $adminState['role'];
}

/**
 * Get currently authenticated admin details
 */
function currentAdmin() {
    if (!isAuthenticated()) {
        return null;
    }
    return [
        'id'        => $_SESSION['admin_id'] ?? null,
        'username'  => $_SESSION['admin_username'] ?? '',
        'full_name' => $_SESSION['admin_name'] ?? 'Staff',
        'role'      => $_SESSION['admin_role'] ?? 'staff',
        'email'     => $_SESSION['admin_email'] ?? ''
    ];
}

/**
 * Check if the currently authenticated admin has one of the specified roles
 *
 * @param string|array $roles Single role string or array of allowed roles
 * @return bool
 */
function hasRole($roles) {
    if (!isAuthenticated()) {
        return false;
    }
    $admin = currentAdmin();
    $userRole = $admin['role'] ?? 'staff';

    if (is_string($roles)) {
        $roles = [$roles];
    }
    return in_array($userRole, $roles, true);
}

/**
 * Enforce role requirement on the server
 * If authenticated user does not have an authorized role, blocks with 403 Forbidden
 *
 * @param string|array $allowedRoles
 */
function requireRole($allowedRoles) {
    requireAuth();

    if (!hasRole($allowedRoles)) {
        http_response_code(403);
        $user = currentAdmin();
        $userRole = $user['role'] ?? 'staff';
        $allowedStr = is_array($allowedRoles) ? implode(', ', $allowedRoles) : $allowedRoles;
        logActivity('ACCESS_DENIED', "Role '{$userRole}' attempted to access restricted endpoint: " . ($_SERVER['REQUEST_URI'] ?? ''), $user['id'] ?? null);

        // If AJAX request, respond with JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error'   => 'Forbidden',
                'message' => "Access Denied (403): Your account role ('{$userRole}') does not have permission to perform this action."
            ]);
            exit;
        }

        // Render clean, branded 403 Forbidden page for all non-AJAX requests (GET and POST)
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="UTF-8" />
          <meta name="viewport" content="width=device-width, initial-scale=1.0" />
          <title>403 Access Denied | Mount Excel Boarding School</title>
          <link rel="icon" type="image/png" href="../images/favicon.png" />
          <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
          <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
          <link rel="stylesheet" href="../css/admin.css?v=<?= e(file_exists(__DIR__ . '/../../css/admin.css') ? filemtime(__DIR__ . '/../../css/admin.css') : time()) ?>" />
          <style>
            body { background-color: #0f1f4b; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; font-family: 'Inter', sans-serif; }
            .denied-card { background: #ffffff; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.3); max-width: 480px; width: 100%; padding: 40px 30px; text-align: center; }
          </style>
        </head>
        <body>
          <div class="denied-card">
            <div style="width: 60px; height: 60px; border-radius: 50%; background: #fef2f2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 20px;">
              <i class="fas fa-ban"></i>
            </div>
            <h2 style="font-family: 'Poppins', sans-serif; color: #0f172a; font-size: 1.4rem; margin-bottom: 12px;">
              403 - Access Denied
            </h2>
            <p style="font-size: 0.95rem; color: #64748b; margin-bottom: 20px; line-height: 1.6;">
              Your account role is <strong><?= strtoupper(htmlspecialchars($userRole)) ?></strong>. You do not have permission to view or manage this restricted resource.
            </p>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; font-size: 0.85rem; color: #475569; margin-bottom: 25px; text-align: left;">
              <p style="margin: 0;"><i class="fas fa-shield-alt" style="color: #1e3a8a;"></i> Required privilege: <code><?= htmlspecialchars($allowedStr) ?></code></p>
            </div>
            <a href="index.php" class="btn btn-primary" style="display: block; width: 100%; text-decoration: none; padding: 12px; background: #1e3a8a; color: #ffffff; border-radius: 6px; font-weight: 600; text-align: center;">
              <i class="fas fa-arrow-left"></i> Return to Staff Dashboard
            </a>
          </div>
        </body>
        </html>
        <?php
        exit;
    }
}
