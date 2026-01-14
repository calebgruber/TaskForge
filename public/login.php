<?php
require_once __DIR__ . '/config/config.php';

$pageTitle = 'Login';

if (isLoggedIn()) {
    redirect('/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        $user = User::authenticate($username, $password);
        if ($user) {
            login($user);
            
            $redirectTo = $_SESSION['redirect_after_login'] ?? '/index.php';
            unset($_SESSION['redirect_after_login']);
            redirect($redirectTo);
        } else {
            $error = 'Invalid username or password';
        }
    } else {
        $error = 'Please enter username and password';
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
            <h2 class="h2 text-center mb-4">Login to your account</h2>
            
            <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <div class="d-flex">
                    <div><i class="ti ti-alert-circle"></i></div>
                    <div class="ms-2"><?php echo h($error); ?></div>
                </div>
            </div>
            <?php endif; ?>
            
            <form method="POST" autocomplete="off">
                <div class="mb-3">
                    <label class="form-label">Username or Email</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" required autofocus>
                </div>
                <div class="mb-2">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-login"></i> Sign in
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="text-center text-muted mt-3">
        Don't have an account? <a href="/register.php" tabindex="-1">Sign up</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
