<?php
/**
 * Timezone Verification Page
 * Check that PHP and MySQL are using correct timezone
 */
require_once __DIR__ . '/../config/config.php';

// Get PHP timezone
$phpTimezone = date_default_timezone_get();
$phpTime = date('Y-m-d H:i:s T');
$phpOffset = date('P');

// Get MySQL timezone
$db = Database::getInstance();
$mysqlTimezone = $db->fetchOne("SELECT @@session.time_zone as tz, NOW() as current_time");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timezone Check - TaskForge</title>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet"/>
</head>
<body>
    <div class="page page-center">
        <div class="container-tight py-4">
            <div class="text-center mb-4">
                <h1><i class="ti ti-clock"></i> Timezone Verification</h1>
                <div class="text-muted">TaskForge System Time Check</div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Current Configuration</h3>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <td><strong>Expected Timezone:</strong></td>
                            <td><span class="badge bg-blue">America/New_York (US Eastern)</span></td>
                        </tr>
                        <tr>
                            <td><strong>PHP Timezone:</strong></td>
                            <td>
                                <?php if ($phpTimezone === 'America/New_York'): ?>
                                    <span class="badge bg-green"><i class="ti ti-check"></i> <?php echo $phpTimezone; ?></span>
                                <?php else: ?>
                                    <span class="badge bg-red"><i class="ti ti-alert-circle"></i> <?php echo $phpTimezone; ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>PHP Current Time:</strong></td>
                            <td><strong><?php echo $phpTime; ?></strong></td>
                        </tr>
                        <tr>
                            <td><strong>PHP UTC Offset:</strong></td>
                            <td><?php echo $phpOffset; ?></td>
                        </tr>
                        <tr>
                            <td><strong>MySQL Timezone:</strong></td>
                            <td>
                                <?php 
                                $mysqlTz = $mysqlTimezone['tz'];
                                $correctMysql = ($mysqlTz === '-05:00' || $mysqlTz === '-04:00' || $mysqlTz === 'America/New_York');
                                ?>
                                <?php if ($correctMysql): ?>
                                    <span class="badge bg-green"><i class="ti ti-check"></i> <?php echo $mysqlTz; ?></span>
                                <?php else: ?>
                                    <span class="badge bg-red"><i class="ti ti-alert-circle"></i> <?php echo $mysqlTz; ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>MySQL Current Time:</strong></td>
                            <td><strong><?php echo $mysqlTimezone['current_time']; ?></strong></td>
                        </tr>
                    </table>
                    
                    <?php if ($phpTimezone === 'America/New_York' && $correctMysql): ?>
                        <div class="alert alert-success mt-3">
                            <h4><i class="ti ti-check-circle"></i> Timezone Configuration Correct</h4>
                            <p class="mb-0">Both PHP and MySQL are using US Eastern Time.</p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mt-3">
                            <h4><i class="ti ti-alert-triangle"></i> Timezone Issue Detected</h4>
                            <p>Expected timezone: <strong>America/New_York</strong></p>
                            <p class="mb-0">Check config/config.php and Database.php configuration.</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <h4>Timezone Information</h4>
                        <p><strong>US Eastern Time:</strong></p>
                        <ul>
                            <li><strong>EST</strong> (Eastern Standard Time): UTC-5 (November - March)</li>
                            <li><strong>EDT</strong> (Eastern Daylight Time): UTC-4 (March - November)</li>
                        </ul>
                        <p class="text-muted small">
                            The system automatically handles Daylight Saving Time transitions when using America/New_York timezone.
                        </p>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="/index.php" class="btn btn-primary">
                        <i class="ti ti-arrow-left"></i> Back to TaskForge
                    </a>
                    <button onclick="location.reload()" class="btn btn-secondary ms-2">
                        <i class="ti ti-refresh"></i> Refresh
                    </button>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Quick Reference</h3>
                </div>
                <div class="card-body">
                    <p><strong>Current System Time:</strong></p>
                    <div class="display-6 mb-3"><?php echo date('l, F j, Y - g:i:s A T'); ?></div>
                    
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>What</th>
                                <th>Current Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Date</td>
                                <td><?php echo date('Y-m-d'); ?></td>
                            </tr>
                            <tr>
                                <td>Time</td>
                                <td><?php echo date('H:i:s'); ?></td>
                            </tr>
                            <tr>
                                <td>12-hour format</td>
                                <td><?php echo date('h:i:s A'); ?></td>
                            </tr>
                            <tr>
                                <td>Timezone abbreviation</td>
                                <td><?php echo date('T'); ?> (<?php echo date('e'); ?>)</td>
                            </tr>
                            <tr>
                                <td>Unix timestamp</td>
                                <td><?php echo time(); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
