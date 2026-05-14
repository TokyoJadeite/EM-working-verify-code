<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function requireRole($role) {
    if (!isLoggedIn()) {
        header("Location: /excell-mark/index.php");
        exit;
    }
    if (!hasRole($role)) {
        header("Location: /excell-mark/index.php?error=unauthorized");
        exit;
    }
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /excell-mark/index.php");
        exit;
    }
}
