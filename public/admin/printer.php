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
        $printer->initialize();
        
        // Print test receipt
        $printer->setAlignment('center');
        $printer->setBold(true);
        $printer->setFontSize(2);
        $printer->textLine("TaskForge Test Receipt");
        $printer->setBold(false);
        $printer->setFontSize(0);
        $printer->feed(1);
        
        $printer->setAlignment('left');
        $printer->textLine("Date: " . date('Y-m-d H:i:s'));
        $printer->textLine("User: " . getCurrentUser()->get('username'));
        $printer->feed(1);
        $printer->textLine("This is a test print to verify");
        $printer->textLine("your thermal printer is working");
        $printer->textLine("correctly with TaskForge.");
        $printer->feed(2);
        
        $printer->setAlignment('center');
        $printer->printBarcode("TEST" . date('YmdHis'), 'CODE128');
        $printer->feed(3);
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
        $result = $printer->printTaskReceipt($task);
        
        if ($result) {
            $message = "Task receipt reprinted successfully!";
        } else {
            throw new Exception("Failed to reprint task");
        }
    } catch (Exception $e) {
        $error = "Reprint error: " . $e->getMessage();
    }
}

// Get filter from query parameter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query based on filter
$whereClause = '';
if ($statusFilter === 'active') {
    $whereClause = "WHERE t.status = 'active'";
} elseif ($statusFilter === 'completed') {
    $whereClause = "WHERE t.status = 'completed'";
} elseif ($statusFilter === 'overdue') {
    $whereClause = "WHERE t.status = 'active' AND t.due_date < NOW()";
}

// Get tasks for reprinting
$tasks = $db->fetchAll("
    SELECT t.*, c.name as category_name, c.icon_id as category_icon_id,
           u.username
    FROM tasks t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN users u ON t.user_id = u.id
    {$whereClause}
    ORDER BY 
        CASE 
            WHEN t.status = 'active' AND t.due_date < NOW() THEN 1
            WHEN t.status = 'active' THEN 2
            WHEN t.status = 'completed' THEN 3
            ELSE 4
        END,
        t.due_date ASC,
        t.completed_at DESC
    LIMIT 100
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
                            
                            <!-- Finding Printer Device Guide -->
                            <div class="alert alert-info mt-3 mb-0">
                                <h4 class="alert-title">
                                    <i class="ti ti-help me-2"></i>
                                    How to Find Your Printer Device Path
                                </h4>
                                <div class="text-muted">
                                    <strong>On Windows:</strong>
                                    <ol class="mb-2 mt-2">
                                        <li>Open <strong>Device Manager</strong> (Win + X, then M)</li>
                                        <li>Expand <strong>Printers</strong> or <strong>Print queues</strong></li>
                                        <li>Right-click your thermal printer → <strong>Properties</strong></li>
                                        <li>Go to <strong>Details</strong> tab → Select <strong>Device instance path</strong></li>
                                        <li>Or use <strong>Ports</strong> tab to see the COM port (e.g., COM3)</li>
                                    </ol>
                                    <p class="mb-2">
                                        <strong>Common Windows paths:</strong><br>
                                        • <code>\\.\COM3</code> (USB/Serial printer on COM3)<br>
                                        • <code>\\.\LPT1</code> (Parallel port printer)<br>
                                        • <code>\\.\USB001</code> (USB printer)<br>
                                    </p>
                                    <p class="mb-2">
                                        <strong>For USB printers sharing:</strong><br>
                                        • Share the printer in Windows settings<br>
                                        • Use network path: <code>\\COMPUTER\PrinterName</code>
                                    </p>
                                    <p class="mb-0">
                                        <strong>EPSON ESC/POS printers:</strong> This system fully supports EPSON ESC/POS thermal printers via USB or network connection. Ensure your printer driver is installed.
                                    </p>
                                </div>
                            </div>
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
                            <div class="card-actions">
                                <div class="btn-group">
                                    <a href="?status=all" class="btn btn-sm <?php echo $statusFilter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                        All Tasks
                                    </a>
                                    <a href="?status=active" class="btn btn-sm <?php echo $statusFilter === 'active' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                        Active
                                    </a>
                                    <a href="?status=overdue" class="btn btn-sm <?php echo $statusFilter === 'overdue' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                        Overdue
                                    </a>
                                    <a href="?status=completed" class="btn btn-sm <?php echo $statusFilter === 'completed' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                        Completed
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Print or reprint receipts for any task. This includes active tasks, overdue tasks, and completed tasks.
                            </p>
                            
                            <div class="alert alert-info">
                                <i class="ti ti-info-circle me-2"></i>
                                <strong>ESC/POS Support:</strong> This system supports EPSON ESC/POS thermal printers via USB or network connection.
                            </div>
                            
                            <?php if (empty($tasks)): ?>
                                <div class="empty">
                                    <div class="empty-icon">
                                        <i class="ti ti-checkbox"></i>
                                    </div>
                                    <p class="empty-title">No tasks found</p>
                                    <p class="empty-subtitle text-muted">
                                        <?php if ($statusFilter === 'all'): ?>
                                            There are no tasks in the system yet.
                                        <?php elseif ($statusFilter === 'active'): ?>
                                            There are no active tasks.
                                        <?php elseif ($statusFilter === 'overdue'): ?>
                                            There are no overdue tasks.
                                        <?php else: ?>
                                            There are no completed tasks.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-vcenter card-table">
                                        <thead>
                                            <tr>
                                                <th>Task</th>
                                                <th>User</th>
                                                <th>Category</th>
                                                <th>Status</th>
                                                <th>Due / Completed</th>
                                                <th>XP</th>
                                                <th class="w-1"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($tasks as $task): ?>
                                                <?php
                                                $isOverdue = $task['status'] === 'active' && strtotime($task['due_date']) < time();
                                                ?>
                                                <tr class="<?php echo $isOverdue ? 'table-danger' : ''; ?>">
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
                                                        <?php if ($task['username']): ?>
                                                            <span class="text-muted"><?php echo h($task['username']); ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($task['category_name']): ?>
                                                            <span class="badge">
                                                                <?php echo h($task['category_name']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($task['status'] === 'completed'): ?>
                                                            <span class="badge bg-success">Completed</span>
                                                        <?php elseif ($isOverdue): ?>
                                                            <span class="badge bg-danger">Overdue</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-blue">Active</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($task['status'] === 'completed' && $task['completed_at']): ?>
                                                            <?php echo date('M j, Y g:i A', strtotime($task['completed_at'])); ?>
                                                        <?php elseif ($task['due_date']): ?>
                                                            Due: <?php echo date('M j, Y', strtotime($task['due_date'])); ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-green">
                                                            +<?php echo number_format($task['xp_value']); ?> XP
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                            <button type="submit" name="reprint_task" class="btn btn-sm btn-outline-primary" title="Print Receipt">
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
