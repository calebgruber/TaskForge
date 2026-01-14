<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pageTitle = 'Dashboard';
$user = getCurrentUser();

// Get task statistics
$db = Database::getInstance();

$stats = [
    'active' => $db->fetchOne("SELECT COUNT(*) as count FROM tasks WHERE user_id = ? AND status = 'active'", [$user->getId()])['count'],
    'completed_today' => $db->fetchOne("SELECT COUNT(*) as count FROM tasks WHERE user_id = ? AND status = 'completed' AND DATE(completed_at) = CURDATE()", [$user->getId()])['count'],
    'overdue' => $db->fetchOne("SELECT COUNT(*) as count FROM tasks WHERE user_id = ? AND status IN ('active', 'overdue') AND due_date < NOW()", [$user->getId()])['count'],
    'total_completed' => $user->get('tasks_completed')
];

// Get recent tasks
$recentTasks = Task::getAllByUser($user->getId(), 'active', 5);

// Get unclaimed rewards
$rewards = $db->fetchAll(
    "SELECT * FROM rewards WHERE user_id = ? AND is_claimed = 0 ORDER BY created_at DESC LIMIT 3",
    [$user->getId()]
);

// Check for overdue tasks
Task::checkOverdueTasks();

include 'includes/header.php';
?>

<div class="page-body">
    <div class="container-xl">
        <?php if (!empty($rewards)): ?>
        <div class="row mb-3">
            <div class="col-12">
                <?php foreach ($rewards as $reward): ?>
                <div class="alert alert-success alert-dismissible" role="alert">
                    <div class="d-flex">
                        <div><i class="ti ti-trophy fs-1"></i></div>
                        <div class="ms-3">
                            <h4 class="alert-title"><?php echo h($reward['title']); ?></h4>
                            <div class="text-muted"><?php echo h($reward['description']); ?></div>
                            <div class="mt-2">
                                <a href="/rewards.php" class="btn btn-success btn-sm">View Rewards</a>
                            </div>
                        </div>
                    </div>
                    <a class="btn-close" data-bs-dismiss="alert"></a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="row row-deck row-cards">
            <!-- Statistics Cards -->
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Active Tasks</div>
                        </div>
                        <div class="h1 mb-3"><?php echo $stats['active']; ?></div>
                        <div class="d-flex mb-2">
                            <div>Current quests in progress</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Completed Today</div>
                        </div>
                        <div class="h1 mb-3"><?php echo $stats['completed_today']; ?></div>
                        <div class="d-flex mb-2">
                            <div>Tasks finished today</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Overdue</div>
                        </div>
                        <div class="h1 mb-3 <?php echo $stats['overdue'] > 0 ? 'text-red' : ''; ?>">
                            <?php echo $stats['overdue']; ?>
                        </div>
                        <div class="d-flex mb-2">
                            <div>Tasks past due date</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total XP</div>
                        </div>
                        <div class="h1 mb-3"><?php echo number_format($user->get('total_xp')); ?></div>
                        <div class="d-flex mb-2">
                            <div>All-time experience points</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Active Quests</h3>
                        <div class="card-actions">
                            <a href="/tasks.php" class="btn btn-primary btn-sm">
                                <i class="ti ti-list-check"></i> View All
                            </a>
                            <a href="/tasks.php?action=create" class="btn btn-success btn-sm ms-2">
                                <i class="ti ti-plus"></i> New Task
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentTasks)): ?>
                        <div class="empty">
                            <div class="empty-icon">
                                <i class="ti ti-list-check"></i>
                            </div>
                            <p class="empty-title">No active tasks</p>
                            <p class="empty-subtitle text-muted">
                                Start by creating your first quest!
                            </p>
                            <div class="empty-action">
                                <a href="/tasks.php?action=create" class="btn btn-primary">
                                    <i class="ti ti-plus"></i> Create Task
                                </a>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentTasks as $task): ?>
                            <div class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="badge <?php echo getUrgencyBadgeClass($task['urgency_level']); ?>">
                                            <?php echo ucfirst($task['urgency_level']); ?>
                                        </span>
                                    </div>
                                    <div class="col">
                                        <div class="text-truncate">
                                            <strong><?php echo h($task['title']); ?></strong>
                                        </div>
                                        <div class="text-muted small">
                                            <?php if ($task['category_name']): ?>
                                                <span class="badge bg-secondary"><?php echo h($task['category_name']); ?></span>
                                            <?php endif; ?>
                                            <?php if ($task['due_date']): ?>
                                                <i class="ti ti-clock"></i> Due: <?php echo formatDateTime($task['due_date']); ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <span class="badge bg-green xp-badge">
                                            +<?php echo $task['xp_value']; ?> XP
                                        </span>
                                    </div>
                                    <div class="col-auto">
                                        <a href="/tasks.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-primary">
                                            View
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="/scanner.php" class="btn btn-lg btn-primary">
                                <i class="ti ti-scan"></i> Open Scanner
                            </a>
                            <a href="/tasks.php?action=create" class="btn btn-lg btn-success">
                                <i class="ti ti-plus"></i> Create New Task
                            </a>
                            <a href="/tasks.php" class="btn btn-lg btn-outline-primary">
                                <i class="ti ti-list-check"></i> View All Tasks
                            </a>
                            <a href="/rewards.php" class="btn btn-lg btn-outline-warning">
                                <i class="ti ti-trophy"></i> View Rewards
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Your Progress</h3>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="display-6 fw-bold text-green">
                                Level <?php echo $user->get('current_level'); ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="row">
                                <div class="col">
                                    <div class="text-muted small">Current XP</div>
                                    <div class="fw-bold"><?php echo number_format($user->get('current_xp')); ?></div>
                                </div>
                                <div class="col">
                                    <div class="text-muted small">Total XP</div>
                                    <div class="fw-bold"><?php echo number_format($user->get('total_xp')); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-2">
                            <div class="text-muted small">Tasks Completed</div>
                            <div class="h3"><?php echo number_format($stats['total_completed']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
