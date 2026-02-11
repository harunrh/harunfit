<?php
/**
 * Secure Download Handler
 * Validates and serves PDF files after payment
 */

// Initialize security
define('HARUNFIT_SECURE', true);
require_once __DIR__ . '/../private/config/db_config.php';
require_once __DIR__ . '/../private/includes/security.php';

// Send security headers
sendSecurityHeaders();

// Get requested file
$file = $_GET['file'] ?? '';
$email = $_GET['email'] ?? '';

// Whitelist of allowed files
$allowedFiles = [
    'PremiumTrainingProgram.pdf' => 'Premium Training Program',
    'StarterProgram.pdf' => 'Standard Program'
];

// Validate file parameter
if (!isset($allowedFiles[$file])) {
    http_response_code(403);
    die('Invalid file requested');
}

// Build full file path
$filePath = dirname(__DIR__) . '/private/products/' . $file;

// Check if file exists
if (!file_exists($filePath)) {
    http_response_code(404);
    die('File not found');
}

// Rate limiting for downloads (prevent abuse)
if (isRateLimited('download_' . $file, 10, 3600)) {
    http_response_code(429);
    die('Too many download attempts. Please try again later.');
}

// Log the download
logSecurityEvent('file_download', [
    'file' => $file,
    'email' => $email,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);

// Serve the file
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . basename($file) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Disable output buffering
if (ob_get_level()) {
    ob_end_clean();
}

// Read and output file
readfile($filePath);
exit;
?>