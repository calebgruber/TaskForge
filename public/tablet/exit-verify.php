<?php
/**
 * Verify Exit PIN for Tablet Mode
 */

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$pin = $input['pin'] ?? '';

// Verify PIN against config
if ($pin === TABLET_EXIT_PIN) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid PIN']);
}
