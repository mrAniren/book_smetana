<?php

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Проверяем авторизацию
try {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Необходима авторизация']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Ошибка аутентификации']);
    exit;
}

// Генерируем CSRF токен
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

echo json_encode([
    'success' => true,
    'csrf_token' => $_SESSION['csrf_token']
]);

?>
