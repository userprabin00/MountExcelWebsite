<?php
/**
 * Admin Staff Manager
 * Mount Excel Boarding School
 * Roles: admin, super_admin only
 */

ob_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/upload_image.php';
require_once __DIR__ . '/includes/auth.php';

requireRole(['admin', 'super_admin']);

$pdo         = getDB();
$currentUser = currentAdmin();

$staffUploadDir = ROOT_PATH . '/uploads/staff';

// ============================================================
// Handle post_max_size overflow (empty $_POST check)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST)) {
    $postMaxSize = ini_get('post_max_size');
    setFlash('error', "Upload failed: the total upload size exceeded the server limit ({$postMaxSize}). Please use a smaller photo.");
    header('Location: staff.php');
    ob_end_flush();
    exit;
}

// ============================================================
// POST: Add new staff member
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Security token invalid. Please try again.');
        header('Location: staff.php');
        ob_end_flush();
        exit;
    }

    $name       = trim($_POST['name'] ?? '');
    $position   = trim($_POST['position'] ?? '');
    $bio        = trim($_POST['bio'] ?? '');
    $sortOrder  = max(0, (int)($_POST['sort_order'] ?? 0));
    $isVisible  = isset($_POST['is_visible']) ? 1 : 0;
    $photoFilename = null;

    if (empty($name) || empty($position)) {
        setFlash('error', 'Name and Position are required fields.');
        header('Location: staff.php');
        ob_end_flush();
        exit;
    }

    // Handle photo upload (optional)
    if (!empty($_FILES['photo']['name'])) {
        try {
            $photoFilename = processUploadedImage($_FILES['photo'], $staffUploadDir);
        } catch (RuntimeException $e) {
            setFlash('error', 'Photo upload failed: ' . $e->getMessage());
            header('Location: staff.php');
            ob_end_flush();
            exit;
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO staff_members (name, position, bio, photo_filename, sort_order, is_visible, created_at, updated_at)
        VALUES (:name, :position, :bio, :photo, :sort_order, :is_visible, NOW(), NOW())
    ");
    $stmt->execute([
        ':name'       => $name,
        ':position'   => $position,
        ':bio'        => $bio ?: null,
        ':photo'      => $photoFilename,
        ':sort_order' => $sortOrder,
        ':is_visible' => $isVisible,
    ]);

    logActivity('STAFF_ADD', "Added staff member: {$name}", $currentUser['id']);
    setFlash('success', "Staff member '{$name}' added successfully.");
    header('Location: staff.php');
    ob_end_flush();
    exit;
}

// ============================================================
// POST: Edit staff member
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Security token invalid. Please try again.');
        header('Location: staff.php');
        ob_end_flush();
        exit;
    }

    $id        = (int)($_POST['id'] ?? 0);
    $name      = trim($_POST['name'] ?? '');
    $position  = trim($_POST['position'] ?? '');
    $bio       = trim($_POST['bio'] ?? '');
    $sortOrder = max(0, (int)($_POST['sort_order'] ?? 0));
    $isVisible = isset($_POST['is_visible']) ? 1 : 0;

    if (empty($name) || empty($position)) {
        setFlash('error', 'Name and Position are required fields.');
        header('Location: staff.php?edit=' . $id);
        ob_end_flush();
        exit;
    }

    // Fetch existing record
    $stmt = $pdo->prepare("SELECT * FROM staff_members WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        setFlash('error', 'Staff member not found.');
        header('Location: staff.php');
        ob_end_flush();
        exit;
    }

    $photoFilename = $existing['photo_filename'];

    // Optional photo replacement
    if (!empty($_FILES['photo']['name'])) {
        try {
            $photoFilename = processUploadedImage($_FILES['photo'], $staffUploadDir);
            if ($existing['photo_filename']) {
                deleteUploadedImage($existing['photo_filename'], 'staff');
            }
        } catch (RuntimeException $e) {
            setFlash('error', 'Photo replacement failed: ' . $e->getMessage());
            header('Location: staff.php?edit=' . $id);
            ob_end_flush();
            exit;
        }
    }

    $stmt = $pdo->prepare("
        UPDATE staff_members
        SET name = :name, position = :position, bio = :bio, photo_filename = :photo,
            sort_order = :sort_order, is_visible = :is_visible, updated_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        ':name'       => $name,
        ':position'   => $position,
        ':bio'        => $bio ?: null,
        ':photo'      => $photoFilename,
        ':sort_order' => $sortOrder,
        ':is_visible' => $isVisible,
        ':id'         => $id,
    ]);

    logActivity('STAFF_EDIT', "Edited staff member ID {$id}: {$name}", $currentUser['id']);
    setFlash('success', "Staff member '{$name}' updated successfully.");
    header('Location: staff.php');
    ob_end_flush();
    exit;
}

// ============================================================
// POST: Delete staff member
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'Security token invalid. Please try again.');
        header('Location: staff.php');
        ob_end_flush();
        exit;
    }

    $id   = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM staff_members WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        if ($existing['photo_filename']) {
            deleteUploadedImage($existing['photo_filename'], 'staff');
        }
        $pdo->prepare("DELETE FROM staff_members WHERE id = :id")->execute([':id' => $id]);
        logActivity('STAFF_DELETE', "Deleted staff member ID {$id}: {$existing['name']}", $currentUser['id']);
        setFlash('success', "Staff member '{$existing['name']}' deleted.");
    } else {
        setFlash('error', 'Staff member not found.');
    }

    header('Location: staff.php');
    ob_end_flush();
    exit;
}

// ============================================================
// GET: Load all staff for display
// ============================================================
$staffList = $pdo->query("SELECT * FROM staff_members ORDER BY sort_order ASC, id ASC")
                 ->fetchAll(PDO::FETCH_ASSOC);

// Edit mode
$editItem = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    foreach ($staffList as $row) {
        if ((int)$row['id'] === $editId) {
            $editItem = $row;
            break;
        }
    }
}

$adminTitle = 'Staff Manager';
$activeNav  = 'staff';
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="page-header-row">
  <div>
    <h1 class="page-title"><i class="fas fa-chalkboard-teacher"></i> Staff Manager</h1>
    <p class="page-subtitle">Add, edit and remove teacher and staff profiles shown on the public Staff page.</p>
  </div>
</div>

<!-- ============================================================
     ADD STAFF FORM
     ============================================================ -->
<?php if (!$editItem): ?>
<div class="card" style="margin-bottom: 30px;">
  <div class="card-header">
    <h3 class="card-title"><i class="fas fa-user-plus"></i> Add New Staff Member</h3>
  </div>
  <div class="card-body">
    <form method="POST" action="staff.php" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="add">

      <div class="form-grid-2">
        <div class="form-group">
          <label for="add_name" class="form-label">Full Name <span class="text-danger">*</span></label>
          <input type="text" name="name" id="add_name" class="form-control" maxlength="120" required>
        </div>
        <div class="form-group">
          <label for="add_position" class="form-label">Position / Title <span class="text-danger">*</span></label>
          <input type="text" name="position" id="add_position" class="form-control" maxlength="120" required
                 placeholder="e.g. Mathematics Teacher, Principal">
        </div>
      </div>

      <div class="form-group">
        <label for="add_bio" class="form-label">Short Bio</label>
        <textarea name="bio" id="add_bio" class="form-control" rows="3"
                  placeholder="Brief description, qualifications, years of experience…"></textarea>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label for="add_photo" class="form-label">Photo (optional)</label>
          <input type="file" name="photo" id="add_photo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
          <small class="form-hint">JPG, PNG or WebP · Max 8 MB</small>
        </div>
        <div class="form-group">
          <label for="add_sort" class="form-label">Sort Order</label>
          <input type="number" name="sort_order" id="add_sort" class="form-control" min="0" max="9999" value="0">
          <small class="form-hint">Lower numbers appear first.</small>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">
          <input type="checkbox" name="is_visible" value="1" checked>
          Visible on public website
        </label>
      </div>

      <button type="submit" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Staff Member
      </button>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- ============================================================
     EDIT FORM
     ============================================================ -->
<?php if ($editItem): ?>
<div class="card" style="margin-bottom: 30px; border: 2px solid var(--primary-500);">
  <div class="card-header" style="background: var(--primary-50);">
    <h3 class="card-title"><i class="fas fa-user-edit"></i> Edit Staff Member</h3>
    <a href="staff.php" class="btn btn-sm btn-outline">Cancel</a>
  </div>
  <div class="card-body">
    <form method="POST" action="staff.php" enctype="multipart/form-data">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" value="<?= (int)$editItem['id'] ?>">

      <?php if ($editItem['photo_filename']): ?>
      <div style="display: flex; gap: 20px; align-items: flex-start; margin-bottom: 20px;">
        <img src="../uploads/staff/<?= e($editItem['photo_filename']) ?>"
             alt="Current photo" style="width:100px;height:120px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;">
        <div style="flex:1;">
          <label class="form-label">Replace Photo (optional)</label>
          <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
          <small class="form-hint">Leave blank to keep the current photo.</small>
        </div>
      </div>
      <?php else: ?>
      <div class="form-group">
        <label for="edit_photo" class="form-label">Photo (optional)</label>
        <input type="file" name="photo" id="edit_photo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
      </div>
      <?php endif; ?>

      <div class="form-grid-2">
        <div class="form-group">
          <label for="edit_name" class="form-label">Full Name <span class="text-danger">*</span></label>
          <input type="text" name="name" id="edit_name" class="form-control" maxlength="120"
                 value="<?= e($editItem['name']) ?>" required>
        </div>
        <div class="form-group">
          <label for="edit_position" class="form-label">Position / Title <span class="text-danger">*</span></label>
          <input type="text" name="position" id="edit_position" class="form-control" maxlength="120"
                 value="<?= e($editItem['position']) ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label for="edit_bio" class="form-label">Short Bio</label>
        <textarea name="bio" id="edit_bio" class="form-control" rows="3"><?= e($editItem['bio'] ?? '') ?></textarea>
      </div>

      <div class="form-grid-2">
        <div class="form-group">
          <label for="edit_sort" class="form-label">Sort Order</label>
          <input type="number" name="sort_order" id="edit_sort" class="form-control" min="0" max="9999"
                 value="<?= (int)$editItem['sort_order'] ?>">
        </div>
        <div class="form-group" style="padding-top: 28px;">
          <label class="form-label">
            <input type="checkbox" name="is_visible" value="1" <?= $editItem['is_visible'] ? 'checked' : '' ?>>
            Visible on public website
          </label>
        </div>
      </div>

      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
      <a href="staff.php" class="btn btn-outline" style="margin-left:8px;">Cancel</a>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- ============================================================
     STAFF LIST TABLE
     ============================================================ -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">
      <i class="fas fa-users"></i> All Staff Members
      <span class="badge" style="background:#e2e8f0;color:#475569;font-size:.75rem;padding:2px 8px;border-radius:12px;margin-left:8px;">
        <?= count($staffList) ?>
      </span>
    </h3>
  </div>

  <?php if (empty($staffList)): ?>
  <div class="card-body" style="color:var(--text-muted);font-style:italic;">No staff members added yet.</div>
  <?php else: ?>
  <div style="overflow-x: auto;">
    <table class="data-table">
      <thead>
        <tr>
          <th style="width:80px;">Photo</th>
          <th>Name</th>
          <th>Position</th>
          <th style="width:200px;">Bio</th>
          <th style="width:70px;">Order</th>
          <th style="width:80px;">Visible</th>
          <th style="width:120px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($staffList as $row): ?>
        <tr>
          <td>
            <?php if ($row['photo_filename']): ?>
              <img src="../uploads/staff/<?= e($row['photo_filename']) ?>"
                   alt="<?= e($row['name']) ?>"
                   style="width:60px;height:70px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0;">
            <?php else: ?>
              <div style="width:60px;height:70px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#94a3b8;">
                <i class="fas fa-user" style="font-size:1.5rem;"></i>
              </div>
            <?php endif; ?>
          </td>
          <td><strong><?= e($row['name']) ?></strong></td>
          <td><?= e($row['position']) ?></td>
          <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:.85rem;color:var(--text-muted);">
            <?= e($row['bio'] ?? '—') ?>
          </td>
          <td><?= (int)$row['sort_order'] ?></td>
          <td>
            <?php if ($row['is_visible']): ?>
              <span class="badge badge-success"><i class="fas fa-eye"></i> Yes</span>
            <?php else: ?>
              <span class="badge badge-secondary"><i class="fas fa-eye-slash"></i> Hidden</span>
            <?php endif; ?>
          </td>
          <td>
            <a href="staff.php?edit=<?= (int)$row['id'] ?>"
               class="btn btn-sm btn-outline" title="Edit">
              <i class="fas fa-edit"></i>
            </a>
            <form method="POST" action="staff.php" style="display:inline;"
                  onsubmit="return confirm('Delete <?= e(addslashes($row['name'])) ?>? This cannot be undone.');">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
              <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                <i class="fas fa-trash"></i>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
