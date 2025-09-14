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

$user = $auth->getCurrentUser();
$action = $_GET['action'] ?? '';

try {
    $db = Database::getInstance();
    
    switch ($action) {
        case 'count':
            // Получаем количество непрочитанных уведомлений
            $count = $db->fetchOne("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = ? AND is_read = 0
            ", [$user['id']]);
            
            echo json_encode([
                'success' => true,
                'count' => $count['count'] ?? 0
            ]);
            break;
            
        case 'list':
            // Получаем список уведомлений
            $notifications = $db->fetchAll("
                SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 50
            ", [$user['id']]);
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Неизвестное действие']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка выполнения запроса к базе данных: ' . $e->getMessage()
    ]);
}

?>
