<?php
/**
 * School Email Notification Service using PHPMailer
 * Mount Excel Boarding School
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send an email notification to SCHOOL_EMAIL using SMTP settings from .env
 * Fail-safe: if sending fails, logs the error and returns false without breaking user flow.
 *
 * @param string $subject
 * @param string $htmlContent
 * @param string $plainTextContent
 * @return bool
 */
function sendSchoolNotification($subject, $htmlContent, $plainTextContent = '', $replyTo = null) {
    $toEmail = SCHOOL_EMAIL;
    if (empty($toEmail)) {
        error_log("Email notification skipped: SCHOOL_EMAIL is not configured.");
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $smtpHost = env('SMTP_HOST', '');
        $smtpPort = (int)env('SMTP_PORT', 587);
        $smtpUser = env('SMTP_USER', '');
        $smtpPass = env('SMTP_PASS', '');
        $smtpSecure = strtolower((string)env('SMTP_SECURE', 'tls'));
        $fromEmail = env('MAIL_FROM_ADDRESS', $toEmail);
        $fromName  = env('MAIL_FROM_NAME', APP_NAME);

        // If SMTP credentials or host provided, configure SMTP
        if (!empty($smtpHost) && !empty($smtpUser) && !empty($smtpPass)) {
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPass;
            $mail->Port       = $smtpPort;
            $mail->Timeout    = 8; // Prevent hanging web request

            if ($smtpSecure === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($smtpSecure === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPSecure = false;
                $mail->SMTPAutoTLS = false;
            }
        } else {
            // Fallback to PHP mail() if SMTP credentials are not yet configured in .env
            $mail->isMail();
        }

        // CharSet & Encodings
        $mail->CharSet = 'UTF-8';

        // Sender & Recipient
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail, APP_NAME . ' Administration');
        if (is_array($replyTo) && !empty($replyTo['email'])) {
            $mail->addReplyTo($replyTo['email'], $replyTo['name'] ?? '');
        }

        // Email Body
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlContent;
        $mail->AltBody = !empty($plainTextContent) ? $plainTextContent : strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlContent));

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("PHPMailer Notification Failed: {$mail->ErrorInfo} | Exception: " . $e->getMessage());
        return false;
    } catch (\Throwable $t) {
        error_log("PHPMailer Notification Error: " . $t->getMessage());
        return false;
    }
}
