<?php
/**
 * API endpoint для мониторинга здоровья системы
 * Используется для проверки доступности системы
 */

header('Content-Type: application/json');

try {
    // Проверяем подключение к базе данных
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/Database.php';
    
    use Database;
    
    $db = Database::getInstance();
    $dbConnection = $db->testConnection();
    
    if (!$dbConnection) {
        throw new Exception('Ошибка подключения к базе данных');
    }
    
    // Проверяем основные таблицы
    $tables = ['users', 'server_slots', 'bookings', 'notifications'];
    $tableStatus = [];
    
    foreach ($tables as $table) {
        try {
            $db->fetchOne("SELECT 1 FROM {$table} LIMIT 1");
            $tableStatus[$table] = 'ok';
        } catch (Exception $e) {
            $tableStatus[$table] = 'error';
        }
    }
    
    // Получаем базовую статистику
    $stats = [];
    
    try {
        $stats['users_total'] = $db->fetchOne("SELECT COUNT(*) as count FROM users")['count'];
        $stats['users_active'] = $db->fetchOne("SELECT COUNT(*) as count FROM users WHERE is_active = 1")['count'];
        $stats['slots_total'] = $db->fetchOne("SELECT COUNT(*) as count FROM server_slots")['count'];
        $stats['slots_available'] = $db->fetchOne("SELECT COUNT(*) as count FROM server_slots WHERE is_available = 1 AND slot_date >= CURDATE()")['count'];
        $stats['bookings_active'] = $db->fetchOne("SELECT COUNT(*) as count FROM bookings WHERE booking_status = 'active'")['count'];
        $stats['notifications_unread'] = $db->fetchOne("SELECT COUNT(*) as count FROM notifications WHERE is_read = 0")['count'];
    } catch (Exception $e) {
        $stats['error'] = 'Ошибка получения статистики';
    }
    
    // Проверяем доступность файловой системы
    $fileSystemStatus = [
        'logs_dir' => is_writable(__DIR__ . '/../logs') ? 'ok' : 'error',
        'uploads_dir' => is_writable(__DIR__ . '/../public/uploads') ? 'ok' : 'error',
        'config_file' => file_exists(__DIR__ . '/../.env') ? 'ok' : 'warning'
    ];
    
    // Проверяем версию PHP
    $phpVersion = PHP_VERSION;
    $phpStatus = version_compare($phpVersion, '7.4.0', '>=') ? 'ok' : 'warning';
    
    // Определяем общий статус системы
    $overallStatus = 'ok';
    
    if (!$dbConnection || in_array('error', $tableStatus) || in_array('error', $fileSystemStatus)) {
        $overallStatus = 'error';
    } elseif (in_array('warning', $fileSystemStatus) || $phpStatus === 'warning') {
        $overallStatus = 'warning';
    }
    
    // Формируем ответ
    $response = [
        'status' => $overallStatus,
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0.0',
        'checks' => [
            'database' => [
                'status' => $dbConnection ? 'ok' : 'error',
                'tables' => $tableStatus
            ],
            'file_system' => $fileSystemStatus,
            'php' => [
                'status' => $phpStatus,
                'version' => $phpVersion
            ]
        ],
        'statistics' => $stats,
        'uptime' => [
            'server_time' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ]
    ];
    
    // Устанавливаем соответствующий HTTP код
    if ($overallStatus === 'error') {
        http_response_code(503);
    } elseif ($overallStatus === 'warning') {
        http_response_code(200);
    } else {
        http_response_code(200);
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(503);
    echo json_encode([
        'status' => 'error',
        'timestamp' => date('Y-m-d H:i:s'),
        'error' => $e->getMessage(),
        'version' => '1.0.0'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>
