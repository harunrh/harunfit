<?php
/**
 * Update Application Contact Status
 */
session_start();

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$application_id = $data['id'] ?? 0;
$contacted = $data['contacted'] ?? 0;

if (!$application_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

// Database connection
define('HARUNFIT_SECURE', true);
require_once __DIR__ . '/../private/config/db_config.php';

try {
    $pdo = getSecureDBConnection();
    
    $stmt = $pdo->prepare("UPDATE coaching_applications SET contacted = ? WHERE id = ?");
    $stmt->execute([$contacted, $application_id]);
    
    echo json_encode(['success' => true, 'message' => 'Status updated']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>