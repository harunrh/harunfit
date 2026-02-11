<?php
/**
 * Coaching Application Submission Handler
 * 
 * Security Features:
 * - CSRF Protection
 * - Input Validation
 * - XSS Prevention
 * - SQL Injection Prevention (Prepared Statements)
 * - Rate Limiting
 * - Security Logging
 */

// Initialize security
define('HARUNFIT_SECURE', true);
require_once '../private/config/db_config.php';
require_once '../private/includes/security.php';

// Send security headers
sendSecurityHeaders();

// Start secure session
startSecureSession();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

// Set JSON response header
header('Content-Type: application/json');

// 1. RATE LIMITING
if (isRateLimited('coaching_submit', 5, 300)) {
    logSecurityEvent('rate_limit_exceeded', ['action' => 'coaching_submit']);
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'message' => 'Too many submission attempts. Please wait a few minutes.'
    ]);
    exit;
}

// 2. CSRF VALIDATION
if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    logSecurityEvent('csrf_validation_failed', ['form' => 'coaching_application']);
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid security token. Please refresh and try again.'
    ]);
    exit;
}

// 3. SANITIZE INPUT
$data = [
    'name' => sanitizeInput($_POST['name'] ?? '', 'string'),
    'gender' => sanitizeInput($_POST['gender'] ?? '', 'string'),
    'age' => sanitizeInput($_POST['age'] ?? 0, 'int'),
    'contact_info' => sanitizeInput($_POST['contact_info'] ?? '', 'string'),
    'goals' => sanitizeInput($_POST['goals'] ?? '', 'string')
];

// 4. VALIDATE INPUT
$validation = validateCoachingForm($data);

if (!$validation['valid']) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Please check your input',
        'errors' => $validation['errors']
    ]);
    exit;
}

// 5. DATABASE INSERTION with PREPARED STATEMENTS
try {
    $pdo = getSecureDBConnection();
    
    $sql = "INSERT INTO coaching_applications 
            (name, gender, age, contact_info, goals, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['name'],
        $data['gender'],
        $data['age'],
        $data['contact_info'],
        $data['goals'],
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    
    // Log successful submission
    logSecurityEvent('coaching_application_submitted', [
        'name' => $data['name'],
        'age' => $data['age']
    ]);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully! We will contact you within 24-48 hours.'
    ]);
    
} catch (PDOException $e) {
    // Log database error
    error_log("Database error in submit.php: " . $e->getMessage());
    logSecurityEvent('database_error', ['error' => 'coaching_submission_failed']);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ]);
}
?>
