<?php
/**
 * TaskForge Configuration File
 * Physical-digital productivity engine
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('America/New_York');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'voxelnodes_taskforge');
define('DB_USER', 'voxelnodes_taskforge');
define('DB_PASS', 'xlLFETJL3$qy');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'TaskForge');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost');
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', BASE_PATH . '/public');
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');

// Security
define('SESSION_LIFETIME', 86400); // 24 hours
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);

// Printer Configuration
define('PRINTER_ENABLED', true);
define('PRINTER_TYPE', 'usb'); // 'usb' or 'ethernet'
define('PRINTER_IP', '192.168.1.100');
define('PRINTER_PORT', 9100);
define('PRINTER_DEVICE', '/dev/usb/lp0'); // USB device path

// Scanner Configuration
define('SCANNER_ENABLED', true);
define('SCANNER_PREFIX', '');
define('SCANNER_SUFFIX', "\n");

// SMS Configuration
define('SMS_ENABLED', false);
define('SMS_PROVIDER', 'twilio'); // twilio, nexmo, etc.
define('SMS_API_KEY', '');
define('SMS_API_SECRET', '');
define('SMS_FROM_NUMBER', '');

// XP and Leveling
define('BASE_XP', 10);
define('MAX_LEVEL', 50);

// File uploads
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('ALLOWED_ICON_TYPES', ['image/png', 'image/jpeg', 'image/gif']);

// Autoloader
spl_autoload_register(function ($class) {
    $file = INCLUDES_PATH . '/classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Include helper functions
require_once INCLUDES_PATH . '/functions/helpers.php';
require_once INCLUDES_PATH . '/functions/auth.php';
