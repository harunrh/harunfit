<?php
/**
 * Security Functions Library
 * 
 * This file contains all security-related functions used throughout the application.
 * 
 * Security Controls Implemented:
 * 1. CSRF Protection
 * 2. XSS Prevention (Input Sanitization & Output Encoding)
 * 3. Input Validation
 * 4. Session Security
 * 5. Rate Limiting
 * 6. Security Logging
 */

// Prevent direct access
if (!defined('HARUNFIT_SECURE')) {
    die('Direct access not permitted');
}

// ============================================================================
// 1. SESSION SECURITY
// ============================================================================

/**
 * Start secure PHP session with hardened settings
 * 
 * Security Features:
 * - HttpOnly cookies (prevents JavaScript access)
 * - Secure flag (HTTPS only in production)
 * - SameSite=Strict (CSRF protection)
 * - Session regeneration
 */
function startSecureSession() {
    // Don't start if session already active
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    
    // Configure session security settings
    $secure = (ENVIRONMENT === 'production'); // HTTPS only in production
    
    ini_set('session.cookie_httponly', 1);  // Prevent JavaScript access
    ini_set('session.cookie_secure', $secure ? 1 : 0);  // HTTPS only
    ini_set('session.cookie_samesite', 'Strict');  // CSRF protection
    ini_set('session.use_strict_mode', 1);  // Reject uninitialized session IDs
    ini_set('session.use_only_cookies', 1);  // Don't allow session ID in URL
    
    // Start session
    session_start();
    
    // Regenerate session ID periodically (every 30 minutes)
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// ============================================================================
// 2. CSRF PROTECTION
// ============================================================================

/**
 * Generate CSRF token for form protection
 * 
 * @return string CSRF token
 */
function generateCSRFToken() {
    startSecureSession();
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token from form submission
 * 
 * @param string $token Token to validate
 * @return bool True if valid, false otherwise
 */
function validateCSRFToken($token) {
    startSecureSession();
    
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    
    // Use hash_equals to prevent timing attacks
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validate CSRF token from HTTP header (for AJAX requests)
 * 
 * @return bool True if valid, false otherwise
 */
function validateCSRFHeader() {
    startSecureSession();
    
    // Check for CSRF token in custom header
    $headers = getallheaders();
    $token = $headers['X-CSRF-Token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    
    if (!$token) {
        return false;
    }
    
    return validateCSRFToken($token);
}

// ============================================================================
// 3. XSS PREVENTION
// ============================================================================

/**
 * Sanitize user input to prevent XSS attacks
 * 
 * @param mixed $data Input data (string or array)
 * @param string $type Type of sanitization ('string', 'email', 'int', 'url')
 * @return mixed Sanitized data
 */
function sanitizeInput($data, $type = 'string') {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitizeInput($value, $type);
        }
        return $data;
    }
    
    // Trim whitespace
    $data = trim($data);
    
    // Remove null bytes
    $data = str_replace(chr(0), '', $data);
    
    // Type-specific sanitization
    switch ($type) {
        case 'email':
            return filter_var($data, FILTER_SANITIZE_EMAIL);
            
        case 'int':
            return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
            
        case 'float':
            return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            
        case 'url':
            return filter_var($data, FILTER_SANITIZE_URL);
            
        case 'string':
        default:
            // Remove HTML and PHP tags
            $data = strip_tags($data);
            
            // Convert special characters to HTML entities
            $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            return $data;
    }
}

/**
 * Encode output for safe display in HTML
 * Use this when displaying user-generated content
 * 
 * @param string $data Data to encode
 * @return string Encoded data
 */
function encodeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Encode output for JavaScript context
 * 
 * @param string $data Data to encode
 * @return string JSON-encoded data
 */
function encodeForJS($data) {
    return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

// ============================================================================
// 4. INPUT VALIDATION
// ============================================================================

/**
 * Validate email address
 * 
 * @param string $email Email to validate
 * @return bool True if valid email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate integer within range
 * 
 * @param mixed $value Value to validate
 * @param int $min Minimum value
 * @param int $max Maximum value
 * @return bool True if valid
 */
function validateInteger($value, $min = null, $max = null) {
    if (!filter_var($value, FILTER_VALIDATE_INT)) {
        return false;
    }
    
    $value = (int)$value;
    
    if ($min !== null && $value < $min) {
        return false;
    }
    
    if ($max !== null && $value > $max) {
        return false;
    }
    
    return true;
}

/**
 * Validate string length
 * 
 * @param string $value String to validate
 * @param int $min Minimum length
 * @param int $max Maximum length
 * @return bool True if valid
 */
function validateStringLength($value, $min = 1, $max = PHP_INT_MAX) {
    $length = mb_strlen($value, 'UTF-8');
    return ($length >= $min && $length <= $max);
}

/**
 * Validate coaching application form data
 * 
 * @param array $data Form data
 * @return array ['valid' => bool, 'errors' => array]
 */
function validateCoachingForm($data) {
    $errors = [];
    
    // Name validation
    if (empty($data['name']) || !validateStringLength($data['name'], 2, 100)) {
        $errors['name'] = 'Name must be between 2 and 100 characters';
    }
    
    // Gender validation
    if (empty($data['gender']) || !in_array($data['gender'], ['Male', 'Female'], true)) {
        $errors['gender'] = 'Please select a valid gender';
    }
    
    // Age validation (16-99)
    if (!isset($data['age']) || !validateInteger($data['age'], 16, 99)) {
        $errors['age'] = 'Age must be between 16 and 99';
    }
    
    // Contact info validation
    if (empty($data['contact_info']) || !validateStringLength($data['contact_info'], 5, 255)) {
        $errors['contact_info'] = 'Please provide valid contact information';
    }
    
    // Goals validation (optional but limited)
    if (isset($data['goals']) && !empty($data['goals'])) {
        if (!validateStringLength($data['goals'], 0, 1000)) {
            $errors['goals'] = 'Goals must not exceed 1000 characters';
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

// ============================================================================
// 5. RATE LIMITING
// ============================================================================

/**
 * Check if action is rate limited
 * Simple file-based rate limiting for specific actions
 * 
 * @param string $action Action identifier (e.g., 'coaching_submit', 'payment_attempt')
 * @param int $limit Maximum attempts
 * @param int $window Time window in seconds
 * @return bool True if rate limit exceeded, false otherwise
 */
function isRateLimited($action, $limit = 5, $window = 300) {
    startSecureSession();
    
    $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = "ratelimit_{$action}_{$identifier}";
    
    // Initialize or get attempt data
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'count' => 0,
            'first_attempt' => time()
        ];
    }
    
    $data = $_SESSION[$key];
    
    // Check if window has expired
    if (time() - $data['first_attempt'] > $window) {
        // Reset counter
        $_SESSION[$key] = [
            'count' => 1,
            'first_attempt' => time()
        ];
        return false;
    }
    
    // Increment counter
    $_SESSION[$key]['count']++;
    
    // Check if limit exceeded
    if ($_SESSION[$key]['count'] > $limit) {
        logSecurityEvent('rate_limit_exceeded', [
            'action' => $action,
            'ip' => $identifier,
            'attempts' => $_SESSION[$key]['count']
        ]);
        return true;
    }
    
    return false;
}

// ============================================================================
// 6. SECURITY LOGGING
// ============================================================================

/**
 * Log security-related events
 * 
 * @param string $event Event type
 * @param array $data Additional data to log
 */
function logSecurityEvent($event, $data = []) {
    $logDir = dirname(__DIR__) . '/logs';
    
    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/security_' . date('Y-m-d') . '.log';
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'data' => $data
    ];
    
    $logLine = json_encode($logEntry) . PHP_EOL;
    
    // Write to log file
    file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
}

// ============================================================================
// 7. PATH SECURITY
// ============================================================================

/**
 * Prevent directory traversal attacks
 * 
 * @param string $filename Filename to validate
 * @return string|false Sanitized filename or false if invalid
 */
function sanitizeFilename($filename) {
    // Remove any path separators
    $filename = basename($filename);
    
    // Remove any null bytes
    $filename = str_replace(chr(0), '', $filename);
    
    // Check for directory traversal patterns
    if (preg_match('/\.\./', $filename)) {
        return false;
    }
    
    return $filename;
}

/**
 * Validate file is in allowed directory
 * 
 * @param string $filepath Full file path
 * @param string $allowedDir Allowed base directory
 * @return bool True if file is in allowed directory
 */
function validateFilePath($filepath, $allowedDir) {
    $realpath = realpath($filepath);
    $allowedPath = realpath($allowedDir);
    
    if ($realpath === false || $allowedPath === false) {
        return false;
    }
    
    // Check if file is within allowed directory
    return strpos($realpath, $allowedPath) === 0;
}

// ============================================================================
// 8. SECURE HEADERS
// ============================================================================

/**
 * Send security headers
 * Call this at the beginning of PHP scripts
 */
function sendSecurityHeaders() {
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // Enable XSS filter in browsers
    header('X-XSS-Protection: 1; mode=block');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy (basic - can be enhanced)
    if (ENVIRONMENT === 'production') {
        header("Content-Security-Policy: default-src 'self'; script-src 'self' https://js.stripe.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; frame-src https://js.stripe.com;");
    }
}

?>
