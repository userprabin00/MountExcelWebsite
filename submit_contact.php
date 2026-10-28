<?php
/**
 * Contact Form Submission Handler
 * Mount Excel Boarding School
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/mail.php';

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.php');
    exit;
}

// 1. Anti-Spam Honeypot Verification
if (!empty($_POST['contact_hp'])) {
    // Bot detected; silently redirect
    header('Location: contact.php');
    exit;
}

// 2. CSRF Token Verification
if (!verifyCsrfToken()) {
    setFlash('error', 'Security token expired or invalid. Please submit your message again.');
    header('Location: contact.php');
    exit;
}

if (!verifyTurnstile($_POST['cf-turnstile-response'] ?? '')) {
    setFlash('error', 'Please complete the spam protection check and try again.');
    header('Location: contact.php');
    exit;
}

// 3. Rate Limiting Check (Max 5 submissions per 3 minutes)
if (!checkRateLimit('contact_submit', 5, 180)) {
    setFlash('error', 'You have submitted multiple messages recently. Please wait a few moments before trying again.');
    header('Location: contact.php');
    exit;
}
if (!checkDatabaseRateLimit('contact', 5, 60)) {
    setFlash('error', 'You have sent the maximum number of messages for this hour. Please try again later.');
    header('Location: contact.php');
    exit;
}

// 4. Sanitize and Validate Inputs
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

$errors = [];

if (!validateNepaliName($name)) {
    $errors[] = 'Please enter a valid full name (letters only).';
}

if (!validateEmail($email)) {
    $errors[] = 'Please provide a valid email address.';
}
if (mb_strlen($email, 'UTF-8') > 120) {
    $errors[] = 'Email address must be 120 characters or fewer.';
}

if (mb_strlen($name, 'UTF-8') > 100) {
    $errors[] = 'Your name must be 100 characters or fewer.';
}
if (mb_strlen($subject, 'UTF-8') > 150) {
    $errors[] = 'The subject must be 150 characters or fewer.';
}
if (mb_strlen($message, 'UTF-8') > 5000) {
    $errors[] = 'The message must be 5,000 characters or fewer.';
}

if (!empty($phone) && !validateNepaliPhone($phone)) {
    $errors[] = 'Please provide a valid Nepali contact phone number.';
}

if (mb_strlen($message, 'UTF-8') < 10) {
    $errors[] = 'Your message is too brief. Please enter at least 10 characters.';
}

if (!empty($errors)) {
    setOldInput($_POST);
    foreach ($errors as $err) {
        setFlash('error', $err);
    }
    header('Location: contact.php');
    exit;
}

// 5. Save to Database using PDO Prepared Statements
try {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO contact_messages (name, email, phone, subject, message, status, ip_address, created_at)
        VALUES (:name, :email, :phone, :subject, :message, 'unread', :ip_address, NOW())
    ");

    $stmt->execute([
        ':name'       => $name,
        ':email'      => $email,
        ':phone'      => !empty($phone) ? formatPhoneNumber($phone) : null,
        ':subject'    => !empty($subject) ? $subject : 'Website Inquiry',
        ':message'    => $message,
        ':ip_address' => getClientIp()
    ]);

    $newMessageId = (int)$pdo->lastInsertId();

    // Send Email Notification to SCHOOL_EMAIL
    $siteUrl = rtrim((string)env('SITE_URL', ''), '/');
    $adminLink = $siteUrl . '/admin/messages.php?id=' . $newMessageId;
    $subjectLine = !empty($subject) ? $subject : 'Website Inquiry';
    $emailSubject = "New Website Contact Inquiry: " . $name . " - " . $subjectLine;

    $emailHtml = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;'>
        <div style='background-color: #0f1f4b; color: #ffffff; padding: 20px; text-align: center;'>
            <h2 style='margin: 0; font-size: 1.3rem;'>" . htmlspecialchars(APP_NAME) . "</h2>
            <p style='margin: 5px 0 0; color: #ea580c; font-weight: bold;'>New Website Contact Inquiry</p>
        </div>
        <div style='padding: 24px; color: #1e293b; line-height: 1.6;'>
            <p style='margin-top: 0;'>A new message has been submitted through the school contact form:</p>
            <table style='width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 0.95rem;'>
                <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 8px 0; color: #64748b; width: 35%;'>Sender Name:</td><td style='padding: 8px 0; font-weight: bold;'>" . htmlspecialchars($name) . "</td></tr>
                <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 8px 0; color: #64748b;'>Sender Email:</td><td style='padding: 8px 0; font-weight: bold; color: #0f1f4b;'><a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></td></tr>
                <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 8px 0; color: #64748b;'>Contact Phone:</td><td style='padding: 8px 0;'>" . htmlspecialchars($phone ?: 'Not provided') . "</td></tr>
                <tr style='border-bottom: 1px solid #e2e8f0;'><td style='padding: 8px 0; color: #64748b;'>Subject:</td><td style='padding: 8px 0; font-weight: bold; color: #ea580c;'>" . htmlspecialchars($subjectLine) . "</td></tr>
            </table>
            <div style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; margin: 20px 0;'>
                <p style='margin: 0 0 8px; font-weight: bold; color: #475569;'>Message Content:</p>
                <p style='margin: 0; white-space: pre-wrap;'>" . htmlspecialchars($message) . "</p>
            </div>
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
        sendSchoolNotification($emailSubject, $emailHtml, '', ['email' => $email, 'name' => $name]);
    } else {
        error_log('Contact notification skipped because the hourly email cap was reached.');
    }

    setFlash('success', 'Thank you, ' . $name . '! Your message has been received. Our administration office will get back to you shortly.');
    header('Location: contact.php');
    exit;

} catch (Exception $e) {
    error_log("Contact submission error: " . $e->getMessage());
    setFlash('error', 'We encountered an error processing your inquiry. Please try again or call our office at ' . SCHOOL_PHONE);
    header('Location: contact.php');
    exit;
}
