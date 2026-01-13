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
        $user = User::authenticate($username, $password);
        
        if ($user) {
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['username'] = $user->get('username');
            $_SESSION['is_admin'] = $user->get('is_admin');
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
            box-sizing: border-box;
        }
        
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            overflow: hidden;
        }
        
        .login-wrapper {
            display: flex;
            height: 100vh;
        }
        
        /* Left Side - Illustration */
        .login-illustration {
            flex: 1;
            background: linear-gradient(135deg, #206bc4 0%, #1a5199 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .login-illustration::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 40px 40px;
            animation: float 20s linear infinite;
        }
        
        @keyframes float {
            0% { transform: translate(0, 0); }
            100% { transform: translate(40px, 40px); }
        }
        
        .illustration-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }
        
        .illustration-icon {
            font-size: 180px;
            margin-bottom: 40px;
            filter: drop-shadow(0 20px 40px rgba(0,0,0,0.2));
            animation: pulse 3s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .illustration-title {
            font-size: 64px;
            font-weight: 800;
            margin: 0 0 20px 0;
            text-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .illustration-subtitle {
            font-size: 32px;
            opacity: 0.9;
            margin: 0;
            font-weight: 300;
        }
        
        .illustration-features {
            margin-top: 60px;
            display: flex;
            gap: 40px;
            justify-content: center;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 24px;
        }
        
        .feature-item i {
            font-size: 36px;
        }
        
        /* Right Side - Login Form */
        .login-form-side {
            flex: 1;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 60px;
        }
        
        .login-form-container {
            width: 100%;
            max-width: 550px;
        }
        
        .login-header {
            margin-bottom: 50px;
        }
        
        .login-header h2 {
            font-size: 42px;
            font-weight: 700;
            color: #1a202c;
            margin: 0 0 15px 0;
        }
        
        .login-header p {
            font-size: 22px;
            color: #64748b;
            margin: 0;
        }
        
        .form-group-large {
            margin-bottom: 30px;
        }
        
        .form-label-large {
            font-size: 22px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 12px;
            display: block;
        }
        
        .form-control-huge {
            font-size: 22px;
            padding: 22px;
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
            margin-top: 10px;
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
        
        .signup-section {
            margin-top: 50px;
            padding-top: 40px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
        }
        
        .signup-title {
            font-size: 24px;
            font-weight: 600;
            color: #1a202c;
            margin: 0 0 20px 0;
        }
        
        .signup-qr {
            background: white;
            border: 3px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .signup-qr canvas {
            display: block;
        }
        
        .signup-text {
            font-size: 18px;
            color: #64748b;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- Left Side - Illustration -->
        <div class="login-illustration">
            <div class="illustration-content">
                <div class="illustration-icon">
                    <i class="ti ti-target"></i>
                </div>
                <h1 class="illustration-title">TaskForge</h1>
                <p class="illustration-subtitle">Your Physical-Digital Productivity Hub</p>
                
                <div class="illustration-features">
                    <div class="feature-item">
                        <i class="ti ti-qrcode"></i>
                        <span>Scan Tasks</span>
                    </div>
                    <div class="feature-item">
                        <i class="ti ti-trophy"></i>
                        <span>Earn XP</span>
                    </div>
                    <div class="feature-item">
                        <i class="ti ti-printer"></i>
                        <span>Print Receipts</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Side - Login Form -->
        <div class="login-form-side">
            <div class="login-form-container">
                <div class="login-header">
                    <h2>Welcome Back</h2>
                    <p>Sign in to your TaskForge account</p>
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
                
                <div class="signup-section">
                    <h3 class="signup-title">Need an Account?</h3>
                    <div class="signup-qr" id="qrcode"></div>
                    <p class="signup-text">Scan to sign up on the main website</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        // Generate QR code for signup URL
        const baseUrl = window.location.protocol + '//' + window.location.host;
        const signupUrl = baseUrl.replace('/tablet', '') + '/register.php';
        
        new QRCode(document.getElementById("qrcode"), {
            text: signupUrl,
            width: 180,
            height: 180,
            colorDark: "#1a202c",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        
        // Request fullscreen on load
        if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen().catch(() => {});
        }
    </script>
</body>
</html>
