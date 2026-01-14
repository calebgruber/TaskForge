<?php
/**
 * JSPrintManager Printer Management
 * Client-side printing using JSPrintManager
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions/auth.php';
require_once __DIR__ . '/../includes/functions/helpers.php';
require_once __DIR__ . '/../includes/classes/Database.php';
require_once __DIR__ . '/../includes/classes/User.php';
require_once __DIR__ . '/../includes/classes/Task.php';

requireLogin();
requireAdmin();

$pageTitle = 'JSPrintManager Printer';
$db = Database::getInstance();

// Get filter from query parameter
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query based on filter
$whereClause = '';
if ($statusFilter === 'active') {
    $whereClause = "WHERE t.status = 'active'";
} elseif ($statusFilter === 'completed') {
    $whereClause = "WHERE t.status = 'completed'";
} elseif ($statusFilter === 'overdue') {
    $whereClause = "WHERE t.status = 'active' AND t.due_date < NOW()";
}

// Get tasks for printing
$tasks = $db->fetchAll("
    SELECT t.*, c.name as category_name, c.icon_id as category_icon_id,
           u.username as user_name,
           CASE 
               WHEN t.status = 'completed' THEN 'completed'
               WHEN t.status = 'active' AND t.due_date < NOW() THEN 'overdue'
               ELSE 'active'
           END as display_status
    FROM tasks t
    LEFT JOIN categories c ON t.category_id = c.id
    LEFT JOIN users u ON t.user_id = u.id
    $whereClause
    ORDER BY 
        CASE 
            WHEN t.status = 'active' AND t.due_date < NOW() THEN 1
            WHEN t.status = 'active' THEN 2
            ELSE 3
        END,
        t.created_at DESC
    LIMIT 100
");

include __DIR__ . '/../includes/header.php';
?>

<style>
    .jspm-status {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 8px;
    }
    .jspm-status.connected {
        background-color: #d1e7dd;
        border: 1px solid #0f5132;
        color: #0f5132;
    }
    .jspm-status.disconnected {
        background-color: #f8d7da;
        border: 1px solid #842029;
        color: #842029;
    }
    .jspm-status.connecting {
        background-color: #cff4fc;
        border: 1px solid #055160;
        color: #055160;
    }
    #printerSelect {
        min-width: 300px;
        padding: 8px;
        font-size: 14px;
    }
    .print-test-btn {
        margin-top: 10px;
    }
</style>

<!-- JSPrintManager Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bluebird/3.3.5/bluebird.min.js"></script>
<script src="https://cdn.neodynamic.com/jspm/5.0.0/JSPrintManager.js"></script>

<div class="container-xl">
    <div class="page-header d-print-none">
        <div class="row align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2" />
                        <path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4" />
                        <rect x="7" y="13" width="10" height="8" rx="2" />
                    </svg>
                    JSPrintManager Printer
                </h2>
                <div class="text-muted mt-1">Client-side printing via JSPrintManager</div>
            </div>
        </div>
    </div>

    <!-- JSPrintManager Status -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">JSPrintManager Status</h3>
                </div>
                <div class="card-body">
                    <div id="jspm-status" class="jspm-status connecting">
                        <strong>Connecting to JSPrintManager...</strong>
                        <div class="mt-2">Please ensure JSPrintManager Client is installed and running.</div>
                    </div>

                    <div id="printer-selection" style="display: none;">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <label for="printerSelect" class="form-label mb-0"><strong>Select Printer:</strong></label>
                            </div>
                            <div class="col-auto">
                                <select id="printerSelect" class="form-select">
                                    <option value="">Loading printers...</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <button onclick="refreshPrinters()" class="btn btn-secondary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" /><path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" /></svg>
                                    Refresh
                                </button>
                            </div>
                        </div>
                        <button onclick="printTest()" class="btn btn-primary print-test-btn">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2" /><path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4" /><rect x="7" y="13" width="10" height="8" rx="2" /></svg>
                            Print Test Receipt
                        </button>
                    </div>

                    <div class="alert alert-info mt-3">
                        <h4 class="alert-title">Installation Required</h4>
                        <p>JSPrintManager Client must be installed on this device:</p>
                        <ol class="mb-0">
                            <li>Download from <a href="https://neodynamic.com/downloads/jspm" target="_blank">https://neodynamic.com/downloads/jspm</a></li>
                            <li>Install the Windows client application</li>
                            <li>Ensure it's running (check system tray)</li>
                            <li>Refresh this page</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link <?= $statusFilter === 'all' ? 'active' : '' ?>" href="?status=all">All Tasks</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $statusFilter === 'active' ? 'active' : '' ?>" href="?status=active">Active</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $statusFilter === 'overdue' ? 'active' : '' ?>" href="?status=overdue">Overdue</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $statusFilter === 'completed' ? 'active' : '' ?>" href="?status=completed">Completed</a>
                        </li>
                    </ul>
                </div>
                <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>User</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>XP</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tasks)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No tasks found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($tasks as $task): ?>
                                    <tr <?= $task['display_status'] === 'overdue' ? 'class="table-danger"' : '' ?>>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($task['urgency'] === 'high'): ?>
                                                    <span class="badge bg-red me-2">!</span>
                                                <?php elseif ($task['urgency'] === 'medium'): ?>
                                                    <span class="badge bg-yellow me-2">!</span>
                                                <?php endif; ?>
                                                <div><?= htmlspecialchars($task['title']) ?></div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($task['user_name']) ?></td>
                                        <td>
                                            <?php if ($task['category_name']): ?>
                                                <span class="badge"><?= htmlspecialchars($task['category_name']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($task['display_status'] === 'completed'): ?>
                                                <span class="badge bg-success">Completed</span>
                                            <?php elseif ($task['display_status'] === 'overdue'): ?>
                                                <span class="badge bg-danger">Overdue</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($task['status'] === 'completed' && $task['completed_at']): ?>
                                                <?= date('M j, Y', strtotime($task['completed_at'])) ?>
                                            <?php elseif ($task['due_date']): ?>
                                                <?= date('M j, Y', strtotime($task['due_date'])) ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($task['xp_value']) ?> XP</td>
                                        <td>
                                            <button onclick='printTaskReceipt(<?= json_encode($task) ?>)' class="btn btn-sm btn-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2" /><path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4" /><rect x="7" y="13" width="10" height="8" rx="2" /></svg>
                                                Print
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // ESC/POS Commands as byte arrays
    const ESC = 0x1B;
    const GS = 0x1D;
    
    let selectedPrinter = null;
    
    // Wait for JSPrintManager library to load
    function initializeJSPM() {
        if (typeof JSPM === 'undefined') {
            setTimeout(initializeJSPM, 100);
            return;
        }
        
        // Initialize JSPrintManager
        JSPM.JSPrintManager.auto_reconnect = true;
        JSPM.JSPrintManager.start();
        
        JSPM.JSPrintManager.WS.onStatusChanged = function() {
            if (JSPM.JSPrintManager.websocket_status == JSPM.WSStatus.Open) {
                document.getElementById('jspm-status').className = 'jspm-status connected';
                document.getElementById('jspm-status').innerHTML = '<strong>✓ Connected to JSPrintManager</strong><div class="mt-2">Client-side printing is available.</div>';
                document.getElementById('printer-selection').style.display = 'block';
                refreshPrinters();
            } else if (JSPM.JSPrintManager.websocket_status == JSPM.WSStatus.Closed) {
                document.getElementById('jspm-status').className = 'jspm-status disconnected';
                document.getElementById('jspm-status').innerHTML = '<strong>✗ JSPrintManager Not Connected</strong><div class="mt-2">Please install and run JSPrintManager Client.</div>';
                document.getElementById('printer-selection').style.display = 'none';
            } else if (JSPM.JSPrintManager.websocket_status == JSPM.WSStatus.Blocked) {
                document.getElementById('jspm-status').className = 'jspm-status disconnected';
                document.getElementById('jspm-status').innerHTML = '<strong>✗ JSPrintManager Blocked</strong><div class="mt-2">Please allow the connection in your browser.</div>';
                document.getElementById('printer-selection').style.display = 'none';
            }
        };
    }
    
    // Start initialization when page loads
    window.addEventListener('load', initializeJSPM);
    
    // Refresh printer list
    function refreshPrinters() {
        JSPM.JSPrintManager.getPrinters().then(function(printers) {
            const select = document.getElementById('printerSelect');
            select.innerHTML = '';
            
            if (printers.length === 0) {
                select.innerHTML = '<option value="">No printers found</option>';
                return;
            }
            
            printers.forEach(function(printer) {
                const option = document.createElement('option');
                option.value = printer;
                option.textContent = printer;
                select.appendChild(option);
            });
            
            selectedPrinter = printers[0];
        });
    }
    
    document.getElementById('printerSelect')?.addEventListener('change', function(e) {
        selectedPrinter = e.target.value;
    });
    
    // Generate ESC/POS commands for test receipt
    function generateTestReceipt() {
        const commands = [];
        
        // Initialize
        commands.push(ESC, 0x40);
        
        // Center align, bold, double size
        commands.push(ESC, 0x61, 0x01); // Center
        commands.push(ESC, 0x45, 0x01); // Bold
        commands.push(GS, 0x21, 0x11);  // Double size
        commands.push(...textToBytes("TaskForge Test\n"));
        
        // Reset, feed
        commands.push(ESC, 0x45, 0x00); // Bold off
        commands.push(GS, 0x21, 0x00);  // Normal size
        commands.push(ESC, 0x64, 0x01); // Feed 1 line
        
        // Left align, normal text
        commands.push(ESC, 0x61, 0x00); // Left
        const now = new Date().toLocaleString();
        commands.push(...textToBytes("Date: " + now + "\n"));
        commands.push(...textToBytes("User: <?= getCurrentUser()->get('username') ?>\n"));
        commands.push(ESC, 0x64, 0x01);
        
        commands.push(...textToBytes("This is a test print to verify\n"));
        commands.push(...textToBytes("your printer is working correctly\n"));
        commands.push(...textToBytes("with TaskForge via JSPrintManager.\n"));
        commands.push(ESC, 0x64, 0x02); // Feed 2 lines
        
        // Barcode
        commands.push(ESC, 0x61, 0x01); // Center
        commands.push(GS, 0x68, 0x50); // Height
        commands.push(GS, 0x77, 0x02); // Width
        commands.push(GS, 0x6B, 0x49); // CODE128
        const barcodeData = "TEST" + Date.now();
        commands.push(barcodeData.length);
        commands.push(...textToBytes(barcodeData));
        
        // Feed and cut
        commands.push(ESC, 0x64, 0x03); // Feed 3 lines
        commands.push(GS, 0x56, 0x00);  // Cut
        
        return new Uint8Array(commands);
    }
    
    // Generate ESC/POS commands for task receipt
    function generateTaskReceipt(task) {
        const commands = [];
        
        // Initialize
        commands.push(ESC, 0x40);
        
        // Header
        commands.push(ESC, 0x61, 0x01); // Center
        commands.push(ESC, 0x45, 0x01); // Bold
        commands.push(GS, 0x21, 0x11);  // Double size
        commands.push(...textToBytes("TaskForge\n"));
        commands.push(ESC, 0x45, 0x00);
        commands.push(GS, 0x21, 0x00);
        commands.push(ESC, 0x64, 0x01);
        
        // Task details
        commands.push(ESC, 0x61, 0x00); // Left
        commands.push(ESC, 0x45, 0x01); // Bold
        commands.push(...textToBytes(task.title + "\n"));
        commands.push(ESC, 0x45, 0x00);
        commands.push(ESC, 0x64, 0x01);
        
        if (task.category_name) {
            commands.push(...textToBytes("Category: " + task.category_name + "\n"));
        }
        commands.push(...textToBytes("XP Value: " + task.xp_value + "\n"));
        commands.push(...textToBytes("Status: " + task.display_status + "\n"));
        
        if (task.due_date) {
            commands.push(...textToBytes("Due: " + new Date(task.due_date).toLocaleDateString() + "\n"));
        }
        
        commands.push(ESC, 0x64, 0x02);
        
        // Barcode
        commands.push(ESC, 0x61, 0x01); // Center
        commands.push(GS, 0x68, 0x50);
        commands.push(GS, 0x77, 0x02);
        commands.push(GS, 0x6B, 0x49); // CODE128
        const barcodeData = task.barcode || ("TASK" + task.id);
        commands.push(barcodeData.length);
        commands.push(...textToBytes(barcodeData));
        
        commands.push(ESC, 0x64, 0x03);
        commands.push(GS, 0x56, 0x00); // Cut
        
        return new Uint8Array(commands);
    }
    
    // Helper function to convert text to bytes
    function textToBytes(text) {
        const bytes = [];
        for (let i = 0; i < text.length; i++) {
            bytes.push(text.charCodeAt(i));
        }
        return bytes;
    }
    
    // Print test receipt
    function printTest() {
        if (!selectedPrinter) {
            alert('Please select a printer');
            return;
        }
        
        const escposCommands = generateTestReceipt();
        
        const cpj = new JSPM.ClientPrintJob();
        cpj.clientPrinter = new JSPM.InstalledPrinter(selectedPrinter);
        cpj.printerCommandsCopies = 1;
        cpj.binaryPrinterCommands = btoa(String.fromCharCode.apply(null, escposCommands));
        
        cpj.sendToClient().then(function() {
            alert('Test receipt sent to printer!');
        }).catch(function(error) {
            alert('Print error: ' + error);
        });
    }
    
    // Print task receipt
    function printTaskReceipt(task) {
        if (!selectedPrinter) {
            alert('Please select a printer');
            return;
        }
        
        const escposCommands = generateTaskReceipt(task);
        
        const cpj = new JSPM.ClientPrintJob();
        cpj.clientPrinter = new JSPM.InstalledPrinter(selectedPrinter);
        cpj.printerCommandsCopies = 1;
        cpj.binaryPrinterCommands = btoa(String.fromCharCode.apply(null, escposCommands));
        
        cpj.sendToClient().then(function() {
            alert('Task receipt sent to printer!');
        }).catch(function(error) {
            alert('Print error: ' + error);
        });
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
