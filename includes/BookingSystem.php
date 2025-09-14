<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/functions.php';

class BookingSystem {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Получение доступных слотов
     */
    public function getAvailableSlots($startDate = null, $endDate = null) {
        $startDate = $startDate ?: date('Y-m-d');
        $endDate = $endDate ?: date('Y-m-d', strtotime('+7 days'));
        
        $sql = "
            SELECT s.*, 
                   COUNT(b.id) as bookings_count,
                   (s.max_users - COUNT(b.id)) as available_spots
            FROM server_slots s
            LEFT JOIN bookings b ON s.id = b.slot_id AND b.booking_status = 'active'
            WHERE s.slot_date >= ? 
              AND s.slot_date <= ?
              AND s.is_available = 1
              AND (s.max_users - COUNT(b.id)) > 0
            GROUP BY s.id
            ORDER BY s.slot_date, s.start_time
        ";
        
        return $this->db->fetchAll($sql, [$startDate, $endDate]);
    }
    
    /**
     * Создание бронирования
     */
    public function createBooking($userId, $slotId, $notes = null) {
        try {
            $this->db->beginTransaction();
            
            // Проверяем существование слота
            $slot = $this->db->fetchOne(
                "SELECT * FROM server_slots WHERE id = ? AND is_available = 1",
                [$slotId]
            );
            
            if (!$slot) {
                throw new Exception("Слот не найден или недоступен");
            }
            
            // Проверяем, что слот в будущем
            $slotDateTime = $slot['slot_date'] . ' ' . $slot['start_time'];
            if (!isFutureDate($slotDateTime)) {
                throw new Exception("Нельзя бронировать слоты в прошлом");
            }
            
            // Проверяем доступность места в слоте
            $bookingsCount = $this->db->count(
                'bookings',
                'slot_id = ? AND booking_status = "active"',
                [$slotId]
            );
            
            if ($bookingsCount >= $slot['max_users']) {
                throw new Exception("В данном слоте нет свободных мест");
            }
            
            // Проверяем лимит бронирований пользователя
            $user = $this->db->fetchOne(
                "SELECT booking_limit, booking_count FROM users WHERE id = ?",
                [$userId]
            );
            
            if ($user['booking_count'] >= $user['booking_limit']) {
                throw new Exception("Превышен лимит бронирований");
            }
            
            // Проверяем, не забронировал ли пользователь уже этот слот
            $existingBooking = $this->db->fetchOne(
                "SELECT id FROM bookings WHERE user_id = ? AND slot_id = ? AND booking_status = 'active'",
                [$userId, $slotId]
            );
            
            if ($existingBooking) {
                throw new Exception("Вы уже забронировали этот слот");
            }
            
            // Создаем бронирование
            $expiresAt = date('Y-m-d H:i:s', strtotime('+' . getSetting('booking_expire_hours', 24) . ' hours'));
            
            $bookingId = $this->db->insert('bookings', [
                'user_id' => $userId,
                'slot_id' => $slotId,
                'booking_status' => 'active',
                'booking_notes' => $notes,
                'expires_at' => $expiresAt
            ]);
            
            // Обновляем счетчик бронирований пользователя
            $this->db->update('users',
                ['booking_count' => $user['booking_count'] + 1],
                'id = ?',
                [$userId]
            );
            
            // Создаем уведомление
            createNotification(
                $userId,
                'booking_created',
                'Бронирование создано',
                "Вы успешно забронировали слот на {$slot['slot_date']} в {$slot['start_time']}"
            );
            
            $this->db->commit();
            
            logInfo("Создано бронирование", [
                'booking_id' => $bookingId,
                'user_id' => $userId,
                'slot_id' => $slotId
            ]);
            
            return $bookingId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Отмена бронирования
     */
    public function cancelBooking($bookingId, $userId = null) {
        try {
            $this->db->beginTransaction();
            
            // Проверяем существование бронирования
            $whereClause = "id = ?";
            $params = [$bookingId];
            
            if ($userId) {
                $whereClause .= " AND user_id = ?";
                $params[] = $userId;
            }
            
            $booking = $this->db->fetchOne(
                "SELECT * FROM bookings WHERE {$whereClause} AND booking_status = 'active'",
                $params
            );
            
            if (!$booking) {
                throw new Exception("Бронирование не найдено");
            }
            
            // Отменяем бронирование
            $this->db->update('bookings',
                ['booking_status' => 'cancelled'],
                'id = ?',
                [$bookingId]
            );
            
            // Уменьшаем счетчик бронирований пользователя
            $this->db->update('users',
                ['booking_count' => 'booking_count - 1'],
                'id = ?',
                [$booking['user_id']]
            );
            
            // Создаем уведомление
            createNotification(
                $booking['user_id'],
                'booking_cancelled',
                'Бронирование отменено',
                'Ваше бронирование было отменено'
            );
            
            $this->db->commit();
            
            logInfo("Отменено бронирование", [
                'booking_id' => $bookingId,
                'user_id' => $booking['user_id']
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Завершение бронирования
     */
    public function completeBooking($bookingId) {
        $this->db->update('bookings',
            ['booking_status' => 'completed'],
            'id = ?',
            [$bookingId]
        );
        
        logInfo("Завершено бронирование", ['booking_id' => $bookingId]);
    }
    
    /**
     * Получение бронирований пользователя
     */
    public function getUserBookings($userId, $status = null) {
        $sql = "
            SELECT b.*, s.slot_date, s.start_time, s.end_time, s.server_login, s.server_password
            FROM bookings b
            JOIN server_slots s ON b.slot_id = s.id
            WHERE b.user_id = ?
        ";
        
        $params = [$userId];
        
        if ($status) {
            $sql .= " AND b.booking_status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY s.slot_date, s.start_time";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Получение информации о бронировании
     */
    public function getBooking($bookingId, $userId = null) {
        $sql = "
            SELECT b.*, s.slot_date, s.start_time, s.end_time, s.server_login, s.server_password,
                   u.email, u.first_name, u.last_name
            FROM bookings b
            JOIN server_slots s ON b.slot_id = s.id
            JOIN users u ON b.user_id = u.id
            WHERE b.id = ?
        ";
        
        $params = [$bookingId];
        
        if ($userId) {
            $sql .= " AND b.user_id = ?";
            $params[] = $userId;
        }
        
        return $this->db->fetchOne($sql, $params);
    }
    
    /**
     * Очистка истекших бронирований
     */
    public function cleanupExpiredBookings() {
        $expiredBookings = $this->db->fetchAll(
            "SELECT id, user_id FROM bookings WHERE booking_status = 'active' AND expires_at < NOW()"
        );
        
        $count = 0;
        foreach ($expiredBookings as $booking) {
            try {
                $this->cancelBooking($booking['id']);
                $count++;
            } catch (Exception $e) {
                logError("Ошибка отмены истекшего бронирования", [
                    'booking_id' => $booking['id'],
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        logInfo("Очищено истекших бронирований", ['count' => $count]);
        return $count;
    }
    
    /**
     * Получение статистики бронирований
     */
    public function getBookingStats($startDate = null, $endDate = null) {
        $startDate = $startDate ?: date('Y-m-01'); // Начало месяца
        $endDate = $endDate ?: date('Y-m-t'); // Конец месяца
        
        $stats = [];
        
        // Общее количество бронирований
        $stats['total_bookings'] = $this->db->count(
            'bookings',
            'created_at >= ? AND created_at <= ?',
            [$startDate, $endDate]
        );
        
        // Активные бронирования
        $stats['active_bookings'] = $this->db->count(
            'bookings',
            'booking_status = "active" AND created_at >= ? AND created_at <= ?',
            [$startDate, $endDate]
        );
        
        // Завершенные бронирования
        $stats['completed_bookings'] = $this->db->count(
            'bookings',
            'booking_status = "completed" AND created_at >= ? AND created_at <= ?',
            [$startDate, $endDate]
        );
        
        // Отмененные бронирования
        $stats['cancelled_bookings'] = $this->db->count(
            'bookings',
            'booking_status = "cancelled" AND created_at >= ? AND created_at <= ?',
            [$startDate, $endDate]
        );
        
        // Количество уникальных пользователей
        $result = $this->db->fetchOne(
            "SELECT COUNT(DISTINCT user_id) as unique_users FROM bookings WHERE created_at >= ? AND created_at <= ?",
            [$startDate, $endDate]
        );
        $stats['unique_users'] = $result['unique_users'];
        
        return $stats;
    }
    
    /**
     * Проверка возможности бронирования для пользователя
     */
    public function canUserBook($userId) {
        $user = $this->db->fetchOne(
            "SELECT booking_limit, booking_count, is_paid_client FROM users WHERE id = ?",
            [$userId]
        );
        
        if (!$user) {
            return false;
        }
        
        if (!$user['is_paid_client']) {
            return false;
        }
        
        return $user['booking_count'] < $user['booking_limit'];
    }
    
    /**
     * Получение ближайших доступных слотов
     */
    public function getNextAvailableSlots($limit = 10) {
        $sql = "
            SELECT s.*, 
                   COUNT(b.id) as bookings_count,
                   (s.max_users - COUNT(b.id)) as available_spots
            FROM server_slots s
            LEFT JOIN bookings b ON s.id = b.slot_id AND b.booking_status = 'active'
            WHERE s.slot_date >= CURDATE() 
              AND s.is_available = 1
              AND (s.max_users - COUNT(b.id)) > 0
            GROUP BY s.id
            ORDER BY s.slot_date, s.start_time
            LIMIT ?
        ";
        
        return $this->db->fetchAll($sql, [$limit]);
    }
}
