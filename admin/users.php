<?php
/**
 * Administrator & Staff User Management (Super Admin Only)
 * Mount Excel Boarding School
 */

$adminTitle = 'User Management';
$activeNav = 'users';
require_once __DIR__ . '/includes/admin_header.php';

// Strict Super Admin Access Control
requireRole('super_admin');

$pdo = getDB();

// Handle User Management Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Security verification expired. Please try again.');
        header('Location: users.php');
        exit;
    }

    $action = trim($_POST['action'] ?? '');

    // 1. Create New Administrator / Staff Account
    if ($action === 'create') {
        $username   = trim($_POST['username'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $fullName   = trim($_POST['full_name'] ?? '');
        $role       = trim($_POST['role'] ?? 'staff');
        $password   = $_POST['password'] ?? '';
        $confirmPw  = $_POST['confirm_password'] ?? '';

        $errors = [];

        if (empty($username) || !preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
            $errors[] = 'Username must be between 3 and 30 characters containing only letters, numbers, and underscores.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid email address.';
        }

        if (mb_strlen($fullName, 'UTF-8') < 2) {
            $errors[] = 'Full name must be at least 2 characters.';
        }

        if (!in_array($role, ['super_admin', 'admin', 'staff'], true)) {
            $errors[] = 'Invalid role selected.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }

        if ($password !== $confirmPw) {
            $errors[] = 'Password and confirmation password do not match.';
        }

        // Check uniqueness
        if (empty($errors)) {
            $checkStmt = $pdo->prepare("SELECT id FROM admins WHERE username = :u OR email = :e LIMIT 1");
            $checkStmt->execute([':u' => $username, ':e' => $email]);
            if ($checkStmt->fetch()) {
                $errors[] = 'A user with this username or email already exists.';
            }
        }

        if (!empty($errors)) {
            foreach ($errors as $err) {
                setFlash('error', $err);
            }
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO admins (username, email, password_hash, full_name, role, is_active, created_at)
                VALUES (:u, :e, :h, :n, :r, 1, NOW())
            ");
            $stmt->execute([
                ':u' => $username,
                ':e' => $email,
                ':h' => $hash,
                ':n' => $fullName,
                ':r' => $role
            ]);
            $newId = (int)$pdo->lastInsertId();
            logActivity('USER_CREATED', "Created user '{$username}' with role '{$role}'", $currentUser['id']);
            setFlash('success', "Account for '{$fullName}' ({$username}) successfully created.");
        }
        header('Location: users.php');
        exit;
    }

    // 2. Change User Role
    if ($action === 'change_role') {
        $userId  = (int)($_POST['user_id'] ?? 0);
        $newRole = trim($_POST['new_role'] ?? '');

        if ($userId <= 0 || !in_array($newRole, ['super_admin', 'admin', 'staff'], true)) {
            setFlash('error', 'Invalid role update request.');
            header('Location: users.php');
            exit;
        }

        // Fetch target user
        $targetStmt = $pdo->prepare("SELECT id, username, role, is_active FROM admins WHERE id = :id LIMIT 1");
        $targetStmt->execute([':id' => $userId]);
        $target = $targetStmt->fetch();

        if (!$target) {
            setFlash('error', 'User not found.');
            header('Location: users.php');
            exit;
        }

        // Guard: Prevent demoting the last active super_admin
        if ($target['role'] === 'super_admin' && $newRole !== 'super_admin') {
            $superCount = (int)$pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'super_admin' AND is_active = 1")->fetchColumn();
            if ($superCount <= 1) {
                setFlash('error', 'Action rejected: cannot demote the only remaining active super administrator.');
                header('Location: users.php');
                exit;
            }
        }

        $upStmt = $pdo->prepare("UPDATE admins SET role = :r WHERE id = :id");
        $upStmt->execute([':r' => $newRole, ':id' => $userId]);
        logActivity('USER_ROLE_CHANGED', "Changed user #{$userId} ({$target['username']}) role from {$target['role']} to {$newRole}", $currentUser['id']);
        setFlash('success', "Role for user '{$target['username']}' updated to " . ucfirst(str_replace('_', ' ', $newRole)) . ".");
        header('Location: users.php');
        exit;
    }

    // 3. Toggle Active / Deactivated Status
    if ($action === 'toggle_active') {
        $userId    = (int)($_POST['user_id'] ?? 0);
        $setActive = (int)($_POST['is_active'] ?? 0) === 1 ? 1 : 0;

        $targetStmt = $pdo->prepare("SELECT id, username, role, is_active FROM admins WHERE id = :id LIMIT 1");
        $targetStmt->execute([':id' => $userId]);
        $target = $targetStmt->fetch();

        if (!$target) {
            setFlash('error', 'User not found.');
            header('Location: users.php');
            exit;
        }

        // Guard: Cannot deactivate yourself
        if ($userId === (int)$currentUser['id'] && $setActive === 0) {
            setFlash('error', 'Action rejected: you cannot deactivate your own active session account.');
            header('Location: users.php');
            exit;
        }

        // Guard: Prevent deactivating the last active super_admin
        if ($target['role'] === 'super_admin' && $setActive === 0) {
            $superCount = (int)$pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'super_admin' AND is_active = 1")->fetchColumn();
            if ($superCount <= 1) {
                setFlash('error', 'Action rejected: cannot deactivate the only remaining active super administrator.');
                header('Location: users.php');
                exit;
            }
        }

        $upStmt = $pdo->prepare("UPDATE admins SET is_active = :a WHERE id = :id");
        $upStmt->execute([':a' => $setActive, ':id' => $userId]);
        $statusStr = ($setActive === 1) ? 'activated' : 'deactivated';
        logActivity('USER_STATUS_CHANGED', "User #{$userId} ({$target['username']}) was {$statusStr}", $currentUser['id']);
        setFlash('success', "User '{$target['username']}' has been successfully {$statusStr}.");
        header('Location: users.php');
        exit;
    }

    // 4. Reset User Password
    if ($action === 'reset_password') {
        $userId     = (int)($_POST['user_id'] ?? 0);
        $newPw      = $_POST['new_password'] ?? '';
        $confirmPw  = $_POST['confirm_password'] ?? '';

        if (strlen($newPw) < 8) {
            setFlash('error', 'Password must be at least 8 characters long.');
            header('Location: users.php');
            exit;
        }

        if ($newPw !== $confirmPw) {
            setFlash('error', 'Password and confirmation password do not match.');
            header('Location: users.php');
            exit;
        }

        $targetStmt = $pdo->prepare("SELECT id, username FROM admins WHERE id = :id LIMIT 1");
        $targetStmt->execute([':id' => $userId]);
        $target = $targetStmt->fetch();

        if (!$target) {
            setFlash('error', 'User not found.');
            header('Location: users.php');
            exit;
        }

        $hash = password_hash($newPw, PASSWORD_DEFAULT);
        $upStmt = $pdo->prepare("UPDATE admins SET password_hash = :h, failed_logins = 0, lockout_until = NULL WHERE id = :id");
        $upStmt->execute([':h' => $hash, ':id' => $userId]);
        logActivity('USER_PASSWORD_RESET', "Reset password for user #{$userId} ({$target['username']})", $currentUser['id']);
        setFlash('success', "Password for user '{$target['username']}' successfully reset.");
        header('Location: users.php');
        exit;
    }

    // 5. Delete User Account
    if ($action === 'delete') {
        $userId = (int)($_POST['user_id'] ?? 0);

        if ($userId === (int)$currentUser['id']) {
            setFlash('error', 'Action rejected: you cannot delete your own account.');
            header('Location: users.php');
            exit;
        }

        $targetStmt = $pdo->prepare("SELECT id, username, role FROM admins WHERE id = :id LIMIT 1");
        $targetStmt->execute([':id' => $userId]);
        $target = $targetStmt->fetch();

        if (!$target) {
            setFlash('error', 'User not found.');
            header('Location: users.php');
            exit;
        }

        // Guard: Prevent deleting the last super_admin
        if ($target['role'] === 'super_admin') {
            $superCount = (int)$pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'super_admin' AND is_active = 1")->fetchColumn();
            if ($superCount <= 1) {
                setFlash('error', 'Action rejected: cannot delete the only remaining super administrator.');
                header('Location: users.php');
                exit;
            }
        }

        $delStmt = $pdo->prepare("DELETE FROM admins WHERE id = :id");
        $delStmt->execute([':id' => $userId]);
        logActivity('USER_DELETED', "Deleted admin user #{$userId} ({$target['username']})", $currentUser['id']);
        setFlash('success', "User '{$target['username']}' successfully deleted.");
        header('Location: users.php');
        exit;
    }
}

// Fetch all administrators
$usersStmt = $pdo->query("SELECT id, username, email, full_name, role, is_active, last_login, created_at FROM admins ORDER BY id ASC");
$users = $usersStmt->fetchAll();
$totalSuperAdmins = (int)$pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'super_admin' AND is_active = 1")->fetchColumn();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
  <div>
    <h3 style="margin: 0; color: #0f1f4b; font-family: var(--font-heading); font-size: 1.4rem;">
      <i class="fas fa-users-cog" style="color: #ea580c;"></i> System Administrators & Staff
    </h3>
    <p style="margin: 4px 0 0; color: #64748b; font-size: 0.9rem;">
      Manage portal user accounts, grant privileges, reset passwords, and toggle access status.
    </p>
  </div>
  <div>
    <button type="button" class="btn btn-sm btn-accent" onclick="document.getElementById('createUserCard').style.display = (document.getElementById('createUserCard').style.display === 'none' ? 'block' : 'none');">
      <i class="fas fa-user-plus"></i> Add New User
    </button>
  </div>
</div>

<!-- Add User Card (Collapsible) -->
<div class="admin-card" id="createUserCard" style="display: none; border: 2px solid #ea580c; margin-bottom: 25px;">
  <div class="admin-card-header" style="background: #fff7ed;">
    <h3 class="admin-card-title" style="color: #9a3412;"><i class="fas fa-user-plus"></i> Create New Portal Account</h3>
    <button type="button" class="btn btn-sm btn-outline-white" style="color: #64748b;" onclick="document.getElementById('createUserCard').style.display='none';">&times; Close</button>
  </div>
  <div class="admin-card-body">
    <form method="POST" action="users.php">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="create" />

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; margin-bottom: 18px;">
        <div class="form-group" style="margin: 0;">
          <label for="username" class="form-label">Username <span class="required-star">*</span></label>
          <input type="text" id="username" name="username" class="form-control" placeholder="e.g. jshrestha" required minlength="3" maxlength="30" />
        </div>

        <div class="form-group" style="margin: 0;">
          <label for="email" class="form-label">Email Address <span class="required-star">*</span></label>
          <input type="email" id="email" name="email" class="form-control" placeholder="e.g. staff@mountexcel.edu.np" required />
        </div>

        <div class="form-group" style="margin: 0;">
          <label for="full_name" class="form-label">Full Name <span class="required-star">*</span></label>
          <input type="text" id="full_name" name="full_name" class="form-control" placeholder="e.g. Januka Shrestha" required />
        </div>

        <div class="form-group" style="margin: 0;">
          <label for="role" class="form-label">System Role <span class="required-star">*</span></label>
          <select id="role" name="role" class="form-control" required>
            <option value="staff">Staff (View admissions & messages only)</option>
            <option value="admin">Administrator (Change status, add notes, CSV export)</option>
            <option value="super_admin">Super Administrator (Full access & user management)</option>
          </select>
        </div>

        <div class="form-group" style="margin: 0;">
          <label for="password" class="form-label">Initial Password <span class="required-star">*</span></label>
          <input type="password" id="password" name="password" class="form-control" placeholder="Min 8 characters" required minlength="8" />
        </div>

        <div class="form-group" style="margin: 0;">
          <label for="confirm_password" class="form-label">Confirm Password <span class="required-star">*</span></label>
          <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter password" required minlength="8" />
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; gap: 12px;">
        <button type="button" class="btn btn-sm btn-outline-white" style="color: #64748b;" onclick="document.getElementById('createUserCard').style.display='none';">Cancel</button>
        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-check"></i> Create Account</button>
      </div>
    </form>
  </div>
</div>

<!-- Users List Table -->
<div class="admin-card">
  <div class="admin-card-header">
    <h3 class="admin-card-title"><i class="fas fa-users"></i> Registered Portal Accounts (<?= count($users) ?>)</h3>
  </div>
  <div class="admin-card-body" style="padding: 0;">
    <div class="table-responsive">
      <table class="admin-table" aria-label="Portal user accounts">
        <thead>
          <tr>
            <th>User</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Last Login</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td>
                <div style="display: flex; align-items: center; gap: 12px;">
                  <div style="width: 36px; height: 36px; border-radius: 50%; background: #eff6ff; color: #1e3a8a; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.95rem;">
                    <?= strtoupper(substr($u['username'], 0, 1)) ?>
                  </div>
                  <div>
                    <div style="font-weight: 600; color: #0f1f4b;"><?= e($u['full_name']) ?></div>
                    <div style="font-size: 0.8rem; color: #64748b;">@<?= e($u['username']) ?> <?php if ($u['id'] == $currentUser['id']): ?><span style="color: #ea580c; font-weight: bold;">(You)</span><?php endif; ?></div>
                  </div>
                </div>
              </td>
              <td><?= e($u['email']) ?></td>
              <td>
                <?php if ($u['role'] === 'super_admin'): ?>
                  <span style="background: #f3e8ff; color: #7e22ce; padding: 4px 10px; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Super Admin</span>
                <?php elseif ($u['role'] === 'admin'): ?>
                  <span style="background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Admin</span>
                <?php else: ?>
                  <span style="background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">Staff</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ((int)$u['is_active'] === 1): ?>
                  <span style="background: #ecfdf5; color: #047857; padding: 4px 10px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">Active</span>
                <?php else: ?>
                  <span style="background: #fef2f2; color: #b91c1c; padding: 4px 10px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">Deactivated</span>
                <?php endif; ?>
              </td>
              <td style="font-size: 0.85rem; color: #64748b;">
                <?= !empty($u['last_login']) ? date('M d, Y h:i A', strtotime($u['last_login'])) : '<em style="color: #94a3b8;">Never</em>' ?>
              </td>
              <td>
                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                  <!-- Change Role Form -->
                  <form method="POST" action="users.php" style="display: inline-block; margin: 0;">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="change_role" />
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>" />
                    <select name="new_role" onchange="this.form.submit()" style="font-size: 0.8rem; padding: 4px 8px; border-radius: 4px; border: 1px solid var(--admin-border);" <?= ($u['role'] === 'super_admin' && $totalSuperAdmins <= 1 && (int)$u['is_active'] === 1) ? 'title="Only super administrator"' : '' ?>>
                      <option value="staff" <?= ($u['role'] === 'staff') ? 'selected' : '' ?>>Staff</option>
                      <option value="admin" <?= ($u['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                      <option value="super_admin" <?= ($u['role'] === 'super_admin') ? 'selected' : '' ?>>Super Admin</option>
                    </select>
                  </form>

                  <!-- Toggle Active / Deactivate Form -->
                  <form method="POST" action="users.php" style="display: inline-block; margin: 0;">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="toggle_active" />
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>" />
                    <input type="hidden" name="is_active" value="<?= ((int)$u['is_active'] === 1) ? '0' : '1' ?>" />
                    <?php if ((int)$u['is_active'] === 1): ?>
                      <button type="submit" class="action-btn" style="color: #dc2626; border-color: #fecaca; background: #fff5f5; padding: 4px 8px; font-size: 0.78rem;" title="Deactivate user" <?= ($u['id'] == $currentUser['id'] || ($u['role'] === 'super_admin' && $totalSuperAdmins <= 1)) ? 'disabled style="opacity: 0.4; cursor: not-allowed;"' : 'onclick="return confirm(\'Deactivate this user account? They will no longer be able to log in.\')"' ?>>
                        <i class="fas fa-user-slash"></i> Deactivate
                      </button>
                    <?php else: ?>
                      <button type="submit" class="action-btn" style="color: #047857; border-color: #a7f3d0; background: #f0fdf4; padding: 4px 8px; font-size: 0.78rem;" title="Activate user">
                        <i class="fas fa-user-check"></i> Activate
                      </button>
                    <?php endif; ?>
                  </form>

                  <!-- Reset Password Modal Trigger -->
                  <button type="button" class="action-btn btn-view" style="padding: 4px 8px; font-size: 0.78rem;" onclick="openResetModal(<?= $u['id'] ?>, '<?= e($u['username']) ?>')">
                    <i class="fas fa-key"></i> Reset PW
                  </button>

                  <!-- Delete User -->
                  <?php if ($u['id'] != $currentUser['id'] && !($u['role'] === 'super_admin' && $totalSuperAdmins <= 1)): ?>
                    <form method="POST" action="users.php" style="display: inline-block; margin: 0;" onsubmit="return confirm('Are you sure you want to permanently delete user <?= e($u['username']) ?>?');">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="delete" />
                      <input type="hidden" name="user_id" value="<?= $u['id'] ?>" />
                      <button type="submit" class="action-btn" style="color: #dc2626; border-color: #fecaca; background: transparent; padding: 4px 8px; font-size: 0.78rem;" title="Delete account">
                        <i class="fas fa-trash-alt"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Password Reset Modal -->
<div id="resetPwModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 31, 75, 0.6); z-index: 2000; align-items: center; justify-content: center; padding: 20px;">
  <div class="admin-card" style="max-width: 420px; width: 100%; margin: 0 auto; box-shadow: 0 20px 40px rgba(0,0,0,0.3);">
    <div class="admin-card-header">
      <h3 class="admin-card-title"><i class="fas fa-key"></i> Reset Password</h3>
      <button type="button" class="btn btn-sm btn-outline-white" style="color: #64748b;" onclick="closeResetModal()">&times;</button>
    </div>
    <div class="admin-card-body">
      <p style="font-size: 0.9rem; color: #475569; margin-bottom: 15px;">
        Setting a new password for <strong id="resetTargetUsername"></strong>:
      </p>
      <form method="POST" action="users.php">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="reset_password" />
        <input type="hidden" name="user_id" id="resetUserId" value="" />

        <div class="form-group">
          <label for="new_password" class="form-label">New Password (min 8 chars) <span class="required-star">*</span></label>
          <input type="password" id="new_password" name="new_password" class="form-control" required minlength="8" />
        </div>

        <div class="form-group">
          <label for="confirm_pw" class="form-label">Confirm New Password <span class="required-star">*</span></label>
          <input type="password" id="confirm_pw" name="confirm_password" class="form-control" required minlength="8" />
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
          <button type="button" class="btn btn-sm btn-outline-white" style="color: #64748b;" onclick="closeResetModal()">Cancel</button>
          <button type="submit" class="btn btn-sm btn-primary">Save New Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openResetModal(userId, username) {
  document.getElementById('resetUserId').value = userId;
  document.getElementById('resetTargetUsername').textContent = '@' + username;
  document.getElementById('resetPwModal').style.display = 'flex';
}
function closeResetModal() {
  document.getElementById('resetPwModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
