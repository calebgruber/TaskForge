<?php
/**
 * Tablet Mode - Create Task
 */

require_once __DIR__ . '/../../config/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Create Task';
require_once __DIR__ . '/header.php';

$db = Database::getInstance();
$success = false;
$error = '';

// Get categories
$stmt = $db->getConnection()->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task = new Task($db);
    
    $data = [
        'user_id' => $_SESSION['user_id'],
        'title' => $_POST['title'],
        'description' => $_POST['description'] ?? '',
        'category_id' => $_POST['category_id'] ?? null,
        'urgency_level' => $_POST['urgency_level'],
        'xp_value' => $_POST['xp_value'],
        'due_date' => $_POST['due_date']
    ];
    
    $taskId = $task->create($data);
    
    if ($taskId) {
        $success = true;
        // Redirect to print
        header('Location: print-task.php?id=' . $taskId);
        exit;
    } else {
        $error = 'Failed to create task';
    }
}
?>

<div class="tablet-container">
    <h1 style="font-size: 48px; font-weight: 700; margin-bottom: 40px;">Create New Task</h1>
    
    <div class="tablet-card">
        <?php if ($error): ?>
            <div style="background: #fef2f2; border: 2px solid #ef4444; color: #991b1b; padding: 20px; border-radius: 12px; font-size: 20px; margin-bottom: 30px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group-large" style="margin-bottom: 30px;">
                <label class="form-label-large" style="font-size: 24px; font-weight: 600; margin-bottom: 15px; display: block;">Task Title</label>
                <input type="text" name="title" class="form-control-huge" required>
            </div>
            
            <div class="form-group-large" style="margin-bottom: 30px;">
                <label class="form-label-large" style="font-size: 24px; font-weight: 600; margin-bottom: 15px; display: block;">Description (Optional)</label>
                <textarea name="description" class="form-control-huge" rows="3"></textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
                <div class="form-group-large">
                    <label class="form-label-large" style="font-size: 24px; font-weight: 600; margin-bottom: 15px; display: block;">Category</label>
                    <select name="category_id" class="form-control-huge">
                        <option value="">None</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group-large">
                    <label class="form-label-large" style="font-size: 24px; font-weight: 600; margin-bottom: 15px; display: block;">Urgency</label>
                    <select name="urgency_level" class="form-control-huge" required>
                        <option value="low">Low</option>
                        <option value="normal" selected>Normal</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
                <div class="form-group-large">
                    <label class="form-label-large" style="font-size: 24px; font-weight: 600; margin-bottom: 15px; display: block;">XP Value</label>
                    <input type="number" name="xp_value" class="form-control-huge" value="10" min="1" required>
                </div>
                
                <div class="form-group-large">
                    <label class="form-label-large" style="font-size: 24px; font-weight: 600; margin-bottom: 15px; display: block;">Due Date</label>
                    <input type="datetime-local" name="due_date" class="form-control-huge" required>
                </div>
            </div>
            
            <div style="display: flex; gap: 20px;">
                <a href="tasks.php" class="btn btn-secondary btn-huge" style="flex: 1; text-decoration: none; text-align: center; line-height: 32px;">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary btn-huge" style="flex: 1;">
                    <i class="ti ti-check"></i> Create &amp; Print
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
