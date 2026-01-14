<?php
/**
 * Tablet Mode - Tasks List
 */

require_once __DIR__ . '/../config/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'My Tasks';
require_once __DIR__ . '/header.php';

$db = Database::getInstance();

// Get active tasks
$sql = "SELECT t.*, c.name as category_name, c.color as category_color
        FROM tasks t
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND t.status = 'active'
        ORDER BY t.due_date ASC, t.created_at DESC";
$tasks = $db->fetchAll($sql, [$_SESSION['user_id']]);
?>

<div class="tablet-container">
    <div style="margin-bottom: 30px;">
        <a href="index.php" class="btn-huge" style="background: #64748b; color: white; text-decoration: none; border: none; display: inline-flex; align-items: center; gap: 10px;">
            <i class="ti ti-arrow-left"></i> Back to Dashboard
        </a>
    </div>
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;">
        <h1 style="font-size: 48px; font-weight: 700; margin: 0;">My Tasks</h1>
        <a href="create-task.php" class="btn-huge" style="background: #8b5cf6; color: white; text-decoration: none; border: none;">
            <i class="ti ti-plus"></i> New Task
        </a>
    </div>
    
    <?php if (empty($tasks)): ?>
        <div class="tablet-card" style="text-align: center; padding: 80px 40px;">
            <i class="ti ti-checkbox" style="font-size: 120px; color: #cbd5e1; margin-bottom: 30px;"></i>
            <h2 style="font-size: 36px; color: #64748b; margin: 0;">No active tasks</h2>
            <p style="font-size: 24px; color: #94a3b8; margin-top: 15px;">Create a new task to get started!</p>
        </div>
    <?php else: ?>
        <?php foreach ($tasks as $t): ?>
            <div class="task-card-large">
                <div class="task-icon-large" style="background: <?= $t['category_color'] ?? '#206bc4' ?>;">
                    <i class="ti ti-checkbox"></i>
                </div>
                <div class="task-info-large">
                    <div class="task-title-large"><?= htmlspecialchars($t['title']) ?></div>
                    <div class="task-meta-large">
                        <span><i class="ti ti-calendar"></i> <?= date('M j, Y', strtotime($t['due_date'])) ?></span>
                        <span style="margin-left: 20px;"><i class="ti ti-star"></i> <?= $t['xp_value'] ?> XP</span>
                        <?php if ($t['urgency_level']): ?>
                            <span style="margin-left: 20px; color: #ef4444;">
                                <i class="ti ti-alert-circle"></i> <?= ucfirst($t['urgency_level']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="task-actions-large">
                    <a href="print-task.php?id=<?= $t['id'] ?>" class="btn btn-primary btn-huge">
                        <i class="ti ti-printer"></i> Print
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
