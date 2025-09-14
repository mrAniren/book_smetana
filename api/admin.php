<?php

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/Database.php';

try {
    $auth = new Auth();
    if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
        throw new Exception('Доступ запрещен');
    }
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Не авторизован']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    $db = Database::getInstance();
    
    switch ($action) {
        case 'stats':
            // Статистика для дашборда
            $stats = [
                'total_users' => $db->fetchColumn('SELECT COUNT(*) FROM users'),
                'active_slots' => $db->fetchColumn('SELECT COUNT(*) FROM server_slots WHERE slot_date >= CURDATE()'),
                'total_bookings' => $db->fetchColumn('SELECT COUNT(*) FROM bookings'),
                'monthly_revenue' => '0 ₽' // Пока нет системы платежей
            ];
            
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;
            
        case 'activities':
            $limit = (int)($_GET['limit'] ?? 10);
            
            // Получаем последние активности из логов
            $activities = $db->fetchAll("
                SELECT 
                    'admin_login' as type,
                    CONCAT('Вход администратора: ', u.first_name, ' ', u.last_name) as description,
                    u.created_at
                FROM users u 
                WHERE u.role = 'admin' 
                ORDER BY u.created_at DESC 
                LIMIT ?
            ", [$limit]);
            
            echo json_encode(['success' => true, 'activities' => $activities]);
            break;
            
        case 'system_info':
            $info = [
                'mysql_version' => $db->fetchColumn('SELECT VERSION()'),
                'db_size' => 'Не определено',
                'last_backup' => 'Никогда'
            ];
            
            echo json_encode(['success' => true, 'info' => $info]);
            break;
            
        case 'chart':
            $period = $_GET['period'] ?? 'week';
            $days = $period === 'week' ? 7 : ($period === 'month' ? 30 : 365);
            
            // Генерируем данные для графика
            $chart = [
                'labels' => [],
                'data' => []
            ];
            
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $chart['labels'][] = date('d.m', strtotime($date));
                
                $count = $db->fetchColumn(
                    'SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = ?',
                    [$date]
                );
                $chart['data'][] = (int)$count;
            }
            
            echo json_encode(['success' => true, 'chart' => $chart]);
            break;
            
        default:
            throw new Exception('Неизвестное действие');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}