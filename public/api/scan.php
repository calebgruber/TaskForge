<?php
/**
 * Scanner API Endpoint
 * Handles barcode scanning and task completion
 */
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Not authenticated'], 401);
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$barcode = trim($input['barcode'] ?? '');

if (!$barcode) {
    jsonResponse(['success' => false, 'message' => 'Barcode required'], 400);
}

try {
    $user = getCurrentUser();
    
    // Find task by barcode
    $task = Task::getByBarcode($barcode);
    
    if (!$task) {
        jsonResponse(['success' => false, 'message' => 'Task not found. Please check the barcode.'], 404);
    }
    
    // Verify task belongs to user
    if ($task->get('user_id') != $user->getId()) {
        jsonResponse(['success' => false, 'message' => 'This task belongs to another user'], 403);
    }
    
    // Check if already completed
    if ($task->get('status') === 'completed') {
        jsonResponse([
            'success' => false, 
            'message' => 'This task is already completed',
            'task' => [
                'title' => $task->get('title'),
                'completed_at' => $task->get('completed_at')
            ]
        ], 400);
    }
    
    // Complete the task
    $result = $task->complete($user->getId(), 'scan');
    
    // Check if should print completion receipt
    $autoPrint = getSetting('auto_print_completion', true);
    if ($autoPrint && PRINTER_ENABLED) {
        try {
            $printer = new Printer();
            $printer->printCompletionReceipt(
                $task, 
                $result['xp_awarded'], 
                $result['xp_result']['leveled_up']
            );
            $result['receipt_printed'] = true;
        } catch (Exception $e) {
            error_log("Failed to print completion receipt: " . $e->getMessage());
            $result['receipt_printed'] = false;
            $result['print_error'] = $e->getMessage();
        }
    }
    
    jsonResponse([
        'success' => true,
        'message' => 'Task completed successfully!',
        'task' => $result['task'],
        'xp_awarded' => $result['xp_awarded'],
        'bonus_xp' => $result['bonus_xp'],
        'penalty_xp' => $result['penalty_xp'],
        'was_early' => $result['was_early'],
        'was_late' => $result['was_late'],
        'xp_result' => $result['xp_result'],
        'receipt_printed' => $result['receipt_printed'] ?? false
    ]);
    
} catch (Exception $e) {
    error_log("Scanner API Error: " . $e->getMessage());
    jsonResponse([
        'success' => false, 
        'message' => $e->getMessage()
    ], 500);
}
