<?php
/**
 * Helper Functions
 */

function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function getSetting($key, $default = null) {
    $db = Database::getInstance();
    $result = $db->fetchOne("SELECT setting_value, setting_type FROM settings WHERE setting_key = ?", [$key]);
    
    if (!$result) {
        return $default;
    }
    
    $value = $result['setting_value'];
    
    switch ($result['setting_type']) {
        case 'int':
            return (int)$value;
        case 'bool':
            return (bool)$value;
        case 'json':
            return json_decode($value, true);
        default:
            return $value;
    }
}

function setSetting($key, $value, $type = 'string') {
    $db = Database::getInstance();
    
    if ($type === 'json') {
        $value = json_encode($value);
    } elseif ($type === 'bool') {
        $value = $value ? '1' : '0';
    }
    
    $existing = $db->fetchOne("SELECT setting_key FROM settings WHERE setting_key = ?", [$key]);
    
    if ($existing) {
        $sql = "UPDATE settings SET setting_value = ?, setting_type = ?, updated_at = NOW() WHERE setting_key = ?";
        $db->execute($sql, [$value, $type, $key]);
    } else {
        $sql = "INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?)";
        $db->execute($sql, [$key, $value, $type]);
    }
}

function formatDate($date, $format = 'M d, Y') {
    if (!$date) return '';
    return date($format, strtotime($date));
}

function formatDateTime($date, $format = 'M d, Y h:i A') {
    if (!$date) return '';
    return date($format, strtotime($date));
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    
    return date('M d, Y', $timestamp);
}

function getUrgencyBadgeClass($urgency) {
    switch ($urgency) {
        case 'critical':
            return 'bg-red';
        case 'high':
            return 'bg-orange';
        case 'normal':
            return 'bg-blue';
        case 'low':
            return 'bg-gray';
        default:
            return 'bg-secondary';
    }
}

function getStatusBadgeClass($status) {
    switch ($status) {
        case 'completed':
            return 'bg-green';
        case 'active':
            return 'bg-blue';
        case 'overdue':
            return 'bg-red';
        case 'cancelled':
            return 'bg-gray';
        default:
            return 'bg-secondary';
    }
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function uploadIcon($file, $type = 'task') {
    $allowedTypes = ALLOWED_ICON_TYPES;
    $maxSize = MAX_UPLOAD_SIZE;
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception("Invalid file type. Allowed: PNG, JPEG, GIF");
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception("File too large. Maximum size: " . ($maxSize / 1024 / 1024) . "MB");
    }
    
    $uploadDir = UPLOAD_PATH . '/icons/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('icon_') . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception("Failed to upload file");
    }
    
    return 'uploads/icons/' . $filename;
}
