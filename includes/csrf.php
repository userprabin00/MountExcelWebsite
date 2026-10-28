<?php
/**
 * CSRF Protection and Flash Messaging
 * Mount Excel Boarding School
 */

require_once __DIR__ . '/config.php';

/**
 * Generate or retrieve the current session CSRF token
 */
function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a CSRF hidden input field for forms
 */
function csrfField() {
    $token = htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Verify submitted CSRF token
 */
function verifyCsrfToken($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    }
    
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'][$type][] = $message;
}

/**
 * Check if flash messages exist
 */
function hasFlash($type = null) {
    if ($type === null) {
        return !empty($_SESSION['flash']);
    }
    return !empty($_SESSION['flash'][$type]);
}

/**
 * Get and clear flash messages
 */
function getFlash($type = null) {
    if ($type === null) {
        $flashes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flashes;
    }
    
    $messages = $_SESSION['flash'][$type] ?? [];
    unset($_SESSION['flash'][$type]);
    return $messages;
}

/**
 * Render flash messages as accessible alert banners
 */
function renderFlashMessages() {
    $html = '';
    $types = ['success' => 'alert-success', 'error' => 'alert-error', 'warning' => 'alert-warning', 'info' => 'alert-info'];
    
    foreach ($types as $type => $cssClass) {
        $messages = getFlash($type);
        if (!empty($messages)) {
            $html .= '<div class="alert ' . $cssClass . '" role="alert">';
            foreach ($messages as $msg) {
                $html .= '<p>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</p>';
            }
            $html .= '</div>';
        }
    }
    return $html;
}
