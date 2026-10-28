<?php
$adminTitle = 'Application Details';
$activeNav = 'admissions';
require_once __DIR__ . '/includes/admin_header.php';

$pdo = getDB();
$appId = (int)($_GET['id'] ?? 0);

if ($appId <= 0) {
    setFlash('error', 'Invalid application ID.');
    header('Location: admissions.php');
    exit;
}

// Fetch record
$stmt = $pdo->prepare("SELECT * FROM admissions WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $appId]);
$app = $stmt->fetch();

if (!$app) {
    setFlash('error', 'Admission application not found.');
    header('Location: admissions.php');
    exit;
}

// Handle Status & Internal Staff Notes Update (admin & super_admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireRole(['admin', 'super_admin']);

    if (!verifyCsrfToken()) {
        setFlash('error', 'CSRF verification failed.');
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $deleteStmt = $pdo->prepare("DELETE FROM admissions WHERE id = :id");
        $deleteStmt->execute([':id' => $appId]);
        logActivity('ADMISSION_DELETED', "Deleted application {$app['application_no']}", $currentUser['id']);
        setFlash('success', 'Admission application deleted.');
        header('Location: admissions.php');
        exit;
    } else {
        $newStatus = trim($_POST['status'] ?? '');
        $adminNotes = trim($_POST['admin_notes'] ?? '');
        $validStatuses = ['pending', 'reviewed', 'accepted', 'rejected'];

        if (in_array($newStatus, $validStatuses, true)) {
            $upStmt = $pdo->prepare("
                UPDATE admissions 
                SET status = :s, admin_notes = :n, updated_at = NOW() 
                WHERE id = :id
            ");
            $upStmt->execute([
                ':s'  => $newStatus,
                ':n'  => $adminNotes,
                ':id' => $appId
            ]);

            logActivity('ADMISSION_UPDATED', "Updated notes/status ({$newStatus}) for {$app['application_no']}", $currentUser['id']);
            setFlash('success', 'Application status and internal notes successfully saved.');
            header("Location: admission_view.php?id={$appId}");
            exit;
        } else {
            setFlash('error', 'Invalid status selection.');
        }
    }
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px;">
  <div>
    <a href="admissions.php" class="btn btn-sm btn-outline-white" style="color: #64748b; border-color: var(--admin-border); margin-bottom: 8px;">
      <i class="fas fa-arrow-left"></i> Back to All Applications
    </a>
    <h2 style="font-family: var(--font-heading); color: var(--color-primary); font-size: 1.6rem;">
      Application #<?= e($app['application_no']) ?>
    </h2>
  </div>

  <div style="display: flex; gap: 10px;">
    <button onclick="window.print()" class="btn btn-sm btn-outline-white" style="color: var(--color-primary); border-color: var(--color-primary);">
      <i class="fas fa-print"></i> Print Application
    </button>
    <?php if (hasRole(['admin', 'super_admin'])): ?>
      <form method="POST" action="admission_view.php?id=<?= $appId ?>" onsubmit="return confirm('Delete this application permanently?');">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="delete" />
        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete Applicant</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 25px;" class="admin-detail-grid">
  <!-- Left Column: Application Data -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3 class="admin-card-title"><i class="fas fa-id-card"></i> Student & Guardian Information</h3>
      <span class="status-pill status-<?= e($app['status']) ?>"><?= ucfirst(e($app['status'])) ?></span>
    </div>

    <div class="admin-card-body">
      <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 25px;">
        <div>
          <label style="font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Student Full Name</label>
          <p style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin-top: 2px;"><?= e($app['full_name']) ?></p>
        </div>

        <div>
          <label style="font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Applying For Grade</label>
          <p style="font-size: 1.15rem; font-weight: 700; color: var(--color-primary); margin-top: 2px;"><?= e($app['grade']) ?></p>
        </div>

        <div>
          <label style="font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Date of Birth</label>
          <p style="font-size: 1rem; color: #334155; margin-top: 2px;"><?= e($app['dob']) ?></p>
        </div>

        <div>
          <label style="font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Gender</label>
          <p style="font-size: 1rem; color: #334155; margin-top: 2px;"><?= e($app['gender']) ?></p>
        </div>
      </div>

      <hr style="border: 0; border-top: 1px solid var(--admin-border); margin: 20px 0;" />

      <h4 style="font-family: var(--font-heading); color: var(--color-primary); margin-bottom: 15px;">Parent & Contact Details</h4>
      <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 25px;">
        <div>
          <label style="font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Parent / Guardian Name</label>
          <p style="font-size: 1rem; font-weight: 600; color: #334155; margin-top: 2px;"><?= e($app['parent_name'] ?? 'Not Specified') ?></p>
        </div>

        <div>
          <label style="font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Parent Mobile Number</label>
          <p style="font-size: 1rem; font-weight: 600; color: #0f172a; margin-top: 2px;">
            <a href="tel:<?= e($app['parent_contact']) ?>" style="color: var(--color-primary);"><?= e($app['parent_contact']) ?></a>
          </p>
        </div>

        <div>
          <label style="font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Parent Email</label>
          <p style="font-size: 1rem; color: #334155; margin-top: 2px;"><?= e($app['parent_email'] ?? 'None provided') ?></p>
        </div>

        <div>
          <label style="font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Previous School</label>
          <p style="font-size: 1rem; color: #334155; margin-top: 2px;"><?= e($app['previous_school'] ?? 'None / Fresh Admission') ?></p>
        </div>
      </div>

      <hr style="border: 0; border-top: 1px solid var(--admin-border); margin: 20px 0;" />

      <div>
        <label style="font-size: 0.8rem; text-transform: uppercase; color: #64748b; font-weight: 700;">Permanent / Residential Address</label>
        <p style="font-size: 1rem; color: #334155; margin-top: 4px; line-height: 1.6; background: #f8fafc; padding: 14px; border-radius: 8px; border: 1px solid var(--admin-border);">
          <?= nl2br(e($app['address'])) ?>
        </p>
      </div>

      <div style="margin-top: 20px; font-size: 0.82rem; color: #94a3b8; display: flex; gap: 20px;">
        <span><i class="fas fa-calendar-alt"></i> Submitted: <?= date('M d, Y h:i A', strtotime($app['created_at'])) ?></span>
        <?php if (!empty($app['ip_address'])): ?>
          <span><i class="fas fa-network-wired"></i> IP: <?= e($app['ip_address']) ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Right Column: Staff Workflow & Notes -->
  <div class="admin-card">
    <div class="admin-card-header">
      <h3 class="admin-card-title"><i class="fas fa-tasks"></i> Status & Staff Notes</h3>
    </div>

    <div class="admin-card-body">
      <?php if (hasRole(['admin', 'super_admin'])): ?>
        <form method="POST" action="admission_view.php?id=<?= $appId ?>">
          <?= csrfField() ?>

          <div class="form-group">
            <label for="status" class="form-label">Application Status</label>
            <select id="status" name="status" class="form-control" style="font-weight: 600;">
              <option value="pending" <?= ($app['status'] === 'pending') ? 'selected' : '' ?>>Pending Review</option>
              <option value="reviewed" <?= ($app['status'] === 'reviewed') ? 'selected' : '' ?>>Reviewed / Contacted</option>
              <option value="accepted" <?= ($app['status'] === 'accepted') ? 'selected' : '' ?>>Accepted & Enrolled</option>
              <option value="rejected" <?= ($app['status'] === 'rejected') ? 'selected' : '' ?>>Rejected / Cancelled</option>
            </select>
          </div>

          <div class="form-group">
            <label for="admin_notes" class="form-label">Internal Staff Notes</label>
            <textarea id="admin_notes" name="admin_notes" class="form-control" rows="6" placeholder="Add confidential notes (e.g. called parent on Sunday, verified birth cert, bus route 2 assigned)"><?= e($app['admin_notes'] ?? '') ?></textarea>
            <span class="form-help-text">Internal notes are visible only to school staff.</span>
          </div>

          <button type="submit" class="btn btn-primary" style="width: 100%;">
            <i class="fas fa-save"></i> Save Changes
          </button>
        </form>
      <?php else: ?>
        <div style="margin-bottom: 20px;">
          <label class="form-label" style="color: #64748b; font-size: 0.85rem;">Current Status</label>
          <div>
            <span class="status-pill status-<?= e($app['status']) ?>" style="font-size: 0.95rem; padding: 6px 14px;">
              <?= ucfirst(e($app['status'])) ?>
            </span>
          </div>
        </div>

        <div style="margin-bottom: 20px;">
          <label class="form-label" style="color: #64748b; font-size: 0.85rem;">Internal Staff Notes</label>
          <div style="background: #f8fafc; border: 1px solid var(--admin-border); border-radius: 8px; padding: 14px; min-height: 100px; font-size: 0.95rem; color: #334155;">
            <?= !empty($app['admin_notes']) ? nl2br(e($app['admin_notes'])) : '<em style="color: #94a3b8;">No staff notes added yet.</em>' ?>
          </div>
        </div>

        <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 6px; padding: 12px; font-size: 0.85rem; color: #b45309;">
          <i class="fas fa-info-circle"></i> <strong>Staff Role:</strong> You have view-only access. Administrator role is required to update status or notes.
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
