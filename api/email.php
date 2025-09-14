<?php

header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/EmailService.php';

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

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $emailService = new EmailService();
    
    switch ($action) {
        case 'test':
            $email = $_POST['email'] ?? '';
            if (empty($email)) {
                throw new Exception('Email не указан');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Некорректный email');
            }
            
            $result = $emailService->sendTestEmail($email);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Тестовое письмо отправлено на ' . $email
            ]);
            break;
            
        case 'test_connection':
            $result = $emailService->testSmtpConnection();
            
            echo json_encode([
                'success' => true, 
                'message' => 'SMTP соединение работает корректно'
            ]);
            break;
            
        case 'send_booking_notification':
            $bookingId = (int)($_POST['booking_id'] ?? 0);
            if (!$bookingId) {
                throw new Exception('ID бронирования не указан');
            }
            
            $result = $emailService->sendBookingNotification($bookingId);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Уведомление о бронировании отправлено'
            ]);
            break;
            
        case 'send_cancellation_notification':
            $bookingId = (int)($_POST['booking_id'] ?? 0);
            if (!$bookingId) {
                throw new Exception('ID бронирования не указан');
            }
            
            $result = $emailService->sendCancellationNotification($bookingId);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Уведомление об отмене отправлено'
            ]);
            break;
            
        case 'send_registration_notification':
            $userId = (int)($_POST['user_id'] ?? 0);
            $email = trim($_POST['email'] ?? '');
            $password = trim($_POST['password'] ?? '');
            
            if (!$userId || empty($email) || empty($password)) {
                throw new Exception('Не все параметры указаны');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Некорректный email');
            }
            
            $result = $emailService->sendRegistrationNotification($userId, $email, $password);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Уведомление о регистрации отправлено на ' . $email
            ]);
            break;
            
        case 'stats':
            $days = (int)($_GET['days'] ?? 30);
            $stats = $emailService->getEmailStats($days);
            
            echo json_encode([
                'success' => true, 
                'stats' => $stats
            ]);
            break;
            
        default:
            throw new Exception('Неизвестное действие');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
