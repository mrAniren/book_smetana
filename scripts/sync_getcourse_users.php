<?php
/**
 * Скрипт для синхронизации пользователей с GetCourse
 * Запускается по cron каждый день в 02:00
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Database.php';

use Database;

// Настройки GetCourse API
$getcourseAccount = $_ENV['GETCOURSE_ACCOUNT'] ?? '';
$getcourseSecretKey = $_ENV['GETCOURSE_SECRET_KEY'] ?? '';
$getcourseApiUrl = $_ENV['GETCOURSE_API_URL'] ?? '';

if (empty($getcourseAccount) || empty($getcourseSecretKey) || empty($getcourseApiUrl)) {
    echo "GetCourse API не настроен. Проверьте файл .env\n";
    exit(1);
}

try {
    $db = Database::getInstance();
    
    echo "Начало синхронизации с GetCourse...\n";
    
    // Получаем список пользователей из GetCourse
    $getcourseUsers = getGetCourseUsers($getcourseAccount, $getcourseSecretKey, $getcourseApiUrl);
    
    if (empty($getcourseUsers)) {
        echo "Пользователи из GetCourse не получены.\n";
        exit(0);
    }
    
    echo "Получено пользователей из GetCourse: " . count($getcourseUsers) . "\n";
    
    $createdCount = 0;
    $updatedCount = 0;
    
    foreach ($getcourseUsers as $gcUser) {
        // Проверяем, существует ли пользователь в нашей системе
        $existingUser = $db->fetchOne(
            "SELECT * FROM users WHERE email = ? OR getcourse_user_id = ?",
            [$gcUser['email'], $gcUser['id']]
        );
        
        if (!$existingUser) {
            // Создаем нового пользователя
            $password = generateRandomPassword();
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            $userId = $db->insert('users', [
                'email' => $gcUser['email'],
                'password_hash' => $passwordHash,
                'first_name' => $gcUser['first_name'] ?? '',
                'last_name' => $gcUser['last_name'] ?? '',
                'phone' => $gcUser['phone'] ?? '',
                'role' => 'user',
                'booking_limit' => $_ENV['DEFAULT_BOOKING_LIMIT'] ?? 3,
                'booking_count' => 0,
                'getcourse_user_id' => $gcUser['id'],
                'is_paid_client' => $gcUser['is_paid'] ?? false,
                'email_verified' => true,
                'is_active' => true
            ]);
            
            // Создаем уведомление о создании аккаунта
            $db->insert('notifications', [
                'user_id' => $userId,
                'type' => 'account_created',
                'title' => 'Аккаунт создан',
                'message' => "Ваш аккаунт в системе бронирования создан. Временный пароль: {$password}",
                'is_read' => false
            ]);
            
            $createdCount++;
            echo "Создан пользователь: {$gcUser['email']} (ID: {$userId})\n";
            
        } else {
            // Обновляем существующего пользователя
            $updateData = [
                'first_name' => $gcUser['first_name'] ?? $existingUser['first_name'],
                'last_name' => $gcUser['last_name'] ?? $existingUser['last_name'],
                'phone' => $gcUser['phone'] ?? $existingUser['phone'],
                'getcourse_user_id' => $gcUser['id'],
                'is_paid_client' => $gcUser['is_paid'] ?? $existingUser['is_paid_client'],
                'is_active' => true
            ];
            
            $db->update('users', $updateData, 'id = ?', [$existingUser['id']]);
            $updatedCount++;
            echo "Обновлен пользователь: {$gcUser['email']} (ID: {$existingUser['id']})\n";
        }
        
        // Логируем синхронизацию
        $db->insert('getcourse_logs', [
            'action' => 'sync_user',
            'getcourse_user_id' => $gcUser['id'],
            'email' => $gcUser['email'],
            'request_data' => json_encode($gcUser),
            'response_data' => json_encode(['status' => 'success']),
            'status' => 'success'
        ]);
    }
    
    echo "Синхронизация завершена!\n";
    echo "Создано пользователей: {$createdCount}\n";
    echo "Обновлено пользователей: {$updatedCount}\n";
    
} catch (Exception $e) {
    echo "Ошибка при синхронизации: " . $e->getMessage() . "\n";
    error_log("GetCourse sync error: " . $e->getMessage());
    exit(1);
}

/**
 * Получение пользователей из GetCourse
 */
function getGetCourseUsers($account, $secretKey, $apiUrl) {
    $params = [
        'action' => 'get_users',
        'key' => $secretKey,
        'account' => $account,
        'limit' => 1000
    ];
    
    $url = $apiUrl . '?' . http_build_query($params);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 30,
            'header' => 'User-Agent: Book Smeta System'
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        throw new Exception('Ошибка получения данных из GetCourse');
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['success']) || !$data['success']) {
        throw new Exception('Неверный ответ от GetCourse API');
    }
    
    return $data['users'] ?? [];
}

/**
 * Генерация случайного пароля
 */
function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    
    return $password;
}
?>