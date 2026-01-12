<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pageTitle = 'Tasks';
$user = getCurrentUser();
$db = Database::getInstance();

// Handle actions
$action = $_GET['action'] ?? 'list';
$taskId = $_GET['id'] ?? null;
$message = '';
$error = '';

// Get categories for form
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY name");

// Handle task creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_task'])) {
    try {
        $taskData = [
            'title' => trim($_POST['title']),
            'description' => trim($_POST['description'] ?? ''),
            'category_id' => (int)$_POST['category_id'] ?: null,
            'urgency_level' => $_POST['urgency_level'] ?? 'normal',
            'difficulty' => (int)($_POST['difficulty'] ?? 1),
            'due_date' => $_POST['due_date'] ?: null,
            'reminder_enabled' => isset($_POST['reminder_enabled']) ? 1 : 0,
            'reminder_minutes_before' => (int)($_POST['reminder_minutes_before'] ?? 60),
            'reminder_frequency' => $_POST['reminder_frequency'] ?? 'once',
            'is_recurring' => isset($_POST['is_recurring']) ? 1 : 0,
            'recurrence_pattern' => $_POST['recurrence_pattern'] ?? null,
            'template_id' => (int)$_POST['template_id'] ?: null
        ];
        
        $task = Task::create($user->getId(), $taskData);
        
        // Print if requested
        if (PRINTER_ENABLED && isset($_POST['print_task'])) {
            try {
                $printer = new Printer();
                $printer->printTaskReceipt($task);
                $_SESSION['success'] = 'Task created and printed successfully!';
            } catch (Exception $e) {
                $_SESSION['warning'] = 'Task created but printing failed: ' . $e->getMessage();
            }
        } else {
            $_SESSION['success'] = 'Task created successfully!';
        }
        
        redirect('/tasks.php');
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle task completion (manual)
if (isset($_GET['complete']) && $taskId) {
    try {
        $task = new Task($taskId);
        if ($task->get('user_id') == $user->getId()) {
            $task->complete($user->getId(), 'manual');
            $_SESSION['success'] = 'Task completed successfully!';
        }
        redirect('/tasks.php');
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle task printing
if (isset($_GET['print']) && $taskId && PRINTER_ENABLED) {
    try {
        $task = new Task($taskId);
        if ($task->get('user_id') == $user->getId()) {
            $printer = new Printer();
            $printer->printTaskReceipt($task);
            $_SESSION['success'] = 'Task receipt printed successfully!';
        }
        redirect('/tasks.php');
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get tasks based on filter
$filter = $_GET['filter'] ?? 'active';
$tasks = Task::getAllByUser($user->getId(), $filter === 'all' ? null : $filter);

include 'includes/header.php';
?>

<div class="page-body">
    <div class="container-xl">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible">
            <i class="ti ti-check"></i> <?php echo h($_SESSION['success']); unset($_SESSION['success']); ?>
            <a class="btn-close" data-bs-dismiss="alert"></a>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="ti ti-alert-circle"></i> <?php echo h($error); ?>
        </div>
        <?php endif; ?>
        
        <?php if ($action === 'create'): ?>
        <!-- Create Task Form -->
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title"><i class="ti ti-plus"></i> Create New Task</h2>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-lg-8">
                <form method="POST" class="card">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label required">Title</label>
                            <input type="text" name="title" class="form-control" placeholder="Enter task title" required autofocus>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Add task details..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Category</label>
                                    <select name="category_id" class="form-select">
                                        <option value="">None</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo h($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Urgency Level</label>
                                    <select name="urgency_level" class="form-select">
                                        <option value="low">Low</option>
                                        <option value="normal" selected>Normal</option>
                                        <option value="high">High</option>
                                        <option value="critical">Critical</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Difficulty</label>
                                    <input type="number" name="difficulty" class="form-control" value="1" min="1" max="10">
                                    <small class="form-hint">1 = Easy, 10 = Very Hard (affects XP)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Due Date & Time</label>
                                    <input type="datetime-local" name="due_date" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-check">
                                <input type="checkbox" name="reminder_enabled" class="form-check-input" value="1">
                                <span class="form-check-label">Enable SMS reminder before due date</span>
                            </label>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex">
                            <a href="/tasks.php" class="btn btn-link">Cancel</a>
                            <button type="submit" name="create_task" value="1" class="btn btn-primary ms-auto">
                                <i class="ti ti-device-floppy"></i> Save Task
                            </button>
                            <?php if (PRINTER_ENABLED): ?>
                            <button type="submit" name="print_task" value="1" class="btn btn-success ms-2">
                                <i class="ti ti-printer"></i> Save & Print
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Task List -->
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title"><i class="ti ti-list-check"></i> Tasks</h2>
                </div>
                <div class="col-auto">
                    <a href="/tasks.php?action=create" class="btn btn-primary">
                        <i class="ti ti-plus"></i> New Task
                    </a>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs">
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter === 'active' ? 'active' : ''; ?>" href="/tasks.php?filter=active">
                                    Active
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter === 'completed' ? 'active' : ''; ?>" href="/tasks.php?filter=completed">
                                    Completed
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter === 'overdue' ? 'active' : ''; ?>" href="/tasks.php?filter=overdue">
                                    Overdue
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $filter === 'all' ? 'active' : ''; ?>" href="/tasks.php?filter=all">
                                    All
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Task</th>
                                    <th>Category</th>
                                    <th>Urgency</th>
                                    <th>XP</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th class="w-1">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tasks)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No tasks found. <a href="/tasks.php?action=create">Create your first task</a>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($tasks as $task): ?>
                                    <tr>
                                        <td>
                                            <div><strong><?php echo h($task['title']); ?></strong></div>
                                            <?php if ($task['description']): ?>
                                            <div class="text-muted small"><?php echo h(substr($task['description'], 0, 50)); ?><?php echo strlen($task['description']) > 50 ? '...' : ''; ?></div>
                                            <?php endif; ?>
                                            <div class="small text-muted">
                                                <i class="ti ti-barcode"></i> <?php echo h($task['barcode']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($task['category_name']): ?>
                                            <span class="badge" style="background-color: <?php echo h($task['category_color']); ?>">
                                                <?php echo h($task['category_name']); ?>
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo getUrgencyBadgeClass($task['urgency_level']); ?>">
                                                <?php echo ucfirst($task['urgency_level']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-green">+<?php echo $task['xp_value']; ?> XP</span>
                                        </td>
                                        <td>
                                            <?php if ($task['due_date']): ?>
                                                <?php echo formatDateTime($task['due_date']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">No due date</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo getStatusBadgeClass($task['status']); ?>">
                                                <?php echo ucfirst($task['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($task['status'] === 'active'): ?>
                                                <a href="/tasks.php?id=<?php echo $task['id']; ?>&complete=1" 
                                                   class="btn btn-success" 
                                                   onclick="return confirm('Complete this task?')">
                                                    <i class="ti ti-check"></i>
                                                </a>
                                                <?php if (PRINTER_ENABLED): ?>
                                                <a href="/tasks.php?id=<?php echo $task['id']; ?>&print=1" 
                                                   class="btn btn-primary">
                                                    <i class="ti ti-printer"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
