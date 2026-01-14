<?php
/**
 * Printer Class
 * Handles ESC/POS thermal printer communication
 */
class Printer {
    private $connection;
    private $type;
    
    public function __construct() {
        $this->type = PRINTER_TYPE;
    }
    
    public function connect() {
        $db = Database::getInstance();
        $enabled = getSetting('printer_enabled', PRINTER_ENABLED);
        
        if (!$enabled) {
            throw new Exception("Printer is disabled");
        }
        
        // Get printer type from settings
        $printerType = getSetting('printer_type', $this->type);
        
        if ($printerType === 'ethernet') {
            // Get network settings from database
            $printerIp = getSetting('printer_ip', PRINTER_IP);
            $printerPort = getSetting('printer_port', PRINTER_PORT);
            
            $this->connection = fsockopen($printerIp, $printerPort, $errno, $errstr, 10);
            if (!$this->connection) {
                throw new Exception("Cannot connect to printer at $printerIp:$printerPort - $errstr ($errno)");
            }
        } else {
            // USB connection - use device path from settings
            $device = getSetting('printer_device', PRINTER_DEVICE);
            
            if (!file_exists($device)) {
                throw new Exception("Printer device not found: $device. Please configure in Settings.");
            }
            $this->connection = fopen($device, 'w');
            if (!$this->connection) {
                throw new Exception("Cannot open printer device: $device");
            }
        }
        
        return true;
    }
    
    public function disconnect() {
        if ($this->connection) {
            fclose($this->connection);
            $this->connection = null;
        }
    }
    
    private function write($data) {
        if (!$this->connection) {
            throw new Exception("Printer not connected");
        }
        fwrite($this->connection, $data);
    }
    
    // ESC/POS Commands
    private function esc() {
        return chr(27);
    }
    
    private function gs() {
        return chr(29);
    }
    
    public function initialize() {
        $this->write($this->esc() . '@'); // Initialize printer
    }
    
    public function setAlignment($align = 'left') {
        $alignments = [
            'left' => 0,
            'center' => 1,
            'right' => 2
        ];
        $this->write($this->esc() . 'a' . chr($alignments[$align] ?? 0));
    }
    
    public function setBold($enable = true) {
        $this->write($this->esc() . 'E' . chr($enable ? 1 : 0));
    }
    
    public function setFontSize($size = 1) {
        // Size 0-7 (0 = normal, 7 = 8x height and width)
        $size = max(0, min(7, $size));
        $this->write($this->gs() . '!' . chr($size));
    }
    
    public function setUnderline($enable = true) {
        $this->write($this->esc() . '-' . chr($enable ? 1 : 0));
    }
    
    public function text($text) {
        $this->write($text);
    }
    
    public function textLine($text) {
        $this->write($text . "\n");
    }
    
    public function feed($lines = 1) {
        for ($i = 0; $i < $lines; $i++) {
            $this->write("\n");
        }
    }
    
    public function cut($partial = false) {
        $this->write($this->gs() . 'V' . chr($partial ? 1 : 0));
    }
    
    public function printBarcode($data, $type = 'CODE128', $height = 50) {
        // Set barcode height
        $this->write($this->gs() . 'h' . chr($height)); // Height in dots
        
        // Set barcode width
        $this->write($this->gs() . 'w' . chr(2)); // 2 dots width
        
        // Print HRI (human readable) below barcode
        $this->write($this->gs() . 'H' . chr(2));
        
        // Print barcode based on type
        $barcodeTypes = [
            'CODE39' => 4,
            'CODE128' => 73,
            'EAN13' => 67,
            'AZTEC' => 75,    // GS1 DataBar stacked for Aztec-like
            'PDF417' => 76    // PDF417 barcode
        ];
        
        $barcodeType = $barcodeTypes[$type] ?? 73;
        
        // Standard barcode printing for all types
        if (in_array($type, ['CODE128', 'CODE39', 'EAN13'])) {
            $len = strlen($data);
            $this->write($this->gs() . 'k' . chr($barcodeType) . chr($len) . $data);
        } elseif ($type === 'PDF417') {
            // PDF417 2D barcode (ESC/POS command for PDF417)
            // This is a simplified version - exact implementation depends on printer model
            $this->write($this->gs() . '(k' . chr(3) . chr(0) . chr(48) . chr(80) . chr(48)); // Select PDF417
            $len = strlen($data);
            $lenL = $len % 256;
            $lenH = floor($len / 256);
            $this->write($this->gs() . '(k' . chr($lenL + 3) . chr($lenH) . chr(48) . chr(80) . chr(48) . $data);
            $this->write($this->gs() . '(k' . chr(3) . chr(0) . chr(48) . chr(81) . chr(48)); // Print stored data
        } elseif ($type === 'AZTEC') {
            // Aztec 2D barcode (ESC/POS command for Aztec)
            // Similar to PDF417, but with Aztec-specific commands
            $this->write($this->gs() . '(k' . chr(3) . chr(0) . chr(48) . chr(90) . chr(48)); // Select Aztec
            $len = strlen($data);
            $lenL = $len % 256;
            $lenH = floor($len / 256);
            $this->write($this->gs() . '(k' . chr($lenL + 3) . chr($lenH) . chr(48) . chr(90) . chr(48) . $data);
            $this->write($this->gs() . '(k' . chr(3) . chr(0) . chr(48) . chr(81) . chr(48)); // Print stored data
        }
    }
    
    public function printImage($imagePath, $width = 384) {
        // Convert image to monochrome bitmap for thermal printer
        if (!file_exists($imagePath)) {
            return false;
        }
        
        // This is a simplified version - full implementation would
        // convert image to ESC/POS bitmap format
        // For production, use a library like mike42/escpos-php
        
        return true;
    }
    
    /**
     * Get print method from settings
     */
    public static function getPrintMethod() {
        return getSetting('printer_method', 'browser');
    }
    
    /**
     * Check if should use browser print dialog
     */
    public static function useBrowserPrint() {
        return self::getPrintMethod() === 'browser';
    }
    
    /**
     * Generate browser-friendly HTML receipt using templates
     */
    public static function generateHtmlReceipt(Task $task, $template = null) {
        $db = Database::getInstance();
        
        // Get template
        if (!$template) {
            if ($task->get('template_id')) {
                $template = $db->fetchOne(
                    "SELECT * FROM receipt_templates WHERE id = ?",
                    [$task->get('template_id')]
                );
            } else {
                $template = $db->fetchOne(
                    "SELECT * FROM receipt_templates WHERE template_type = 'task' LIMIT 1"
                );
            }
        }
        
        // Generate HTML receipt
        $html = '<div class="thermal-receipt" style="width: 80mm; font-family: monospace; padding: 10mm;">';
        
        // Header
        if ($template && $template['header_text']) {
            $align = $template['text_alignment'] ?? 'center';
            $html .= '<div style="text-align: ' . h($align) . '; font-weight: bold; font-size: 18px; margin-bottom: 10px;">';
            $html .= h($template['header_text']);
            $html .= '</div>';
        }
        
        // Task title
        $html .= '<div style="text-align: center; font-weight: bold; font-size: 16px; margin: 10px 0;">';
        $html .= h($task->get('title'));
        $html .= '</div>';
        
        $html .= '<div style="border-top: 2px dashed #000; margin: 10px 0;"></div>';
        
        // Details
        if ($template && $template['show_category'] && $task->get('category_name')) {
            $html .= '<div><strong>Category:</strong> ' . h($task->get('category_name')) . '</div>';
        }
        
        if ($template && $template['show_urgency']) {
            $urgencyLabels = [
                'low' => 'Low',
                'normal' => 'Normal',
                'high' => 'HIGH',
                'critical' => '!!! CRITICAL !!!'
            ];
            $urgency = $urgencyLabels[$task->get('urgency_level')] ?? 'Normal';
            $html .= '<div><strong>Urgency:</strong> ' . h($urgency) . '</div>';
        }
        
        if ($template && $template['show_xp_value']) {
            $html .= '<div><strong>XP Reward:</strong> ' . h($task->get('xp_value')) . ' XP</div>';
        }
        
        if ($template && $template['show_due_date'] && $task->get('due_date')) {
            $dueDate = date('M d, Y h:i A', strtotime($task->get('due_date')));
            $html .= '<div><strong>Due:</strong> ' . h($dueDate) . '</div>';
        }
        
        if ($task->get('description')) {
            $html .= '<div style="margin-top: 10px;"><strong>Description:</strong></div>';
            $html .= '<div style="white-space: pre-wrap;">' . h($task->get('description')) . '</div>';
        }
        
        if ($template && $template['show_timestamp']) {
            $html .= '<div style="margin-top: 10px;"><strong>Printed:</strong> ' . date('M d, Y h:i A') . '</div>';
        }
        
        $html .= '<div style="border-top: 2px dashed #000; margin: 10px 0;"></div>';
        
        // Barcode
        $html .= '<div style="text-align: center; margin: 15px 0;">';
        $html .= '<div style="font-size: 12px; margin-bottom: 5px;">Scan to complete:</div>';
        $barcodeData = 'TF' . str_pad($task->getId(), 8, '0', STR_PAD_LEFT);
        $html .= '<div style="font-family: \'Libre Barcode 128\', monospace; font-size: 48px; letter-spacing: 0;">';
        $html .= h($barcodeData);
        $html .= '</div>';
        $html .= '<div style="font-size: 11px; margin-top: 5px;">' . h($barcodeData) . '</div>';
        $html .= '</div>';
        
        // Footer
        if ($template && $template['footer_text']) {
            $html .= '<div style="border-top: 2px dashed #000; margin: 10px 0;"></div>';
            $align = $template['text_alignment'] ?? 'center';
            $html .= '<div style="text-align: ' . h($align) . '; font-size: 12px;">';
            $html .= h($template['footer_text']);
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    public function printTaskReceipt(Task $task, $template = null) {
        try {
            $this->connect();
            $this->initialize();
            
            // Get template
            if (!$template) {
                $db = Database::getInstance();
                if ($task->get('template_id')) {
                    $template = $db->fetchOne(
                        "SELECT * FROM receipt_templates WHERE id = ?",
                        [$task->get('template_id')]
                    );
                } else {
                    $template = $db->fetchOne(
                        "SELECT * FROM receipt_templates WHERE template_type = 'task' LIMIT 1"
                    );
                }
            }
            
            // Header
            if ($template && $template['header_text']) {
                $this->setAlignment('center');
                $this->setBold(true);
                $this->setFontSize(2);
                $this->textLine($template['header_text']);
                $this->setBold(false);
                $this->setFontSize(0);
                $this->feed(1);
            }
            
            // Task title
            $this->setAlignment('center');
            $this->setBold(true);
            $this->setFontSize(1);
            $this->textLine($task->get('title'));
            $this->setBold(false);
            $this->setFontSize(0);
            $this->feed(1);
            
            // Details
            $this->setAlignment('left');
            
            if ($task->get('category_name')) {
                $this->textLine("Category: " . $task->get('category_name'));
            }
            
            $urgencyLabels = [
                'low' => 'Low',
                'normal' => 'Normal',
                'high' => 'HIGH',
                'critical' => '!!! CRITICAL !!!'
            ];
            $urgency = $urgencyLabels[$task->get('urgency_level')] ?? 'Normal';
            $this->textLine("Urgency: " . $urgency);
            
            $this->textLine("XP Reward: " . $task->get('xp_value') . " XP");
            
            if ($task->get('due_date')) {
                $dueDate = date('M d, Y h:i A', strtotime($task->get('due_date')));
                $this->textLine("Due: " . $dueDate);
            }
            
            if ($task->get('description')) {
                $this->feed(1);
                $this->textLine("Description:");
                $this->textLine(wordwrap($task->get('description'), 32));
            }
            
            $this->feed(2);
            
            // Barcode
            $this->setAlignment('center');
            $this->textLine("Scan to complete:");
            $this->printBarcode($task->get('barcode'), $template['barcode_type'] ?? 'CODE128');
            $this->feed(1);
            
            // Footer
            if ($template && $template['footer_text']) {
                $this->setAlignment('center');
                $this->textLine($template['footer_text']);
            }
            
            $this->feed(2);
            $this->textLine(date('Y-m-d H:i:s'));
            $this->feed(2);
            $this->cut();
            
            $this->disconnect();
            
            // Mark task as printed
            $task->markAsPrinted();
            
            return true;
            
        } catch (Exception $e) {
            error_log("Printer Error: " . $e->getMessage());
            if ($this->connection) {
                $this->disconnect();
            }
            throw $e;
        }
    }
    
    public function printCompletionReceipt($task, $xpAwarded, $levelUp = false) {
        try {
            $this->connect();
            $this->initialize();
            
            // Header
            $this->setAlignment('center');
            $this->setBold(true);
            $this->setFontSize(2);
            $this->textLine("QUEST COMPLETE!");
            $this->setBold(false);
            $this->setFontSize(0);
            $this->feed(1);
            
            // Task completed
            $this->setAlignment('center');
            $this->setBold(true);
            $this->textLine($task->get('title'));
            $this->setBold(false);
            $this->feed(1);
            
            // XP awarded
            $this->setFontSize(1);
            $this->textLine("+ " . $xpAwarded . " XP");
            $this->setFontSize(0);
            $this->feed(1);
            
            if ($levelUp) {
                $this->setBold(true);
                $this->setFontSize(1);
                $this->textLine("*** LEVEL UP! ***");
                $this->setFontSize(0);
                $this->setBold(false);
                $this->feed(1);
            }
            
            $this->setAlignment('center');
            $this->textLine("Great work!");
            $this->feed(2);
            $this->textLine(date('Y-m-d H:i:s'));
            $this->feed(2);
            $this->cut();
            
            $this->disconnect();
            
            return true;
            
        } catch (Exception $e) {
            error_log("Printer Error: " . $e->getMessage());
            if ($this->connection) {
                $this->disconnect();
            }
            return false;
        }
    }
}
