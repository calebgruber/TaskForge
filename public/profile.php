<?php
require_once __DIR__ . '/config/config.php';
requireLogin();

$pageTitle = 'Profile';
$user = getCurrentUser();

$message = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $updateData = [
            'email' => trim($_POST['email']),
            'phone_number' => trim($_POST['phone_number'] ?? ''),
            'sms_enabled' => isset($_POST['sms_enabled']) ? 1 : 0
        ];
        
        $user->updateProfile($updateData);
        
        // Update password if provided
        if (!empty($_POST['new_password'])) {
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            if (!User::authenticate($user->get('username'), $currentPassword)) {
                throw new Exception('Current password is incorrect');
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception('New passwords do not match');
            }
            
            if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                throw new Exception('Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters');
            }
            
            $db = Database::getInstance();
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $db->execute("UPDATE users SET password_hash = ? WHERE id = ?", [$hash, $user->getId()]);
        }
        
        $message = 'Profile updated successfully!';
        $user = new User($user->getId()); // Reload user data
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$progress = $user->getProgressToNextLevel();

include 'includes/header.php';
?>

<div class="page-body">
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title"><i class="ti ti-user"></i> Profile</h2>
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
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <span class="avatar avatar-xl" style="font-size: 3rem;">
                                <i class="ti ti-user"></i>
                            </span>
                        </div>
                        <h3 class="m-0"><?php echo h($user->get('username')); ?></h3>
                        <div class="text-muted"><?php echo h($user->get('email')); ?></div>
                        <div class="mt-3">
                            <span class="badge bg-green badge-lg me-2">
                                Level <?php echo $user->get('current_level'); ?>
                            </span>
                            <span class="badge bg-blue badge-lg">
                                <?php echo number_format($user->get('total_xp')); ?> XP
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Statistics</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="text-muted small">Tasks Completed</div>
                            <div class="h3"><?php echo number_format($user->get('tasks_completed')); ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Current Level</div>
                            <div class="h3"><?php echo $user->get('current_level'); ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Total XP Earned</div>
                            <div class="h3"><?php echo number_format($user->get('total_xp')); ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Current XP</div>
                            <div class="h3"><?php echo number_format($user->get('current_xp')); ?></div>
                        </div>
                        <?php if (!$progress['is_max_level']): ?>
                        <div>
                            <div class="text-muted small">Progress to Level <?php echo $progress['next_level']; ?></div>
                            <div class="progress">
                                <div class="progress-bar bg-green" style="width: <?php echo $progress['progress']; ?>%"></div>
                            </div>
                            <div class="small text-muted mt-1">
                                <?php echo number_format($progress['level_xp']); ?> / <?php echo number_format($progress['xp_needed']); ?> XP
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <form method="POST">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Account Settings</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="<?php echo h($user->get('username')); ?>" disabled>
                                <small class="form-hint">Username cannot be changed</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo h($user->get('email')); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone_number" class="form-control" value="<?php echo h($user->get('phone_number')); ?>">
                                <small class="form-hint">For SMS reminders</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input type="checkbox" name="sms_enabled" class="form-check-input" value="1" <?php echo $user->get('sms_enabled') ? 'checked' : ''; ?>>
                                    <span class="form-check-label">Enable SMS reminders</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">Change Password</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control">
                                <small class="form-hint">Leave empty to keep current password</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control">
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex">
                                <a href="/index.php" class="btn btn-link">Back to Dashboard</a>
                                <button type="submit" class="btn btn-primary ms-auto">
                                    <i class="ti ti-device-floppy"></i> Save Changes
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
