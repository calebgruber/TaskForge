<?php
/**
 * TaskForge Auto-Update System
 * 
 * This script pulls the latest code from GitHub repository and updates the installation.
 * It requires a password defined in config.php for security.
 * 
 * IMPORTANT: 
 * - Change INSTALL_PASSWORD in config/config.php before using
 * - Set repository to public on GitHub
 * - Backup your database and uploads folder before updating
 * - This will DELETE and replace all code files
 */

// Prevent execution if accessed directly without proper setup
$isSetup = file_exists(__DIR__ . '/config/config.php');

if ($isSetup) {
    require_once __DIR__ . '/config/config.php';
} else {
    // Initial setup mode
    define('APP_NAME', 'TaskForge');
    define('BASE_PATH', __DIR__);
    define('INSTALL_PASSWORD', 'TaskForge2024!Install');
    define('GITHUB_REPO_OWNER', 'calebgruber');
    define('GITHUB_REPO_NAME', 'TaskForge');
    define('GITHUB_BRANCH', 'copilot/add-barcode-scanning-rewards');
}

session_start();

// Check if password is provided
$authenticated = false;
$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === INSTALL_PASSWORD) {
        $_SESSION['install_authenticated'] = true;
        $authenticated = true;
    } else {
        $error = 'Invalid password. Check INSTALL_PASSWORD in config/config.php';
    }
}

if (isset($_SESSION['install_authenticated']) && $_SESSION['install_authenticated'] === true) {
    $authenticated = true;
}

// Handle update action
if ($authenticated && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'check_update':
            $result = checkForUpdates();
            $message = $result['message'];
            break;
            
        case 'perform_update':
            $result = performUpdate();
            $message = $result['message'];
            $error = $result['error'] ?? '';
            break;
            
        case 'logout':
            unset($_SESSION['install_authenticated']);
            $authenticated = false;
            $message = 'Logged out successfully';
            break;
    }
}

function checkForUpdates() {
    $repoUrl = sprintf(
        'https://api.github.com/repos/%s/%s/commits/%s',
        GITHUB_REPO_OWNER,
        GITHUB_REPO_NAME,
        GITHUB_BRANCH
    );
    
    $ch = curl_init($repoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'TaskForge-Updater');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/vnd.github.v3+json']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        $latestCommit = $data['sha'] ?? 'unknown';
        $commitDate = $data['commit']['committer']['date'] ?? 'unknown';
        $commitMessage = $data['commit']['message'] ?? 'No message';
        
        return [
            'success' => true,
            'message' => "Latest commit: " . substr($latestCommit, 0, 7) . " on " . $commitDate . " - " . $commitMessage
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Could not check for updates. HTTP ' . $httpCode
    ];
}

function performUpdate() {
    set_time_limit(300); // 5 minutes
    
    $steps = [];
    $basePath = BASE_PATH;
    
    // Step 1: Download repository ZIP
    $steps[] = "Downloading latest code from GitHub...";
    $zipUrl = sprintf(
        'https://github.com/%s/%s/archive/refs/heads/%s.zip',
        GITHUB_REPO_OWNER,
        GITHUB_REPO_NAME,
        GITHUB_BRANCH
    );
    
    $zipFile = $basePath . '/temp_update.zip';
    $zipContent = @file_get_contents($zipUrl);
    
    if (!$zipContent) {
        return [
            'success' => false,
            'error' => 'Failed to download repository. Make sure repository is public.',
            'message' => implode("\n", $steps)
        ];
    }
    
    file_put_contents($zipFile, $zipContent);
    $steps[] = "✓ Downloaded " . number_format(strlen($zipContent)) . " bytes";
    
    // Step 2: Extract ZIP
    $steps[] = "Extracting files...";
    $zip = new ZipArchive;
    if ($zip->open($zipFile) !== true) {
        unlink($zipFile);
        return [
            'success' => false,
            'error' => 'Failed to extract ZIP file',
            'message' => implode("\n", $steps)
        ];
    }
    
    $extractPath = $basePath . '/temp_update';
    if (!is_dir($extractPath)) {
        mkdir($extractPath, 0755, true);
    }
    
    $zip->extractTo($extractPath);
    $zip->close();
    unlink($zipFile);
    $steps[] = "✓ Files extracted to temporary directory";
    
    // Step 3: Find extracted directory (GitHub creates a subdirectory)
    $extractedDirs = glob($extractPath . '/*', GLOB_ONLYDIR);
    if (empty($extractedDirs)) {
        return [
            'success' => false,
            'error' => 'Could not find extracted directory',
            'message' => implode("\n", $steps)
        ];
    }
    
    $sourcePath = $extractedDirs[0];
    
    // Step 4: Backup critical files
    $steps[] = "Backing up critical files...";
    $backupPath = $basePath . '/backup_' . date('Y-m-d_His');
    mkdir($backupPath, 0755, true);
    
    // Backup config if it exists
    if (file_exists($basePath . '/config/config.php')) {
        @copy($basePath . '/config/config.php', $backupPath . '/config.php');
    }
    
    // Backup uploads folder
    if (is_dir($basePath . '/public/uploads')) {
        recursiveCopy($basePath . '/public/uploads', $backupPath . '/uploads');
    }
    
    $steps[] = "✓ Backup created at: " . basename($backupPath);
    
    // Step 5: Remove old files (except protected directories)
    $steps[] = "Removing old files...";
    $protectedDirs = ['uploads', 'backup_', 'temp_update'];
    
    // Delete old directories except protected ones
    $dirs = glob($basePath . '/*', GLOB_ONLYDIR);
    foreach ($dirs as $dir) {
        $dirName = basename($dir);
        $protected = false;
        foreach ($protectedDirs as $protectedDir) {
            if (strpos($dirName, $protectedDir) === 0) {
                $protected = true;
                break;
            }
        }
        if (!$protected && $dirName !== 'public') {
            recursiveDelete($dir);
        }
    }
    
    // Delete old files in public except uploads
    if (is_dir($basePath . '/public')) {
        $publicItems = glob($basePath . '/public/*');
        foreach ($publicItems as $item) {
            if (basename($item) !== 'uploads') {
                if (is_dir($item)) {
                    recursiveDelete($item);
                } else {
                    @unlink($item);
                }
            }
        }
    }
    
    $steps[] = "✓ Old files removed";
    
    // Step 6: Copy new files
    $steps[] = "Installing new files...";
    recursiveCopy($sourcePath, $basePath);
    $steps[] = "✓ New files installed";
    
    // Step 7: Restore config
    if (file_exists($backupPath . '/config.php')) {
        copy($backupPath . '/config.php', $basePath . '/config/config.php');
        $steps[] = "✓ Configuration restored";
    }
    
    // Step 8: Set permissions
    $steps[] = "Setting permissions...";
    if (is_dir($basePath . '/public/uploads')) {
        @chmod($basePath . '/public/uploads', 0755);
        @chmod($basePath . '/public/uploads/icons', 0755);
    }
    $steps[] = "✓ Permissions set";
    
    // Step 9: Cleanup
    $steps[] = "Cleaning up...";
    recursiveDelete($extractPath);
    $steps[] = "✓ Temporary files removed";
    
    // Step 10: Database update check
    $steps[] = "\n⚠️ IMPORTANT: Check if database updates are needed!";
    $steps[] = "Run database/schema.sql if there are new tables or columns.";
    
    $steps[] = "\n✅ UPDATE COMPLETE!";
    $steps[] = "Backup saved to: " . basename($backupPath);
    
    return [
        'success' => true,
        'message' => implode("\n", $steps)
    ];
}

function recursiveCopy($src, $dst) {
    if (!is_dir($dst)) {
        mkdir($dst, 0755, true);
    }
    
    $dir = opendir($src);
    while (($file = readdir($dir)) !== false) {
        if ($file !== '.' && $file !== '..') {
            $srcFile = $src . '/' . $file;
            $dstFile = $dst . '/' . $file;
            
            if (is_dir($srcFile)) {
                recursiveCopy($srcFile, $dstFile);
            } else {
                copy($srcFile, $dstFile);
            }
        }
    }
    closedir($dir);
}

function recursiveDelete($dir) {
    if (!is_dir($dir)) {
        return @unlink($dir);
    }
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? recursiveDelete($path) : @unlink($path);
    }
    
    return @rmdir($dir);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskForge Auto-Update System</title>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet"/>
    <style>
        .update-log {
            background: #1e1e1e;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            padding: 20px;
            border-radius: 5px;
            white-space: pre-wrap;
            max-height: 500px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="page page-center">
        <div class="container-tight py-4">
            <div class="text-center mb-4">
                <h1><i class="ti ti-download"></i> <?php echo APP_NAME; ?> Auto-Update</h1>
                <div class="text-muted">System Update & Installation</div>
            </div>
            
            <?php if (!$authenticated): ?>
            <!-- Login Form -->
            <div class="card card-md">
                <div class="card-body">
                    <h2 class="h2 text-center mb-4">Enter Install Password</h2>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="ti ti-alert-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-warning">
                        <i class="ti ti-alert-triangle"></i>
                        <strong>Warning:</strong> This will update all system files from GitHub. 
                        Make sure you have backed up your database and custom files!
                    </div>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Install Password</label>
                            <input type="password" name="password" class="form-control" required autofocus>
                            <small class="form-hint">Defined as INSTALL_PASSWORD in config/config.php</small>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-lock-open"></i> Authenticate
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="text-center text-muted mt-3">
                <a href="/index.php">← Back to TaskForge</a>
            </div>
            
            <?php else: ?>
            <!-- Update Interface -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">System Update Control Panel</h3>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                    <div class="alert <?php echo $error ? 'alert-danger' : 'alert-success'; ?> mb-3">
                        <?php if ($error): ?>
                            <h4><i class="ti ti-alert-circle"></i> Update Failed</h4>
                            <div class="update-log"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <div class="update-log"><?php echo htmlspecialchars($message); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <h4>Repository Information</h4>
                        <table class="table">
                            <tr>
                                <td><strong>Repository:</strong></td>
                                <td><?php echo GITHUB_REPO_OWNER; ?>/<?php echo GITHUB_REPO_NAME; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Branch:</strong></td>
                                <td><?php echo GITHUB_BRANCH; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Current Version:</strong></td>
                                <td><?php echo defined('APP_VERSION') ? APP_VERSION : 'Unknown'; ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="alert alert-info">
                        <h4><i class="ti ti-info-circle"></i> Before Updating</h4>
                        <ul class="mb-0">
                            <li>✓ Backup your database via phpMyAdmin</li>
                            <li>✓ The uploads folder will be preserved automatically</li>
                            <li>✓ Your config.php will be restored after update</li>
                            <li>✓ All other code files will be replaced</li>
                            <li>✓ Repository must be set to PUBLIC on GitHub</li>
                        </ul>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <form method="POST">
                                <input type="hidden" name="action" value="check_update">
                                <button type="submit" class="btn btn-info w-100 mb-2">
                                    <i class="ti ti-refresh"></i> Check for Updates
                                </button>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <form method="POST" onsubmit="return confirm('This will replace all code files! Continue?');">
                                <input type="hidden" name="action" value="perform_update">
                                <button type="submit" class="btn btn-success w-100 mb-2">
                                    <i class="ti ti-download"></i> Perform Update
                                </button>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <form method="POST">
                                <input type="hidden" name="action" value="logout">
                                <button type="submit" class="btn btn-secondary w-100 mb-2">
                                    <i class="ti ti-logout"></i> Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-body">
                    <h4>After Update Checklist</h4>
                    <ul>
                        <li>Visit your website to verify it's working</li>
                        <li>Check for database updates in database/schema.sql</li>
                        <li>Test login and core functionality</li>
                        <li>Verify printer/scanner settings if using hardware</li>
                        <li>Check Admin panel for any new features</li>
                    </ul>
                    <a href="/index.php" class="btn btn-primary">
                        <i class="ti ti-home"></i> Go to TaskForge
                    </a>
                </div>
            </div>
            
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
</body>
</html>
