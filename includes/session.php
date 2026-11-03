<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function is_staff() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'staff';
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: /hustisyang-pambarangay/auth/login.php");
        exit();
    }
}

function require_admin() {
    require_login();
    if (!is_admin() && !is_staff()) {
        header("Location: /hustisyang-pambarangay/auth/login.php");
        exit();
    }
}

function require_super_admin() {
    require_login();
    if (!is_admin()) {
        header("Location: /hustisyang-pambarangay/admin/dashboard.php");
        exit();
    }
}

function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message
    ];
}

function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
?>