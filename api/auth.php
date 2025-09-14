<?php

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    $auth = new Auth();
    
    switch ($method) {
        case 'POST':
            $action = $_POST['action'] ?? '';
            
            switch ($action) {
                case 'login':
                    $email = trim($_POST['email'] ?? '');
                    $password = $_POST['password'] ?? '';
                    
                    if (empty($email) || empty($password)) {
                        echo json_encode(['success' => false, 'error' => 'Email и пароль обязательны']);
                        break;
                    }
                    
                    $user = $auth->login($email, $password);
                    
                    if ($user) {
                        echo json_encode([
                            'success' => true,
                            'user' => [
                                'id' => $user['id'],
                                'email' => $user['email'],
                                'first_name' => $user['first_name'],
                                'last_name' => $user['last_name'],
                                'role' => $user['role']
                            ]
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Неверные учетные данные']);
                    }
                    break;
                    
                case 'logout':
                    $auth->logout();
                    echo json_encode(['success' => true, 'message' => 'Выход выполнен']);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Неизвестное действие']);
                    break;
            }
            break;
            
        case 'GET':
            // Проверка статуса авторизации
            if ($auth->isLoggedIn()) {
                $user = $auth->getCurrentUser();
                echo json_encode([
                    'success' => true,
                    'authenticated' => true,
                    'user' => [
                        'id' => $user['id'],
                        'email' => $user['email'],
                        'first_name' => $user['first_name'],
                        'last_name' => $user['last_name'],
                        'role' => $user['role'],
                        'booking_count' => $user['booking_count'],
                        'booking_limit' => $user['booking_limit']
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'authenticated' => false
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Метод не поддерживается']);
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