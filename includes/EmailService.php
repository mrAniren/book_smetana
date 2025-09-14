<?php

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../vendor/autoload.php';

class EmailService {
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->loadConfig();
    }
    
    /**
     * Загрузка конфигурации email из базы данных
     */
    private function loadConfig() {
        $settings = $this->db->fetchAll('SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE "email_%" OR setting_key LIKE "smtp_%" OR setting_key LIKE "from_%"');
        $this->config = [];
        
        foreach ($settings as $setting) {
            $this->config[$setting['setting_key']] = $setting['setting_value'];
        }
        
        // Настройки по умолчанию для Unisender Go
        $this->config['email_notifications'] = $this->config['email_notifications'] ?? 0;
        $this->config['smtp_host'] = $this->config['smtp_host'] ?? 'smtp.go1.unisender.ru';
        $this->config['smtp_port'] = $this->config['smtp_port'] ?? 587;
        $this->config['smtp_username'] = $this->config['smtp_username'] ?? '';
        $this->config['smtp_password'] = $this->config['smtp_password'] ?? '';
        $this->config['smtp_encryption'] = $this->config['smtp_encryption'] ?? 'tls';
        $this->config['from_email'] = $this->config['from_email'] ?? '';
        $this->config['from_name'] = $this->config['from_name'] ?? 'Book Smeta';
    }
    
    /**
     * Отправка email через SMTP
     */
    public function sendEmail($to, $subject, $message, $options = []) {
        if (!$this->config['email_notifications']) {
            throw new Exception('Email уведомления отключены');
        }
        
        if (empty($this->config['smtp_host']) || empty($this->config['smtp_username']) || empty($this->config['smtp_password'])) {
            throw new Exception('SMTP настройки не заполнены');
        }
        
        try {
            // Создаем PHPMailer экземпляр
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Настройки SMTP для Unisender Go
            $mail->isSMTP();
            $mail->Host = $this->config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['smtp_username'];
            $mail->Password = $this->config['smtp_password'];
            $mail->SMTPSecure = $this->config['smtp_encryption'];
            $mail->Port = $this->config['smtp_port'];
            $mail->CharSet = 'UTF-8';
            
            // Отправитель
            $mail->setFrom($this->config['from_email'], $this->config['from_name']);
            
            // Получатель
            if (is_array($to)) {
                foreach ($to as $email => $name) {
                    $mail->addAddress($email, $name);
                }
            } else {
                $mail->addAddress($to);
            }
            
            // Заголовки для Unisender Go
            $unisenderHeaders = $this->buildUnisenderHeaders($options);
            if (!empty($unisenderHeaders)) {
                $mail->addCustomHeader('X-UNISENDER-GO', json_encode($unisenderHeaders));
            }
            
            // Содержимое письма
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            
            // Отправляем
            $result = $mail->send();
            
            // Логируем отправку
            $this->logEmail($to, $subject, 'sent', $options);
            
            return $result;
            
        } catch (Exception $e) {
            // Логируем ошибку
            $this->logEmail($to, $subject, 'error', $options, $e->getMessage());
            throw new Exception('Ошибка отправки email: ' . $e->getMessage());
        }
    }
    
    /**
     * Построение заголовков для Unisender Go
     */
    private function buildUnisenderHeaders($options) {
        $headers = [];
        
        // Язык для ссылок отписки
        if (isset($options['language'])) {
            $headers['global_language'] = $options['language'];
        }
        
        // Шаблонизатор
        if (isset($options['template_engine'])) {
            $headers['template_engine'] = $options['template_engine'];
        }
        
        // ID шаблона
        if (isset($options['template_id'])) {
            $headers['template_id'] = $options['template_id'];
        }
        
        // Подстановки
        if (isset($options['substitutions']) && is_array($options['substitutions'])) {
            $headers['global_substitutions'] = $options['substitutions'];
        }
        
        // Метаданные
        if (isset($options['metadata']) && is_array($options['metadata'])) {
            $headers['global_metadata'] = $options['metadata'];
        }
        
        // Отслеживание ссылок
        if (isset($options['track_links'])) {
            $headers['track_links'] = $options['track_links'] ? 1 : 0;
        }
        
        // Отслеживание прочтений
        if (isset($options['track_read'])) {
            $headers['track_read'] = $options['track_read'] ? 1 : 0;
        }
        
        // Пропуск ссылки отписки
        if (isset($options['skip_unsubscribe'])) {
            $headers['skip_unsubscribe'] = $options['skip_unsubscribe'] ? 1 : 0;
        }
        
        // Строгий режим SMTP
        if (isset($options['strict'])) {
            $headers['strict'] = $options['strict'] ? true : false;
        }
        
        // Теги
        if (isset($options['tags']) && is_array($options['tags'])) {
            $headers['tags'] = array_slice($options['tags'], 0, 4); // Максимум 4 тега
        }
        
        // Ключ идемпотентности
        if (isset($options['idempotence_key'])) {
            $headers['idempotence_key'] = substr($options['idempotence_key'], 0, 64);
        }
        
        return $headers;
    }
    
    /**
     * Логирование отправки email
     */
    private function logEmail($to, $subject, $status, $options = [], $error = null) {
        try {
            $this->db->insert('email_logs', [
                'to_email' => is_array($to) ? implode(', ', array_keys($to)) : $to,
                'subject' => $subject,
                'status' => $status,
                'options' => json_encode($options),
                'error_message' => $error,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Игнорируем ошибки логирования
        }
    }
    
    /**
     * Отправка уведомления о бронировании
     */
    public function sendBookingNotification($bookingId) {
        try {
            $booking = $this->getBookingDetails($bookingId);
            if (!$booking) {
                throw new Exception('Бронирование не найдено');
            }
            
            $subject = 'Подтверждение бронирования #' . $bookingId;
            $message = $this->buildBookingEmailTemplate($booking);
            
            $options = [
                'language' => 'ru',
                'track_links' => true,
                'track_read' => true,
                'metadata' => [
                    'booking_id' => $bookingId,
                    'type' => 'booking_confirmation'
                ],
                'tags' => ['booking', 'confirmation']
            ];
            
            return $this->sendEmail($booking['user_email'], $subject, $message, $options);
            
        } catch (Exception $e) {
            throw new Exception('Ошибка отправки уведомления о бронировании: ' . $e->getMessage());
        }
    }
    
    /**
     * Отправка уведомления об отмене бронирования
     */
    public function sendCancellationNotification($bookingId) {
        try {
            $booking = $this->getBookingDetails($bookingId);
            if (!$booking) {
                throw new Exception('Бронирование не найдено');
            }
            
            $subject = 'Отмена бронирования #' . $bookingId;
            $message = $this->buildCancellationEmailTemplate($booking);
            
            $options = [
                'language' => 'ru',
                'track_links' => true,
                'track_read' => true,
                'metadata' => [
                    'booking_id' => $bookingId,
                    'type' => 'booking_cancellation'
                ],
                'tags' => ['booking', 'cancellation']
            ];
            
            return $this->sendEmail($booking['user_email'], $subject, $message, $options);
            
        } catch (Exception $e) {
            throw new Exception('Ошибка отправки уведомления об отмене: ' . $e->getMessage());
        }
    }
    
    /**
     * Отправка уведомления о регистрации нового пользователя
     */
    public function sendRegistrationNotification($userId, $email, $password) {
        try {
            $user = $this->getUserDetails($userId);
            if (!$user) {
                throw new Exception('Пользователь не найден');
            }
            
            $subject = 'Добро пожаловать в Book Smeta!';
            $message = $this->buildRegistrationEmailTemplate($user, $password);
            
            $options = [
                'language' => 'ru',
                'track_links' => true,
                'track_read' => true,
                'metadata' => [
                    'user_id' => $userId,
                    'type' => 'registration'
                ],
                'tags' => ['registration', 'welcome']
            ];
            
            return $this->sendEmail($email, $subject, $message, $options);
            
        } catch (Exception $e) {
            throw new Exception('Ошибка отправки уведомления о регистрации: ' . $e->getMessage());
        }
    }
    
    /**
     * Отправка тестового письма
     */
    public function sendTestEmail($to) {
        $subject = 'Тестовое письмо от Book Smeta';
        $message = $this->buildTestEmailTemplate();
        
        $options = [
            'language' => 'ru',
            'track_links' => false,
            'track_read' => false,
            'metadata' => [
                'type' => 'test'
            ],
            'tags' => ['test']
        ];
        
        return $this->sendEmail($to, $subject, $message, $options);
    }
    
    /**
     * Получение деталей бронирования
     */
    private function getBookingDetails($bookingId) {
        return $this->db->fetchOne("
            SELECT b.*, 
                   u.email as user_email, u.first_name, u.last_name,
                   s.slot_date, s.start_time, s.end_time, s.server_login, s.server_password
            FROM bookings b
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN server_slots s ON b.slot_id = s.id
            WHERE b.id = ?
        ", [$bookingId]);
    }
    
    /**
     * Получение деталей пользователя
     */
    private function getUserDetails($userId) {
        return $this->db->fetchOne("
            SELECT id, email, first_name, last_name, role, created_at
            FROM users
            WHERE id = ?
        ", [$userId]);
    }
    
    /**
     * Шаблон письма о бронировании
     */
    private function buildBookingEmailTemplate($booking) {
        $slotDate = date('d.m.Y', strtotime($booking['slot_date']));
        $startTime = date('H:i', strtotime($booking['start_time']));
        $endTime = date('H:i', strtotime($booking['end_time']));
        
        return "
        <!DOCTYPE html>
        <html lang='ru'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Подтверждение бронирования</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff;'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <h1 style='color: #2c5aa0; margin: 0;'>Book Smeta</h1>
                    <p style='color: #666; margin: 5px 0 0 0;'>Система бронирования серверов</p>
                </div>
                
                <h2 style='color: #2c5aa0; border-bottom: 2px solid #e9ecef; padding-bottom: 10px;'>Подтверждение бронирования</h2>
                
                <p>Здравствуйте, <strong>{$booking['first_name']} {$booking['last_name']}</strong>!</p>
                
                <p>Ваше бронирование сервера подтверждено. Ниже вы найдете все необходимые данные для подключения.</p>
                
                <div style='background: #f8f9fa; padding: 25px; border-radius: 8px; margin: 25px 0; border-left: 4px solid #2c5aa0;'>
                    <h3 style='margin-top: 0; color: #2c5aa0;'>📅 Детали бронирования</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; width: 120px;'>Дата:</td>
                            <td style='padding: 8px 0;'>{$slotDate}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Время:</td>
                            <td style='padding: 8px 0;'>{$startTime} - {$endTime}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Номер:</td>
                            <td style='padding: 8px 0;'>#{$booking['id']}</td>
                        </tr>
                    </table>
                </div>
                
                <div style='background: #e8f5e8; padding: 25px; border-radius: 8px; margin: 25px 0; border-left: 4px solid #28a745;'>
                    <h3 style='margin-top: 0; color: #28a745;'>🔑 Данные для доступа к серверу</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; width: 120px;'>Логин:</td>
                            <td style='padding: 8px 0; font-family: monospace; background: #f8f9fa; padding: 5px; border-radius: 3px;'>{$booking['server_login']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Пароль:</td>
                            <td style='padding: 8px 0; font-family: monospace; background: #f8f9fa; padding: 5px; border-radius: 3px;'>{$booking['server_password']}</td>
                        </tr>
                    </table>
                </div>
                
                <div style='background: #fff3cd; padding: 25px; border-radius: 8px; margin: 25px 0; border-left: 4px solid #ffc107;'>
                    <h3 style='margin-top: 0; color: #856404;'>💻 Как подключиться к серверу</h3>
                    <h4 style='color: #856404; margin-bottom: 10px;'>Способ 1: Удаленный рабочий стол (RDP)</h4>
                    <ol style='margin: 0; padding-left: 20px;'>
                        <li style='margin-bottom: 8px;'>Нажмите <strong>Win + R</strong>, введите <code>mstsc</code> и нажмите Enter</li>
                        <li style='margin-bottom: 8px;'>Введите IP-адрес сервера: <strong>book.smetanaschool.ru</strong></li>
                        <li style='margin-bottom: 8px;'>Введите логин и пароль (указаны выше)</li>
                        <li style='margin-bottom: 8px;'>Нажмите <strong>Подключить</strong></li>
                    </ol>
                    
                    <h4 style='color: #856404; margin-bottom: 10px; margin-top: 20px;'>Способ 2: Через браузер</h4>
                    <ol style='margin: 0; padding-left: 20px;'>
                        <li style='margin-bottom: 8px;'>Откройте браузер</li>
                        <li style='margin-bottom: 8px;'>Перейдите по адресу: <strong>https://book.smetanaschool.ru</strong></li>
                        <li style='margin-bottom: 8px;'>Введите логин и пароль</li>
                    </ol>
                </div>
                
                <div style='background: #d1ecf1; padding: 20px; border-radius: 8px; margin: 25px 0; border-left: 4px solid #17a2b8;'>
                    <h4 style='margin-top: 0; color: #0c5460;'>⚠️ Важная информация</h4>
                    <ul style='margin: 0; padding-left: 20px;'>
                        <li>Сервер будет доступен только в указанное время бронирования</li>
                        <li>Сохраните данные для входа в безопасном месте</li>
                        <li>При возникновении проблем обращайтесь в поддержку</li>
                    </ul>
                </div>
                
                <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef;'>
                    <p style='margin: 0; color: #666;'>С уважением,<br><strong>Команда Book Smeta</strong></p>
                    <p style='margin: 10px 0 0 0; font-size: 12px; color: #999;'>
                        Если у вас возникли вопросы, обратитесь в службу поддержки
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Шаблон письма об отмене бронирования
     */
    private function buildCancellationEmailTemplate($booking) {
        $slotDate = date('d.m.Y', strtotime($booking['slot_date']));
        $startTime = date('H:i', strtotime($booking['start_time']));
        $endTime = date('H:i', strtotime($booking['end_time']));
        
        return "
        <!DOCTYPE html>
        <html lang='ru'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Отмена бронирования</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #dc3545;'>Отмена бронирования</h2>
                
                <p>Здравствуйте, {$booking['first_name']} {$booking['last_name']}!</p>
                
                <p>Ваше бронирование было отменено:</p>
                
                <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <h3 style='margin-top: 0; color: #dc3545;'>Детали отмененного бронирования</h3>
                    <p><strong>Дата:</strong> {$slotDate}</p>
                    <p><strong>Время:</strong> {$startTime} - {$endTime}</p>
                    <p><strong>Номер бронирования:</strong> #{$booking['id']}</p>
                </div>
                
                <p>Если у вас возникли вопросы, пожалуйста, свяжитесь с нами.</p>
                
                <p>С уважением,<br>Команда Book Smeta</p>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Шаблон письма о регистрации
     */
    private function buildRegistrationEmailTemplate($user, $password) {
        $registrationDate = date('d.m.Y', strtotime($user['created_at']));
        
        return "
        <!DOCTYPE html>
        <html lang='ru'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Добро пожаловать в Book Smeta!</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff;'>
                <div style='text-align: center; margin-bottom: 30px;'>
                    <h1 style='color: #2c5aa0; margin: 0;'>Book Smeta</h1>
                    <p style='color: #666; margin: 5px 0 0 0;'>Система бронирования серверов</p>
                </div>
                
                <h2 style='color: #2c5aa0; border-bottom: 2px solid #e9ecef; padding-bottom: 10px;'>Добро пожаловать!</h2>
                
                <p>Здравствуйте, <strong>{$user['first_name']} {$user['last_name']}</strong>!</p>
                
                <p>Ваш аккаунт в системе Book Smeta успешно создан. Теперь вы можете бронировать серверы для работы.</p>
                
                <div style='background: #e8f5e8; padding: 25px; border-radius: 8px; margin: 25px 0; border-left: 4px solid #28a745;'>
                    <h3 style='margin-top: 0; color: #28a745;'>🔑 Данные для входа</h3>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold; width: 120px;'>Email:</td>
                            <td style='padding: 8px 0; font-family: monospace; background: #f8f9fa; padding: 5px; border-radius: 3px;'>{$user['email']}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-weight: bold;'>Пароль:</td>
                            <td style='padding: 8px 0; font-family: monospace; background: #f8f9fa; padding: 5px; border-radius: 3px;'>{$password}</td>
                        </tr>
                    </table>
                </div>
                
                <div style='background: #f8f9fa; padding: 25px; border-radius: 8px; margin: 25px 0; border-left: 4px solid #2c5aa0;'>
                    <h3 style='margin-top: 0; color: #2c5aa0;'>🌐 Как войти в систему</h3>
                    <ol style='margin: 0; padding-left: 20px;'>
                        <li style='margin-bottom: 8px;'>Откройте браузер</li>
                        <li style='margin-bottom: 8px;'>Перейдите по адресу: <strong><a href='https://book.smetanaschool.ru' style='color: #2c5aa0;'>https://book.smetanaschool.ru</a></strong></li>
                        <li style='margin-bottom: 8px;'>Введите ваш email и пароль (указаны выше)</li>
                        <li style='margin-bottom: 8px;'>Нажмите <strong>Войти</strong></li>
                    </ol>
                </div>
                
                <div style='background: #fff3cd; padding: 25px; border-radius: 8px; margin: 25px 0; border-left: 4px solid #ffc107;'>
                    <h3 style='margin-top: 0; color: #856404;'>📋 Что можно делать в системе</h3>
                    <ul style='margin: 0; padding-left: 20px;'>
                        <li style='margin-bottom: 8px;'>Просматривать доступные слоты для бронирования</li>
                        <li style='margin-bottom: 8px;'>Бронировать серверы на удобное время</li>
                        <li style='margin-bottom: 8px;'>Просматривать ваши активные и завершенные бронирования</li>
                        <li style='margin-bottom: 8px;'>Получать данные для доступа к забронированным серверам</li>
                    </ul>
                </div>
                
                <div style='background: #d1ecf1; padding: 20px; border-radius: 8px; margin: 25px 0; border-left: 4px solid #17a2b8;'>
                    <h4 style='margin-top: 0; color: #0c5460;'>🔒 Безопасность</h4>
                    <ul style='margin: 0; padding-left: 20px;'>
                        <li>Сохраните пароль в безопасном месте</li>
                        <li>Не передавайте данные для входа третьим лицам</li>
                        <li>При подозрении на компрометацию аккаунта обратитесь в поддержку</li>
                    </ul>
                </div>
                
                <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e9ecef;'>
                    <p style='margin: 0; color: #666;'>С уважением,<br><strong>Команда Book Smeta</strong></p>
                    <p style='margin: 10px 0 0 0; font-size: 12px; color: #999;'>
                        Дата регистрации: {$registrationDate}<br>
                        Если у вас возникли вопросы, обратитесь в службу поддержки
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Шаблон тестового письма
     */
    private function buildTestEmailTemplate() {
        return "
        <!DOCTYPE html>
        <html lang='ru'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Тестовое письмо</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2c5aa0;'>Тестовое письмо</h2>
                
                <p>Это тестовое письмо от системы Book Smeta.</p>
                
                <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <p><strong>Время отправки:</strong> " . date('d.m.Y H:i:s') . "</p>
                    <p><strong>Статус:</strong> ✅ Email сервис работает корректно</p>
                </div>
                
                <p>Если вы получили это письмо, значит настройки SMTP работают правильно!</p>
                
                <p>С уважением,<br>Команда Book Smeta</p>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Получение статистики отправки email
     */
    public function getEmailStats($days = 30) {
        try {
            $stats = $this->db->fetchAll("
                SELECT 
                    DATE(created_at) as date,
                    status,
                    COUNT(*) as count
                FROM email_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at), status
                ORDER BY date DESC
            ", [$days]);
            
            return $stats;
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Проверка настроек SMTP
     */
    public function testSmtpConnection() {
        try {
            if (empty($this->config['smtp_host']) || empty($this->config['smtp_username']) || empty($this->config['smtp_password'])) {
                throw new Exception('SMTP настройки не заполнены');
            }
            
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            $mail->isSMTP();
            $mail->Host = $this->config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $this->config['smtp_username'];
            $mail->Password = $this->config['smtp_password'];
            $mail->SMTPSecure = $this->config['smtp_encryption'];
            $mail->Port = $this->config['smtp_port'];
            $mail->Timeout = 10;
            
            // Тестируем подключение
            if ($mail->smtpConnect()) {
                $mail->smtpClose();
                return true;
            } else {
                throw new Exception('Не удалось подключиться к SMTP серверу');
            }
            
        } catch (Exception $e) {
            throw new Exception('Ошибка подключения к SMTP: ' . $e->getMessage());
        }
    }
}
