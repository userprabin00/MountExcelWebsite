<?php
$adminTitle = 'Account Security';
$activeNav = 'security';
require_once __DIR__ . '/includes/admin_header.php';

$pdo = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'CSRF security token invalid or expired.';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Fetch current user hash
        $stmt = $pdo->prepare("SELECT password_hash FROM admins WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $currentUser['id']]);
        $userRecord = $stmt->fetch();

        if (!$userRecord || !password_verify($currentPassword, $userRecord['password_hash'])) {
            $error = 'Your current password was entered incorrectly.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'New password must be at least 8 characters long.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New password and confirmation do not match.';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $upStmt = $pdo->prepare("UPDATE admins SET password_hash = :h, updated_at = NOW() WHERE id = :id");
            $upStmt->execute([':h' => $newHash, ':id' => $currentUser['id']]);
            session_regenerate_id(true);

            logActivity('PASSWORD_CHANGED', "Staff {$currentUser['username']} updated their password", $currentUser['id']);
            setFlash('success', 'Your password has been successfully updated.');
            header('Location: change_password.php');
            exit;
        }
    }
}
?>

<div style="max-width: 600px; margin: 0 auto;">
  <div class="admin-card">
    <div class="admin-card-header">
      <h3 class="admin-card-title"><i class="fas fa-lock"></i> Update Account Password</h3>
    </div>

    <div class="admin-card-body">
      <?php if ($error): ?>
        <div class="alert alert-error" role="alert"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="change_password.php" novalidate>
        <?= csrfField() ?>

        <div class="form-group">
          <label for="current_password" class="form-label">Current Password <span class="required-star">*</span></label>
          <input type="password" id="current_password" name="current_password" class="form-control" required />
        </div>

        <div class="form-group">
          <label for="new_password" class="form-label">New Password <span class="required-star">*</span></label>
          <input type="password" id="new_password" name="new_password" class="form-control" required minlength="8" />
          <span class="form-help-text">Minimum 8 characters. Use a combination of letters and numbers.</span>
        </div>

        <div class="form-group">
          <label for="confirm_password" class="form-label">Confirm New Password <span class="required-star">*</span></label>
          <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="8" />
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
          <i class="fas fa-key"></i> Update Password
        </button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
