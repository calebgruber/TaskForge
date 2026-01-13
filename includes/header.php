<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($pageTitle ?? APP_NAME); ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Favicons -->
    <link rel="icon" type="image/svg+xml" href="/assets/favicons/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicons/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicons/apple-touch-icon.png">
    <link rel="manifest" href="/assets/favicons/site.webmanifest">
    <link rel="shortcut icon" href="/assets/favicons/favicon.ico">
    <meta name="theme-color" content="#206bc4">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="TaskForge">
    <meta name="msapplication-TileColor" content="#206bc4">
    <meta name="msapplication-config" content="/assets/favicons/browserconfig.xml">
    
    <!-- Tabler CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet"/>
    
    <style>
        .task-card {
            transition: transform 0.2s;
        }
        .task-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .xp-badge {
            font-weight: bold;
            font-size: 0.9rem;
        }
        .level-progress {
            height: 8px;
        }
        .barcode-display {
            font-family: 'Courier New', monospace;
            font-size: 1.2rem;
            letter-spacing: 2px;
            padding: 10px;
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            text-align: center;
        }
        .scanner-ready {
            background: #d1f2eb;
            border-color: #1abc9c;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    </style>
</head>
<body>
    <?php if (isLoggedIn()): 
        $currentUser = getCurrentUser();
        $progress = $currentUser->getProgressToNextLevel();
    ?>
    <div class="page">
        <header class="navbar navbar-expand-md navbar-light d-print-none">
            <div class="container-xl">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                    <a href="/index.php">
                        <i class="ti ti-target"></i> <?php echo APP_NAME; ?>
                    </a>
                </h1>
                <div class="navbar-nav flex-row order-md-last">
                    <div class="nav-item d-none d-md-flex me-3">
                        <div class="btn-list">
                            <span class="badge bg-green me-2">
                                <i class="ti ti-star"></i> Level <?php echo $currentUser->get('current_level'); ?>
                            </span>
                            <span class="badge bg-blue">
                                <i class="ti ti-bolt"></i> <?php echo number_format($currentUser->get('current_xp')); ?> XP
                            </span>
                        </div>
                    </div>
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                            <span class="avatar avatar-sm">
                                <i class="ti ti-user"></i>
                            </span>
                            <div class="d-none d-xl-block ps-2">
                                <div><?php echo h($currentUser->get('username')); ?></div>
                                <div class="mt-1 small text-muted"><?php echo number_format($currentUser->get('tasks_completed')); ?> tasks completed</div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <a href="/profile.php" class="dropdown-item">
                                <i class="ti ti-user me-2"></i> Profile
                            </a>
                            <a href="/rewards.php" class="dropdown-item">
                                <i class="ti ti-trophy me-2"></i> Rewards
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="/logout.php" class="dropdown-item">
                                <i class="ti ti-logout me-2"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="navbar-expand-md">
            <div class="collapse navbar-collapse" id="navbar-menu">
                <div class="navbar navbar-light">
                    <div class="container-xl">
                        <ul class="navbar-nav">
                            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
                                <a class="nav-link" href="/index.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-home"></i>
                                    </span>
                                    <span class="nav-link-title">Dashboard</span>
                                </a>
                            </li>
                            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'tasks.php' ? 'active' : ''; ?>">
                                <a class="nav-link" href="/tasks.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-list-check"></i>
                                    </span>
                                    <span class="nav-link-title">Tasks</span>
                                </a>
                            </li>
                            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'scanner.php' ? 'active' : ''; ?>">
                                <a class="nav-link" href="/scanner.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-scan"></i>
                                    </span>
                                    <span class="nav-link-title">Scanner</span>
                                </a>
                            </li>
                            <li class="nav-item dropdown <?php echo strpos(basename($_SERVER['PHP_SELF']), 'admin') === 0 ? 'active' : ''; ?>">
                                <a class="nav-link dropdown-toggle" href="#navbar-admin" data-bs-toggle="dropdown" role="button">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-settings"></i>
                                    </span>
                                    <span class="nav-link-title">Admin</span>
                                </a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="/admin/templates.php">
                                        <i class="ti ti-template me-2"></i> Templates
                                    </a>
                                    <a class="dropdown-item" href="/admin/icons.php">
                                        <i class="ti ti-photo me-2"></i> Icons
                                    </a>
                                    <a class="dropdown-item" href="/admin/categories.php">
                                        <i class="ti ti-category me-2"></i> Categories
                                    </a>
                                    <a class="dropdown-item" href="/admin/xp-rules.php">
                                        <i class="ti ti-chart-bar me-2"></i> XP Rules
                                    </a>
                                    <a class="dropdown-item" href="/admin/goals.php">
                                        <i class="ti ti-target me-2"></i> Goals
                                    </a>
                                    <a class="dropdown-item" href="/admin/rewards.php">
                                        <i class="ti ti-trophy me-2"></i> Rewards
                                    </a>
                                    <a class="dropdown-item" href="/admin/printer.php">
                                        <i class="ti ti-printer me-2"></i> Printer
                                    </a>
                                    <a class="dropdown-item" href="/admin/settings.php">
                                        <i class="ti ti-adjustments me-2"></i> Settings
                                    </a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="/tablet/login.php" target="_blank">
                                        <i class="ti ti-device-tablet me-2"></i> Launch Tablet Mode
                                    </a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Level Progress Bar -->
        <?php if (!$progress['is_max_level']): ?>
        <div class="container-xl mt-2">
            <div class="card">
                <div class="card-body py-2">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <strong>Level <?php echo $progress['current_level']; ?></strong>
                        </div>
                        <div class="flex-fill">
                            <div class="progress level-progress">
                                <div class="progress-bar bg-green" style="width: <?php echo $progress['progress']; ?>%" role="progressbar">
                                </div>
                            </div>
                        </div>
                        <div class="ms-3 text-muted small">
                            <?php echo number_format($progress['level_xp']); ?> / <?php echo number_format($progress['xp_needed']); ?> XP
                        </div>
                        <div class="ms-3">
                            <strong>Level <?php echo $progress['next_level']; ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="page-wrapper">
    <?php else: ?>
    <div class="page page-center">
    <?php endif; ?>
