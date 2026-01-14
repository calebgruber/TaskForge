<?php
require_once __DIR__ . '/config/config.php';

$pageTitle = 'Register';

if (isLoggedIn()) {
    redirect('/index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    
    if (!$username || !$email || !$password) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
        $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters';
    } else {
        try {
            $user = User::create($username, $email, $password, $phone);
            $success = 'Account created successfully! You can now log in.';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="container-tight py-4">
    <div class="text-center mb-4">
        <a href="/" class="navbar-brand navbar-brand-autodark">
            <h1><i class="ti ti-target"></i> <?php echo APP_NAME; ?></h1>
        </a>
    </div>
    <div class="card card-md">
        <div class="card-body">
            <h2 class="h2 text-center mb-4">Create new account</h2>
            
            <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <div class="d-flex">
                    <div><i class="ti ti-alert-circle"></i></div>
                    <div class="ms-2"><?php echo h($error); ?></div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                <div class="d-flex">
                    <div><i class="ti ti-check"></i></div>
                    <div class="ms-2"><?php echo h($success); ?></div>
                </div>
            </div>
            <?php endif; ?>
            
            <form method="POST" autocomplete="off">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" value="<?php echo h($_POST['username'] ?? ''); ?>" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo h($_POST['email'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone (optional, for SMS reminders)</label>
                    <input type="tel" name="phone" class="form-control" value="<?php echo h($_POST['phone'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                    <small class="form-hint">Minimum <?php echo PASSWORD_MIN_LENGTH; ?> characters</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-user-plus"></i> Create account
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="text-center text-muted mt-3">
        Already have an account? <a href="/login.php" tabindex="-1">Sign in</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
