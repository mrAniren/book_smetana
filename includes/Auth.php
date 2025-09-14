<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/functions.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Авторизация пользователя
     */
    public function login($email, $password) {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ? AND is_active = 1",
            [$email]
        );
        
        if (!$user || !verifyPassword($password, $user['password_hash'])) {
            return false;
        }
        
        // Обновляем время последнего входа
        $this->db->update('users', 
            ['last_login' => date('Y-m-d H:i:s')],
            'id = ?',
            [$user['id']]
        );
        
        // Устанавливаем сессию
        $this->setSession($user);
        
        return $user;
    }
    
    /**
     * Регистрация пользователя (только для оплативших клиентов)
     */
    public function register($userData) {
        // Проверяем, что пользователь оплативший клиент
        if (!$userData['is_paid_client']) {
            throw new Exception("Регистрация доступна только для оплативших клиентов");
        }
        
        // Проверяем, не существует ли уже пользователь
        if ($this->db->exists('users', 'email = ?', [$userData['email']])) {
            throw new Exception("Пользователь с таким email уже существует");
        }
        
        $userId = $this->db->insert('users', [
            'email' => $userData['email'],
            'password_hash' => hashPassword($userData['password']),
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'phone' => $userData['phone'] ?? null,
            'role' => 'user',
            'is_paid_client' => true,
            'email_verified' => true,
            'booking_limit' => getSetting('default_booking_limit', 3),
            'booking_count' => 0
        ]);
        
        logInfo("Зарегистрирован новый пользователь", [
            'user_id' => $userId,
            'email' => $userData['email']
        ]);
        
        return $userId;
    }
    
    /**
     * Установка сессии пользователя
     */
    private function setSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['is_paid_client'] = $user['is_paid_client'];
        $_SESSION['booking_limit'] = $user['booking_limit'];
        $_SESSION['booking_count'] = $user['booking_count'];
    }
    
    /**
     * Проверка авторизации
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Получение текущего пользователя
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        );
    }
    
    /**
     * Проверка роли пользователя
     */
    public function hasRole($role) {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }
    
    /**
     * Проверка прав администратора
     */
    public function isAdmin() {
        return $this->hasRole('admin') || $this->hasRole('super_admin');
    }
    
    /**
     * Проверка прав суперадминистратора
     */
    public function isSuperAdmin() {
        return $this->hasRole('super_admin');
    }
    
    /**
     * Выход из системы
     */
    public function logout() {
        session_destroy();
        session_start();
    }
    
    /**
     * Смена пароля
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        $user = $this->db->fetchOne(
            "SELECT password_hash FROM users WHERE id = ?",
            [$userId]
        );
        
        if (!$user || !verifyPassword($currentPassword, $user['password_hash'])) {
            throw new Exception("Неверный текущий пароль");
        }
        
        $this->db->update('users',
            ['password_hash' => hashPassword($newPassword)],
            'id = ?',
            [$userId]
        );
        
        logInfo("Пароль изменен", ['user_id' => $userId]);
    }
    
    /**
     * Сброс пароля
     */
    public function resetPassword($email) {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ? AND is_active = 1",
            [$email]
        );
        
        if (!$user) {
            throw new Exception("Пользователь не найден");
        }
        
        $newPassword = generatePassword();
        $passwordHash = hashPassword($newPassword);
        
        $this->db->update('users',
            ['password_hash' => $passwordHash],
            'id = ?',
            [$user['id']]
        );
        
        // Отправляем новый пароль на email
        $this->sendPasswordResetEmail($user['email'], $newPassword);
        
        logInfo("Пароль сброшен", [
            'user_id' => $user['id'],
            'email' => $email
        ]);
        
        return true;
    }
    
    /**
     * Отправка email со сброшенным паролем
     */
    private function sendPasswordResetEmail($email, $password) {
        // TODO: Реализовать отправку email
        logInfo("Отправка email со сброшенным паролем", [
            'email' => $email,
            'password' => $password
        ]);
    }
    
    /**
     * Получение пользователя по ID
     */
    public function getUserById($userId) {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE id = ?",
            [$userId]
        );
    }
    
    /**
     * Получение пользователя по email
     */
    public function getUserByEmail($email) {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );
    }
    
    /**
     * Обновление профиля пользователя
     */
    public function updateProfile($userId, $data) {
        $allowedFields = ['first_name', 'last_name', 'phone'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (empty($updateData)) {
            throw new Exception("Нет данных для обновления");
        }
        
        $this->db->update('users', $updateData, 'id = ?', [$userId]);
        
        logInfo("Профиль пользователя обновлен", [
            'user_id' => $userId,
            'updated_fields' => array_keys($updateData)
        ]);
    }
    
    /**
     * Установка лимита бронирований
     */
    public function setBookingLimit($userId, $limit) {
        if (!is_numeric($limit) || $limit < 0) {
            throw new Exception("Неверное значение лимита бронирований");
        }
        
        $this->db->update('users',
            ['booking_limit' => (int)$limit],
            'id = ?',
            [$userId]
        );
        
        // Обновляем сессию если это текущий пользователь
        if ($_SESSION['user_id'] == $userId) {
            $_SESSION['booking_limit'] = (int)$limit;
        }
        
        logInfo("Лимит бронирований изменен", [
            'user_id' => $userId,
            'new_limit' => $limit
        ]);
    }
    
    /**
     * Получение статистики пользователя
     */
    public function getUserStats($userId) {
        $user = $this->getUserById($userId);
        if (!$user) {
            return null;
        }
        
        $stats = [
            'user' => $user,
            'total_bookings' => $this->db->count('bookings', 'user_id = ?', [$userId]),
            'active_bookings' => $this->db->count('bookings', 'user_id = ? AND booking_status = "active"', [$userId]),
            'completed_bookings' => $this->db->count('bookings', 'user_id = ? AND booking_status = "completed"', [$userId]),
            'available_bookings' => $user['booking_limit'] - $user['booking_count']
        ];
        
        return $stats;
    }
    
    /**
     * Создание нового пользователя (для админов)
     */
    public function createUser($data) {
        $email = trim($data['email'] ?? '');
        $firstName = trim($data['first_name'] ?? '');
        $lastName = trim($data['last_name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $role = $data['role'] ?? 'user';
        $bookingLimit = (int)($data['booking_limit'] ?? 3);
        $isPaidClient = (int)($data['is_paid_client'] ?? 0);
        
        if (empty($email) || empty($firstName) || empty($lastName)) {
            throw new Exception("Email, имя и фамилия обязательны");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Некорректный email");
        }
        
        // Проверяем, не существует ли уже пользователь с таким email
        $existingUser = $this->getUserByEmail($email);
        if ($existingUser) {
            throw new Exception("Пользователь с таким email уже существует");
        }
        
        // Генерируем случайный пароль
        $password = $this->generateRandomPassword();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $userId = $this->db->insert('users', [
            'email' => $email,
            'password_hash' => $hashedPassword,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => $phone,
            'role' => $role,
            'booking_limit' => $bookingLimit,
            'booking_count' => 0,
            'is_paid_client' => $isPaidClient,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        logInfo("Пользователь создан администратором", [
            'user_id' => $userId,
            'email' => $email,
            'role' => $role
        ]);
        
        // Отправляем email уведомление о регистрации
        try {
            require_once __DIR__ . '/EmailService.php';
            $emailService = new EmailService();
            $emailService->sendRegistrationNotification($userId, $email, $password);
            logInfo("Email уведомление о регистрации отправлено", ['user_id' => $userId, 'email' => $email]);
        } catch (Exception $e) {
            // Логируем ошибку, но не прерываем процесс создания пользователя
            logError("Ошибка отправки email уведомления о регистрации", [
                'user_id' => $userId,
                'email' => $email,
                'error' => $e->getMessage()
            ]);
        }
        
        return [
            'user_id' => $userId,
            'email' => $email,
            'password' => $password // Возвращаем пароль для передачи пользователю
        ];
    }
    
    /**
     * Обновление пользователя (для админов)
     */
    public function updateUser($userId, $data) {
        $allowedFields = [
            'first_name', 'last_name', 'phone', 'role', 
            'booking_limit', 'is_paid_client', 'is_active'
        ];
        
        $updateData = [];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (empty($updateData)) {
            throw new Exception("Нет данных для обновления");
        }
        
        // Проверяем, что пользователь существует
        $user = $this->getUserById($userId);
        if (!$user) {
            throw new Exception("Пользователь не найден");
        }
        
        $this->db->update('users', $updateData, 'id = ?', [$userId]);
        
        logInfo("Пользователь обновлен администратором", [
            'user_id' => $userId,
            'updated_fields' => array_keys($updateData)
        ]);
    }
    
    /**
     * Удаление пользователя (для админов)
     */
    public function deleteUser($userId) {
        // Проверяем, что пользователь существует
        $user = $this->getUserById($userId);
        if (!$user) {
            throw new Exception("Пользователь не найден");
        }
        
        // Проверяем, что это не супер-админ
        if ($user['role'] === 'super_admin') {
            throw new Exception("Нельзя удалить супер-администратора");
        }
        
        // Удаляем все бронирования пользователя
        $this->db->query("DELETE FROM bookings WHERE user_id = ?", [$userId]);
        
        // Удаляем пользователя
        $this->db->query("DELETE FROM users WHERE id = ?", [$userId]);
        
        logInfo("Пользователь удален администратором", [
            'user_id' => $userId,
            'email' => $user['email']
        ]);
    }
    
    /**
     * Генерация случайного пароля
     */
    private function generateRandomPassword($length = 12) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        return substr(str_shuffle($chars), 0, $length);
    }
}
