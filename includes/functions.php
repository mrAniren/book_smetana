<?php

// Функции для работы с системой бронирования

/**
 * Генерация случайного пароля
 */
function generatePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle($chars), 0, $length);
}

/**
 * Генерация логина для сервера
 */
function generateServerLogin($prefix = 'user') {
    return $prefix . '_' . substr(uniqid(), -8);
}

/**
 * Хеширование пароля
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Проверка пароля
 */
function verifyPassword($password, $hash) {
    // Проверяем если хеш MD5 (32 символа)
    if (strlen($hash) === 32 && ctype_xdigit($hash)) {
        return md5($password) === $hash;
    }
    // Иначе используем стандартную проверку password_verify
    return password_verify($password, $hash);
}

/**
 * Генерация CSRF токена
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Проверка CSRF токена
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Безопасный вывод данных
 */
function escape($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Валидация email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Валидация телефона (простая)
 */
function validatePhone($phone) {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    return strlen($phone) >= 10 && strlen($phone) <= 15;
}

/**
 * Форматирование даты
 */
function formatDate($date, $format = 'd.m.Y H:i') {
    if (is_string($date)) {
        $date = new DateTime($date);
    }
    return $date->format($format);
}

/**
 * Форматирование времени
 */
function formatTime($time, $format = 'H:i') {
    if (is_string($time)) {
        $time = new DateTime($time);
    }
    return $time->format($format);
}

/**
 * Проверка, что дата в будущем
 */
function isFutureDate($date) {
    $date = new DateTime($date);
    $now = new DateTime();
    return $date > $now;
}

/**
 * Получение временной зоны приложения
 */
function getAppTimezone() {
    $timezone = $_ENV['APP_TIMEZONE'] ?? 'Europe/Moscow';
    date_default_timezone_set($timezone);
    return $timezone;
}

/**
 * Конвертация времени в UTC
 */
function toUTC($datetime) {
    $timezone = getAppTimezone();
    $date = new DateTime($datetime, new DateTimeZone($timezone));
    $date->setTimezone(new DateTimeZone('UTC'));
    return $date->format('Y-m-d H:i:s');
}

/**
 * Конвертация времени из UTC
 */
function fromUTC($datetime) {
    $timezone = getAppTimezone();
    $date = new DateTime($datetime, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone($timezone));
    return $date->format('Y-m-d H:i:s');
}

/**
 * Логирование ошибок
 */
function logError($message, $context = []) {
    $logFile = __DIR__ . '/../logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context) : '';
    $logMessage = "[{$timestamp}] ERROR: {$message} {$contextStr}" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Логирование информации
 */
function logInfo($message, $context = []) {
    $logFile = __DIR__ . '/../logs/info.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context) : '';
    $logMessage = "[{$timestamp}] INFO: {$message} {$contextStr}" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Отправка JSON ответа
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Получение данных из POST запроса
 */
function getPostData() {
    $input = file_get_contents('php://input');
    return json_decode($input, true);
}

/**
 * Проверка авторизации API
 */
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        sendJsonResponse(['error' => 'Требуется авторизация'], 401);
    }
}

/**
 * Проверка прав администратора
 */
function requireAdmin() {
    requireAuth();
    if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'super_admin') {
        sendJsonResponse(['error' => 'Недостаточно прав'], 403);
    }
}

/**
 * Проверка прав суперадминистратора
 */
function requireSuperAdmin() {
    requireAuth();
    if ($_SESSION['user_role'] !== 'super_admin') {
        sendJsonResponse(['error' => 'Требуются права суперадминистратора'], 403);
    }
}

/**
 * Валидация входных данных
 */
function validateInput($data, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule) {
        $value = $data[$field] ?? null;
        
        if (isset($rule['required']) && $rule['required'] && empty($value)) {
            $errors[$field] = "Поле {$field} обязательно для заполнения";
            continue;
        }
        
        if (!empty($value)) {
            if (isset($rule['type'])) {
                switch ($rule['type']) {
                    case 'email':
                        if (!validateEmail($value)) {
                            $errors[$field] = "Неверный формат email";
                        }
                        break;
                    case 'phone':
                        if (!validatePhone($value)) {
                            $errors[$field] = "Неверный формат телефона";
                        }
                        break;
                    case 'int':
                        if (!is_numeric($value)) {
                            $errors[$field] = "Поле {$field} должно быть числом";
                        }
                        break;
                }
            }
            
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[$field] = "Минимальная длина поля {$field}: {$rule['min_length']} символов";
            }
            
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[$field] = "Максимальная длина поля {$field}: {$rule['max_length']} символов";
            }
        }
    }
    
    return $errors;
}

/**
 * Форматирование ошибок валидации для JSON
 */
function formatValidationErrors($errors) {
    return [
        'success' => false,
        'message' => 'Ошибки валидации',
        'errors' => $errors
    ];
}

/**
 * Получение настройки системы
 */
function getSetting($key, $default = null) {
    $db = Database::getInstance();
    $result = $db->fetchOne("SELECT setting_value FROM system_settings WHERE setting_key = ?", [$key]);
    return $result ? $result['setting_value'] : $default;
}

/**
 * Установка настройки системы
 */
function setSetting($key, $value, $description = null) {
    $db = Database::getInstance();
    
    if ($db->exists('system_settings', 'setting_key = ?', [$key])) {
        return $db->update('system_settings', 
            ['setting_value' => $value], 
            'setting_key = ?', 
            [$key]
        );
    } else {
        return $db->insert('system_settings', [
            'setting_key' => $key,
            'setting_value' => $value,
            'description' => $description
        ]);
    }
}

/**
 * Создание уведомления для пользователя
 */
function createNotification($userId, $type, $title, $message) {
    $db = Database::getInstance();
    return $db->insert('notifications', [
        'user_id' => $userId,
        'type' => $type,
        'title' => $title,
        'message' => $message
    ]);
}

/**
 * Получение количества непрочитанных уведомлений
 */
function getUnreadNotificationsCount($userId) {
    $db = Database::getInstance();
    return $db->count('notifications', 'user_id = ? AND is_read = 0', [$userId]);
}
