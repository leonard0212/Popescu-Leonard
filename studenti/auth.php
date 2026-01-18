<?php
// Central authentication helpers — use role-based checks
if (session_status() === PHP_SESSION_NONE) session_start();

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && !empty($_SESSION['admin_id']);
}

function is_client() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'client' && !empty($_SESSION['client_id']);
}

function require_admin() {
    if (!is_admin()) {
        header('Location: login.php');
        exit();
    }
}

function require_client() {
    if (!is_client()) {
        header('Location: index.php');
        exit();
    }
}

?>
