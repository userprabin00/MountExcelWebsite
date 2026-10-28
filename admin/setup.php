<?php
/**
 * First-Time Admin Setup Wizard
 * Mount Excel Boarding School
 *
 * NOTE: For maximum security, this wizard permanently disables itself
 * as soon as an administrator account exists or the lockfile is created.
 * School administrators should delete this file after initializing their account.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

$setupToken = (string)env('SETUP_TOKEN', '');
$providedToken = (string)($_GET['token'] ?? '');
if (($setupToken === '' && APP_ENV === 'production') || ($setupToken !== '' && !hash_equals($setupToken, $providedToken))) {
    http_response_code(403);
    exit('Setup is unavailable.');
}

$lockFile = ROOT_PATH . '/database/.setup_completed';
$pdo = getDB();

// Check if any admin accounts already exist or lockfile is present
$adminCount = (int)$pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
$isLocked = ($adminCount > 0 || file_exists($lockFile));

if ($isLocked) {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <title>Setup Locked | Mount Excel Boarding School</title>
      <link rel="icon" type="image/png" href="../images/favicon.png" />
      <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
      <link rel="stylesheet" href="../css/style.css?v=<?= e(file_exists(__DIR__ . '/../css/style.css') ? filemtime(__DIR__ . '/../css/style.css') : time()) ?>" />
      <style>
        body { background-color: #0f1f4b; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
        .locked-card { background: #ffffff; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.3); max-width: 480px; width: 100%; padding: 40px 30px; text-align: center; }
      </style>
    </head>
    <body>
      <div class="locked-card">
        <div style="width: 60px; height: 60px; border-radius: 50%; background: #fef2f2; color: #dc2626; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 20px;">
          <i class="fas fa-lock"></i>
        </div>
        <h2 style="font-family: var(--font-heading); color: #0f172a; font-size: 1.4rem; margin-bottom: 12px;">
          Setup Permanently Locked
        </h2>
        <p style="font-size: 0.95rem; color: #64748b; margin-bottom: 25px; line-height: 1.6;">
          An administrator account is already configured. For security, re-running the setup wizard is permanently disabled.
        </p>
        <div style="background: #f8fafc; border: 1px solid var(--border-color); padding: 14px; border-radius: 8px; font-size: 0.85rem; color: #475569; margin-bottom: 25px; text-align: left;">
          <p><i class="fas fa-shield-alt" style="color: var(--primary-800);"></i> <strong>Security Advisory:</strong> If this website is in production, please remove or restrict <code>admin/setup.php</code>.</p>
        </div>
        <a href="login.php" class="btn btn-primary" style="width: 100%;">
          <i class="fas fa-sign-in-alt"></i> Proceed to Staff Login
        </a>
      </div>
    </body>
    </html>
    <?php
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Security verification failed. Please refresh and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (!preg_match('/^[a-zA-Z0-9_\.]{3,30}$/', $username)) {
            $error = 'Username must be 3 to 30 characters (letters, numbers, underscore, or dot).';
        } elseif (mb_strlen($fullName, 'UTF-8') < 2) {
            $error = 'Please provide the administrator\'s full name.';
        } elseif (!validateEmail($email)) {
            $error = 'Please provide a valid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $error = 'Password must include both letters and numbers for adequate strength.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            // Check if username or email already taken
            $checkStmt = $pdo->prepare("SELECT id FROM admins WHERE username = :u OR email = :e LIMIT 1");
            $checkStmt->execute([':u' => $username, ':e' => $email]);
            if ($checkStmt->fetch()) {
                $error = 'An account with this username or email already exists.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $insertStmt = $pdo->prepare("
                    INSERT INTO admins (username, email, password_hash, full_name, role, created_at)
                    VALUES (:username, :email, :hash, :name, 'super_admin', NOW())
                ");
                $insertStmt->execute([
                    ':username' => $username,
                    ':email'    => $email,
                    ':hash'     => $passwordHash,
                    ':name'     => $fullName
                ]);

                $newId = $pdo->lastInsertId();
                logActivity('ADMIN_INITIALIZED', "Primary administrator account created: {$username}", $newId);

                // Write lockfile to permanently lock setup
                @file_put_contents($lockFile, "SETUP_COMPLETED_AT=" . date('c') . "\nADMIN_ID={$newId}\n");

                // Self-delete setup script after first admin creation for production hardening
                @unlink(__FILE__);

                setFlash('success', "Administrator account for '{$username}' successfully initialized! Please log in.");
                header('Location: login.php');
                exit;
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
  <title>Initialize Staff Administrator | Mount Excel Boarding School</title>
  <link rel="icon" type="image/png" href="../images/favicon.png" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link rel="stylesheet" href="../css/style.css?v=<?= e(file_exists(__DIR__ . '/../css/style.css') ? filemtime(__DIR__ . '/../css/style.css') : time()) ?>" />
  <style>
    body { background-color: #0f1f4b; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 20px; }
    .setup-card { background: #ffffff; border-radius: 12px; box-shadow: 0 15px 35px rgba(0,0,0,0.3); max-width: 500px; width: 100%; padding: 35px; }
    .setup-logo { display: block; margin: 0 auto 15px; border-radius: 8px; }
  </style>
</head>
<body>
  <div class="setup-card">
    <img src="../images/logo.jpg" alt="Mount Excel Boarding School Logo" class="setup-logo" width="60" height="60" />
    <h2 style="font-family: var(--font-heading); color: var(--primary-800); text-align: center; font-size: 1.5rem; margin-bottom: 5px;">
      Staff Portal Setup
    </h2>
    <p style="text-align: center; font-size: 0.85rem; color: #64748b; margin-bottom: 25px;">
      Mount Excel Boarding School &bull; First Administrator Account
    </p>

    <?php if ($error): ?>
      <div class="alert alert-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="setup.php?token=<?= e($setupToken) ?>" novalidate>
      <?= csrfField() ?>

      <div class="form-group">
        <label for="full_name" class="form-label">Administrator Full Name <span class="required-star">*</span></label>
        <input type="text" id="full_name" name="full_name" class="form-control" placeholder="e.g. Principal Nabaraj Rai" required />
      </div>

      <div class="form-group">
        <label for="username" class="form-label">Username <span class="required-star">*</span></label>
        <input type="text" id="username" name="username" class="form-control" placeholder="e.g. mebs_principal" required />
        <span class="form-help-text">Letters, numbers, and underscores only (3-30 chars).</span>
      </div>

      <div class="form-group">
        <label for="email" class="form-label">Email Address <span class="required-star">*</span></label>
        <input type="email" id="email" name="email" class="form-control" placeholder="e.g. <?= e(SCHOOL_EMAIL) ?>" required />
      </div>

      <div class="form-group">
        <label for="password" class="form-label">Secure Password <span class="required-star">*</span></label>
        <input type="password" id="password" name="password" class="form-control" placeholder="Minimum 8 characters (letters & numbers)" required minlength="8" />
      </div>

      <div class="form-group">
        <label for="confirm_password" class="form-label">Confirm Password <span class="required-star">*</span></label>
        <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Repeat password" required minlength="8" />
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
        <i class="fas fa-user-shield"></i> Initialize Admin Account
      </button>
    </form>

    <p style="text-align: center; margin-top: 25px; font-size: 0.85rem;">
      <a href="../index.php" style="color: #64748b;"><i class="fas fa-arrow-left"></i> Return to School Website</a>
    </p>
  </div>
</body>
</html>
