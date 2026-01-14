<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pageTitle = 'Rewards';
$user = getCurrentUser();
$db = Database::getInstance();

// Get all rewards for user
$rewards = $db->fetchAll(
    "SELECT * FROM rewards WHERE user_id = ? ORDER BY is_claimed ASC, created_at DESC",
    [$user->getId()]
);

// Handle claim action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_reward'])) {
    $rewardId = (int)$_POST['reward_id'];
    $sql = "UPDATE rewards SET is_claimed = 1, claimed_at = NOW() WHERE id = ? AND user_id = ?";
    $db->execute($sql, [$rewardId, $user->getId()]);
    redirect('/rewards.php');
}

include 'includes/header.php';
?>

<div class="page-body">
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <i class="ti ti-trophy"></i> Your Rewards
                    </h2>
                    <div class="text-muted mt-1">Achievements and milestones you've unlocked</div>
                </div>
            </div>
        </div>
        
        <div class="row row-cards mt-3">
            <?php if (empty($rewards)): ?>
            <div class="col-12">
                <div class="empty">
                    <div class="empty-icon">
                        <i class="ti ti-trophy"></i>
                    </div>
                    <p class="empty-title">No rewards yet</p>
                    <p class="empty-subtitle text-muted">
                        Complete tasks and level up to earn rewards!
                    </p>
                    <div class="empty-action">
                        <a href="/tasks.php" class="btn btn-primary">
                            <i class="ti ti-list-check"></i> View Tasks
                        </a>
                    </div>
                </div>
            </div>
            <?php else: ?>
                <?php foreach ($rewards as $reward): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card <?php echo $reward['is_claimed'] ? 'bg-light' : 'border-warning'; ?>">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <?php if ($reward['is_claimed']): ?>
                                        <i class="ti ti-trophy text-muted" style="font-size: 2rem;"></i>
                                    <?php else: ?>
                                        <i class="ti ti-trophy text-warning" style="font-size: 2rem;"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-fill">
                                    <h3 class="card-title mb-1"><?php echo h($reward['title']); ?></h3>
                                    <div class="text-muted small">
                                        <?php echo ucfirst($reward['reward_type']); ?>
                                    </div>
                                </div>
                                <?php if (!$reward['is_claimed']): ?>
                                <div>
                                    <span class="badge bg-warning">NEW!</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <p class="text-muted">
                                <?php echo h($reward['description']); ?>
                            </p>
                            
                            <div class="mt-3">
                                <?php if ($reward['is_claimed']): ?>
                                    <div class="text-muted small">
                                        <i class="ti ti-check"></i> Claimed <?php echo timeAgo($reward['claimed_at']); ?>
                                    </div>
                                <?php else: ?>
                                    <form method="POST">
                                        <input type="hidden" name="reward_id" value="<?php echo $reward['id']; ?>">
                                        <input type="hidden" name="claim_reward" value="1">
                                        <button type="submit" class="btn btn-warning w-100">
                                            <i class="ti ti-gift"></i> Claim Reward
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mt-2 text-muted small">
                                Unlocked <?php echo formatDateTime($reward['created_at']); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
