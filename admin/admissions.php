<?php
$adminTitle = 'Admission Applications';
$activeNav = 'admissions';
require_once __DIR__ . '/includes/admin_header.php';

$pdo = getDB();

// Handle Quick Status Change (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (!verifyCsrfToken()) {
        setFlash('error', 'CSRF verification failed.');
    } else {
        $appId = (int)($_POST['app_id'] ?? 0);
        $newStatus = trim($_POST['new_status'] ?? '');
        $validStatuses = ['pending', 'reviewed', 'accepted', 'rejected'];

        if ($appId > 0 && in_array($newStatus, $validStatuses, true)) {
            $updateStmt = $pdo->prepare("UPDATE admissions SET status = :s WHERE id = :id");
            $updateStmt->execute([':s' => $newStatus, ':id' => $appId]);
            logActivity('ADMISSION_STATUS_UPDATED', "Status updated to {$newStatus} for application ID #{$appId}", $currentUser['id']);
            setFlash('success', "Application #{$appId} status successfully updated to " . ucfirst($newStatus) . ".");
        } else {
            setFlash('error', 'Invalid status update parameters.');
        }
    }
    header('Location: admissions.php?' . http_build_query($_GET));
    exit;
}

// Search and filter parameters
$search = trim($_GET['search'] ?? '');
$filterGradeInput = trim($_GET['grade'] ?? '');
$filterGrade = $filterGradeInput !== '' ? normalizeGradeName($filterGradeInput) : '';
$filterStatus = trim($_GET['status'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Build query
$whereClauses = [];
$params = [];

if ($search !== '') {
    $whereClauses[] = "(full_name LIKE :s1 OR application_no LIKE :s2 OR parent_contact LIKE :s3 OR parent_name LIKE :s4)";
    $searchTerm = "%{$search}%";
    $params[':s1'] = $searchTerm;
    $params[':s2'] = $searchTerm;
    $params[':s3'] = $searchTerm;
    $params[':s4'] = $searchTerm;
}

if ($filterGrade !== '') {
    $whereClauses[] = "grade IN (:grade, :legacy_grade)";
    $params[':grade'] = $filterGrade;
    $params[':legacy_grade'] = $filterGrade === 'Play Group' ? 'PG' : $filterGrade;
}

if ($filterStatus !== '') {
    $whereClauses[] = "status = :status";
    $params[':status'] = $filterStatus;
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Count total matching records
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM admissions {$whereSql}");
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRecords / $perPage));

// Fetch records for current page
$sql = "SELECT id, application_no, full_name, dob, gender, grade, parent_name, parent_contact, address, status, created_at 
        FROM admissions 
        {$whereSql} 
        ORDER BY id DESC 
        LIMIT {$perPage} OFFSET {$offset}";

$dataStmt = $pdo->prepare($sql);
$dataStmt->execute($params);
$admissions = $dataStmt->fetchAll();
?>

<!-- Filter & Search Toolbar -->
<div class="admin-card">
  <div class="admin-card-body" style="padding: 18px 24px;">
    <form method="GET" action="admissions.php" class="filter-bar" style="margin-bottom: 0;">
      <div style="flex-grow: 1; min-width: 220px;">
        <input type="search" name="search" value="<?= e($search) ?>" placeholder="Search student name, app #, parent phone..." class="filter-input" style="width: 100%;" aria-label="Search applications" />
      </div>

      <div>
        <select name="grade" class="filter-input" aria-label="Filter by grade">
          <option value="">All Grades</option>
          <?php foreach (['Play Group', 'Nursery', 'LKG', 'UKG', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8'] as $g): ?>
            <option value="<?= e($g) ?>" <?= ($filterGrade === $g) ? 'selected' : '' ?>><?= e($g === 'Play Group' ? 'Play Group (PG)' : $g) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <select name="status" class="filter-input" aria-label="Filter by application status">
          <option value="">All Statuses</option>
          <option value="pending" <?= ($filterStatus === 'pending') ? 'selected' : '' ?>>Pending</option>
          <option value="reviewed" <?= ($filterStatus === 'reviewed') ? 'selected' : '' ?>>Reviewed</option>
          <option value="accepted" <?= ($filterStatus === 'accepted') ? 'selected' : '' ?>>Accepted</option>
          <option value="rejected" <?= ($filterStatus === 'rejected') ? 'selected' : '' ?>>Rejected</option>
        </select>
      </div>

      <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i> Apply Filters</button>

      <?php if ($search !== '' || $filterGrade !== '' || $filterStatus !== ''): ?>
        <a href="admissions.php" class="btn btn-sm btn-outline-white" style="color: #64748b; border-color: var(--admin-border);">Reset</a>
      <?php endif; ?>

      <?php if (hasRole(['admin', 'super_admin'])): ?>
        <div style="margin-left: auto;">
          <a href="export_admissions.php?<?= http_build_query($_GET) ?>" class="btn btn-sm btn-accent"><i class="fas fa-file-csv"></i> Export CSV</a>
        </div>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- Table Card -->
<div class="admin-card">
  <div class="admin-card-header">
    <h3 class="admin-card-title">
      Admission Applications (<?= number_format($totalRecords) ?> total)
    </h3>
    <span style="font-size: 0.85rem; color: #64748b;">Page <?= $page ?> of <?= $totalPages ?></span>
  </div>

  <div class="admin-card-body" style="padding: 0;">
    <div class="table-responsive">
      <table class="admin-table" aria-label="Admission applications">
        <thead>
          <tr>
            <th>Ref #</th>
            <th>Student Name</th>
            <th>DOB</th>
            <th>Grade</th>
            <th>Parent / Contact</th>
            <th>Address</th>
            <th>Status</th>
            <th>Submitted</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($admissions)): ?>
            <tr>
              <td colspan="9" style="text-align: center; padding: 40px; color: #64748b;">
                No matching admission records found.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($admissions as $row): ?>
              <tr>
                <td><strong><?= e($row['application_no']) ?></strong></td>
                <td>
                  <strong><?= e($row['full_name']) ?></strong>
                  <div style="font-size: 0.78rem; color: #64748b;"><?= e($row['gender']) ?></div>
                </td>
                <td style="font-size: 0.85rem;"><?= e($row['dob']) ?></td>
                <td><span class="badge badge-blue"><?= e($row['grade']) ?></span></td>
                <td>
                  <div><?= e($row['parent_name'] ?? 'Parent') ?></div>
                  <a href="tel:<?= e($row['parent_contact']) ?>" style="font-size: 0.85rem; color: var(--color-primary); font-weight: 600;">
                    <?= e($row['parent_contact']) ?>
                  </a>
                </td>
                <td style="max-width: 180px; font-size: 0.85rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= e($row['address']) ?>">
                  <?= e($row['address']) ?>
                </td>
                <td>
                  <!-- Quick Status Dropdown Form -->
                  <form method="POST" action="admissions.php?<?= http_build_query($_GET) ?>" style="display: inline-block;">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_status" />
                    <input type="hidden" name="app_id" value="<?= (int)$row['id'] ?>" />
                    <select name="new_status" onchange="this.form.submit()" class="filter-input" style="padding: 4px 8px; font-size: 0.8rem; font-weight: 600;">
                      <option value="pending" <?= ($row['status'] === 'pending') ? 'selected' : '' ?>>Pending</option>
                      <option value="reviewed" <?= ($row['status'] === 'reviewed') ? 'selected' : '' ?>>Reviewed</option>
                      <option value="accepted" <?= ($row['status'] === 'accepted') ? 'selected' : '' ?>>Accepted</option>
                      <option value="rejected" <?= ($row['status'] === 'rejected') ? 'selected' : '' ?>>Rejected</option>
                    </select>
                  </form>
                </td>
                <td style="font-size: 0.85rem; color: #64748b;"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                <td>
                  <a href="admission_view.php?id=<?= (int)$row['id'] ?>" class="action-btn btn-view" title="View Full Details">
                    <i class="fas fa-eye"></i> Details
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <div style="padding: 20px;">
        <div class="pagination">
          <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-link">&laquo; Prev</a>
          <?php endif; ?>

          <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="page-link <?= ($i === $page) ? 'active' : '' ?>">
              <?= $i ?>
            </a>
          <?php endfor; ?>

          <?php if ($page < $totalPages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-link">Next &raquo;</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
