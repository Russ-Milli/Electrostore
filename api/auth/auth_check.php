<?php
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function logout() {
    session_start();
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}
?>