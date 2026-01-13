<?php
/**
 * Tablet Mode Login
 */

require_once __DIR__ . '/../config/config.php';

// If already logged in, redirect to tablet dashboard
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $db = Database::getInstance();
        $user = new User($db);
        
        $userData = $user->authenticate($username, $password);
        
        if ($userData) {
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['username'] = $userData['username'];
            $_SESSION['is_admin'] = $userData['is_admin'];
            $_SESSION['tablet_mode'] = true;
            
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Tablet Mode Login - TaskForge</title>
    
    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="../assets/favicons/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="../assets/favicons/favicon.svg">
    
    <!-- Tabler CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    
    <style>
        * {
            -webkit-tap-highlight-color: transparent;
        }
        
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            background: linear-gradient(135deg, #206bc4 0%, #1a5199 100%);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .login-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 40px;
        }
        
        .login-card {
            background: white;
            border-radius: 24px;
            padding: 80px 60px;
            box-shadow: 0 24px 48px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .login-logo i {
            font-size: 96px;
            color: #206bc4;
            margin-bottom: 20px;
        }
        
        .login-logo h1 {
            font-size: 48px;
            font-weight: 700;
            color: #1a202c;
            margin: 0 0 10px 0;
        }
        
        .login-logo p {
            font-size: 24px;
            color: #64748b;
            margin: 0;
        }
        
        .form-group-large {
            margin-bottom: 30px;
        }
        
        .form-label-large {
            font-size: 24px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 15px;
            display: block;
        }
        
        .form-control-huge {
            font-size: 24px;
            padding: 24px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            width: 100%;
            transition: all 0.3s;
        }
        
        .form-control-huge:focus {
            border-color: #206bc4;
            box-shadow: 0 0 0 4px rgba(32, 107, 196, 0.1);
            outline: none;
        }
        
        .btn-huge {
            padding: 24px 48px;
            font-size: 24px;
            border-radius: 16px;
            font-weight: 700;
            width: 100%;
            min-height: 80px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary-huge {
            background: linear-gradient(135deg, #206bc4 0%, #1a5199 100%);
            color: white;
        }
        
        .btn-primary-huge:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(32, 107, 196, 0.3);
        }
        
        .error-message {
            background: #fef2f2;
            border: 2px solid #ef4444;
            color: #991b1b;
            padding: 20px;
            border-radius: 12px;
            font-size: 20px;
            margin-bottom: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <i class="ti ti-target"></i>
                <h1>TaskForge</h1>
                <p>Tablet Mode</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="ti ti-alert-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group-large">
                    <label class="form-label-large">Username</label>
                    <input type="text" name="username" class="form-control-huge" autofocus required>
                </div>
                
                <div class="form-group-large">
                    <label class="form-label-large">Password</label>
                    <input type="password" name="password" class="form-control-huge" required>
                </div>
                
                <button type="submit" class="btn-huge btn-primary-huge">
                    <i class="ti ti-login"></i> Sign In
                </button>
            </form>
        </div>
    </div>
    
    <script>
        // Request fullscreen on load
        if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen().catch(() => {});
        }
    </script>
</body>
</html>
