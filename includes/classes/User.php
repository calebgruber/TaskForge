<?php
/**
 * User Class
 * Manages user accounts, XP, and leveling
 */
class User {
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
        $sql = "SELECT * FROM users WHERE id = ?";
        $this->data = $this->db->fetchOne($sql, [$this->id]);
        if (!$this->data) {
            throw new Exception("User not found");
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
    
    public static function create($username, $email, $password, $phone = null) {
        $db = Database::getInstance();
        
        // Validate
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            throw new Exception("Password must be at least " . PASSWORD_MIN_LENGTH . " characters");
        }
        
        // Check if username or email exists
        $existing = $db->fetchOne(
            "SELECT id FROM users WHERE username = ? OR email = ?",
            [$username, $email]
        );
        if ($existing) {
            throw new Exception("Username or email already exists");
        }
        
        // Create user
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password_hash, phone_number) VALUES (?, ?, ?, ?)";
        $db->execute($sql, [$username, $email, $hash, $phone]);
        
        return new self($db->lastInsertId());
    }
    
    public static function authenticate($username, $password) {
        $db = Database::getInstance();
        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $user = $db->fetchOne($sql, [$username, $username]);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            return new self($user['id']);
        }
        
        return false;
    }
    
    public function addXP($amount, $reason = '') {
        $db = Database::getInstance();
        
        $newXP = $this->data['current_xp'] + $amount;
        $newTotalXP = $this->data['total_xp'] + $amount;
        
        // Check for level up
        $currentLevel = $this->data['current_level'];
        $newLevel = $this->calculateLevel($newTotalXP);
        
        $leveledUp = $newLevel > $currentLevel;
        
        // Update user
        $sql = "UPDATE users SET current_xp = ?, total_xp = ?, current_level = ?, 
                updated_at = NOW() WHERE id = ?";
        $db->execute($sql, [$newXP, $newTotalXP, $newLevel, $this->id]);
        
        // Create reward if leveled up
        if ($leveledUp) {
            $this->createLevelUpReward($newLevel);
        }
        
        $this->load();
        
        return [
            'xp_gained' => $amount,
            'new_xp' => $newXP,
            'new_total_xp' => $newTotalXP,
            'leveled_up' => $leveledUp,
            'old_level' => $currentLevel,
            'new_level' => $newLevel
        ];
    }
    
    private function calculateLevel($totalXP) {
        $db = Database::getInstance();
        $sql = "SELECT level FROM levels WHERE xp_required <= ? ORDER BY level DESC LIMIT 1";
        $result = $db->fetchOne($sql, [$totalXP]);
        return $result ? $result['level'] : 1;
    }
    
    private function createLevelUpReward($level) {
        $db = Database::getInstance();
        
        // Get level info
        $levelInfo = $db->fetchOne("SELECT * FROM levels WHERE level = ?", [$level]);
        
        $title = "Level {$level} Achieved!";
        $description = $levelInfo['reward_text'] ?? "You've reached level {$level}! Keep up the great work!";
        
        $sql = "INSERT INTO rewards (user_id, reward_type, title, description, level_threshold, created_at)
                VALUES (?, 'level_up', ?, ?, ?, NOW())";
        $db->execute($sql, [$this->id, $title, $description, $level]);
    }
    
    public function incrementTasksCompleted() {
        $db = Database::getInstance();
        $sql = "UPDATE users SET tasks_completed = tasks_completed + 1, updated_at = NOW() WHERE id = ?";
        $db->execute($sql, [$this->id]);
        $this->load();
    }
    
    public function updateProfile($data) {
        $db = Database::getInstance();
        $fields = [];
        $params = [];
        
        if (isset($data['email'])) {
            $fields[] = "email = ?";
            $params[] = $data['email'];
        }
        if (isset($data['phone_number'])) {
            $fields[] = "phone_number = ?";
            $params[] = $data['phone_number'];
        }
        if (isset($data['sms_enabled'])) {
            $fields[] = "sms_enabled = ?";
            $params[] = $data['sms_enabled'] ? 1 : 0;
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $fields[] = "updated_at = NOW()";
        $params[] = $this->id;
        
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $db->execute($sql, $params);
        $this->load();
        
        return true;
    }
    
    public function getProgressToNextLevel() {
        $db = Database::getInstance();
        $currentLevel = $this->data['current_level'];
        $currentXP = $this->data['total_xp'];
        
        // Get current and next level requirements
        $currentLevelXP = $db->fetchOne(
            "SELECT xp_required FROM levels WHERE level = ?", 
            [$currentLevel]
        )['xp_required'];
        
        $nextLevel = $db->fetchOne(
            "SELECT level, xp_required FROM levels WHERE level > ? ORDER BY level ASC LIMIT 1",
            [$currentLevel]
        );
        
        if (!$nextLevel) {
            return [
                'current_level' => $currentLevel,
                'next_level' => null,
                'progress' => 100,
                'xp_needed' => 0,
                'is_max_level' => true
            ];
        }
        
        $xpForLevel = $currentXP - $currentLevelXP;
        $xpNeeded = $nextLevel['xp_required'] - $currentLevelXP;
        $progress = ($xpForLevel / $xpNeeded) * 100;
        
        return [
            'current_level' => $currentLevel,
            'next_level' => $nextLevel['level'],
            'current_xp' => $currentXP,
            'level_xp' => $xpForLevel,
            'xp_needed' => $xpNeeded,
            'progress' => round($progress, 1),
            'is_max_level' => false
        ];
    }
}
