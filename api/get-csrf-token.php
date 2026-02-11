<?php
/**
 * CSRF Token API Endpoint
 * Returns a CSRF token for form submissions
 */

// Initialize security
define('HARUNFIT_SECURE', true);
require_once '../private/config/db_config.php';
require_once '../private/includes/security.php';

// Send security headers
sendSecurityHeaders();

// Start secure session
startSecureSession();

// Set JSON response header
header('Content-Type: application/json');

// Generate CSRF token
$token = generateCSRFToken();

// Return token as JSON
echo json_encode([
    'csrf_token' => $token
]);
?>
