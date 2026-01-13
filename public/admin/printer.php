<?php
/**
 * Admin Printer Management
 * Test printer and reprint failed receipts
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions/auth.php';
require_once __DIR__ . '/../includes/functions/helpers.php';
require_once __DIR__ . '/../includes/classes/Database.php';
require_once __DIR__ . '/../includes/classes/User.php';
require_once __DIR__ . '/../includes/classes/Task.php';
require_once __DIR__ . '/../includes/classes/Printer.php';

requireLogin();
requireAdmin();

$pageTitle = 'Printer Management';
$db = Database::getInstance();
$message = '';
$error = '';

// Handle test print
if (isset($_POST['test_print'])) {
    try {
        $printer = new Printer();
        $printer->connect();
        
        // Print test receipt
        $printer->printText("TaskForge Test Receipt", true, true);
        $printer->printText("");
        $printer->printText("Date: " . date('Y-m-d H:i:s'));
        $printer->printText("User: " . getCurrentUser()->get('username'));
        $printer->printText("");
        $printer->printText("This is a test print to verify");
        $printer->printText("your thermal printer is working");
        $printer->printText("correctly with TaskForge.");
        $printer->printText("");
        $printer->printBarcode("TEST" . date('YmdHis'), 'CODE128');
        $printer->printText("");
        $printer->cut();
        
        $printer->disconnect();
        $message = "Test receipt printed successfully!";
    } catch (Exception $e) {
        $error = "Print error: " . $e->getMessage();
    }
}

// Handle reprint task
if (isset($_POST['reprint_task'])) {
    $taskId = (int)$_POST['task_id'];
    
    try {
        $task = new Task($taskId);
        $taskData = $task->getData();
        
        if (!$taskData) {
            throw new Exception("Task not found");
        }
        
        $printer = new Printer();
        $result = $printer->printTask($taskId);
        
        if ($result) {
            $message = "Task receipt reprinted successfully!";
        } else {
            throw new Exception("Failed to reprint task");
        }
    } catch (Exception $e) {
        $error = "Reprint error: " . $e->getMessage();
    }
}

// Get completed tasks for reprinting
$completedTasks = $db->fetchAll("
    SELECT t.*, c.name as category_name, c.icon_id as category_icon_id
    FROM tasks t
    LEFT JOIN categories c ON t.category_id = c.id
    WHERE t.status = 'completed'
    ORDER BY t.completed_at DESC
    LIMIT 50
");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-wrapper">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <i class="ti ti-printer me-2"></i>
                        Printer Management
                    </h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="page-body">
        <div class="container-xl">
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    <i class="ti ti-check me-2"></i>
                    <?php echo h($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    <i class="ti ti-alert-triangle me-2"></i>
                    <?php echo h($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="row row-cards">
                <!-- Test Print Section -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ti ti-test-pipe me-2"></i>
                                Test Printer
                            </h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Print a test receipt to verify your thermal printer is configured correctly and working properly.
                            </p>
                            
                            <div class="alert alert-info">
                                <i class="ti ti-info-circle me-2"></i>
                                The test receipt will include:
                                <ul class="mb-0 mt-2">
                                    <li>Current date and time</li>
                                    <li>Your username</li>
                                    <li>A test barcode</li>
                                </ul>
                            </div>
                            
                            <form method="POST" class="mt-3">
                                <button type="submit" name="test_print" class="btn btn-primary">
                                    <i class="ti ti-printer me-2"></i>
                                    Print Test Receipt
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Printer Status -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ti ti-info-circle me-2"></i>
                                Printer Status
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <div>
                                            <?php if (PRINTER_ENABLED): ?>
                                                <span class="badge bg-success">Enabled</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Disabled</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <label class="form-label">Connection Type</label>
                                        <div>
                                            <span class="badge bg-blue">
                                                <?php echo strtoupper(PRINTER_TYPE); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (PRINTER_TYPE === 'usb'): ?>
                                <div class="mb-3">
                                    <label class="form-label">Device Path</label>
                                    <div>
                                        <code><?php echo h(getSetting('printer_device', PRINTER_DEVICE)); ?></code>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="mb-3">
                                    <label class="form-label">Network Address</label>
                                    <div>
                                        <code><?php echo h(PRINTER_IP . ':' . PRINTER_PORT); ?></code>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <a href="/admin/settings.php" class="btn btn-outline-primary mt-2">
                                <i class="ti ti-settings me-2"></i>
                                Configure Printer
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Reprint Tasks Section -->
            <div class="row row-cards mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="ti ti-refresh me-2"></i>
                                Reprint Task Receipts
                            </h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Reprint receipts for previously completed tasks. This is useful if a receipt failed to print or was lost.
                            </p>
                            
                            <?php if (empty($completedTasks)): ?>
                                <div class="empty">
                                    <div class="empty-icon">
                                        <i class="ti ti-checkbox"></i>
                                    </div>
                                    <p class="empty-title">No completed tasks</p>
                                    <p class="empty-subtitle text-muted">
                                        Complete some tasks first, then you'll be able to reprint their receipts here.
                                    </p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table">
                                        <thead>
                                            <tr>
                                                <th>Task</th>
                                                <th>Category</th>
                                                <th>Completed</th>
                                                <th>XP</th>
                                                <th class="w-1"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($completedTasks as $task): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if ($task['urgency'] === 'high'): ?>
                                                                <i class="ti ti-alert-circle text-danger me-2"></i>
                                                            <?php elseif ($task['urgency'] === 'medium'): ?>
                                                                <i class="ti ti-alert-triangle text-warning me-2"></i>
                                                            <?php else: ?>
                                                                <i class="ti ti-circle text-muted me-2"></i>
                                                            <?php endif; ?>
                                                            <div>
                                                                <strong><?php echo h($task['title']); ?></strong>
                                                                <?php if ($task['barcode']): ?>
                                                                    <div class="small text-muted">
                                                                        <code><?php echo h($task['barcode']); ?></code>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if ($task['category_name']): ?>
                                                            <span class="badge">
                                                                <?php echo h($task['category_name']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php echo date('M j, Y g:i A', strtotime($task['completed_at'])); ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-green">
                                                            +<?php echo number_format($task['xp_value']); ?> XP
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                            <button type="submit" name="reprint_task" class="btn btn-sm btn-outline-primary" title="Reprint Receipt">
                                                                <i class="ti ti-printer"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
