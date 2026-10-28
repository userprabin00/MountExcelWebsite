<?php
$adminTitle = 'Dashboard Overview';
$activeNav = 'dashboard';
require_once __DIR__ . '/includes/admin_header.php';

$pdo = getDB();

// Gather key metrics
$totalAdmissions   = (int)$pdo->query("SELECT COUNT(*) FROM admissions")->fetchColumn();
$pendingAdmissions = (int)$pdo->query("SELECT COUNT(*) FROM admissions WHERE status = 'pending'")->fetchColumn();
$acceptedAdmissions= (int)$pdo->query("SELECT COUNT(*) FROM admissions WHERE status = 'accepted'")->fetchColumn();
$totalMessages     = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
$unreadMessages    = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'")->fetchColumn();

// Fetch 5 most recent admission submissions
$recentAdmissions = $pdo->query("
    SELECT id, application_no, full_name, grade, parent_contact, status, created_at 
    FROM admissions 
    ORDER BY id DESC 
    LIMIT 5
")->fetchAll();

// Fetch 5 most recent messages
$recentMessages = $pdo->query("
    SELECT id, name, email, subject, status, created_at 
    FROM contact_messages 
    ORDER BY id DESC 
    LIMIT 5
")->fetchAll();
?>

<section class="admin-page-intro" aria-labelledby="dashboard-heading">
  <div>
    <p class="admin-eyebrow">Staff workspace</p>
    <h1 id="dashboard-heading">Welcome back, <?= e($currentUser['full_name']) ?></h1>
    <p>Here’s what needs your attention across admissions and inquiries.</p>
  </div>
  <div class="admin-date-chip"><i class="far fa-calendar-alt" aria-hidden="true"></i> <?= e(date('l, F j, Y')) ?></div>
</section>

<!-- Metrics Strip -->
<div class="admin-metrics-grid">
  <a href="admissions.php" class="metric-card metric-card-link" aria-label="View all <?= $totalAdmissions ?> applications">
    <div class="metric-icon-box icon-blue"><i class="fas fa-user-graduate" aria-hidden="true"></i></div>
    <div class="metric-data"><h3><?= $totalAdmissions ?></h3><p>Total Applications</p></div>
  </a>
  <a href="admissions.php?status=pending" class="metric-card metric-card-link" aria-label="Review <?= $pendingAdmissions ?> pending applications">
    <div class="metric-icon-box icon-yellow"><i class="fas fa-clock" aria-hidden="true"></i></div>
    <div class="metric-data"><h3><?= $pendingAdmissions ?></h3><p>Pending Applications</p></div>
  </a>
  <a href="admissions.php?status=accepted" class="metric-card metric-card-link" aria-label="View <?= $acceptedAdmissions ?> accepted students">
    <div class="metric-icon-box icon-green"><i class="fas fa-check-circle" aria-hidden="true"></i></div>
    <div class="metric-data"><h3><?= $acceptedAdmissions ?></h3><p>Accepted Students</p></div>
  </a>
  <a href="messages.php" class="metric-card metric-card-link" aria-label="View all <?= $totalMessages ?> inquiries">
    <div class="metric-icon-box icon-blue"><i class="fas fa-comments" aria-hidden="true"></i></div>
    <div class="metric-data"><h3><?= $totalMessages ?></h3><p>Total Inquiries</p></div>
  </a>
  <a href="messages.php?status=unread" class="metric-card metric-card-link" aria-label="Open <?= $unreadMessages ?> unread inquiries">
    <div class="metric-icon-box icon-purple"><i class="fas fa-envelope-open-text" aria-hidden="true"></i></div>
    <div class="metric-data"><h3><?= $unreadMessages ?></h3><p>Unread Inquiries</p></div>
  </a>
</div>

<!-- Quick Action Shortcuts -->
<nav class="admin-quick-actions" aria-label="Quick actions">
  <a href="admissions.php" class="btn btn-primary"><i class="fas fa-list" aria-hidden="true"></i> Manage applications</a>
  <a href="messages.php?status=unread" class="btn btn-secondary"><i class="fas fa-envelope-open-text" aria-hidden="true"></i> Review unread inquiries</a>
  <?php if (hasRole(['admin', 'super_admin'])): ?>
    <a href="export_admissions.php" class="btn btn-outline"><i class="fas fa-file-csv" aria-hidden="true"></i> Export applications</a>
  <?php endif; ?>
</nav>

<!-- Recent Admissions -->
<div class="admin-card">
  <div class="admin-card-header">
    <h3 class="admin-card-title"><i class="fas fa-user-plus"></i> Recent Admission Applications</h3>
    <a href="admissions.php" class="action-btn btn-view">View All (<?= $totalAdmissions ?>) &rarr;</a>
  </div>
  <div class="admin-card-body" style="padding: 0;">
    <div class="table-responsive">
      <table class="admin-table" aria-label="Recent admission applications">
        <thead>
          <tr>
            <th>App Ref #</th>
            <th>Student Name</th>
            <th>Grade</th>
            <th>Parent Phone</th>
            <th>Status</th>
            <th>Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentAdmissions)): ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 30px; color: #64748b;">
                No admission applications received yet.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($recentAdmissions as $row): ?>
              <tr>
                <td><strong><?= e($row['application_no']) ?></strong></td>
                <td><?= e($row['full_name']) ?></td>
                <td><span class="badge badge-blue"><?= e($row['grade']) ?></span></td>
                <td><?= e($row['parent_contact']) ?></td>
                <td>
                  <span class="status-pill status-<?= e($row['status']) ?>">
                    <?= ucfirst(e($row['status'])) ?>
                  </span>
                </td>
                <td style="color: #64748b; font-size: 0.85rem;"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                <td>
                  <a href="admission_view.php?id=<?= (int)$row['id'] ?>" class="action-btn btn-view">
                    <i class="fas fa-eye"></i> View
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

<!-- Recent Contact Inquiries -->
<div class="admin-card">
  <div class="admin-card-header">
    <h3 class="admin-card-title"><i class="fas fa-envelope"></i> Recent Contact Inquiries</h3>
    <a href="messages.php" class="action-btn btn-view">View All (<?= $totalMessages ?>) &rarr;</a>
  </div>
  <div class="admin-card-body" style="padding: 0;">
    <div class="table-responsive">
      <table class="admin-table" aria-label="Recent contact inquiries">
        <thead>
          <tr>
            <th>Sender Name</th>
            <th>Email</th>
            <th>Subject</th>
            <th>Status</th>
            <th>Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentMessages)): ?>
            <tr>
              <td colspan="6" style="text-align: center; padding: 30px; color: #64748b;">
                No contact messages received yet.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($recentMessages as $msg): ?>
              <tr>
                <td><strong><?= e($msg['name']) ?></strong></td>
                <td><?= e($msg['email']) ?></td>
                <td><?= e($msg['subject'] ?? 'General Inquiry') ?></td>
                <td>
                  <span class="status-pill status-<?= e($msg['status']) ?>">
                    <?= ucfirst(e($msg['status'])) ?>
                  </span>
                </td>
                <td style="color: #64748b; font-size: 0.85rem;"><?= date('M d, Y', strtotime($msg['created_at'])) ?></td>
                <td>
                  <a href="messages.php?id=<?= (int)$msg['id'] ?>" class="action-btn btn-view">
                    <i class="fas fa-eye"></i> View
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
