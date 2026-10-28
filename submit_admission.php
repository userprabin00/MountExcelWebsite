<?php
/**
 * Admission Form Submission Handler
 * Mount Excel Boarding School
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/mail.php';

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admissions.php');
    exit;
}

// 1. Anti-Spam Honeypot Verification
if (!empty($_POST['website_hp'])) {
    // Bot detected; silently redirect to avoid giving feedback to spammers
    header('Location: admissions.php');
    exit;
}

// 2. CSRF Token Verification
if (!verifyCsrfToken()) {
    setFlash('error', 'Security verification expired or invalid. Please submit the form again.');
    header('Location: admissions.php#application-form');
    exit;
}

if (!verifyTurnstile($_POST['cf-turnstile-response'] ?? '')) {
    setFlash('error', 'Please complete the spam protection check and try again.');
    header('Location: admissions.php#application-form');
    exit;
}

// 3. Rate Limiting Check
if (!checkRateLimit('admission_submit', 5, 180)) {
    setFlash('error', 'Too many admission submissions from this browser. Please wait a few minutes before trying again.');
    header('Location: admissions.php#application-form');
    exit;
}
if (!checkDatabaseRateLimit('admission', 5, 60)) {
    setFlash('error', 'You have submitted the maximum number of applications for this hour. Please try again later.');
    header('Location: admissions.php#application-form');
    exit;
}

// 4. Admission Schedule & Window Enforcement
if (!isAdmissionOpen()) {
    setFlash('error', getAdmissionClosedMessage());
    header('Location: admissions.php#application-form');
    exit;
}

// 5. Input Sanitization & Validation
$fullName       = trim($_POST['fullName'] ?? '');
$dob            = trim($_POST['dob'] ?? '');
$gender         = trim($_POST['gender'] ?? '');
$grade          = trim($_POST['grade'] ?? '');
$parentContact  = trim($_POST['parentContact'] ?? '');
$parentName     = trim($_POST['parentName'] ?? '');
$parentEmail    = trim($_POST['parentEmail'] ?? '');
$previousSchool = trim($_POST['previousSchool'] ?? '');
$address        = trim($_POST['address'] ?? '');

$errors = [];

// Name validation (Nepali Unicode & English)
if (!validateNepaliName($fullName)) {
    $errors[] = 'Invalid student name. Please enter a valid name using English or Nepali Unicode letters.';
}
if (mb_strlen($fullName, 'UTF-8') > 100) {
    $errors[] = 'Student name must be 100 characters or fewer.';
}

// Date of birth validation
if (!validateDate($dob) || strtotime($dob) > time() || strtotime($dob) < strtotime('-25 years')) {
    $errors[] = 'Invalid date of birth. Please choose a valid date.';
}

// Gender validation
$validGenders = ['Male', 'Female', 'Other'];
if (!in_array($gender, $validGenders, true)) {
    $errors[] = 'Please select a valid gender option.';
}

// Grade validation & per-grade deadline check
$validGrades = ['PG', 'Nursery', 'LKG', 'UKG', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', '1', '2', '3', '4', '5', '6', '7', '8'];
if (!in_array($grade, $validGrades, true)) {
    $errors[] = 'Please select a valid grade.';
} elseif (!isGradeAdmissionOpen($grade)) {
    $errors[] = "Admissions for {$grade} are currently closed.";
}

$grade = normalizeGradeName($grade);

// Parent contact validation
if (!validateNepaliPhone($parentContact)) {
    $errors[] = 'Invalid parent mobile number. Must be a valid 10-digit Nepali number (e.g., 9851148834).';
}
$parentContactFormatted = formatPhoneNumber($parentContact);

// Parent Name (if provided)
if (!empty($parentName) && !validateNepaliName($parentName)) {
    $errors[] = 'Invalid parent/guardian name.';
}
if (mb_strlen($parentName, 'UTF-8') > 100) {
    $errors[] = 'Parent or guardian name must be 100 characters or fewer.';
}

// Parent Email (if provided)
if (!empty($parentEmail) && !validateEmail($parentEmail)) {
    $errors[] = 'Invalid email address provided.';
}
if (mb_strlen($parentEmail, 'UTF-8') > 120) {
    $errors[] = 'Parent email must be 120 characters or fewer.';
}
if (mb_strlen($previousSchool, 'UTF-8') > 150) {
    $errors[] = 'Previous school name must be 150 characters or fewer.';
}

// Address validation
if (mb_strlen($address, 'UTF-8') < 5) {
    $errors[] = 'Address is too short. Please provide a complete residential address.';
}
if (mb_strlen($address, 'UTF-8') > 500) {
    $errors[] = 'Address must be 500 characters or fewer.';
}

// Redirect back if validation failed
if (!empty($errors)) {
    setOldInput($_POST);
    foreach ($errors as $error) {
        setFlash('error', $error);
    }
    header('Location: admissions.php#application-form');
    exit;
}

// 5. Database Interaction (PDO Prepared Statements)
try {
    $pdo = getDB();

    // Check for duplicate recent submission (same student, DOB and parent phone within 2 hours)
    $dupStmt = $pdo->prepare("
        SELECT application_no, created_at 
        FROM admissions 
        WHERE full_name = :full_name 
          AND dob = :dob 
          AND parent_contact = :parent_contact 
          AND created_at >= NOW() - INTERVAL 2 HOUR
        LIMIT 1
    ");
    $dupStmt->execute([
        ':full_name'      => $fullName,
        ':dob'            => $dob,
        ':parent_contact' => $parentContactFormatted
    ]);
    $existing = $dupStmt->fetch();

    if ($existing) {
        $applicationNo = $existing['application_no'];
        $isDuplicateNotice = true;
    } else {
        // Generate unique application number
        $applicationNo = generateApplicationNo();

        $insertStmt = $pdo->prepare("
            INSERT INTO admissions (
                application_no, full_name, dob, gender, grade, 
                parent_name, parent_contact, parent_email, address, 
                previous_school, status, ip_address, created_at
            ) VALUES (
                :app_no, :full_name, :dob, :gender, :grade, 
                :parent_name, :parent_contact, :parent_email, :address, 
                :prev_school, 'pending', :ip_address, NOW()
            )
        ");

        $insertStmt->execute([
            ':app_no'         => $applicationNo,
            ':full_name'      => $fullName,
            ':dob'            => $dob,
            ':gender'         => $gender,
            ':grade'          => $grade,
            ':parent_name'    => !empty($parentName) ? $parentName : null,
            ':parent_contact' => $parentContactFormatted,
            ':parent_email'   => !empty($parentEmail) ? $parentEmail : null,
            ':address'        => $address,
            ':prev_school'    => !empty($previousSchool) ? $previousSchool : null,
            ':ip_address'     => getClientIp()
        ]);
        $newAdmissionId = (int)$pdo->lastInsertId();
        $isDuplicateNotice = false;

        // Send Email Notification to SCHOOL_EMAIL
        $siteUrl = rtrim((string)env('SITE_URL', ''), '/');
        $adminLink = $siteUrl . '/admin/admission_view.php?id=' . $newAdmissionId;
        $subject = "New Admission Application: " . $fullName . " (" . $grade . ") - " . $applicationNo;
        
        $emailHtml = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;'>
            <div style='background-color: #0f1f4b; color: #ffffff; padding: 20px; text-align: center;'>
                <h2 style='margin: 0; font-size: 1.3rem;'>" . htmlspecialchars(APP_NAME) . "</h2>
                <p style='margin: 5px 0 0; color: #ea580c; font-weight: bold;'>New Online Admission Application</p>
            </div>
            <div style='padding: 24px; color: #1e293b; line-height: 1.6;'>
                <p style='margin-top: 0;'>A new student admission application has just been received through the school website:</p>
                <table style='width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 0.95rem;'>
                    <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 8px 0; color: #64748b; width: 40%;'>Application Number:</td><td style='padding: 8px 0; font-weight: bold; color: #0f1f4b;'>" . htmlspecialchars($applicationNo) . "</td></tr>
                    <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 8px 0; color: #64748b;'>Student Full Name:</td><td style='padding: 8px 0; font-weight: bold;'>" . htmlspecialchars($fullName) . "</td></tr>
                    <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 8px 0; color: #64748b;'>Grade Applying For:</td><td style='padding: 8px 0; font-weight: bold; color: #ea580c;'>" . htmlspecialchars($grade) . "</td></tr>
                    <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 8px 0; color: #64748b;'>Date of Birth & Gender:</td><td style='padding: 8px 0;'>" . htmlspecialchars($dob) . " (" . htmlspecialchars($gender) . ")</td></tr>
                    <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 8px 0; color: #64748b;'>Parent/Guardian Name:</td><td style='padding: 8px 0;'>" . htmlspecialchars($parentName ?: 'Not specified') . "</td></tr>
                    <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 8px 0; color: #64748b;'>Parent Contact:</td><td style='padding: 8px 0; font-weight: bold;'>" . htmlspecialchars($parentContactFormatted) . "</td></tr>
                    <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 8px 0; color: #64748b;'>Parent Email:</td><td style='padding: 8px 0;'>" . htmlspecialchars($parentEmail ?: 'Not provided') . "</td></tr>
                    <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 8px 0; color: #64748b;'>Residential Address:</td><td style='padding: 8px 0;'>" . htmlspecialchars($address) . "</td></tr>
                    <tr><td style='padding: 8px 0; color: #64748b;'>Previous School:</td><td style='padding: 8px 0;'>" . htmlspecialchars($previousSchool ?: 'None') . "</td></tr>
                </table>
                <div style='text-align: center; margin-top: 25px;'>
                    <a href='" . htmlspecialchars($adminLink) . "' style='background-color: #0f1f4b; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>View in Admin Portal &rarr;</a>
                </div>
            </div>
            <div style='background-color: #f8fafc; padding: 12px; text-align: center; font-size: 0.8rem; color: #94a3b8; border-top: 1px solid #e2e8f0;'>
                Mount Excel Boarding School &bull; Automated System Notification
            </div>
        </div>
        ";
        if (canSendSubmissionNotification($pdo)) {
            sendSchoolNotification($subject, $emailHtml);
        } else {
            error_log('Admission notification skipped because the hourly email cap was reached.');
        }
    }

} catch (Exception $e) {
    error_log("Admission Submission Error: " . $e->getMessage());
    setFlash('error', 'We encountered an unexpected technical issue processing your application. Please try again or contact the school office directly at ' . SCHOOL_PHONE);
    header('Location: admissions.php#application-form');
    exit;
}

// 6. Display Clean, Accessible Branded Confirmation Page
$pageTitle = 'Application Submitted';
$activePage = 'admissions';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section-pad">
  <div class="container">
    <div class="confirmation-box">
      <div class="confirmation-icon">
        <i class="fas fa-check-circle"></i>
      </div>
      <h1 style="font-family: var(--font-heading); font-size: 2rem; color: var(--primary-800); margin-bottom: 12px;">
        <?= $isDuplicateNotice ? 'Application Already Received' : 'Application Successfully Submitted!' ?>
      </h1>
      
      <p style="font-size: 1.05rem; color: #475569; margin-bottom: 10px;">
        Thank you, <strong><?= e($fullName) ?></strong>. Your admission application has been registered with Mount Excel Boarding School.
      </p>

      <p style="font-size: 0.95rem; color: #64748b;">
        Please save your official application reference number:
      </p>

      <div class="confirmation-app-no">
        <?= e($applicationNo) ?>
      </div>

      <div style="background: #ffffff; border-radius: var(--radius-md); padding: 20px; text-align: left; margin: 25px 0; border: 1px solid var(--border-color); font-size: 0.95rem;">
        <h4 style="font-family: var(--font-heading); color: var(--primary-800); margin-bottom: 10px;">
          <i class="fas fa-info-circle" style="color: var(--accent-gold);"></i> What happens next?
        </h4>
        <ol style="padding-left: 20px; color: #334155; line-height: 1.7;">
          <li>Our admissions office will review your application within 2 working days.</li>
          <li>We will contact you on <strong><?= e($parentContactFormatted) ?></strong> to confirm your school visit or schedule an interaction.</li>
          <li>Please bring the child's birth certificate and previous grade reports (if applicable) when visiting the school.</li>
        </ol>
      </div>

      <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; margin-top: 25px;">
        <button onclick="window.print()" class="btn btn-outline-white" style="color: var(--primary-800); border-color: var(--primary-800);">
          <i class="fas fa-print"></i> Print Confirmation
        </button>
        <a href="index.php" class="btn btn-primary">
          <i class="fas fa-home"></i> Return to Homepage
        </a>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
