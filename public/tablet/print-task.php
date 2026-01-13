<?php
/**
 * Tablet Mode - Print Task
 */

require_once __DIR__ . '/../../config/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$taskId = $_GET['id'] ?? null;

if (!$taskId) {
    header('Location: tasks.php');
    exit;
}

$db = new Database();
$task = new Task($db);
$taskData = $task->get($taskId);

if (!$taskData || $taskData['user_id'] != $_SESSION['user_id']) {
    header('Location: tasks.php');
    exit;
}

// Print the task
$printer = new Printer($db);
try {
    $printer->printTask($taskId);
    $success = true;
} catch (Exception $e) {
    $error = $e->getMessage();
}

$pageTitle = 'Print Task';
require_once __DIR__ . '/header.php';
?>

<div class="tablet-container">
    <div class="tablet-card" style="text-align: center; padding: 80px 40px;">
        <?php if (isset($success) && $success): ?>
            <i class="ti ti-check" style="font-size: 120px; color: #10b981; margin-bottom: 30px;"></i>
            <h1 style="font-size: 48px; font-weight: 700; margin-bottom: 20px; color: #10b981;">Task Printed!</h1>
            <p style="font-size: 28px; color: #64748b; margin-bottom: 10px;">
                <?= htmlspecialchars($taskData['title']) ?>
            </p>
            <p style="font-size: 24px; color: #94a3b8; margin-bottom: 60px;">
                Barcode: <?= htmlspecialchars($taskData['barcode']) ?>
            </p>
        <?php else: ?>
            <i class="ti ti-alert-circle" style="font-size: 120px; color: #ef4444; margin-bottom: 30px;"></i>
            <h1 style="font-size: 48px; font-weight: 700; margin-bottom: 20px; color: #ef4444;">Print Failed</h1>
            <p style="font-size: 24px; color: #64748b; margin-bottom: 60px;">
                <?= htmlspecialchars($error ?? 'Unknown error occurred') ?>
            </p>
        <?php endif; ?>
        
        <div style="display: flex; gap: 20px; justify-content: center;">
            <a href="tasks.php" class="btn btn-secondary btn-huge" style="text-decoration: none;">
                <i class="ti ti-arrow-left"></i> Back to Tasks
            </a>
            <a href="create-task.php" class="btn btn-primary btn-huge" style="text-decoration: none;">
                <i class="ti ti-plus"></i> Create Another
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
