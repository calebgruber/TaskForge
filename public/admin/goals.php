<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$pageTitle = 'Goals Management';
$db = Database::getInstance();

$message = '';
$error = '';

// Handle goal reset
if (isset($_GET['reset'])) {
    try {
        $goalId = (int)$_GET['reset'];
        $sql = "UPDATE goals SET current_value = 0, status = 'active', last_reset = NOW(), completed_at = NULL WHERE id = ?";
        $db->execute($sql, [$goalId]);
        $message = 'Goal reset successfully!';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle goal deletion
if (isset($_GET['delete'])) {
    try {
        $goalId = (int)$_GET['delete'];
        $sql = "DELETE FROM goals WHERE id = ?";
        $db->execute($sql, [$goalId]);
        $message = 'Goal deleted successfully!';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle goal archive
if (isset($_GET['archive'])) {
    try {
        $goalId = (int)$_GET['archive'];
        $sql = "UPDATE goals SET status = 'archived' WHERE id = ?";
        $db->execute($sql, [$goalId]);
        $message = 'Goal archived successfully!';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle goal creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_goal'])) {
    try {
        $sql = "INSERT INTO goals (
            user_id, title, description, target_type, target_value, 
            category_id, reset_frequency
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $db->execute($sql, [
            (int)$_POST['user_id'],
            trim($_POST['title']),
            trim($_POST['description']),
            $_POST['target_type'],
            (int)$_POST['target_value'],
            !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null,
            $_POST['reset_frequency']
        ]);
        
        $message = 'Goal created successfully!';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get all goals with user info
$goals = $db->fetchAll("
    SELECT g.*, u.username, c.name as category_name, c.color as category_color
    FROM goals g
    LEFT JOIN users u ON g.user_id = u.id
    LEFT JOIN categories c ON g.category_id = c.id
    ORDER BY g.status, g.created_at DESC
");

// Get users for dropdown
$users = $db->fetchAll("SELECT id, username FROM users ORDER BY username");

// Get categories for dropdown
$categories = $db->fetchAll("SELECT id, name FROM categories ORDER BY name");

include __DIR__ . '/../includes/header.php';
?>

<div class="page-body">
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title"><i class="ti ti-target"></i> Goals Management</h2>
                    <div class="text-muted mt-1">Manage user goals, track progress, and configure reset frequencies</div>
                </div>
            </div>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible">
            <i class="ti ti-check"></i> <?php echo h($message); ?>
            <a class="btn-close" data-bs-dismiss="alert"></a>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="ti ti-alert-circle"></i> <?php echo h($error); ?>
        </div>
        <?php endif; ?>
        
        <div class="row mt-3">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Create New Goal</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label required">User</label>
                                <select name="user_id" class="form-select" required>
                                    <option value="">Select user...</option>
                                    <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>"><?php echo h($user['username']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label required">Goal Title</label>
                                <input type="text" name="title" class="form-control" required placeholder="Complete 100 tasks">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="Optional details about this goal"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label required">Target Type</label>
                                <select name="target_type" class="form-select" required>
                                    <option value="tasks">Tasks Completed</option>
                                    <option value="xp">XP Earned</option>
                                    <option value="days">Days Active</option>
                                    <option value="streak">Current Streak</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label required">Target Value</label>
                                <input type="number" name="target_value" class="form-control" required min="1" value="100">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Category (Optional)</label>
                                <select name="category_id" class="form-select">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo h($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Reset Frequency</label>
                                <select name="reset_frequency" class="form-select">
                                    <option value="none">Never (One-time goal)</option>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>
                            
                            <button type="submit" name="create_goal" class="btn btn-primary w-100">
                                <i class="ti ti-plus"></i> Create Goal
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Goals</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Goal</th>
                                        <th>Progress</th>
                                        <th>Reset</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($goals)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            No goals yet. Create one to get started!
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($goals as $goal): ?>
                                    <?php
                                    $progress = $goal['target_value'] > 0 ? ($goal['current_value'] / $goal['target_value']) * 100 : 0;
                                    $progress = min(100, $progress);
                                    
                                    $statusColors = [
                                        'active' => 'success',
                                        'completed' => 'info',
                                        'archived' => 'secondary'
                                    ];
                                    $statusColor = $statusColors[$goal['status']] ?? 'secondary';
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo h($goal['username']); ?></strong>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo h($goal['title']); ?></div>
                                            <?php if ($goal['category_name']): ?>
                                            <div class="small">
                                                <span class="badge" style="background-color: <?php echo h($goal['category_color']); ?>">
                                                    <?php echo h($goal['category_name']); ?>
                                                </span>
                                            </div>
                                            <?php endif; ?>
                                            <?php if ($goal['description']): ?>
                                            <div class="small text-muted"><?php echo h($goal['description']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="mb-1">
                                                <small><?php echo number_format($goal['current_value']); ?> / <?php echo number_format($goal['target_value']); ?></small>
                                            </div>
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: <?php echo $progress; ?>%"
                                                     aria-valuenow="<?php echo $progress; ?>" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                </div>
                                            </div>
                                            <small class="text-muted"><?php echo number_format($progress, 1); ?>%</small>
                                        </td>
                                        <td>
                                            <?php if ($goal['reset_frequency'] !== 'none'): ?>
                                            <span class="badge bg-blue-lt"><?php echo ucfirst($goal['reset_frequency']); ?></span>
                                            <?php if ($goal['last_reset']): ?>
                                            <div class="small text-muted">
                                                Last: <?php echo date('M d', strtotime($goal['last_reset'])); ?>
                                            </div>
                                            <?php endif; ?>
                                            <?php else: ?>
                                            <span class="text-muted">One-time</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $statusColor; ?>">
                                                <?php echo ucfirst($goal['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($goal['status'] !== 'archived'): ?>
                                                <a href="?reset=<?php echo $goal['id']; ?>" 
                                                   class="btn btn-sm btn-warning" 
                                                   onclick="return confirm('Reset this goal? Progress will be set to 0.')">
                                                    <i class="ti ti-refresh"></i>
                                                </a>
                                                <a href="?archive=<?php echo $goal['id']; ?>" 
                                                   class="btn btn-sm btn-secondary" 
                                                   onclick="return confirm('Archive this goal?')">
                                                    <i class="ti ti-archive"></i>
                                                </a>
                                                <?php endif; ?>
                                                <a href="?delete=<?php echo $goal['id']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="return confirm('Permanently delete this goal?')">
                                                    <i class="ti ti-trash"></i>
                                                </a>
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
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
