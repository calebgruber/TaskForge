<?php
/**
 * Authentication Functions
 */

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('/login.php');
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        return new User($_SESSION['user_id']);
    } catch (Exception $e) {
        logout();
        return null;
    }
}

function login($user) {
    $_SESSION['user_id'] = $user->getId();
    $_SESSION['username'] = $user->get('username');
    $_SESSION['login_time'] = time();
    
    // Regenerate session ID for security
    session_regenerate_id(true);
}

function logout() {
    $_SESSION = [];
    session_destroy();
    redirect('/login.php');
}

function checkSessionTimeout() {
    if (isLoggedIn() && isset($_SESSION['login_time'])) {
        $elapsed = time() - $_SESSION['login_time'];
        if ($elapsed > SESSION_LIFETIME) {
            logout();
        }
        // Update login time
        $_SESSION['login_time'] = time();
    }
}

function isAdmin() {
    // For now, all logged in users are admins
    // You can extend this with a role system
    return isLoggedIn();
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        redirect('/index.php');
    }
}
