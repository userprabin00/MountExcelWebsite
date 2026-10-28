<?php
/**
 * Application Configuration & Security Bootstrapper
 * Mount Excel Boarding School
 */

// Prevent multiple inclusions
if (defined('MEBS_APP_INIT')) {
    return;
}
define('MEBS_APP_INIT', true);

// Determine root path
define('ROOT_PATH', dirname(__DIR__));

/**
 * Simple .env parser to avoid heavy external dependencies
 */
function loadEnv($path) {
    if (!file_exists($path) || !is_readable($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Remove wrapping quotes if present
            if (preg_match('/^"(.*)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                $value = $matches[1];
            }
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Load .env at the very beginning
loadEnv(ROOT_PATH . '/.env');

// Helper to get env variable with fallback
function env($key, $default = null) {
    $val = getenv($key);
    if ($val === false) {
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
    return $val;
}

// Environment definitions
define('APP_ENV', env('APP_ENV', 'development')); // 'production' or 'development'
define('APP_NAME', env('APP_NAME', 'Mount Excel Boarding School'));
define('APP_MOTTO', 'School for Excellence');
define('APP_ESTD', '2015');

// Application Timezone (Asia/Kathmandu UTC+05:45)
date_default_timezone_set('Asia/Kathmandu');
define('APP_TIMEZONE', 'Asia/Kathmandu');

// Database configuration
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'mount_excel_school'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

// School Contact details
define('SCHOOL_EMAIL', env('SCHOOL_EMAIL', 'mountexcel.principal@gmail.com'));
define('SCHOOL_PHONE', env('SCHOOL_PHONE', '+977-9851148834'));
define('SCHOOL_ADDRESS', 'Indrawati Municipality 12, Bhimtar, Sindhupalchowk, Nepal');
define('SCHOOL_FACEBOOK', 'https://www.facebook.com/share/1CbR8iAh1s/');
define('SCHOOL_INSTAGRAM', 'https://www.instagram.com/mebs_134?igsh=cGt0NmN0M2hpMzV6');
define('SCHOOL_TIKTOK', 'https://www.tiktok.com/@mebs400?_r=1&_t=ZS-9Au8dWLSVYW');

// Setup / Secret keys
define('APP_KEY', env('APP_KEY', 'mount_excel_secure_key_change_in_production_2026'));

// HTTPS Detection
$remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
$trustedProxies = array_filter(array_map('trim', explode(',', (string)env('TRUSTED_PROXIES', ''))));
$proxyHttps = filter_var($remoteIp, FILTER_VALIDATE_IP) && in_array($remoteIp, $trustedProxies, true)
    && strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0])) === 'https';
$isHttps = ((!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') || $proxyHttps || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443));

// Production Hardening: Force HTTPS in production (non-CLI)
if (APP_ENV === 'production' && !$isHttps && php_sapi_name() !== 'cli') {
    $configuredSite = parse_url((string)env('SITE_URL', 'https://mountexcel.edu.np'));
    $host = $configuredSite['host'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
    if (!empty($configuredSite['port'])) {
        $host .= ':' . (int)$configuredSite['port'];
    }
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    header('Location: https://' . $host . $uri, true, 301);
    exit;
}

// Secure Session Configuration
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    $sessionLifetime = (int)env('SESSION_LIFETIME', 7200);

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', (string)$sessionLifetime);

    // Enforce secure cookies in production or whenever HTTPS is active
    if (APP_ENV === 'production' || $isHttps) {
        ini_set('session.cookie_secure', '1');
    }

    session_name('MEBS_SESSID');
    session_start();
}

// Move backups/ and logs/ outside web root (with secure fallback)
$storageBase = dirname(ROOT_PATH) . DIRECTORY_SEPARATOR . 'mount_excel_storage';
$defaultLogPath = $storageBase . DIRECTORY_SEPARATOR . 'logs';
$defaultBackupPath = $storageBase . DIRECTORY_SEPARATOR . 'backups';

$configuredLogPath = env('LOG_PATH');
$targetLogDir = !empty($configuredLogPath) ? $configuredLogPath : $defaultLogPath;
if (!is_dir($targetLogDir)) {
    @mkdir($targetLogDir, 0750, true);
}
if (!is_dir($targetLogDir) || !is_writable($targetLogDir)) {
    $targetLogDir = ROOT_PATH . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($targetLogDir)) {
        @mkdir($targetLogDir, 0750, true);
    }
}
define('LOG_DIR', $targetLogDir);

$configuredBackupPath = env('BACKUP_PATH');
$targetBackupDir = !empty($configuredBackupPath) ? $configuredBackupPath : $defaultBackupPath;
if (!is_dir($targetBackupDir)) {
    @mkdir($targetBackupDir, 0750, true);
}
if (!is_dir($targetBackupDir) || !is_writable($targetBackupDir)) {
    $targetBackupDir = ROOT_PATH . DIRECTORY_SEPARATOR . 'backups';
    if (!is_dir($targetBackupDir)) {
        @mkdir($targetBackupDir, 0750, true);
    }
}
define('BACKUP_DIR', $targetBackupDir);

// Protect internal directories if they exist inside web root
foreach ([ROOT_PATH . '/logs', ROOT_PATH . '/backups'] as $internalDir) {
    if (is_dir($internalDir)) {
        $htFile = $internalDir . '/.htaccess';
        if (!file_exists($htFile)) {
            @file_put_contents($htFile, "Require all denied\nDeny from all\n");
        }
    }
}

// Error handling based on environment
if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

ini_set('log_errors', '1');
ini_set('error_log', LOG_DIR . DIRECTORY_SEPARATOR . 'app_error.log');

// Send standard security headers (only if headers not already sent)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Permissions-Policy: geolocation=(), camera=(), microphone=()");
    header("Content-Security-Policy-Report-Only: default-src 'self'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' fonts.googleapis.com cdnjs.cloudflare.com; font-src 'self' fonts.gstatic.com cdnjs.cloudflare.com; script-src 'self' cdnjs.cloudflare.com challenges.cloudflare.com; connect-src 'self' challenges.cloudflare.com; frame-src 'self' www.google.com challenges.cloudflare.com; frame-ancestors 'self'");
    if (APP_ENV === 'production' && $isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}
