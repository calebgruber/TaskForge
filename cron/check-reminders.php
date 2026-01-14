<?php
/**
 * SMS Reminder Checker
 * Run this script via cron every 5 minutes
 * 
 * Cron entry example:
 * */5 * * * * /usr/bin/php /path/to/taskforge/cron/check-reminders.php
 */

// Adjust path to your config file
require_once __DIR__ . '/../config/config.php';

class ReminderChecker {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function run() {
        if (!getSetting('sms_enabled', false)) {
            $this->log("SMS reminders are disabled");
            return;
        }
        
        $this->checkDueReminders();
        $this->checkOverdueReminders();
        $this->log("Reminder check completed");
    }
    
    private function checkDueReminders() {
        $interval = getSetting('reminder_check_interval', 5);
        
        // Find tasks with reminders that are due soon
        $sql = "SELECT t.*, u.phone_number, u.sms_enabled 
                FROM tasks t
                JOIN users u ON t.user_id = u.id
                WHERE t.reminder_enabled = 1
                  AND t.status = 'active'
                  AND u.sms_enabled = 1
                  AND u.phone_number IS NOT NULL
                  AND u.phone_number != ''
                  AND t.due_date IS NOT NULL
                  AND t.due_date > NOW()
                  AND t.due_date <= DATE_ADD(NOW(), INTERVAL t.reminder_minutes_before MINUTE)
                  AND (t.last_reminder_sent IS NULL 
                       OR t.last_reminder_sent < DATE_SUB(NOW(), INTERVAL ? MINUTE))";
        
        $tasks = $this->db->fetchAll($sql, [$interval]);
        
        foreach ($tasks as $task) {
            $this->sendReminder($task, 'due_soon');
        }
        
        $this->log("Checked " . count($tasks) . " due reminders");
    }
    
    private function checkOverdueReminders() {
        // Find overdue tasks without reminders sent today
        $sql = "SELECT t.*, u.phone_number, u.sms_enabled 
                FROM tasks t
                JOIN users u ON t.user_id = u.id
                WHERE t.status IN ('active', 'overdue')
                  AND u.sms_enabled = 1
                  AND u.phone_number IS NOT NULL
                  AND u.phone_number != ''
                  AND t.due_date IS NOT NULL
                  AND t.due_date < NOW()
                  AND (t.last_reminder_sent IS NULL 
                       OR DATE(t.last_reminder_sent) < CURDATE())";
        
        $tasks = $this->db->fetchAll($sql);
        
        foreach ($tasks as $task) {
            $this->sendReminder($task, 'overdue');
        }
        
        $this->log("Checked " . count($tasks) . " overdue reminders");
    }
    
    private function sendReminder($task, $type) {
        $message = $this->buildMessage($task, $type);
        
        // Send SMS
        $success = $this->sendSMS($task['phone_number'], $message);
        
        // Log reminder
        $sql = "INSERT INTO sms_reminders (task_id, user_id, phone_number, message, reminder_type, status, sent_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $this->db->execute($sql, [
            $task['id'],
            $task['user_id'],
            $task['phone_number'],
            $message,
            $type,
            $success ? 'sent' : 'failed'
        ]);
        
        // Update last_reminder_sent on task
        if ($success) {
            $this->db->execute(
                "UPDATE tasks SET last_reminder_sent = NOW() WHERE id = ?",
                [$task['id']]
            );
        }
        
        $this->log("Sent {$type} reminder for task #{$task['id']}: " . ($success ? 'SUCCESS' : 'FAILED'));
    }
    
    private function buildMessage($task, $type) {
        $appName = APP_NAME;
        
        if ($type === 'overdue') {
            $message = "[{$appName}] OVERDUE: {$task['title']}";
            if ($task['due_date']) {
                $dueDate = date('M d, h:i A', strtotime($task['due_date']));
                $message .= " (was due {$dueDate})";
            }
        } else {
            $message = "[{$appName}] Reminder: {$task['title']}";
            if ($task['due_date']) {
                $dueDate = date('M d, h:i A', strtotime($task['due_date']));
                $message .= " - Due: {$dueDate}";
            }
        }
        
        $message .= " | XP: {$task['xp_value']}";
        
        return $message;
    }
    
    private function sendSMS($phoneNumber, $message) {
        $provider = getSetting('sms_provider', 'twilio');
        
        switch ($provider) {
            case 'twilio':
                return $this->sendTwilio($phoneNumber, $message);
            case 'nexmo':
                return $this->sendNexmo($phoneNumber, $message);
            default:
                $this->log("Unknown SMS provider: {$provider}");
                return false;
        }
    }
    
    private function sendTwilio($phoneNumber, $message) {
        $accountSid = getSetting('sms_api_key');
        $authToken = getSetting('sms_api_secret');
        $fromNumber = getSetting('sms_from_number', '');
        
        if (!$accountSid || !$authToken || !$fromNumber) {
            $this->log("Twilio credentials not configured");
            return false;
        }
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";
        
        $data = [
            'From' => $fromNumber,
            'To' => $phoneNumber,
            'Body' => $message
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, "{$accountSid}:{$authToken}");
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }
        
        $this->log("Twilio API error: HTTP {$httpCode} - {$response}");
        return false;
    }
    
    private function sendNexmo($phoneNumber, $message) {
        $apiKey = getSetting('sms_api_key');
        $apiSecret = getSetting('sms_api_secret');
        $fromNumber = getSetting('sms_from_number', 'TaskForge');
        
        if (!$apiKey || !$apiSecret) {
            $this->log("Nexmo credentials not configured");
            return false;
        }
        
        $url = "https://rest.nexmo.com/sms/json";
        
        $data = [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'from' => $fromNumber,
            'to' => $phoneNumber,
            'text' => $message
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $result = json_decode($response, true);
            if (isset($result['messages'][0]['status']) && $result['messages'][0]['status'] == '0') {
                return true;
            }
        }
        
        $this->log("Nexmo API error: HTTP {$httpCode} - {$response}");
        return false;
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        echo "[{$timestamp}] {$message}\n";
        error_log("[TaskForge Reminders] {$message}");
    }
}

// Run the checker
try {
    $checker = new ReminderChecker();
    $checker->run();
} catch (Exception $e) {
    error_log("Reminder checker error: " . $e->getMessage());
    echo "ERROR: " . $e->getMessage() . "\n";
}
