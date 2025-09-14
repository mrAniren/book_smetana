<?php

class GetCourseConfig {
    private static $instance = null;
    private $config = [];
    
    private function __construct() {
        $this->loadEnvironment();
        $this->loadConfig();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadEnvironment() {
        // Сначала загружаем из .env файла
        $envFile = __DIR__ . '/../.env';
        if (!file_exists($envFile)) {
            $envFile = __DIR__ . '/../env.example';
        }
        
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                
                if (strpos($line, '=') !== false) {
                    list($name, $value) = explode('=', $line, 2);
                    $_ENV[trim($name)] = trim($value);
                }
            }
        }
        
        // Затем загружаем из базы данных (перезаписывает .env)
        try {
            require_once __DIR__ . '/../includes/Database.php';
            $db = Database::getInstance();
            $settings = $db->fetchAll('SELECT setting_key, setting_value FROM system_settings WHERE setting_key LIKE "getcourse_%"');
            
            foreach ($settings as $setting) {
                $key = strtoupper($setting['setting_key']);
                $_ENV[$key] = $setting['setting_value'];
            }
        } catch (Exception $e) {
            // Игнорируем ошибки базы данных при инициализации
        }
    }
    
    private function loadConfig() {
        $this->config = [
            'account' => $_ENV['GETCOURSE_ACCOUNT'] ?? $_ENV['GETCOURSE_API_URL'] ?? '',
            'secret_key' => $_ENV['GETCOURSE_SECRET_KEY'] ?? $_ENV['GETCOURSE_API_KEY'] ?? '',
            'api_url' => $_ENV['GETCOURSE_API_URL'] ?? '',
            'timeout' => 30,
            'retry_attempts' => 3,
            'webhook_secret' => $_ENV['GETCOURSE_WEBHOOK_SECRET'] ?? $_ENV['GETCOURSE_API_SECRET'] ?? '',
        ];
        
        // Извлекаем название аккаунта из API URL если не указано отдельно
        if (empty($this->config['account']) && !empty($this->config['api_url'])) {
            if (preg_match('/https?:\/\/([^.]+)\.getcourse\.ru/', $this->config['api_url'], $matches)) {
                $this->config['account'] = $matches[1];
            }
        }
    }
    
    public function get($key, $default = null) {
        return $this->config[$key] ?? $default;
    }
    
    public function getAccountName() {
        return $this->get('account');
    }
    
    public function getSecretKey() {
        return $this->get('secret_key');
    }
    
    public function getApiUrl() {
        return $this->get('api_url');
    }
    
    public function getWebhookSecret() {
        return $this->get('webhook_secret');
    }
    
    public function getUsersApiUrl() {
        $apiUrl = $this->get('api_url');
        return $apiUrl . '/users';
    }
    
    public function getDealsApiUrl() {
        $apiUrl = $this->get('api_url');
        return $apiUrl . '/deals';
    }
    
    public function getMessagesApiUrl() {
        $apiUrl = $this->get('api_url');
        return $apiUrl . '/messages';
    }
    
    public function validateConfig() {
        $required = ['account', 'secret_key', 'api_url'];
        $missing = [];
        
        foreach ($required as $key) {
            if (empty($this->get($key))) {
                $missing[] = $key;
            }
        }
        
        if (!empty($missing)) {
            throw new Exception("Отсутствуют обязательные настройки GetCourse: " . implode(', ', $missing));
        }
        
        return true;
    }
}
