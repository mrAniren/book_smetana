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
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    $db = Database::getInstance();
    
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'available':
                    // Получаем доступные слоты
                    $slots = $db->fetchAll("
                        SELECT s.*, 
                               COALESCE(booking_counts.booking_count, 0) as booking_count,
                               (s.max_users - COALESCE(booking_counts.booking_count, 0)) as available_spots
                        FROM server_slots s
                        LEFT JOIN (
                            SELECT slot_id, COUNT(*) as booking_count
                            FROM bookings 
                            WHERE booking_status = 'active'
                            GROUP BY slot_id
                        ) booking_counts ON s.id = booking_counts.slot_id
                        WHERE s.is_available = 1 
                        AND s.slot_date >= CURDATE()
                        AND s.max_users > COALESCE(booking_counts.booking_count, 0)
                        ORDER BY s.slot_date ASC, s.start_time ASC
                    ");
                    
                    echo json_encode([
                        'success' => true,
                        'slots' => $slots
                    ]);
                    break;
                    
                case 'my':
                    // Получаем бронирования текущего пользователя
                    $bookings = $db->fetchAll("
                        SELECT b.*, s.slot_date, s.start_time, s.end_time, s.server_login, s.server_password
                        FROM bookings b
                        JOIN server_slots s ON b.slot_id = s.id
                        WHERE b.user_id = ?
                        ORDER BY b.created_at DESC
                    ", [$user['id']]);
                    
                    echo json_encode([
                        'success' => true,
                        'bookings' => $bookings
                    ]);
                    break;
                    
                case 'stats':
                    // Получаем статистику бронирований пользователя
                    $stats = $db->fetchOne("
                        SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN booking_status = 'active' THEN 1 ELSE 0 END) as active,
                            SUM(CASE WHEN booking_status = 'completed' THEN 1 ELSE 0 END) as completed,
                            SUM(CASE WHEN booking_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                        FROM bookings 
                        WHERE user_id = ?
                    ", [$user['id']]);
                    
                    $remaining = $user['booking_limit'] - $stats['active'];
                    
                    echo json_encode([
                        'success' => true,
                        'stats' => [
                            'total' => $stats['total'],
                            'active' => $stats['active'],
                            'completed' => $stats['completed'],
                            'cancelled' => $stats['cancelled'],
                            'remaining' => max(0, $remaining)
                        ]
                    ]);
                    break;
                    
                case 'recent':
                    // Получаем последние бронирования
                    $limit = (int)($_GET['limit'] ?? 5);
                    $bookings = $db->fetchAll("
                        SELECT b.*, s.slot_date, s.start_time, s.end_time
                        FROM bookings b
                        JOIN server_slots s ON b.slot_id = s.id
                        WHERE b.user_id = ?
                        ORDER BY b.created_at DESC
                        LIMIT ?
                    ", [$user['id'], $limit]);
                    
                    echo json_encode([
                        'success' => true,
                        'bookings' => $bookings
                    ]);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Неизвестное действие']);
                    break;
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? '';
            
            switch ($action) {
                case 'create':
                    // Создание нового бронирования
                    $slotId = (int)($input['slot_id'] ?? 0);
                    $notes = trim($input['notes'] ?? '');
                    
                    if (!$slotId) {
                        echo json_encode(['success' => false, 'error' => 'Не указан ID слота']);
                        break;
                    }
                    
                    // Проверяем, есть ли у пользователя свободные бронирования
                    if ($user['booking_count'] >= $user['booking_limit']) {
                        echo json_encode(['success' => false, 'error' => 'Достигнут лимит бронирований']);
                        break;
                    }
                    
                    // Проверяем, доступен ли слот
                    $slot = $db->fetchOne("
                        SELECT s.*, COUNT(b.id) as booking_count
                        FROM server_slots s
                        LEFT JOIN bookings b ON s.id = b.slot_id AND b.booking_status = 'active'
                        WHERE s.id = ? AND s.is_available = 1
                        GROUP BY s.id
                        HAVING s.max_users > COUNT(b.id)
                    ", [$slotId]);
                    
                    if (!$slot) {
                        echo json_encode(['success' => false, 'error' => 'Слот недоступен или заполнен']);
                        break;
                    }
                    
                    // Проверяем, не забронировал ли уже этот слот
                    $existingBooking = $db->fetchOne("
                        SELECT id FROM bookings 
                        WHERE user_id = ? AND slot_id = ? AND booking_status = 'active'
                    ", [$user['id'], $slotId]);
                    
                    if ($existingBooking) {
                        echo json_encode(['success' => false, 'error' => 'Вы уже забронировали этот слот']);
                        break;
                    }
                    
                    // Создаем бронирование
                    $bookingId = $db->insert('bookings', [
                        'user_id' => $user['id'],
                        'slot_id' => $slotId,
                        'booking_status' => 'active',
                        'booking_notes' => !empty($notes) ? $notes : 'Автоматическое бронирование',
                        'expires_at' => date('Y-m-d H:i:s', strtotime($slot['slot_date'] . ' ' . $slot['end_time']))
                    ]);
                    
                    // Обновляем счетчик бронирований пользователя
                    $db->update('users', 
                        ['booking_count' => $user['booking_count'] + 1],
                        'id = ?',
                        [$user['id']]
                    );
                    
                    // Отправляем уведомление по email
                    try {
                        require_once __DIR__ . '/../includes/EmailService.php';
                        $emailService = new EmailService();
                        $emailService->sendBookingNotification($bookingId);
                    } catch (Exception $e) {
                        // Логируем ошибку, но не прерываем процесс
                        error_log('Ошибка отправки email уведомления: ' . $e->getMessage());
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'booking_id' => $bookingId,
                        'message' => 'Бронирование создано успешно'
                    ]);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Неизвестное действие']);
                    break;
            }
            break;
            
        case 'DELETE':
            // Отмена бронирования
            $path = $_SERVER['REQUEST_URI'];
            $pathParts = explode('/', trim($path, '/'));
            $bookingId = end($pathParts);
            
            if (!is_numeric($bookingId)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Неверный ID бронирования']);
                break;
            }
            
            // Проверяем, принадлежит ли бронирование пользователю
            $booking = $db->fetchOne("
                SELECT * FROM bookings 
                WHERE id = ? AND user_id = ? AND booking_status = 'active'
            ", [$bookingId, $user['id']]);
            
            if (!$booking) {
                echo json_encode(['success' => false, 'error' => 'Бронирование не найдено']);
                break;
            }
            
            // Отменяем бронирование
            $db->update('bookings', 
                ['booking_status' => 'cancelled'],
                'id = ?',
                [$bookingId]
            );
            
            // Обновляем счетчик бронирований пользователя
            $db->update('users', 
                ['booking_count' => $user['booking_count'] - 1],
                'id = ?',
                [$user['id']]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Бронирование отменено'
            ]);
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