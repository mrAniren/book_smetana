<?php
/**
 * Скрипт для автоматического создания слотов на неделю
 * Запускается по cron каждое воскресенье в 00:00
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/Database.php';

use Database;

// Настройки
$slotsPerDay = 8; // С 9:00 до 17:00 (каждый час)
$slotDuration = 60; // 60 минут
$weekStart = 'sunday'; // Начинаем с воскресенья
$maxUsersPerSlot = 1; // Один пользователь на слот

try {
    $db = Database::getInstance();
    
    // Определяем дату начала недели
    $startDate = new DateTime('next ' . $weekStart);
    $startDate->setTime(0, 0, 0);
    
    echo "Создание слотов с " . $startDate->format('Y-m-d') . "\n";
    
    // Создаем слоты на 7 дней
    for ($day = 0; $day < 7; $day++) {
        $currentDate = clone $startDate;
        $currentDate->add(new DateInterval('P' . $day . 'D'));
        
        echo "Создание слотов для " . $currentDate->format('Y-m-d') . "\n";
        
        // Создаем слоты с 9:00 до 17:00
        for ($hour = 9; $hour < 17; $hour++) {
            $startTime = sprintf('%02d:00:00', $hour);
            $endTime = sprintf('%02d:00:00', $hour + 1);
            
            // Генерируем уникальные данные для сервера
            $serverLogin = 'user_' . $currentDate->format('Ymd') . '_' . sprintf('%02d', $hour);
            $serverPassword = generatePassword(12);
            
            // Проверяем, не существует ли уже такой слот
            $existingSlot = $db->fetchOne(
                "SELECT id FROM server_slots WHERE slot_date = ? AND start_time = ?",
                [$currentDate->format('Y-m-d'), $startTime]
            );
            
            if (!$existingSlot) {
                // Создаем новый слот
                $slotId = $db->insert('server_slots', [
                    'slot_date' => $currentDate->format('Y-m-d'),
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'server_login' => $serverLogin,
                    'server_password' => $serverPassword,
                    'is_available' => true,
                    'max_users' => $maxUsersPerSlot,
                    'current_users' => 0
                ]);
                
                echo "  Создан слот ID {$slotId}: {$startTime}-{$endTime} (логин: {$serverLogin})\n";
            } else {
                echo "  Слот уже существует: {$startTime}-{$endTime}\n";
            }
        }
    }
    
    echo "Создание слотов завершено успешно!\n";
    
} catch (Exception $e) {
    echo "Ошибка при создании слотов: " . $e->getMessage() . "\n";
    error_log("Create slots error: " . $e->getMessage());
    exit(1);
}

/**
 * Генерация случайного пароля
 */
function generatePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    
    return $password;
}
?>