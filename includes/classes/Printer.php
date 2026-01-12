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
        if (!PRINTER_ENABLED) {
            throw new Exception("Printer is disabled");
        }
        
        if ($this->type === 'ethernet') {
            $this->connection = fsockopen(PRINTER_IP, PRINTER_PORT, $errno, $errstr, 10);
            if (!$this->connection) {
                throw new Exception("Cannot connect to printer: $errstr ($errno)");
            }
        } else {
            // USB connection
            $device = PRINTER_DEVICE;
            if (!file_exists($device)) {
                throw new Exception("Printer device not found: $device");
            }
            $this->connection = fopen($device, 'w');
            if (!$this->connection) {
                throw new Exception("Cannot open printer device");
            }
        }
        
        return true;
    }
    
    public function disconnect() {
        if ($this->connection) {
            if ($this->type === 'ethernet') {
                fclose($this->connection);
            } else {
                fclose($this->connection);
            }
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
    
    public function printBarcode($data, $type = 'CODE128') {
        // Set barcode height
        $this->write($this->gs() . 'h' . chr(50)); // 50 dots height
        
        // Set barcode width
        $this->write($this->gs() . 'w' . chr(2)); // 2 dots width
        
        // Print HRI (human readable) below barcode
        $this->write($this->gs() . 'H' . chr(2));
        
        // Print barcode based on type
        $barcodeTypes = [
            'CODE39' => 4,
            'CODE128' => 73,
            'EAN13' => 67
        ];
        
        $barcodeType = $barcodeTypes[$type] ?? 73;
        
        // For CODE128
        if ($type === 'CODE128') {
            $len = strlen($data);
            $this->write($this->gs() . 'k' . chr($barcodeType) . chr($len) . $data);
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
