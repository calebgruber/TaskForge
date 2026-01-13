<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$pageTitle = 'Rewards Management';
$db = Database::getInstance();

$message = '';
$error = '';

// Handle reward creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_reward'])) {
    try {
        $sql = "INSERT INTO rewards (
            user_id, reward_type, title, description, 
            xp_threshold, level_threshold, tasks_threshold
        ) VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $db->execute($sql, [
            (int)$_POST['user_id'],
            $_POST['reward_type'],
            trim($_POST['title']),
            trim($_POST['description']),
            !empty($_POST['xp_threshold']) ? (int)$_POST['xp_threshold'] : null,
            !empty($_POST['level_threshold']) ? (int)$_POST['level_threshold'] : null,
            !empty($_POST['tasks_threshold']) ? (int)$_POST['tasks_threshold'] : null
        ]);
        
        $message = 'Reward created successfully!';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle reward deletion
if (isset($_GET['delete'])) {
    try {
        $rewardId = (int)$_GET['delete'];
        $sql = "DELETE FROM rewards WHERE id = ?";
        $db->execute($sql, [$rewardId]);
        $message = 'Reward deleted successfully!';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle reward claim reset
if (isset($_GET['reset'])) {
    try {
        $rewardId = (int)$_GET['reset'];
        $sql = "UPDATE rewards SET is_claimed = 0, claimed_at = NULL WHERE id = ?";
        $db->execute($sql, [$rewardId]);
        $message = 'Reward reset successfully!';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get all rewards with user info
$rewards = $db->fetchAll("
    SELECT r.*, u.username
    FROM rewards r
    LEFT JOIN users u ON r.user_id = u.id
    ORDER BY r.is_claimed, r.created_at DESC
");

// Get users for dropdown
$users = $db->fetchAll("SELECT id, username FROM users ORDER BY username");

include __DIR__ . '/../includes/header.php';
?>

<div class="page-body">
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title"><i class="ti ti-trophy"></i> Rewards Management</h2>
                    <div class="text-muted mt-1">Configure rewards, milestones, and achievement unlocks</div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRewardModal">
                        <i class="ti ti-plus"></i> Create Reward
                    </button>
                </div>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ti ti-check"></i> <?php echo h($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ti ti-alert-circle"></i> <?php echo h($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Rewards</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>Title</th>
                                    <th>Thresholds</th>
                                    <th>Status</th>
                                    <th>Claimed</th>
                                    <th class="w-1">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rewards)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="ti ti-trophy" style="font-size: 3rem; opacity: 0.3;"></i>
                                            <p class="mt-2">No rewards configured yet</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rewards as $reward): ?>
                                        <tr>
                                            <td><?php echo $reward['id']; ?></td>
                                            <td>
                                                <span class="badge bg-blue-lt"><?php echo h($reward['username']); ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $typeColors = [
                                                    'level_up' => 'green',
                                                    'streak' => 'orange',
                                                    'milestone' => 'purple',
                                                    'custom' => 'blue'
                                                ];
                                                $typeIcons = [
                                                    'level_up' => 'arrow-up',
                                                    'streak' => 'flame',
                                                    'milestone' => 'flag',
                                                    'custom' => 'star'
                                                ];
                                                $color = $typeColors[$reward['reward_type']] ?? 'gray';
                                                $icon = $typeIcons[$reward['reward_type']] ?? 'gift';
                                                ?>
                                                <span class="badge bg-<?php echo $color; ?>-lt">
                                                    <i class="ti ti-<?php echo $icon; ?>"></i> 
                                                    <?php echo ucwords(str_replace('_', ' ', $reward['reward_type'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?php echo h($reward['title']); ?></strong>
                                                <?php if ($reward['description']): ?>
                                                    <br><small class="text-muted"><?php echo h($reward['description']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($reward['xp_threshold']): ?>
                                                    <span class="badge bg-blue me-1"><?php echo number_format($reward['xp_threshold']); ?> XP</span>
                                                <?php endif; ?>
                                                <?php if ($reward['level_threshold']): ?>
                                                    <span class="badge bg-green me-1">Level <?php echo $reward['level_threshold']; ?></span>
                                                <?php endif; ?>
                                                <?php if ($reward['tasks_threshold']): ?>
                                                    <span class="badge bg-purple"><?php echo $reward['tasks_threshold']; ?> Tasks</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($reward['is_claimed']): ?>
                                                    <span class="badge bg-success"><i class="ti ti-check"></i> Claimed</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning"><i class="ti ti-clock"></i> Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($reward['claimed_at']): ?>
                                                    <small class="text-muted"><?php echo date('M j, Y', strtotime($reward['claimed_at'])); ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted">—</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <?php if ($reward['is_claimed']): ?>
                                                        <a href="?reset=<?php echo $reward['id']; ?>" 
                                                           class="btn btn-sm btn-warning"
                                                           onclick="return confirm('Reset this reward to unclaimed status?')">
                                                            <i class="ti ti-refresh"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="?delete=<?php echo $reward['id']; ?>" 
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('Delete this reward permanently?')">
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

<!-- Create Reward Modal -->
<div class="modal fade" id="createRewardModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ti ti-trophy"></i> Create New Reward</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">User</label>
                                <select name="user_id" class="form-select" required>
                                    <option value="">Select User...</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>"><?php echo h($user['username']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label required">Reward Type</label>
                                <select name="reward_type" class="form-select" required>
                                    <option value="level_up">Level Up</option>
                                    <option value="streak">Streak</option>
                                    <option value="milestone">Milestone</option>
                                    <option value="custom">Custom</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g., Welcome Bonus, Level 10 Champion" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Describe what this reward is for..."></textarea>
                    </div>
                    
                    <hr>
                    
                    <h4 class="mb-3">Unlock Thresholds <small class="text-muted">(Optional - leave blank for manual rewards)</small></h4>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">XP Threshold</label>
                                <input type="number" name="xp_threshold" class="form-control" placeholder="e.g., 1000">
                                <small class="form-hint">Unlock when user reaches this XP</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Level Threshold</label>
                                <input type="number" name="level_threshold" class="form-control" placeholder="e.g., 10" min="1" max="50">
                                <small class="form-hint">Unlock when user reaches this level</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Tasks Threshold</label>
                                <input type="number" name="tasks_threshold" class="form-control" placeholder="e.g., 100">
                                <small class="form-hint">Unlock when user completes this many tasks</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_reward" class="btn btn-primary">
                        <i class="ti ti-check"></i> Create Reward
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
