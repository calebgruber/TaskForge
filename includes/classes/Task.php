<?php
/**
 * Task Class
 * Manages task creation, completion, and printing
 */
class Task {
    private $db;
    private $id;
    private $data;
    
    public function __construct($id = null) {
        $this->db = Database::getInstance();
        if ($id) {
            $this->id = $id;
            $this->load();
        }
    }
    
    private function load() {
        $sql = "SELECT t.*, c.name as category_name, c.color as category_color,
                       i.filename as icon_file, ui.filename as urgency_icon_file,
                       rt.name as template_name
                FROM tasks t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN icons i ON t.icon_id = i.id
                LEFT JOIN icons ui ON t.urgency_icon_id = ui.id
                LEFT JOIN receipt_templates rt ON t.template_id = rt.id
                WHERE t.id = ?";
        $this->data = $this->db->fetchOne($sql, [$this->id]);
        if (!$this->data) {
            throw new Exception("Task not found");
        }
    }
    
    public function get($field) {
        return $this->data[$field] ?? null;
    }
    
    public function getData() {
        return $this->data;
    }
    
    public function getId() {
        return $this->id;
    }
    
    public static function create($userId, $data) {
        $db = Database::getInstance();
        
        // Generate unique barcode
        $barcode = self::generateBarcode();
        
        // Calculate XP value based on rules
        $xpValue = self::calculateXP($data);
        
        // Set defaults
        $defaults = [
            'urgency_level' => 'normal',
            'difficulty' => 1,
            'reminder_enabled' => 0,
            'reminder_minutes_before' => 60,
            'reminder_frequency' => 'once',
            'is_recurring' => 0,
            'status' => 'active'
        ];
        
        $data = array_merge($defaults, $data);
        
        $sql = "INSERT INTO tasks (
            user_id, title, description, category_id, urgency_level, difficulty,
            xp_value, barcode, icon_id, urgency_icon_id, due_date,
            reminder_enabled, reminder_minutes_before, reminder_frequency,
            is_recurring, recurrence_pattern, template_id, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $userId,
            $data['title'],
            $data['description'] ?? null,
            $data['category_id'] ?? null,
            $data['urgency_level'],
            $data['difficulty'],
            $xpValue,
            $barcode,
            $data['icon_id'] ?? null,
            $data['urgency_icon_id'] ?? null,
            $data['due_date'] ?? null,
            $data['reminder_enabled'],
            $data['reminder_minutes_before'],
            $data['reminder_frequency'],
            $data['is_recurring'],
            $data['recurrence_pattern'] ?? null,
            $data['template_id'] ?? null,
            $data['status']
        ];
        
        $db->execute($sql, $params);
        return new self($db->lastInsertId());
    }
    
    private static function generateBarcode() {
        return 'TF' . date('Ymd') . strtoupper(substr(uniqid(), -8));
    }
    
    private static function calculateXP($data) {
        $db = Database::getInstance();
        
        // Get XP rule
        $rule = $db->fetchOne(
            "SELECT * FROM xp_rules WHERE category_id = ? AND is_active = 1 
             ORDER BY id DESC LIMIT 1",
            [$data['category_id'] ?? null]
        );
        
        if (!$rule) {
            $rule = $db->fetchOne(
                "SELECT * FROM xp_rules WHERE category_id IS NULL AND is_active = 1 
                 ORDER BY id DESC LIMIT 1"
            );
        }
        
        $baseXP = $rule['base_xp'] ?? BASE_XP;
        $urgencyMultiplier = 1.0;
        $difficultyMultiplier = $rule['difficulty_multiplier'] ?? 1.0;
        
        // Apply urgency multiplier
        switch ($data['urgency_level'] ?? 'normal') {
            case 'critical':
                $urgencyMultiplier = 2.0;
                break;
            case 'high':
                $urgencyMultiplier = 1.5;
                break;
            case 'normal':
                $urgencyMultiplier = 1.0;
                break;
            case 'low':
                $urgencyMultiplier = 0.75;
                break;
        }
        
        $difficulty = $data['difficulty'] ?? 1;
        
        return round($baseXP * $urgencyMultiplier * $difficultyMultiplier * $difficulty);
    }
    
    public function complete($userId, $method = 'scan') {
        $db = Database::getInstance();
        
        if ($this->data['status'] === 'completed') {
            throw new Exception("Task already completed");
        }
        
        $db->beginTransaction();
        
        try {
            // Calculate XP with bonuses/penalties
            $xpAwarded = $this->data['xp_value'];
            $bonusXP = 0;
            $penaltyXP = 0;
            $wasEarly = false;
            $wasLate = false;
            
            if ($this->data['due_date']) {
                $now = new DateTime();
                $dueDate = new DateTime($this->data['due_date']);
                
                if ($now < $dueDate) {
                    $wasEarly = true;
                    $bonusXP = round($xpAwarded * 0.1); // 10% bonus
                } elseif ($now > $dueDate) {
                    $wasLate = true;
                    $penaltyXP = round($xpAwarded * 0.2); // 20% penalty
                }
            }
            
            $finalXP = max(1, $xpAwarded + $bonusXP - $penaltyXP);
            
            // Update task
            $sql = "UPDATE tasks SET status = 'completed', completed_at = NOW(), updated_at = NOW() 
                    WHERE id = ?";
            $db->execute($sql, [$this->id]);
            
            // Log completion
            $sql = "INSERT INTO task_completions (
                task_id, user_id, xp_awarded, completion_method, was_early, was_late,
                bonus_xp, penalty_xp, completed_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $db->execute($sql, [
                $this->id, $userId, $finalXP, $method, $wasEarly, $wasLate, $bonusXP, $penaltyXP
            ]);
            
            // Award XP to user
            $user = new User($userId);
            $xpResult = $user->addXP($finalXP);
            $user->incrementTasksCompleted();
            
            $db->commit();
            
            $this->load();
            
            return [
                'success' => true,
                'xp_awarded' => $finalXP,
                'bonus_xp' => $bonusXP,
                'penalty_xp' => $penaltyXP,
                'was_early' => $wasEarly,
                'was_late' => $wasLate,
                'xp_result' => $xpResult,
                'task' => $this->data
            ];
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }
    
    public static function getByBarcode($barcode) {
        $db = Database::getInstance();
        $task = $db->fetchOne("SELECT id FROM tasks WHERE barcode = ?", [$barcode]);
        if (!$task) {
            return null;
        }
        return new self($task['id']);
    }
    
    public static function getAllByUser($userId, $status = null, $limit = 100, $offset = 0) {
        $db = Database::getInstance();
        
        $sql = "SELECT t.*, c.name as category_name, c.color as category_color
                FROM tasks t
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE t.user_id = ?";
        $params = [$userId];
        
        if ($status) {
            $sql .= " AND t.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY 
                  CASE t.urgency_level
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'normal' THEN 3
                    WHEN 'low' THEN 4
                  END,
                  t.due_date ASC,
                  t.created_at DESC
                  LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $db->fetchAll($sql, $params);
    }
    
    public function markAsPrinted() {
        $db = Database::getInstance();
        $sql = "UPDATE tasks SET printed = 1, printed_at = NOW() WHERE id = ?";
        $db->execute($sql, [$this->id]);
        $this->load();
    }
    
    public static function checkOverdueTasks() {
        $db = Database::getInstance();
        $sql = "UPDATE tasks SET status = 'overdue' 
                WHERE status = 'active' AND due_date < NOW()";
        return $db->execute($sql);
    }
}
