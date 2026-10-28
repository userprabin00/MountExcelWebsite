<?php
/**
 * Administrator Login
 * Mount Excel Boarding School
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect directly to dashboard
if (isAuthenticated()) {
    header('Location: index.php');
    exit;
}

$error = '';
$pdo = getDB();
$checkLoginRateLimit = function() {
    if (!checkRateLimit('admin_login_ip', 6, 300)) {
        return 'Too many failed login attempts from this connection. Please wait 5 minutes.';
    }
    if (!checkDatabaseRateLimit('admin_login', 20, 15)) {
        return 'Too many failed login attempts from this connection. Please wait 15 minutes.';
    }
    return '';
};

// Check if first-time setup is needed (no admins in DB)
$adminCount = (int)$pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
if ($adminCount === 0) {
    header('Location: setup.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            // Check admin record
            $stmt = $pdo->prepare("
                SELECT id, username, email, password_hash, full_name, role, is_active, failed_logins, lockout_until 
                FROM admins 
                WHERE username = :u1 OR email = :u2 
                LIMIT 1
            ");
            $stmt->execute([':u1' => $username, ':u2' => $username]);
            $admin = $stmt->fetch();

            if ($admin) {
                // Check if account is deactivated
                if (isset($admin['is_active']) && (int)$admin['is_active'] === 0) {
                    $rateLimitError = $checkLoginRateLimit();
                    $error = $rateLimitError !== '' ? $rateLimitError : 'Your account has been deactivated. Please contact a super administrator.';
                    logActivity('LOGIN_BLOCKED_INACTIVE', "Deactivated account login attempt: {$username}", $admin['id']);
                } elseif (password_verify($password, $admin['password_hash'])) {
                    // Password correct!
                    // Check if password hash needs rehash
                    if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $upHash = $pdo->prepare("UPDATE admins SET password_hash = :h WHERE id = :id");
                        $upHash->execute([':h' => $newHash, ':id' => $admin['id']]);
                    }

                    // Reset failed logins, update last login
                    $resetStmt = $pdo->prepare("
                        UPDATE admins 
                        SET failed_logins = 0, lockout_until = NULL, last_login = NOW() 
                        WHERE id = :id
                    ");
                    $resetStmt->execute([':id' => $admin['id']]);

                    // Clear previous failed-attempt counters after successful authentication.
                    unset($_SESSION['rate_limits']['admin_login_ip']);
                    try {
                        $clearIpLimit = $pdo->prepare("DELETE FROM rate_limits WHERE ip = :ip AND action = 'admin_login'");
                        $clearIpLimit->execute([':ip' => getClientIp()]);
                    } catch (Exception $e) {
                        error_log('Could not clear admin login rate-limit records: ' . $e->getMessage());
                    }

                    // Prevent session fixation
                    session_regenerate_id(true);

                    // Set session variables
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id']        = $admin['id'];
                    $_SESSION['admin_username']  = $admin['username'];
                    $_SESSION['admin_name']      = $admin['full_name'];
                    $_SESSION['admin_email']     = $admin['email'];
                    $_SESSION['admin_role']      = $admin['role'];
                    $_SESSION['last_activity']   = time();

                    logActivity('LOGIN_SUCCESS', "Admin logged in successfully: {$admin['username']}", $admin['id']);

                    $target = $_SESSION['intended_url'] ?? 'index.php';
                    unset($_SESSION['intended_url']);
                    header('Location: ' . $target);
                    exit;
                } else {
                    // Password incorrect
                    $rateLimitError = $checkLoginRateLimit();
                    if ($rateLimitError !== '') {
                        $error = $rateLimitError;
                    } elseif (!empty($admin['lockout_until']) && strtotime($admin['lockout_until']) > time()) {
                        $minutesLeft = ceil((strtotime($admin['lockout_until']) - time()) / 60);
                        $error = "This account is temporarily locked due to repeated failed logins. Please try again in {$minutesLeft} minute(s).";
                    } else {
                        $newFails = $admin['failed_logins'] + 1;
                        if ($newFails >= 5) {
                            $lockStmt = $pdo->prepare("
                                UPDATE admins 
                                SET failed_logins = :f, lockout_until = NOW() + INTERVAL 15 MINUTE 
                                WHERE id = :id
                            ");
                            $lockStmt->execute([':f' => $newFails, ':id' => $admin['id']]);
                            $error = 'Too many failed login attempts. Account temporarily locked for 15 minutes.';
                        } else {
                            $failStmt = $pdo->prepare("UPDATE admins SET failed_logins = :f WHERE id = :id");
                            $failStmt->execute([':f' => $newFails, ':id' => $admin['id']]);
                            $remaining = 5 - $newFails;
                            $error = "Invalid username or password. ({$remaining} attempts remaining before temporary lockout).";
                        }
                    }
                    logActivity('LOGIN_FAILED', "Failed login attempt for admin: {$username}");
                }
            } else {
                // User not found - use constant time failure feedback
                $rateLimitError = $checkLoginRateLimit();
                $error = $rateLimitError !== '' ? $rateLimitError : 'Invalid username or password.';
                logActivity('LOGIN_FAILED', "Failed login attempt for non-existent admin: {$username}");
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Portal Login | Mount Excel Boarding School</title>
  <link rel="icon" type="image/png" href="../images/favicon.png" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link rel="stylesheet" href="../css/style.css?v=<?= e(file_exists(__DIR__ . '/../css/style.css') ? filemtime(__DIR__ . '/../css/style.css') : time()) ?>" />
  <style>
    body { background-color: #0f1f4b; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
    .login-card { background: #ffffff; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.3); max-width: 440px; width: 100%; padding: 40px 35px; }
    .login-logo { display: block; margin: 0 auto 15px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
  </style>
</head>
<body>
  <div class="login-card">
    <img src="../images/logo.jpg" alt="Mount Excel Boarding School Logo" class="login-logo" width="65" height="65" />
    <h2 style="font-family: var(--font-heading); color: var(--primary-800); text-align: center; font-size: 1.5rem; margin-bottom: 5px;">
      Staff Portal Sign In
    </h2>
    <p style="text-align: center; font-size: 0.85rem; color: #64748b; margin-bottom: 25px;">
      Mount Excel Boarding School Administration
    </p>

    <?= renderFlashMessages() ?>

    <?php if ($error): ?>
      <div class="alert alert-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php" novalidate>
      <?= csrfField() ?>

      <div class="form-group">
        <label for="username" class="form-label">Username or Email</label>
        <input type="text" id="username" name="username" class="form-control" placeholder="Enter username or email" required autofocus />
      </div>

      <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required />
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 15px;">
        <i class="fas fa-sign-in-alt"></i> Sign In to Portal
      </button>
    </form>

    <div style="border-top: 1px solid var(--border-color); margin-top: 30px; padding-top: 20px; text-align: center; font-size: 0.85rem;">
      <a href="../index.php" style="color: #64748b;"><i class="fas fa-arrow-left"></i> Return to Public Website</a>
    </div>
  </div>
</body>
</html>
