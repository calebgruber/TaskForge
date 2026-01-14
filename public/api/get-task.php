<?php
/**
 * Get Task API
 * Returns task data as JSON for browser print
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions/auth.php';
require_once __DIR__ . '/../../includes/functions/helpers.php';
require_once __DIR__ . '/../../includes/classes/Database.php';
require_once __DIR__ . '/../../includes/classes/Task.php';

header('Content-Type: application/json');

try {
    if (!isLoggedIn()) {
        throw new Exception('Not authenticated');
    }
    
    $taskId = $_GET['id'] ?? null;
    if (!$taskId) {
        throw new Exception('Task ID required');
    }
    
    $task = new Task((int)$taskId);
    $user = getCurrentUser();
    
    // Check ownership
    if ($task->get('user_id') != $user->getId()) {
        throw new Exception('Access denied');
    }
    
    // Return task data
    echo json_encode([
        'id' => $task->getId(),
        'title' => $task->get('title'),
        'description' => $task->get('description'),
        'category_name' => $task->get('category_name'),
        'urgency_level' => $task->get('urgency_level'),
        'xp_value' => $task->get('xp_value'),
        'due_date' => $task->get('due_date'),
        'barcode' => $task->get('barcode'),
        'status' => $task->get('status')
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
