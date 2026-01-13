<?php
/**
 * Tablet Mode - Rewards
 */

require_once __DIR__ . '/../../config/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Rewards';
require_once __DIR__ . '/header.php';

$db = Database::getInstance();
$stmt = $db->getConnection()->prepare("
    SELECT r.*, ur.claimed_at, ur.id as user_reward_id
    FROM rewards r
    LEFT JOIN user_rewards ur ON r.id = ur.reward_id AND ur.user_id = ?
    WHERE ur.id IS NOT NULL
    ORDER BY ur.claimed_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$rewards = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="tablet-container">
    <h1 style="font-size: 48px; font-weight: 700; margin-bottom: 40px;">
        <i class="ti ti-trophy" style="color: #f59e0b;"></i> My Rewards
    </h1>
    
    <?php if (empty($rewards)): ?>
        <div class="tablet-card" style="text-align: center; padding: 80px 40px;">
            <i class="ti ti-trophy" style="font-size: 120px; color: #cbd5e1; margin-bottom: 30px;"></i>
            <h2 style="font-size: 36px; color: #64748b; margin: 0;">No rewards yet</h2>
            <p style="font-size: 24px; color: #94a3b8; margin-top: 15px;">Keep completing tasks to earn rewards!</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 30px;">
            <?php foreach ($rewards as $r): ?>
                <div class="tablet-card" style="border-top: 6px solid #f59e0b;">
                    <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 20px;">
                        <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 40px;">
                            <i class="ti ti-trophy"></i>
                        </div>
                        <div style="flex: 1;">
                            <h3 style="font-size: 28px; font-weight: 700; margin: 0 0 10px 0;"><?= htmlspecialchars($r['name']) ?></h3>
                            <span style="background: #fef3c7; color: #92400e; padding: 8px 16px; border-radius: 20px; font-size: 16px; font-weight: 600;">
                                <?= ucfirst(str_replace('_', ' ', $r['reward_type'])) ?>
                            </span>
                        </div>
                    </div>
                    <p style="font-size: 20px; color: #64748b; margin: 0 0 20px 0;"><?= htmlspecialchars($r['description']) ?></p>
                    <div style="font-size: 18px; color: #94a3b8;">
                        <i class="ti ti-check"></i> Claimed <?= date('M j, Y', strtotime($r['claimed_at'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
