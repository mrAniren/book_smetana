<?php
/**
 * Скрипт для очистки истекших бронирований
 * Запускается по cron каждый час
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Database.php';

use Database;

try {
    $db = Database::getInstance();
    
    echo "Начало очистки истекших бронирований...\n";
    
    // Находим истекшие бронирования
    $expiredBookings = $db->fetchAll("
        SELECT b.*, u.email, u.first_name, u.last_name
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        WHERE b.booking_status = 'active'
        AND b.expires_at < NOW()
    ");
    
    if (empty($expiredBookings)) {
        echo "Истекших бронирований не найдено.\n";
        exit(0);
    }
    
    echo "Найдено " . count($expiredBookings) . " истекших бронирований.\n";
    
    // Обновляем статус бронирований
    $updatedCount = $db->update(
        'bookings',
        ['booking_status' => 'expired'],
        'booking_status = ? AND expires_at < NOW()',
        ['active']
    );
    
    echo "Обновлено статусов бронирований: {$updatedCount}\n";
    
    // Обновляем счетчики пользователей
    foreach ($expiredBookings as $booking) {
        $user = $db->fetchOne(
            "SELECT booking_count FROM users WHERE id = ?",
            [$booking['user_id']]
        );
        
        if ($user && $user['booking_count'] > 0) {
            $db->update(
                'users',
                ['booking_count' => $user['booking_count'] - 1],
                'id = ?',
                [$booking['user_id']]
            );
            
            echo "Обновлен счетчик для пользователя {$booking['email']}: {$user['booking_count']} -> " . ($user['booking_count'] - 1) . "\n";
        }
    }
    
    // Создаем уведомления для пользователей
    foreach ($expiredBookings as $booking) {
        $db->insert('notifications', [
            'user_id' => $booking['user_id'],
            'type' => 'booking_expired',
            'title' => 'Бронирование истекло',
            'message' => "Ваше бронирование на {$booking['slot_date']} в {$booking['start_time']} истекло.",
            'is_read' => false
        ]);
    }
    
    echo "Создано уведомлений: " . count($expiredBookings) . "\n";
    
    // Очищаем старые уведомления (старше 30 дней)
    $deletedNotifications = $db->delete(
        'notifications',
        'created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)'
    );
    
    echo "Удалено старых уведомлений: {$deletedNotifications}\n";
    
    // Очищаем старые логи GetCourse (старше 90 дней)
    $deletedLogs = $db->delete(
        'getcourse_logs',
        'created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)'
    );
    
    echo "Удалено старых логов: {$deletedLogs}\n";
    
    echo "Очистка завершена успешно!\n";
    
} catch (Exception $e) {
    echo "Ошибка при очистке: " . $e->getMessage() . "\n";
    error_log("Cleanup error: " . $e->getMessage());
    exit(1);
}
?>