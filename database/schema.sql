-- TaskForge Database Schema
-- Full physical-digital productivity engine with barcode scanning and thermal printing

-- Users table with XP and leveling system
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    current_xp INT DEFAULT 0,
    current_level INT DEFAULT 1,
    total_xp INT DEFAULT 0,
    tasks_completed INT DEFAULT 0,
    phone_number VARCHAR(20),
    sms_enabled TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_level (current_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Task categories
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon_id INT,
    color VARCHAR(7) DEFAULT '#000000',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Icons for tasks, urgency, and visual communication
CREATE TABLE IF NOT EXISTS icons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    icon_type ENUM('task', 'urgency', 'level', 'category') DEFAULT 'task',
    width INT DEFAULT 48,
    height INT DEFAULT 48,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (icon_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Receipt templates for different task types
CREATE TABLE IF NOT EXISTS receipt_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    template_type ENUM('task', 'completion') DEFAULT 'task',
    header_text VARCHAR(255),
    use_header_icon TINYINT(1) DEFAULT 1,
    header_icon_id INT,
    show_timestamp TINYINT(1) DEFAULT 1,
    show_urgency TINYINT(1) DEFAULT 1,
    show_xp_value TINYINT(1) DEFAULT 1,
    show_category TINYINT(1) DEFAULT 1,
    show_due_date TINYINT(1) DEFAULT 1,
    footer_text VARCHAR(255),
    text_alignment ENUM('left', 'center', 'right') DEFAULT 'left',
    barcode_type ENUM('CODE128', 'CODE39', 'EAN13') DEFAULT 'CODE128',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (template_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- XP rules and leveling configuration
CREATE TABLE IF NOT EXISTS xp_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(100) NOT NULL,
    base_xp INT DEFAULT 10,
    urgency_multiplier DECIMAL(3,2) DEFAULT 1.00,
    difficulty_multiplier DECIMAL(3,2) DEFAULT 1.00,
    category_id INT,
    overdue_penalty INT DEFAULT 0,
    early_completion_bonus INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Level progression table
CREATE TABLE IF NOT EXISTS levels (
    level INT PRIMARY KEY,
    xp_required INT NOT NULL,
    reward_text VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_xp (xp_required)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tasks table with full metadata
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT,
    urgency_level ENUM('low', 'normal', 'high', 'critical') DEFAULT 'normal',
    difficulty INT DEFAULT 1,
    xp_value INT DEFAULT 10,
    barcode VARCHAR(100) UNIQUE NOT NULL,
    icon_id INT,
    urgency_icon_id INT,
    due_date DATETIME,
    reminder_enabled TINYINT(1) DEFAULT 0,
    reminder_minutes_before INT DEFAULT 60,
    reminder_frequency ENUM('once', 'hourly', 'daily') DEFAULT 'once',
    last_reminder_sent TIMESTAMP NULL,
    is_recurring TINYINT(1) DEFAULT 0,
    recurrence_pattern VARCHAR(50),
    status ENUM('pending', 'active', 'completed', 'cancelled', 'overdue') DEFAULT 'active',
    completed_at TIMESTAMP NULL,
    template_id INT,
    printed TINYINT(1) DEFAULT 0,
    printed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (icon_id) REFERENCES icons(id) ON DELETE SET NULL,
    FOREIGN KEY (urgency_icon_id) REFERENCES icons(id) ON DELETE SET NULL,
    FOREIGN KEY (template_id) REFERENCES receipt_templates(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_barcode (barcode),
    INDEX idx_due_date (due_date),
    INDEX idx_urgency (urgency_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Task completion log
CREATE TABLE IF NOT EXISTS task_completions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    xp_awarded INT DEFAULT 0,
    completion_method ENUM('scan', 'manual', 'web') DEFAULT 'scan',
    was_early TINYINT(1) DEFAULT 0,
    was_late TINYINT(1) DEFAULT 0,
    bonus_xp INT DEFAULT 0,
    penalty_xp INT DEFAULT 0,
    completion_receipt_printed TINYINT(1) DEFAULT 0,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_task (task_id),
    INDEX idx_user (user_id),
    INDEX idx_completed (completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- SMS reminders log
CREATE TABLE IF NOT EXISTS sms_reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    reminder_type ENUM('due_soon', 'overdue', 'daily', 'hourly') DEFAULT 'due_soon',
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_task (task_id),
    INDEX idx_status (status),
    INDEX idx_sent (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- System settings
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    setting_type ENUM('string', 'int', 'bool', 'json') DEFAULT 'string',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rewards table (display only, no printing)
CREATE TABLE IF NOT EXISTS rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reward_type ENUM('level_up', 'streak', 'milestone', 'custom') DEFAULT 'level_up',
    title VARCHAR(255) NOT NULL,
    description TEXT,
    xp_threshold INT,
    level_threshold INT,
    tasks_threshold INT,
    is_claimed TINYINT(1) DEFAULT 0,
    claimed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_claimed (is_claimed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default data

-- Default levels (1-50)
INSERT INTO levels (level, xp_required) VALUES
(1, 0), (2, 100), (3, 250), (4, 450), (5, 700),
(6, 1000), (7, 1350), (8, 1750), (9, 2200), (10, 2700),
(11, 3250), (12, 3850), (13, 4500), (14, 5200), (15, 5950),
(16, 6750), (17, 7600), (18, 8500), (19, 9450), (20, 10450),
(21, 11500), (22, 12600), (23, 13750), (24, 14950), (25, 16200),
(26, 17500), (27, 18850), (28, 20250), (29, 21700), (30, 23200),
(31, 24750), (32, 26350), (33, 28000), (34, 29700), (35, 31450),
(36, 33250), (37, 35100), (38, 37000), (39, 38950), (40, 40950),
(41, 43000), (42, 45100), (43, 47250), (44, 49450), (45, 51700),
(46, 54000), (47, 56350), (48, 58750), (49, 61200), (50, 63700);

-- Default categories
INSERT INTO categories (name, description, color) VALUES
('Work', 'Work-related tasks', '#2196F3'),
('Personal', 'Personal errands and tasks', '#4CAF50'),
('Chores', 'Household chores', '#FF9800'),
('Habits', 'Daily habits and routines', '#9C27B0'),
('Errands', 'Shopping and errands', '#F44336'),
('Projects', 'Long-term projects', '#00BCD4');

-- Default receipt templates
INSERT INTO receipt_templates (name, template_type, header_text, footer_text) VALUES
('Standard Task', 'task', 'TASK QUEST', 'Scan to complete'),
('High Priority', 'task', '!!! URGENT TASK !!!', 'Complete ASAP'),
('Completion Receipt', 'completion', 'QUEST COMPLETE!', 'Great work!');

-- Default XP rules
INSERT INTO xp_rules (rule_name, base_xp, urgency_multiplier, difficulty_multiplier) VALUES
('Standard', 10, 1.00, 1.00),
('High Priority', 25, 1.50, 1.00),
('Critical', 50, 2.00, 1.00);

-- Default system settings
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
('printer_enabled', '1', 'bool', 'Enable thermal printer'),
('printer_type', 'usb', 'string', 'Printer connection type: usb or ethernet'),
('printer_ip', '192.168.1.100', 'string', 'Printer IP for ethernet connection'),
('printer_port', '9100', 'int', 'Printer port for ethernet connection'),
('scanner_enabled', '1', 'bool', 'Enable barcode scanner'),
('scanner_prefix', '', 'string', 'Scanner prefix characters'),
('scanner_suffix', '\n', 'string', 'Scanner suffix characters'),
('sms_enabled', '0', 'bool', 'Enable SMS reminders'),
('sms_provider', 'twilio', 'string', 'SMS provider: twilio, nexmo, etc'),
('sms_api_key', '', 'string', 'SMS API key'),
('sms_api_secret', '', 'string', 'SMS API secret'),
('reminder_check_interval', '5', 'int', 'Minutes between reminder checks'),
('auto_print_completion', '1', 'bool', 'Auto-print completion receipts');
