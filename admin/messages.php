<?php
$adminTitle = 'Contact Inquiries';
$activeNav = 'messages';
require_once __DIR__ . '/includes/admin_header.php';

$pdo = getDB();

// Handle Status Change (admin and super_admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    requireRole(['admin', 'super_admin']);

    if (!verifyCsrfToken()) {
        setFlash('error', 'CSRF verification failed.');
    } else {
        $msgId = (int)($_POST['msg_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? '');
        $validStatuses = ['unread', 'read', 'replied', 'archived'];

        if ($msgId > 0 && in_array($newStatus, $validStatuses, true)) {
            $stmt = $pdo->prepare("UPDATE contact_messages SET status = :s WHERE id = :id");
            $stmt->execute([':s' => $newStatus, ':id' => $msgId]);
            logActivity('MESSAGE_STATUS_UPDATED', "Message #{$msgId} set to {$newStatus}", $currentUser['id']);
            setFlash('success', "Message status updated to " . ucfirst($newStatus) . ".");
        }
    }
    header('Location: messages.php?' . http_build_query($_GET));
    exit;
}

// Search & filters
$search = trim($_GET['search'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');
$selectedId = (int)($_GET['id'] ?? 0);

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(name LIKE :s1 OR email LIKE :s2 OR message LIKE :s3 OR subject LIKE :s4)";
    $searchTerm = "%{$search}%";
    $params[':s1'] = $searchTerm;
    $params[':s2'] = $searchTerm;
    $params[':s3'] = $searchTerm;
    $params[':s4'] = $searchTerm;
}

if ($filterStatus !== '') {
    $where[] = "status = :status";
    $params[':status'] = $filterStatus;
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT * FROM contact_messages {$whereSql} ORDER BY id DESC LIMIT 50");
$stmt->execute($params);
$messages = $stmt->fetchAll();

// If viewing a specific message, auto-mark as read if unread
$selectedMessage = null;
if ($selectedId > 0) {
    foreach ($messages as $m) {
        if ($m['id'] == $selectedId) {
            $selectedMessage = $m;
            if ($m['status'] === 'unread') {
                $pdo->prepare("UPDATE contact_messages SET status = 'read' WHERE id = :id")->execute([':id' => $selectedId]);
                $selectedMessage['status'] = 'read';
            }
            break;
        }
    }
}
?>

<!-- Search & Filter Toolbar -->
<div class="admin-card">
  <div class="admin-card-body" style="padding: 18px 24px;">
    <form method="GET" action="messages.php" class="filter-bar" style="margin-bottom: 0;">
      <div style="flex-grow: 1; min-width: 220px;">
        <input type="search" name="search" value="<?= e($search) ?>" placeholder="Search sender, email, subject, keyword..." class="filter-input" style="width: 100%;" aria-label="Search inquiries" />
      </div>

      <div>
        <select name="status" class="filter-input" aria-label="Filter by inquiry status">
          <option value="">All Statuses</option>
          <option value="unread" <?= ($filterStatus === 'unread') ? 'selected' : '' ?>>Unread</option>
          <option value="read" <?= ($filterStatus === 'read') ? 'selected' : '' ?>>Read</option>
          <option value="replied" <?= ($filterStatus === 'replied') ? 'selected' : '' ?>>Replied</option>
          <option value="archived" <?= ($filterStatus === 'archived') ? 'selected' : '' ?>>Archived</option>
        </select>
      </div>

      <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> Filter</button>
      <?php if ($search !== '' || $filterStatus !== ''): ?>
        <a href="messages.php" class="btn btn-sm btn-outline-white" style="color: #64748b; border-color: var(--admin-border);">Reset</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php if ($selectedMessage): ?>
<!-- Selected Message Detail View -->
<div class="admin-card" style="border: 2px solid var(--color-primary);">
  <div class="admin-card-header" style="background: #eff6ff;">
    <div>
      <h3 class="admin-card-title"><i class="fas fa-envelope-open"></i> <?= e($selectedMessage['subject'] ?? 'Website Inquiry') ?></h3>
      <span style="font-size: 0.85rem; color: #64748b;">From: <strong><?= e($selectedMessage['name']) ?></strong> &lt;<?= e($selectedMessage['email']) ?>&gt;</span>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
      <span class="status-pill status-<?= e($selectedMessage['status']) ?>"><?= ucfirst(e($selectedMessage['status'])) ?></span>
      <a href="messages.php" class="action-btn btn-view"><i class="fas fa-times"></i> Close</a>
    </div>
  </div>
  <div class="admin-card-body">
    <div style="display: flex; gap: 30px; margin-bottom: 20px; font-size: 0.9rem; color: #475569; flex-wrap: wrap;">
      <span><i class="fas fa-phone-alt"></i> Phone: <strong><?= e($selectedMessage['phone'] ?? 'None provided') ?></strong></span>
      <span><i class="fas fa-calendar-alt"></i> Received: <strong><?= date('M d, Y h:i A', strtotime($selectedMessage['created_at'])) ?></strong></span>
      <?php if (!empty($selectedMessage['ip_address'])): ?>
        <span><i class="fas fa-network-wired"></i> IP: <strong><?= e($selectedMessage['ip_address']) ?></strong></span>
      <?php endif; ?>
    </div>

    <div style="background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid var(--admin-border); margin-bottom: 20px; font-size: 1rem; line-height: 1.7; color: #1e293b;">
      <?= nl2br(e($selectedMessage['message'])) ?>
    </div>

    <!-- Action Controls -->
    <?php if (hasRole(['admin', 'super_admin'])): ?>
      <form method="POST" action="messages.php?id=<?= $selectedId ?>" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="update_status" />
        <input type="hidden" name="msg_id" value="<?= $selectedId ?>" />

        <label for="status" style="font-weight: 600; font-size: 0.9rem;">Update Status:</label>
        <select name="status" class="filter-input">
          <option value="read" <?= ($selectedMessage['status'] === 'read') ? 'selected' : '' ?>>Mark as Read</option>
          <option value="unread" <?= ($selectedMessage['status'] === 'unread') ? 'selected' : '' ?>>Mark as Unread</option>
          <option value="replied" <?= ($selectedMessage['status'] === 'replied') ? 'selected' : '' ?>>Mark as Replied</option>
          <option value="archived" <?= ($selectedMessage['status'] === 'archived') ? 'selected' : '' ?>>Archive</option>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">Update</button>
        <a href="mailto:<?= e($selectedMessage['email']) ?>?subject=Re:%20<?= urlencode($selectedMessage['subject'] ?? 'Mount Excel Inquiry') ?>" class="btn btn-sm btn-accent">
          <i class="fas fa-reply"></i> Reply via Email
        </a>
      </form>
    <?php else: ?>
      <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
        <a href="mailto:<?= e($selectedMessage['email']) ?>?subject=Re:%20<?= urlencode($selectedMessage['subject'] ?? 'Mount Excel Inquiry') ?>" class="btn btn-sm btn-accent">
          <i class="fas fa-reply"></i> Reply via Email
        </a>
        <span style="font-size: 0.85rem; color: #b45309; background: #fffbeb; padding: 6px 12px; border-radius: 6px; border: 1px solid #fef3c7;">
          <i class="fas fa-info-circle"></i> Staff view-only access. Administrator required to change status.
        </span>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- Message List Table -->
<div class="admin-card">
  <div class="admin-card-header">
    <h3 class="admin-card-title">Inquiry Messages (<?= count($messages) ?>)</h3>
  </div>

  <div class="admin-card-body" style="padding: 0;">
    <div class="table-responsive">
      <table class="admin-table" aria-label="Contact inquiries">
        <thead>
          <tr>
            <th>Status</th>
            <th>Sender Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Subject</th>
            <th>Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($messages)): ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 40px; color: #64748b;">
                No contact messages found.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($messages as $msg): ?>
              <tr style="<?= ($msg['status'] === 'unread') ? 'background-color: #fffbeb; font-weight: 600;' : '' ?>">
                <td>
                  <span class="status-pill status-<?= e($msg['status']) ?>">
                    <?= ucfirst(e($msg['status'])) ?>
                  </span>
                </td>
                <td><?= e($msg['name']) ?></td>
                <td><?= e($msg['email']) ?></td>
                <td><?= e($msg['phone'] ?? '-') ?></td>
                <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                  <?= e($msg['subject'] ?? 'Website Inquiry') ?>
                </td>
                <td style="font-size: 0.85rem; color: #64748b;"><?= date('M d, Y', strtotime($msg['created_at'])) ?></td>
                <td>
                  <a href="messages.php?id=<?= (int)$msg['id'] ?>" class="action-btn btn-view">
                    <i class="fas fa-envelope-open-text"></i> Open
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
