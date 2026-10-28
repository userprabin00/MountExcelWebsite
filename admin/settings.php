<?php
$adminTitle = 'Admission Settings';
$activeNav = 'settings';
require_once __DIR__ . '/includes/admin_header.php';

// Only admin and super_admin can configure admission settings
requireRole(['admin', 'super_admin']);

$error = '';
$success = '';

$standardGrades = [
    'Play Group', 'Nursery', 'LKG', 'UKG',
    'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4',
    'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'CSRF verification failed. Please try again.';
    } else {
        $isOpen = isset($_POST['admission_open']) && $_POST['admission_open'] === 'on' ? 'on' : 'off';
        $startDate = trim($_POST['admission_start_date'] ?? '');
        $endDate = trim($_POST['admission_end_date'] ?? '');
        $closedMsg = trim($_POST['admission_closed_message'] ?? '');

        // Per-grade end dates
        $postedGradeDates = $_POST['grade_end_dates'] ?? [];
        $cleanedGradeDates = [];
        foreach ($standardGrades as $g) {
            $dateVal = trim($postedGradeDates[$g] ?? '');
            if (!empty($dateVal)) {
                $cleanedGradeDates[$g] = $dateVal;
            } elseif (!empty($endDate)) {
                $cleanedGradeDates[$g] = $endDate;
            }
        }

        // Validate dates if provided
        if (!empty($startDate) && !validateDate($startDate)) {
            $error = 'Invalid admission start date format (must be YYYY-MM-DD).';
        } elseif (!empty($endDate) && !validateDate($endDate)) {
            $error = 'Invalid admission end date format (must be YYYY-MM-DD).';
        } elseif (empty($closedMsg)) {
            $error = 'Closed notice message cannot be empty.';
        } else {
            updateSetting('admission_open', $isOpen);
            updateSetting('admission_start_date', $startDate);
            updateSetting('admission_end_date', $endDate);
            updateSetting('admission_closed_message', $closedMsg);
            updateSetting('admission_grade_end_dates', json_encode($cleanedGradeDates, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            logActivity('ADMISSION_SETTINGS_UPDATED', "Updated admission schedule: Open={$isOpen}, Start={$startDate}, End={$endDate}", $currentUser['id']);
            setFlash('success', 'Admission settings successfully updated and saved.');
            header('Location: settings.php');
            exit;
        }
    }
}

// Current values
$currentOpen = getSetting('admission_open', 'on');
$currentStartDate = getSetting('admission_start_date', '2026-12-15');
$currentEndDate = getSetting('admission_end_date', '2027-04-13');
$currentClosedMsg = getAdmissionClosedMessage();
$currentGradeEndDates = getGradeEndDates();

// Calculate live admission status
$isCurrentlyOpen = isAdmissionOpen();
$tz = new DateTimeZone('Asia/Kathmandu');
$nowKathmandu = new DateTime('now', $tz);
?>

<div style="max-width: 900px; margin: 0 auto;">
  <?php if ($error): ?>
    <div class="alert alert-error" role="alert"><?= e($error) ?></div>
  <?php endif; ?>

  <div class="admin-card">
    <div class="admin-card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
      <div>
        <h3 class="admin-card-title"><i class="fas fa-calendar-check" style="color: var(--accent-gold);"></i> Admission Schedule & Window Settings</h3>
        <p style="font-size: 0.85rem; color: #64748b; margin-top: 4px;">
          Configure session availability, application deadlines, and per-grade rules in <strong>Asia/Kathmandu (UTC+05:45)</strong>.
        </p>
      </div>

      <div style="text-align: right;">
        <span class="status-pill <?= $isCurrentlyOpen ? 'status-accepted' : 'status-rejected' ?>" style="font-size: 0.85rem; padding: 6px 14px;">
          <i class="fas <?= $isCurrentlyOpen ? 'fa-check-circle' : 'fa-clock' ?>"></i>
          Currently <?= $isCurrentlyOpen ? 'Open for Submissions' : 'Closed' ?>
        </span>
      </div>
    </div>

    <div class="admin-card-body">
      <!-- Live Kathmandu Time Banner -->
      <div style="background: #f8fafc; border: 1px solid var(--admin-border); border-radius: 8px; padding: 14px 18px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
        <div>
          <i class="fas fa-globe-asia" style="color: var(--primary-700);"></i>
          <span style="font-weight: 600; color: #1e293b; margin-left: 6px;">School Timezone:</span>
          <span style="color: #475569;">Asia/Kathmandu (UTC+05:45)</span>
        </div>
        <div style="font-family: monospace; font-size: 0.95rem; color: #0f172a; font-weight: 600;">
          <?= $nowKathmandu->format('Y-m-d H:i:s T') ?>
        </div>
      </div>

      <form method="POST" action="settings.php" novalidate>
        <?= csrfField() ?>

        <!-- Master Switch -->
        <div class="form-group" style="background: #f1f5f9; padding: 16px 20px; border-radius: 8px; margin-bottom: 25px;">
          <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; margin: 0; font-weight: 600; color: #0f172a; font-size: 1rem;">
            <input type="checkbox" name="admission_open" value="on" <?= ($currentOpen === 'on' || $currentOpen === '1') ? 'checked' : '' ?> style="width: 20px; height: 20px; accent-color: var(--primary-700); cursor: pointer;" />
            <span>Master Switch: Enable Online Admissions (Active = Yes)</span>
          </label>
          <span class="form-help-text" style="margin-left: 32px; display: block; margin-top: 5px;">
            If unchecked, online admissions will be closed immediately regardless of configured dates.
          </span>
        </div>

        <!-- Global Start & End Dates -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 25px;">
          <div class="form-group">
            <label for="admission_start_date" class="form-label">
              <i class="fas fa-calendar-alt"></i> Admission Start Date <span class="required-star">*</span>
            </label>
            <input type="date" id="admission_start_date" name="admission_start_date" value="<?= e($currentStartDate) ?>" class="form-control" required />
            <span class="form-help-text">Effective from 00:00:00 (Kathmandu Time).</span>
          </div>

          <div class="form-group">
            <label for="admission_end_date" class="form-label">
              <i class="fas fa-calendar-times"></i> Global Admission End Date <span class="required-star">*</span>
            </label>
            <input type="date" id="admission_end_date" name="admission_end_date" value="<?= e($currentEndDate) ?>" class="form-control" required />
            <span class="form-help-text">Includes the whole day until 23:59:59 (Kathmandu Time).</span>
          </div>
        </div>

        <!-- Closed Message -->
        <div class="form-group" style="margin-bottom: 30px;">
          <label for="admission_closed_message" class="form-label">
            <i class="fas fa-comment-dots"></i> Closed Notice Message <span class="required-star">*</span>
          </label>
          <textarea id="admission_closed_message" name="admission_closed_message" class="form-control" rows="3" required><?= e($currentClosedMsg) ?></textarea>
          <span class="form-help-text">Displayed on the public admissions page whenever admissions are closed or before the session start date.</span>
        </div>

        <!-- Per-Grade End Date Deadlines -->
        <div class="admin-card" style="margin-bottom: 25px; border: 1px solid var(--admin-border);">
          <div class="admin-card-header" style="background: #f8fafc;">
            <h4 style="font-size: 1rem; color: #1e293b; margin: 0;">
              <i class="fas fa-layer-group" style="color: var(--accent-gold);"></i> Per-Grade End Dates (Optional per-grade deadline support)
            </h4>
            <p style="font-size: 0.8rem; color: #64748b; margin-top: 4px;">
              Specify custom end dates for individual grades. Each grade's deadline includes the whole day until <strong>23:59:59 Kathmandu Time</strong>.
            </p>
          </div>
          <div class="admin-card-body" style="padding: 15px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 15px;">
              <?php foreach ($standardGrades as $g): 
                $gDate = $currentGradeEndDates[$g] ?? $currentEndDate;
                $gOpen = isGradeAdmissionOpen($g);
              ?>
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px;">
                  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <strong style="color: #0f172a; font-size: 0.95rem;"><?= e($g) ?></strong>
                    <span style="font-size: 0.75rem; padding: 2px 8px; border-radius: 12px; font-weight: 600; background: <?= $gOpen ? '#dcfce7; color: #166534;' : '#fee2e2; color: #991b1b;' ?>">
                      <?= $gOpen ? 'Open' : 'Closed' ?>
                    </span>
                  </div>
                  <input type="date" name="grade_end_dates[<?= e($g) ?>]" value="<?= e($gDate) ?>" class="form-control" style="font-size: 0.88rem; padding: 6px 10px;" />
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
          <i class="fas fa-save"></i> Save Admission Settings
        </button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
