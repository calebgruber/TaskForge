<?php
/**
 * Tablet/Kiosk Mode - Main Dashboard
 * Touch-optimized interface for Windows tablets
 */

require_once __DIR__ . '/../../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'TaskForge Tablet Mode';
require_once __DIR__ . '/header.php';

$db = Database::getInstance();
$user = new User($db);
$userData = $user->get($_SESSION['user_id']);

// Get user stats
$stats = $user->getStats($_SESSION['user_id']);
?>

<div class="tablet-container">
    <!-- User Info Card -->
    <div class="tablet-card user-card">
        <div class="user-avatar">
            <i class="ti ti-user icon-huge"></i>
        </div>
        <div class="user-info">
            <h1 class="user-name"><?= htmlspecialchars($userData['username']) ?></h1>
            <div class="user-level">
                <span class="level-badge">Level <?= $userData['level'] ?></span>
                <span class="xp-text"><?= number_format($userData['xp']) ?> XP</span>
            </div>
            <div class="progress-bar-large">
                <?php
                $nextLevelXP = pow($userData['level'] + 1, 2) * 100;
                $currentLevelXP = pow($userData['level'], 2) * 100;
                $progress = (($userData['xp'] - $currentLevelXP) / ($nextLevelXP - $currentLevelXP)) * 100;
                ?>
                <div class="progress-fill" style="width: <?= min(100, max(0, $progress)) ?>%"></div>
            </div>
        </div>
    </div>

    <!-- Main Action Cards -->
    <div class="tablet-grid">
        <!-- Scanner Card -->
        <a href="scanner.php" class="tablet-card action-card scanner-card">
            <i class="ti ti-scan icon-huge"></i>
            <h2>Scan Task</h2>
            <p>Complete tasks by scanning barcodes</p>
        </a>

        <!-- Tasks Card -->
        <a href="tasks.php" class="tablet-card action-card tasks-card">
            <i class="ti ti-checkbox icon-huge"></i>
            <h2>My Tasks</h2>
            <p><?= $stats['active_tasks'] ?> active tasks</p>
        </a>

        <!-- Create Task Card -->
        <a href="create-task.php" class="tablet-card action-card create-card">
            <i class="ti ti-plus icon-huge"></i>
            <h2>New Task</h2>
            <p>Create and print a new task</p>
        </a>

        <!-- Rewards Card -->
        <a href="rewards.php" class="tablet-card action-card rewards-card">
            <i class="ti ti-trophy icon-huge"></i>
            <h2>Rewards</h2>
            <p><?= $stats['pending_rewards'] ?> pending rewards</p>
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="tablet-stats">
        <div class="stat-card">
            <div class="stat-icon"><i class="ti ti-check"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $stats['completed_tasks'] ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="ti ti-flame"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $stats['streak'] ?></div>
                <div class="stat-label">Day Streak</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="ti ti-target"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $stats['goals_completed'] ?></div>
                <div class="stat-label">Goals</div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
