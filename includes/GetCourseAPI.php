<?php

require_once __DIR__ . '/../config/getcourse.php';
require_once __DIR__ . '/Database.php';

class GetCourseAPI {
    private $config;
    private $db;
    
    public function __construct() {
        $this->config = GetCourseConfig::getInstance();
        $this->db = Database::getInstance();
    }
    
    /**
     * Получение объекта конфигурации
     */
    public function getConfig() {
        return $this->config;
    }
    
    /**
     * Выполнение запроса к GetCourse API
     */
    private function makeRequest($url, $action, $params = []) {
        $secretKey = $this->config->getSecretKey();
        
        $postData = [
            'action' => $action,
            'key' => $secretKey,
            'params' => base64_encode(json_encode($params))
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->config->get('timeout', 30),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: BookSmeta/1.0'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Ошибка cURL: " . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP ошибка: " . $httpCode);
        }
        
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Ошибка декодирования JSON: " . json_last_error_msg());
        }
        
        return $data;
    }
    
    /**
     * Логирование запроса к GetCourse
     */
    private function logRequest($action, $getcourseUserId, $email, $requestData, $responseData, $status) {
        $this->db->insert('getcourse_logs', [
            'action' => $action,
            'getcourse_user_id' => $getcourseUserId,
            'email' => $email,
            'request_data' => json_encode($requestData),
            'response_data' => json_encode($responseData),
            'status' => $status
        ]);
    }
    
    /**
     * Добавление пользователя в GetCourse
     */
    public function addUser($userData) {
        try {
            $url = $this->config->getUsersApiUrl();
            $response = $this->makeRequest($url, 'add', $userData);
            
            $this->logRequest(
                'add_user',
                $response['result']['user_id'] ?? null,
                $userData['user']['email'] ?? null,
                $userData,
                $response,
                $response['success'] ? 'success' : 'error'
            );
            
            return $response;
        } catch (Exception $e) {
            $this->logRequest(
                'add_user',
                null,
                $userData['user']['email'] ?? null,
                $userData,
                ['error' => $e->getMessage()],
                'error'
            );
            throw $e;
        }
    }
    
    /**
     * Добавление сделки в GetCourse
     */
    public function addDeal($dealData) {
        try {
            $url = $this->config->getDealsApiUrl();
            $response = $this->makeRequest($url, 'add', $dealData);
            
            $this->logRequest(
                'add_deal',
                $response['result']['user_id'] ?? null,
                $dealData['user']['email'] ?? null,
                $dealData,
                $response,
                $response['success'] ? 'success' : 'error'
            );
            
            return $response;
        } catch (Exception $e) {
            $this->logRequest(
                'add_deal',
                null,
                $dealData['user']['email'] ?? null,
                $dealData,
                ['error' => $e->getMessage()],
                'error'
            );
            throw $e;
        }
    }
    
    /**
     * Отправка сообщения через GetCourse
     */
    public function sendMessage($messageData) {
        try {
            $url = $this->config->getMessagesApiUrl();
            $response = $this->makeRequest($url, 'send', $messageData);
            
            $this->logRequest(
                'send_message',
                null,
                $messageData['message']['email'] ?? null,
                $messageData,
                $response,
                $response['success'] ? 'success' : 'error'
            );
            
            return $response;
        } catch (Exception $e) {
            $this->logRequest(
                'send_message',
                null,
                $messageData['message']['email'] ?? null,
                $messageData,
                ['error' => $e->getMessage()],
                'error'
            );
            throw $e;
        }
    }
    
    /**
     * Создание пользователя в системе при оплате в GetCourse
     */
    public function createUserFromGetCourse($email, $userInfo = []) {
        try {
            $this->db->beginTransaction();
            
            // Проверяем, не существует ли уже пользователь
            $existingUser = $this->db->fetchOne(
                "SELECT id FROM users WHERE email = ? OR getcourse_user_id = ?", 
                [$email, $userInfo['user_id'] ?? null]
            );
            
            if ($existingUser) {
                // Обновляем статус оплаты
                $this->db->update('users', 
                    ['is_paid_client' => true],
                    'id = ?',
                    [$existingUser['id']]
                );
                
                $this->db->commit();
                return $existingUser['id'];
            }
            
            // Генерируем пароль
            $password = generatePassword();
            $passwordHash = hashPassword($password);
            
            // Создаем пользователя
            $userId = $this->db->insert('users', [
                'email' => $email,
                'password_hash' => $passwordHash,
                'first_name' => $userInfo['first_name'] ?? '',
                'last_name' => $userInfo['last_name'] ?? '',
                'phone' => $userInfo['phone'] ?? null,
                'getcourse_user_id' => $userInfo['user_id'] ?? null,
                'is_paid_client' => true,
                'email_verified' => true,
                'role' => 'user',
                'booking_limit' => getSetting('default_booking_limit', 3)
            ]);
            
            // Отправляем email с паролем
            $this->sendWelcomeEmail($email, $password, $userInfo);
            
            $this->db->commit();
            
            logInfo("Создан новый пользователь из GetCourse", [
                'user_id' => $userId,
                'email' => $email,
                'getcourse_user_id' => $userInfo['user_id'] ?? null
            ]);
            
            return $userId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            logError("Ошибка создания пользователя из GetCourse", [
                'email' => $email,
                'user_info' => $userInfo,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Отправка приветственного email с паролем
     */
    private function sendWelcomeEmail($email, $password, $userInfo) {
        // Здесь будет реализована отправка email
        // Пока просто логируем
        logInfo("Отправка приветственного email", [
            'email' => $email,
            'password' => $password,
            'user_info' => $userInfo
        ]);
        
        // TODO: Реализовать отправку email через PHPMailer
    }
    
    /**
     * Получение списка пользователей из GetCourse
     */
    public function getUsers($params = []) {
        try {
            $url = $this->config->getUsersApiUrl();
            $response = $this->makeRequest($url, 'get', $params);
            
            $this->logRequest(
                'get_users',
                null,
                null,
                $params,
                $response,
                $response['success'] ? 'success' : 'error'
            );
            
            return $response['result']['users'] ?? [];
        } catch (Exception $e) {
            $this->logRequest(
                'get_users',
                null,
                null,
                $params,
                ['error' => $e->getMessage()],
                'error'
            );
            throw $e;
        }
    }
    
    /**
     * Проверка статуса пользователя в GetCourse
     */
    public function checkUserStatus($email) {
        try {
            $users = $this->getUsers(['email' => $email]);
            
            if (!empty($users)) {
                $user = $users[0];
                return [
                    'is_paid' => $user['is_paid'] ?? false,
                    'user_id' => $user['id'] ?? null,
                    'email' => $user['email'] ?? null
                ];
            }
            
            return [
                'is_paid' => false,
                'user_id' => null,
                'email' => $email
            ];
        } catch (Exception $e) {
            logError("Ошибка проверки статуса пользователя GetCourse", [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            
            return [
                'is_paid' => false,
                'user_id' => null,
                'email' => $email
            ];
        }
    }
    
    /**
     * Обработка webhook от GetCourse
     */
    public function handleWebhook($data) {
        try {
            // Проверяем подпись webhook если настроена
            if ($this->config->getWebhookSecret()) {
                $this->verifyWebhookSignature($data);
            }
            
            $action = $data['action'] ?? '';
            
            switch ($action) {
                case 'user_payment':
                    $this->handleUserPayment($data);
                    break;
                case 'deal_created':
                    $this->handleDealCreated($data);
                    break;
                default:
                    logInfo("Неизвестное действие webhook GetCourse", $data);
            }
            
            return ['success' => true];
            
        } catch (Exception $e) {
            logError("Ошибка обработки webhook GetCourse", [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Обработка уведомления об оплате пользователя
     */
    private function handleUserPayment($data) {
        $email = $data['user']['email'] ?? null;
        $userInfo = $data['user'] ?? [];
        
        if (!$email) {
            throw new Exception("Email пользователя не указан в webhook");
        }
        
        $this->createUserFromGetCourse($email, $userInfo);
    }
    
    /**
     * Обработка создания сделки
     */
    private function handleDealCreated($data) {
        // Логика обработки создания сделки
        logInfo("Создана сделка в GetCourse", $data);
    }
    
    /**
     * Проверка подписи webhook
     */
    private function verifyWebhookSignature($data) {
        $secret = $this->config->getWebhookSecret();
        
        if (empty($secret)) {
            // Если секрет не настроен, пропускаем проверку
            logInfo("Webhook секрет не настроен, пропускаем проверку подписи");
            return true;
        }
        
        // Получаем подпись из заголовка
        $signature = $_SERVER['HTTP_X_GETCOURSE_SIGNATURE'] ?? 
                    $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? 
                    $_SERVER['HTTP_X_SIGNATURE'] ?? '';
        
        if (empty($signature)) {
            throw new Exception("Отсутствует подпись webhook в заголовках");
        }
        
        // Получаем сырые данные POST запроса
        $payload = file_get_contents('php://input');
        
        // Создаем ожидаемую подпись
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        
        // Проверяем подпись
        if (!hash_equals($expectedSignature, $signature)) {
            logError("Неверная подпись webhook", [
                'received_signature' => $signature,
                'expected_signature' => $expectedSignature,
                'payload_length' => strlen($payload)
            ]);
            throw new Exception("Неверная подпись webhook");
        }
        
        logInfo("Webhook подпись проверена успешно");
        return true;
    }
}
