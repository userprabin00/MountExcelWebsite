<?php
/**
 * Core Helper Functions & Validators
 * Mount Excel Boarding School
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Contextual HTML escape helper
 */
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate Person Name (Supports English letters, Devanagari script, dots, spaces, hyphens)
 * E.g., "Aarav Sharma", "राम बहादुर श्रेष्ठ", "K.C. Gurung", "Om Rai"
 */
function validateNepaliName($name) {
    $name = trim($name);
    if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 100) {
        return false;
    }
    // Allow Latin letters, Devanagari Unicode block (\p{Devanagari}), spaces, dots, hyphens, and apostrophes
    // \x{0900}-\x{097F} is Devanagari in PCRE UTF-8
    return (bool)preg_match('/^[\p{L}\p{Devanagari}\s\.\'\-]+$/u', $name);
}

/**
 * Validate Nepali Phone Number
 * Accepts:
 * - 10-digit mobile starting with 98, 97, 96 (e.g., 9851148834, 9741234567, 9612345678)
 * - With country code +977 or 977 (e.g., +977-9851148834, +9779851148834)
 * - Nepali landline numbers (e.g., 01-4412345, 011-661234)
 */
function validateNepaliPhone($phone) {
    $phone = trim($phone);
    // Remove common separators
    $clean = preg_replace('/[\s\-\(\)]/', '', $phone);
    
    // Check with +977 or 977 prefix
    if (preg_match('/^(\+?977)?(9[678]\d{8}|0\d{1,2}\d{6,7})$/', $clean)) {
        return true;
    }
    return false;
}

/**
 * Standardize phone number for storage
 */
function formatPhoneNumber($phone) {
    $phone = trim($phone);
    $clean = preg_replace('/[\s\-\(\)]/', '', $phone);
    if (preg_match('/^\+?977(9[678]\d{8})$/', $clean, $m)) {
        return '+977-' . $m[1];
    }
    if (preg_match('/^(9[678]\d{8})$/', $clean, $m)) {
        return '+977-' . $m[1];
    }
    return $phone;
}

/**
 * Validate Email
 */
function validateEmail($email) {
    $email = trim($email);
    if (empty($email)) {
        return false;
    }
    return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate Date (YYYY-MM-DD)
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Generate Unique School Application Reference Number
 * Format: MEBS-YYYY-XXXX (e.g. MEBS-2026-A8F2)
 */
function generateApplicationNo() {
    $year = date('Y');
    $bytes = random_bytes(3);
    $hex = strtoupper(bin2hex($bytes));
    return "MEBS-{$year}-{$hex}";
}

/**
 * Get Client IP Address Safely
 */
function getClientIp() {
    $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $trustedProxies = array_filter(array_map('trim', explode(',', (string)env('TRUSTED_PROXIES', ''))));
    if (filter_var($remoteIp, FILTER_VALIDATE_IP) && in_array($remoteIp, $trustedProxies, true) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        foreach (explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']) as $forwardedIp) {
            $forwardedIp = trim($forwardedIp);
            if (filter_var($forwardedIp, FILTER_VALIDATE_IP)) {
                return $forwardedIp;
            }
        }
    }
    return filter_var($remoteIp, FILTER_VALIDATE_IP) ? $remoteIp : '127.0.0.1';
}

/** Apply a database-backed limit per client IP and action. */
function checkDatabaseRateLimit($action, $maxAttempts, $windowMinutes) {
    try {
        $pdo = getDB();
        $purge = $pdo->prepare("DELETE FROM rate_limits WHERE created_at < NOW() - INTERVAL 1 DAY");
        $purge->execute();
        $windowMinutes = max(1, (int)$windowMinutes);
        $count = $pdo->prepare("SELECT COUNT(*) FROM rate_limits WHERE ip = :ip AND action = :action AND created_at >= NOW() - INTERVAL {$windowMinutes} MINUTE");
        $count->bindValue(':ip', getClientIp());
        $count->bindValue(':action', $action);
        $count->execute();
        if ((int)$count->fetchColumn() >= $maxAttempts) {
            return false;
        }
        $insert = $pdo->prepare("INSERT INTO rate_limits (ip, action, created_at) VALUES (:ip, :action, NOW())");
        $insert->execute([':ip' => getClientIp(), ':action' => $action]);
        return true;
    } catch (Exception $e) {
        error_log('Database rate limit unavailable: ' . $e->getMessage());
        return false;
    }
}

/** Verify a Cloudflare Turnstile response; fail open only if verification is unreachable. */
function verifyTurnstile($responseToken) {
    $secret = (string)env('TURNSTILE_SECRET', '');
    if ($secret === '') {
        return true;
    }
    if (!function_exists('curl_init')) {
        error_log('Turnstile verification unavailable: cURL extension is missing.');
        return true;
    }
    $curl = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['secret' => $secret, 'response' => $responseToken, 'remoteip' => getClientIp()]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 5
    ]);
    $body = curl_exec($curl);
    $error = curl_error($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($body === false || $status === 0 || $status >= 500) {
        error_log('Turnstile verification service unreachable: ' . $error);
        return true;
    }
    $result = json_decode($body, true);
    return is_array($result) && !empty($result['success']);
}

/** Check whether notification email volume is still within the hourly cap. */
function canSendSubmissionNotification($pdo) {
    try {
        $stmt = $pdo->query("SELECT (SELECT COUNT(*) FROM admissions WHERE created_at >= NOW() - INTERVAL 1 HOUR) + (SELECT COUNT(*) FROM contact_messages WHERE created_at >= NOW() - INTERVAL 1 HOUR)");
        return (int)$stmt->fetchColumn() < 30;
    } catch (Exception $e) {
        error_log('Notification volume check failed: ' . $e->getMessage());
        return false;
    }
}

/**
 * Session & Timestamp Rate Limiter
 */
function checkRateLimit($actionKey, $maxAttempts = 5, $decaySeconds = 60) {
    $now = time();
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = [];
    }
    
    if (!isset($_SESSION['rate_limits'][$actionKey])) {
        $_SESSION['rate_limits'][$actionKey] = [];
    }
    
    // Filter out expired timestamps
    $_SESSION['rate_limits'][$actionKey] = array_filter(
        $_SESSION['rate_limits'][$actionKey],
        function($timestamp) use ($now, $decaySeconds) {
            return ($now - $timestamp) < $decaySeconds;
        }
    );
    
    if (count($_SESSION['rate_limits'][$actionKey]) >= $maxAttempts) {
        return false; // Rate limit exceeded
    }
    
    $_SESSION['rate_limits'][$actionKey][] = $now;
    return true;
}

/**
 * Log Administrative / Audit Action
 */
function logActivity($action, $details = null, $adminId = null) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (admin_id, action, details, ip_address, created_at)
            VALUES (:admin_id, :action, :details, :ip_address, NOW())
        ");
        $stmt->execute([
            ':admin_id'   => $adminId,
            ':action'     => $action,
            ':details'    => is_array($details) ? json_encode($details) : (string)$details,
            ':ip_address' => getClientIp()
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Safe CSV Field Escaping to prevent CSV Injection / Formula Injection
 * Prepends single quote if field starts with =, +, -, @, \t, \r
 */
function escapeCsvCell($cell) {
    $cell = (string)$cell;
    if (isset($cell[0]) && in_array($cell[0], ['=', '+', '-', '@', "\t", "\r"])) {
        return "'" . $cell;
    }
    return $cell;
}

/**
 * Flash old input for user-friendly form re-population on error
 */
function setOldInput(array $data) {
    // Exclude password and sensitive fields
    unset($data['password'], $data['confirm_password'], $data['csrf_token'], $data['website_hp'], $data['contact_hp']);
    $_SESSION['old_input'] = $data;
}

/**
 * Retrieve flashed old input value
 */
function old($key, $default = '') {
    return $_SESSION['old_input'][$key] ?? $default;
}

/**
 * Clear old input from session
 */
function clearOldInput() {
    unset($_SESSION['old_input']);
}

/**
 * ==============================================================================
 * Application Settings & Admission Schedule Logic
 * Timezone: Asia/Kathmandu (UTC+05:45)
 * ==============================================================================
 */

// Cache of settings loaded during this request
$GLOBALS['_MEBS_SETTINGS_CACHE'] = null;

/**
 * Fetch a single setting by key with fallback default
 */
function getSetting($key, $default = null) {
    global $_MEBS_SETTINGS_CACHE;
    if ($_MEBS_SETTINGS_CACHE === null) {
        $_MEBS_SETTINGS_CACHE = getAllSettings();
    }
    return $_MEBS_SETTINGS_CACHE[$key] ?? $default;
}

/**
 * Fetch all settings as key => value array
 */
function getAllSettings() {
    global $_MEBS_SETTINGS_CACHE;
    $defaults = [
        'admission_open'           => 'on',
        'admission_start_date'     => '2026-12-15',
        'admission_end_date'       => '2027-04-13',
        'admission_closed_message' => 'Admission is currently closed. Applications for the 2084 session open on 15 December 2026. For queries call ' . SCHOOL_PHONE . '.',
        'admission_grade_end_dates' => json_encode([
            'Play Group' => '2027-04-13',
            'Nursery'    => '2027-04-13',
            'LKG'        => '2027-04-13',
            'UKG'        => '2027-04-13',
            'Grade 1'    => '2027-04-13',
            'Grade 2'    => '2027-04-13',
            'Grade 3'    => '2027-04-13',
            'Grade 4'    => '2027-04-13',
            'Grade 5'    => '2027-04-30',
            'Grade 6'    => '2027-04-30',
            'Grade 7'    => '2027-04-30',
            'Grade 8'    => '2027-04-30'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    ];

    try {
        $pdo = getDB();
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $dbRows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        return array_merge($defaults, $dbRows ?: []);
    } catch (Exception $e) {
        return $defaults;
    }
}

/**
 * Update or insert a setting
 */
function updateSetting($key, $value) {
    global $_MEBS_SETTINGS_CACHE;
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value, created_at, updated_at)
            VALUES (:key, :val, NOW(), NOW())
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ");
        $stmt->execute([':key' => $key, ':val' => $value]);
        if (is_array($_MEBS_SETTINGS_CACHE)) {
            $_MEBS_SETTINGS_CACHE[$key] = $value;
        }
        return true;
    } catch (Exception $e) {
        error_log("Failed to update setting {$key}: " . $e->getMessage());
        return false;
    }
}

/**
 * Normalized grade names map (handles short codes and numbers)
 */
function normalizeGradeName($grade) {
    $grade = trim((string)$grade);
    $map = [
        'PG' => 'Play Group',
        'Play Group' => 'Play Group',
        'Playgroup' => 'Play Group',
        'Nursery' => 'Nursery',
        'LKG' => 'LKG',
        'UKG' => 'UKG',
        'KG' => 'UKG',
        '1' => 'Grade 1', 'Grade 1' => 'Grade 1', 'Class 1' => 'Grade 1',
        '2' => 'Grade 2', 'Grade 2' => 'Grade 2', 'Class 2' => 'Grade 2',
        '3' => 'Grade 3', 'Grade 3' => 'Grade 3', 'Class 3' => 'Grade 3',
        '4' => 'Grade 4', 'Grade 4' => 'Grade 4', 'Class 4' => 'Grade 4',
        '5' => 'Grade 5', 'Grade 5' => 'Grade 5', 'Class 5' => 'Grade 5',
        '6' => 'Grade 6', 'Grade 6' => 'Grade 6', 'Class 6' => 'Grade 6',
        '7' => 'Grade 7', 'Grade 7' => 'Grade 7', 'Class 7' => 'Grade 7',
        '8' => 'Grade 8', 'Grade 8' => 'Grade 8', 'Class 8' => 'Grade 8',
    ];
    return $map[$grade] ?? $grade;
}

/**
 * Get parsed per-grade end dates
 */
function getGradeEndDates() {
    $raw = getSetting('admission_grade_end_dates');
    $parsed = !empty($raw) ? json_decode($raw, true) : null;
    if (!is_array($parsed)) {
        $parsed = [
            'Play Group' => '2027-04-13',
            'Nursery'    => '2027-04-13',
            'LKG'        => '2027-04-13',
            'UKG'        => '2027-04-13',
            'Grade 1'    => '2027-04-13',
            'Grade 2'    => '2027-04-13',
            'Grade 3'    => '2027-04-13',
            'Grade 4'    => '2027-04-13',
            'Grade 5'    => '2027-04-30',
            'Grade 6'    => '2027-04-30',
            'Grade 7'    => '2027-04-30',
            'Grade 8'    => '2027-04-30'
        ];
    }
    return $parsed;
}

/**
 * Check whether admissions are open overall
 * Uses Asia/Kathmandu timezone (UTC+05:45)
 * End date includes the entire day (up to 23:59:59)
 *
 * @param DateTimeInterface|string|null $atTime Optional target time to evaluate (defaults to now)
 * @return bool
 */
function isAdmissionOpen($atTime = null) {
    $tz = new DateTimeZone('Asia/Kathmandu');
    if ($atTime instanceof DateTimeInterface) {
        $now = (clone $atTime)->setTimezone($tz);
    } elseif (is_string($atTime) && !empty($atTime)) {
        $now = new DateTime($atTime, $tz);
    } else {
        $now = new DateTime('now', $tz);
    }

    $isOpen = getSetting('admission_open', 'on');
    if ($isOpen !== 'on' && $isOpen !== '1' && $isOpen !== true) {
        return false;
    }

    // Check Start Date (effective from 00:00:00 Kathmandu time)
    $startDateStr = getSetting('admission_start_date', '2026-12-15');
    if (!empty($startDateStr)) {
        $startDate = new DateTime($startDateStr . ' 00:00:00', $tz);
        if ($now < $startDate) {
            return false;
        }
    }

    // Check Global End Date (includes the whole day until 23:59:59 Kathmandu time)
    // If any per-grade end date is later than the global end date, admissions overall remain open
    // until the latest grade end date.
    $endDateStr = getSetting('admission_end_date', '2027-04-13');
    $latestEndStr = $endDateStr;
    $gradeEndDates = getGradeEndDates();
    foreach ($gradeEndDates as $gDate) {
        if (!empty($gDate) && $gDate > $latestEndStr) {
            $latestEndStr = $gDate;
        }
    }

    if (!empty($latestEndStr)) {
        $latestEndDate = new DateTime($latestEndStr . ' 23:59:59', $tz);
        if ($now > $latestEndDate) {
            return false;
        }
    }

    return true;
}

/**
 * Check whether admissions are open for a specific grade
 * Uses Asia/Kathmandu timezone (UTC+05:45)
 * End date includes the entire day (up to 23:59:59)
 *
 * @param string $grade
 * @param DateTimeInterface|string|null $atTime Optional target time
 * @return bool
 */
function isGradeAdmissionOpen($grade, $atTime = null) {
    if (!isAdmissionOpen($atTime)) {
        return false;
    }

    $tz = new DateTimeZone('Asia/Kathmandu');
    if ($atTime instanceof DateTimeInterface) {
        $now = (clone $atTime)->setTimezone($tz);
    } elseif (is_string($atTime) && !empty($atTime)) {
        $now = new DateTime($atTime, $tz);
    } else {
        $now = new DateTime('now', $tz);
    }

    $canonicalGrade = normalizeGradeName($grade);
    $gradeEndDates = getGradeEndDates();
    $globalEndStr = getSetting('admission_end_date', '2027-04-13');

    // Look for exact canonical match, or match in gradeEndDates
    $gradeEndStr = $gradeEndDates[$canonicalGrade] 
        ?? $gradeEndDates[$grade] 
        ?? $globalEndStr;

    if (!empty($gradeEndStr)) {
        $gradeEndDate = new DateTime($gradeEndStr . ' 23:59:59', $tz);
        if ($now > $gradeEndDate) {
            return false;
        }
    }

    return true;
}

/**
 * Get admission closed notification message
 */
function getAdmissionClosedMessage() {
    return getSetting(
        'admission_closed_message',
        'Admission is currently closed. Applications for the 2084 session open on 15 December 2026. For queries call ' . SCHOOL_PHONE . '.'
    );
}

/**
 * Get all supported grades with their open/closed status and end dates
 */
function getGradesAvailability($atTime = null) {
    $standardGrades = [
        'Play Group', 'Nursery', 'LKG', 'UKG',
        'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4',
        'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8'
    ];
    $gradeEndDates = getGradeEndDates();
    $globalEndStr = getSetting('admission_end_date', '2027-04-13');

    $result = [];
    foreach ($standardGrades as $grade) {
        $endDate = $gradeEndDates[$grade] ?? $globalEndStr;
        $isOpen = isGradeAdmissionOpen($grade, $atTime);
        $result[$grade] = [
            'grade'    => $grade,
            'end_date' => $endDate,
            'is_open'  => $isOpen
        ];
    }
    return $result;
}

