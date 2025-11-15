<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        if (!headers_sent()) {
            header('Location: /login.php');
        }
        exit;
    }
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function logout() {
    session_destroy();
    if (!headers_sent()) {
        header('Location: /login.php');
    }
    exit;
}
