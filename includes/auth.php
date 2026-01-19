<?php
function isLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_login_time']);
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
    
    // Session timeout (8 jam)
    if (time() - $_SESSION['admin_login_time'] > 28800) {
        session_destroy();
        header('Location: login.php');
        exit();
    }
}

function getAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

function getAdminUsername() {
    return $_SESSION['admin_username'] ?? null;
}
?>