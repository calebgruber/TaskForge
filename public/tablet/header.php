<?php
/**
 * Tablet Mode Header
 * Fullscreen, touch-optimized header
 */

if (!isset($pageTitle)) {
    $pageTitle = 'TaskForge Tablet Mode';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <title><?= htmlspecialchars($pageTitle) ?> - TaskForge</title>
    
    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="../assets/favicons/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="../assets/favicons/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicons/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicons/apple-touch-icon.png">
    <link rel="manifest" href="../assets/favicons/site.webmanifest">
    <meta name="theme-color" content="#206bc4">
    
    <!-- Tabler CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet">
    
    <!-- Tablet Mode Styles -->
    <style>
        * {
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            user-select: none;
        }
        
        input, textarea {
            -webkit-user-select: text;
            user-select: text;
        }
        
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
            background: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        
        .tablet-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 80px;
            background: linear-gradient(135deg, #206bc4 0%, #1a5199 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
        }
        
        .tablet-logo {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 32px;
            font-weight: 700;
        }
        
        .tablet-logo i {
            font-size: 48px;
        }
        
        .tablet-header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .exit-button {
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            padding: 15px 30px;
            font-size: 18px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }
        
        .exit-button:hover {
            background: rgba(255,255,255,0.3);
            border-color: rgba(255,255,255,0.5);
            transform: translateY(-2px);
        }
        
        .tablet-content {
            position: fixed;
            top: 80px;
            left: 0;
            right: 0;
            bottom: 0;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 40px;
            -webkit-overflow-scrolling: touch;
        }
        
        .tablet-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .tablet-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            transition: all 0.3s;
        }
        
        .tablet-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            transform: translateY(-4px);
        }
        
        .user-card {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .user-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #206bc4 0%, #1a5199 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            font-size: 42px;
            font-weight: 700;
            margin: 0 0 15px 0;
            color: #1a202c;
        }
        
        .user-level {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .level-badge {
            background: linear-gradient(135deg, #206bc4 0%, #1a5199 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            font-size: 24px;
            font-weight: 700;
        }
        
        .xp-text {
            font-size: 28px;
            color: #64748b;
            font-weight: 600;
        }
        
        .progress-bar-large {
            height: 24px;
            background: #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
            transition: width 1s ease;
        }
        
        .tablet-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .action-card {
            text-decoration: none;
            color: inherit;
            text-align: center;
            padding: 60px 40px;
            min-height: 280px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .action-card h2 {
            font-size: 32px;
            margin: 20px 0 10px;
            font-weight: 700;
        }
        
        .action-card p {
            font-size: 20px;
            color: #64748b;
            margin: 0;
        }
        
        .icon-huge {
            font-size: 96px !important;
            margin-bottom: 10px;
        }
        
        .scanner-card { border-top: 6px solid #206bc4; }
        .scanner-card i { color: #206bc4; }
        
        .tasks-card { border-top: 6px solid #10b981; }
        .tasks-card i { color: #10b981; }
        
        .create-card { border-top: 6px solid #8b5cf6; }
        .create-card i { color: #8b5cf6; }
        
        .rewards-card { border-top: 6px solid #f59e0b; }
        .rewards-card i { color: #f59e0b; }
        
        .tablet-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            display: flex;
            align-items: center;
            gap: 25px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        }
        
        .stat-icon {
            width: 80px;
            height: 80px;
            border-radius: 16px;
            background: linear-gradient(135deg, #206bc4 0%, #1a5199 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
        }
        
        .stat-value {
            font-size: 48px;
            font-weight: 700;
            color: #1a202c;
            line-height: 1;
        }
        
        .stat-label {
            font-size: 20px;
            color: #64748b;
            margin-top: 8px;
        }
        
        .btn-huge {
            padding: 24px 48px;
            font-size: 24px;
            border-radius: 16px;
            font-weight: 700;
            min-height: 80px;
        }
        
        .form-control-huge {
            font-size: 24px;
            padding: 24px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            min-height: 80px;
        }
        
        .form-control-huge:focus {
            border-color: #206bc4;
            box-shadow: 0 0 0 4px rgba(32, 107, 196, 0.1);
        }
        
        /* Modal Styles */
        .tablet-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .tablet-modal.active {
            display: flex;
        }
        
        .modal-content-large {
            background: white;
            border-radius: 24px;
            padding: 60px;
            max-width: 600px;
            width: 90%;
            box-shadow: 0 24px 48px rgba(0,0,0,0.3);
        }
        
        .modal-title {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .pin-input {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin: 40px 0;
        }
        
        .pin-digit {
            width: 80px;
            height: 80px;
            font-size: 32px;
            text-align: center;
            border: 3px solid #e2e8f0;
            border-radius: 12px;
            font-weight: 700;
        }
        
        .pin-digit:focus {
            border-color: #206bc4;
            outline: none;
        }
        
        .task-card-large {
            background: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 25px;
            transition: all 0.3s;
        }
        
        .task-card-large:hover {
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }
        
        .task-icon-large {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: white;
        }
        
        .task-info-large {
            flex: 1;
        }
        
        .task-title-large {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #1a202c;
        }
        
        .task-meta-large {
            font-size: 20px;
            color: #64748b;
        }
        
        .task-actions-large {
            display: flex;
            gap: 15px;
        }
    </style>
</head>
<body>
    <div class="tablet-header">
        <div class="tablet-logo">
            <i class="ti ti-target"></i>
            <span>TaskForge</span>
        </div>
        <div class="tablet-header-right">
            <a href="../logout.php" class="exit-button" style="text-decoration: none; margin-right: 10px;">
                <i class="ti ti-user-off"></i>
                Logout
            </a>
            <button class="exit-button" onclick="showExitModal()">
                <i class="ti ti-logout"></i>
                Exit Tablet Mode
            </button>
        </div>
    </div>
    
    <div class="tablet-content">
    
    <!-- Exit PIN Modal -->
    <div id="exitModal" class="tablet-modal">
        <div class="modal-content-large">
            <h2 class="modal-title">Enter Admin PIN</h2>
            <p style="text-align: center; font-size: 20px; color: #64748b; margin-bottom: 30px;">
                Enter the 4-digit PIN to exit Tablet Mode
            </p>
            <form id="exitForm" onsubmit="verifyPin(event)">
                <div class="pin-input">
                    <input type="password" class="pin-digit" maxlength="1" id="pin1" autofocus>
                    <input type="password" class="pin-digit" maxlength="1" id="pin2">
                    <input type="password" class="pin-digit" maxlength="1" id="pin3">
                    <input type="password" class="pin-digit" maxlength="1" id="pin4">
                </div>
                <div style="display: flex; gap: 20px;">
                    <button type="button" class="btn btn-secondary btn-huge" onclick="hideExitModal()" style="flex: 1;">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-huge" style="flex: 1;">Verify</button>
                </div>
                <div id="pinError" style="color: #ef4444; text-align: center; margin-top: 20px; font-size: 20px; display: none;">
                    Incorrect PIN. Please try again.
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Prevent context menu
        document.addEventListener('contextmenu', e => e.preventDefault());
        
        // Prevent F11 and other keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'F11' || (e.ctrlKey && (e.key === 'w' || e.key === 't'))) {
                e.preventDefault();
            }
        });
        
        // Request fullscreen on load
        if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen().catch(() => {});
        }
        
        // Exit modal functions
        function showExitModal() {
            document.getElementById('exitModal').classList.add('active');
            document.getElementById('pin1').focus();
        }
        
        function hideExitModal() {
            document.getElementById('exitModal').classList.remove('active');
            document.getElementById('pin1').value = '';
            document.getElementById('pin2').value = '';
            document.getElementById('pin3').value = '';
            document.getElementById('pin4').value = '';
            document.getElementById('pinError').style.display = 'none';
        }
        
        // Auto-advance PIN inputs
        document.querySelectorAll('.pin-digit').forEach((input, index, inputs) => {
            input.addEventListener('input', function() {
                if (this.value.length === 1 && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });
            
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && this.value === '' && index > 0) {
                    inputs[index - 1].focus();
                }
            });
        });
        
        // Verify PIN
        function verifyPin(e) {
            e.preventDefault();
            const pin = document.getElementById('pin1').value + 
                       document.getElementById('pin2').value + 
                       document.getElementById('pin3').value + 
                       document.getElementById('pin4').value;
            
            fetch('exit-verify.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({pin: pin})
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '../index.php';
                } else {
                    document.getElementById('pinError').style.display = 'block';
                    document.getElementById('pin1').value = '';
                    document.getElementById('pin2').value = '';
                    document.getElementById('pin3').value = '';
                    document.getElementById('pin4').value = '';
                    document.getElementById('pin1').focus();
                }
            });
        }
    </script>
