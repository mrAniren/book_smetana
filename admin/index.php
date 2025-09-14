<?php

session_start();
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Проверяем авторизацию администратора
try {
    $auth = new Auth();
    if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
        header('Location: login.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: ../install.php');
    exit;
}

// Перенаправляем на дашборд
header('Location: dashboard.php');
exit;

?>