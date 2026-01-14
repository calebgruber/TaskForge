<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$pageTitle = 'Settings';
$db = Database::getInstance();

$message = '';
$error = '';

// Function to detect USB printers
function detectUSBPrinters() {
    $printers = [];
    
    // Check /dev/usb/ directory
    if (is_dir('/dev/usb/')) {
        $files = glob('/dev/usb/lp*');
        foreach ($files as $file) {
            if (is_writable($file)) {
                $printers[] = [
                    'path' => $file,
                    'name' => basename($file),
                    'type' => 'USB',
                    'writable' => true
                ];
            }
        }
    }
    
    // Check /dev/ directory for direct LP devices
    $files = glob('/dev/lp*');
    foreach ($files as $file) {
        if (is_writable($file)) {
            $printers[] = [
                'path' => $file,
                'name' => basename($file),
                'type' => 'USB/Parallel',
                'writable' => true
            ];
        }
    }
    
    // Try to use lpstat command to detect CUPS printers
    if (function_exists('exec')) {
        $output = [];
        @exec('lpstat -p 2>&1', $output);
        foreach ($output as $line) {
            if (preg_match('/printer (.+?) /', $line, $matches)) {
                $printerName = $matches[1];
                $printers[] = [
                    'path' => $printerName,
                    'name' => $printerName,
                    'type' => 'CUPS',
                    'writable' => true
                ];
            }
        }
    }
    
    return $printers;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Printer settings
        setSetting('printer_enabled', isset($_POST['printer_enabled']) ? '1' : '0', 'bool');
        setSetting('printer_method', $_POST['printer_method'] ?? 'browser', 'string');
        setSetting('printer_type', $_POST['printer_type'] ?? 'usb', 'string');
        setSetting('printer_device', $_POST['printer_device'] ?? '/dev/usb/lp0', 'string');
        setSetting('printer_ip', $_POST['printer_ip'] ?? '', 'string');
        setSetting('printer_port', $_POST['printer_port'] ?? '9100', 'int');
        setSetting('auto_print_completion', isset($_POST['auto_print_completion']) ? '1' : '0', 'bool');
        
        // Scanner settings
        setSetting('scanner_enabled', isset($_POST['scanner_enabled']) ? '1' : '0', 'bool');
        setSetting('scanner_prefix', $_POST['scanner_prefix'] ?? '', 'string');
        setSetting('scanner_suffix', $_POST['scanner_suffix'] ?? "\n", 'string');
        
        // SMS settings
        setSetting('sms_enabled', isset($_POST['sms_enabled']) ? '1' : '0', 'bool');
        setSetting('sms_provider', $_POST['sms_provider'] ?? 'twilio', 'string');
        setSetting('sms_api_key', $_POST['sms_api_key'] ?? '', 'string');
        setSetting('sms_api_secret', $_POST['sms_api_secret'] ?? '', 'string');
        setSetting('reminder_check_interval', $_POST['reminder_check_interval'] ?? '5', 'int');
        
        $message = 'Settings saved successfully!';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Detect available printers
$detectedPrinters = detectUSBPrinters();

// Load current settings
$settings = [
    'printer_enabled' => getSetting('printer_enabled', true),
    'printer_method' => getSetting('printer_method', 'browser'),
    'printer_type' => getSetting('printer_type', 'usb'),
    'printer_device' => getSetting('printer_device', '/dev/usb/lp0'),
    'printer_ip' => getSetting('printer_ip', '192.168.1.100'),
    'printer_port' => getSetting('printer_port', 9100),
    'auto_print_completion' => getSetting('auto_print_completion', true),
    'scanner_enabled' => getSetting('scanner_enabled', true),
    'scanner_prefix' => getSetting('scanner_prefix', ''),
    'scanner_suffix' => getSetting('scanner_suffix', "\n"),
    'sms_enabled' => getSetting('sms_enabled', false),
    'sms_provider' => getSetting('sms_provider', 'twilio'),
    'sms_api_key' => getSetting('sms_api_key', ''),
    'sms_api_secret' => getSetting('sms_api_secret', ''),
    'reminder_check_interval' => getSetting('reminder_check_interval', 5),
];

include __DIR__ . '/../includes/header.php';
?>

<div class="page-body">
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title"><i class="ti ti-settings"></i> System Settings</h2>
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
        
        <form method="POST">
            <div class="row mt-3">
                <div class="col-lg-6">
                    <!-- Printer Settings -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title"><i class="ti ti-printer"></i> Thermal Printer Settings</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input type="checkbox" name="printer_enabled" class="form-check-input" value="1" <?php echo $settings['printer_enabled'] ? 'checked' : ''; ?>>
                                    <span class="form-check-label">Enable thermal printer</span>
                                </label>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Print Method</label>
                                <select name="printer_method" class="form-select" id="printerMethod">
                                    <option value="browser" <?php echo $settings['printer_method'] === 'browser' ? 'selected' : ''; ?>>Browser Print Dialog (Recommended for USB)</option>
                                    <option value="direct" <?php echo $settings['printer_method'] === 'direct' ? 'selected' : ''; ?>>Direct ESC/POS (For Network Printers)</option>
                                </select>
                                <small class="form-hint">
                                    <strong>Browser Print Dialog:</strong> Works with any printer (USB, network, etc.). Shows standard Windows/Mac print dialog.<br>
                                    <strong>Direct ESC/POS:</strong> Silent printing for network-accessible thermal printers. Requires server access to printer.
                                </small>
                            </div>
                            
                            <div id="direct-printer-settings">
                            <div class="mb-3">
                                <label class="form-label">Connection Type</label>
                                <select name="printer_type" class="form-select" id="printerType">
                                    <option value="usb" <?php echo $settings['printer_type'] === 'usb' ? 'selected' : ''; ?>>USB</option>
                                    <option value="ethernet" <?php echo $settings['printer_type'] === 'ethernet' ? 'selected' : ''; ?>>Ethernet (Network)</option>
                                </select>
                            </div>
                            
                            <div class="mb-3" id="usb-settings">
                                <label class="form-label">USB Device Path</label>
                                <?php if (!empty($detectedPrinters)): ?>
                                    <select name="printer_device" class="form-select" id="printerDeviceSelect">
                                        <?php foreach ($detectedPrinters as $printer): ?>
                                            <option value="<?php echo h($printer['path']); ?>" <?php echo $settings['printer_device'] === $printer['path'] ? 'selected' : ''; ?>>
                                                <?php echo h($printer['name']); ?> - <?php echo h($printer['type']); ?> (<?php echo h($printer['path']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                        <option value="custom">Custom path...</option>
                                    </select>
                                    <input type="text" name="printer_device_custom" class="form-control mt-2" id="customDevicePath" style="display: none;" placeholder="/dev/usb/lp0">
                                    <small class="form-hint text-success">
                                        <i class="ti ti-check"></i> <?php echo count($detectedPrinters); ?> printer(s) detected
                                    </small>
                                <?php else: ?>
                                    <input type="text" name="printer_device" class="form-control" value="<?php echo h($settings['printer_device']); ?>" placeholder="/dev/usb/lp0">
                                    <small class="form-hint text-warning">
                                        <i class="ti ti-alert-triangle"></i> No printers auto-detected. Enter path manually or check connections.
                                    </small>
                                <?php endif; ?>
                                <small class="form-hint">Common paths: /dev/usb/lp0, /dev/usb/lp1, or check with "ls /dev/usb/"</small>
                            </div>
                            
                            <div class="mb-3" id="ethernet-settings">
                                <label class="form-label">Printer IP Address</label>
                                <input type="text" name="printer_ip" class="form-control" value="<?php echo h($settings['printer_ip']); ?>" placeholder="192.168.1.100">
                            </div>
                            
                            <div class="mb-3" id="ethernet-port">
                                <label class="form-label">Printer Port</label>
                                <input type="number" name="printer_port" class="form-control" value="<?php echo $settings['printer_port']; ?>" placeholder="9100">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input type="checkbox" name="auto_print_completion" class="form-check-input" value="1" <?php echo $settings['auto_print_completion'] ? 'checked' : ''; ?>>
                                    <span class="form-check-label">Auto-print completion receipts</span>
                                </label>
                            </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Scanner Settings -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title"><i class="ti ti-scan"></i> Barcode Scanner Settings</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input type="checkbox" name="scanner_enabled" class="form-check-input" value="1" <?php echo $settings['scanner_enabled'] ? 'checked' : ''; ?>>
                                    <span class="form-check-label">Enable barcode scanner</span>
                                </label>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Scanner Prefix</label>
                                <input type="text" name="scanner_prefix" class="form-control" value="<?php echo h($settings['scanner_prefix']); ?>" placeholder="Characters before barcode">
                                <small class="form-hint">Leave empty if none</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Scanner Suffix</label>
                                <input type="text" name="scanner_suffix" class="form-control" value="<?php echo h($settings['scanner_suffix']); ?>" placeholder="Characters after barcode">
                                <small class="form-hint">Usually Enter key (\n)</small>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="ti ti-info-circle"></i> Zebra DS81XX-HC operates in keyboard emulation mode. No special drivers needed!
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <!-- SMS Settings -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title"><i class="ti ti-message"></i> SMS Reminder Settings</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input type="checkbox" name="sms_enabled" class="form-check-input" value="1" <?php echo $settings['sms_enabled'] ? 'checked' : ''; ?>>
                                    <span class="form-check-label">Enable SMS reminders</span>
                                </label>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">SMS Provider</label>
                                <select name="sms_provider" class="form-select">
                                    <option value="twilio" <?php echo $settings['sms_provider'] === 'twilio' ? 'selected' : ''; ?>>Twilio</option>
                                    <option value="nexmo" <?php echo $settings['sms_provider'] === 'nexmo' ? 'selected' : ''; ?>>Nexmo/Vonage</option>
                                    <option value="aws" <?php echo $settings['sms_provider'] === 'aws' ? 'selected' : ''; ?>>AWS SNS</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">API Key / Account SID</label>
                                <input type="text" name="sms_api_key" class="form-control" value="<?php echo h($settings['sms_api_key']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">API Secret / Auth Token</label>
                                <input type="password" name="sms_api_secret" class="form-control" value="<?php echo h($settings['sms_api_secret']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Reminder Check Interval (minutes)</label>
                                <input type="number" name="reminder_check_interval" class="form-control" value="<?php echo $settings['reminder_check_interval']; ?>" min="1" max="60">
                                <small class="form-hint">How often to check for due reminders</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-footer">
                            <div class="d-flex">
                                <a href="/index.php" class="btn btn-link">Back to Dashboard</a>
                                <button type="submit" class="btn btn-primary ms-auto">
                                    <i class="ti ti-device-floppy"></i> Save Settings
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle printer method and connection settings
const printerMethod = document.getElementById('printerMethod');
const directPrinterSettings = document.getElementById('direct-printer-settings');
const printerType = document.getElementById('printerType');
const usbSettings = document.getElementById('usb-settings');
const ethernetSettings = document.getElementById('ethernet-settings');
const ethernetPort = document.getElementById('ethernet-port');

function togglePrinterMethod() {
    if (printerMethod.value === 'browser') {
        directPrinterSettings.style.display = 'none';
    } else {
        directPrinterSettings.style.display = 'block';
        togglePrinterSettings(); // Also update connection type visibility
    }
}

function togglePrinterSettings() {
    if (printerType.value === 'usb') {
        usbSettings.style.display = 'block';
        ethernetSettings.style.display = 'none';
        ethernetPort.style.display = 'none';
    } else {
        usbSettings.style.display = 'none';
        ethernetSettings.style.display = 'block';
        ethernetPort.style.display = 'block';
    }
}

printerMethod.addEventListener('change', togglePrinterMethod);
printerType.addEventListener('change', togglePrinterSettings);
togglePrinterMethod(); // Initialize on page load
togglePrinterSettings(); // Initialize on page load

// Handle custom device path
const deviceSelect = document.getElementById('printerDeviceSelect');
const customPathInput = document.getElementById('customDevicePath');

if (deviceSelect && customPathInput) {
    deviceSelect.addEventListener('change', function() {
        if (this.value === 'custom') {
            customPathInput.style.display = 'block';
            customPathInput.name = 'printer_device';
            this.name = '';
        } else {
            customPathInput.style.display = 'none';
            customPathInput.name = '';
            this.name = 'printer_device';
        }
    });
    
    // Initialize on page load
    if (deviceSelect.value === 'custom') {
        customPathInput.style.display = 'block';
        customPathInput.name = 'printer_device';
        deviceSelect.name = '';
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
