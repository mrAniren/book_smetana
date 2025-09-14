# 🎓 Руководство по настройке GetCourse API

## 📋 Что вам нужно для работы с GetCourse API

### 1. **Основные данные:**
- ✅ **API ключ** (у вас уже есть)
- 🔍 **Название аккаунта** (например: `yourcompany`)
- 🔗 **URL аккаунта** (например: `https://yourcompany.getcourse.ru`)

### 2. **Дополнительные настройки:**
- 🔔 **Webhook секрет** (для получения уведомлений)
- 📧 **Email настройки** (для отправки уведомлений)
- 🔐 **Права доступа** к API

## 🔧 Пошаговая настройка

### Шаг 1: Получение данных из GetCourse

1. **Войдите в ваш аккаунт GetCourse**
2. **Перейдите в Настройки → API**
3. **Найдите раздел "API ключи"**
4. **Скопируйте:**
   - API ключ
   - Название аккаунта (из URL)
   - URL API (обычно `https://ваш_аккаунт.getcourse.ru/pl/api`)

### Шаг 2: Настройка файла .env

Отредактируйте ваш файл `.env`:

```env
# GetCourse API настройки
GETCOURSE_ACCOUNT=your_company_name
GETCOURSE_SECRET_KEY=your_api_key_here
GETCOURSE_API_URL=https://your_company_name.getcourse.ru/pl/api
GETCOURSE_WEBHOOK_SECRET=your_webhook_secret_here
```

### Шаг 3: Проверка подключения

Создайте тестовый скрипт для проверки:

```bash
# Создайте файл test_getcourse.php
touch test_getcourse.php
```

```php
<?php
require_once 'config/getcourse.php';
require_once 'includes/GetCourseAPI.php';

try {
    $getcourse = new GetCourseAPI();
    
    // Тестируем получение пользователей
    $users = $getcourse->getUsers(['limit' => 5]);
    
    echo "✅ Подключение к GetCourse успешно!\n";
    echo "Найдено пользователей: " . count($users) . "\n";
    
    // Выводим первого пользователя для проверки
    if (!empty($users)) {
        echo "Пример пользователя:\n";
        print_r($users[0]);
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка подключения: " . $e->getMessage() . "\n";
}
?>
```

Запустите тест:
```bash
php test_getcourse.php
```

## 🔌 API методы GetCourse

### Основные методы, которые поддерживает система:

#### 1. **Работа с пользователями:**
```php
// Получение списка пользователей
$users = $getcourse->getUsers(['limit' => 100]);

// Добавление пользователя
$result = $getcourse->addUser([
    'user' => [
        'email' => 'user@example.com',
        'first_name' => 'Иван',
        'last_name' => 'Петров',
        'phone' => '+7 (999) 123-45-67'
    ]
]);

// Проверка статуса пользователя
$status = $getcourse->checkUserStatus('user@example.com');
```

#### 2. **Работа со сделками:**
```php
// Создание сделки
$deal = $getcourse->addDeal([
    'user' => [
        'email' => 'user@example.com'
    ],
    'deal' => [
        'product_title' => 'Доступ к системе бронирования',
        'deal_cost' => 1000,
        'currency' => 'RUB'
    ]
]);
```

#### 3. **Отправка сообщений:**
```php
// Отправка email
$message = $getcourse->sendMessage([
    'message' => [
        'email' => 'user@example.com',
        'subject' => 'Добро пожаловать!',
        'text' => 'Ваш аккаунт создан успешно.'
    ]
]);
```

## 🔔 Настройка Webhooks

### Шаг 1: Создание webhook endpoint

Создайте файл `api/getcourse_webhook.php`:

```php
<?php
require_once __DIR__ . '/../includes/GetCourseAPI.php';

header('Content-Type: application/json');

try {
    // Получаем данные из POST запроса
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Неверный формат данных');
    }
    
    // Обрабатываем webhook
    $getcourse = new GetCourseAPI();
    $result = $getcourse->handleWebhook($data);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
```

### Шаг 2: Настройка в GetCourse

1. **Войдите в GetCourse**
2. **Перейдите в Настройки → API → Webhooks**
3. **Добавьте новый webhook:**
   - **URL:** `https://ваш_домен.com/api/getcourse_webhook.php`
   - **События:** `user_payment`, `deal_created`
   - **Секрет:** сгенерируйте случайную строку

### Шаг 3: Обновление .env

```env
GETCOURSE_WEBHOOK_SECRET=your_generated_secret_here
```

## 📧 Настройка email уведомлений

### Шаг 1: Настройка SMTP

```env
# Настройки почты
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_FROM_NAME="Book Smeta System"
MAIL_FROM_EMAIL=noreply@book-smeta.com
```

### Шаг 2: Создание email шаблонов

Создайте директорию для шаблонов:
```bash
mkdir -p templates/emails
```

Создайте шаблон приветственного письма:
```html
<!-- templates/emails/welcome.html -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Добро пожаловать в Book Smeta!</title>
</head>
<body>
    <h1>Добро пожаловать, {{first_name}}!</h1>
    <p>Ваш аккаунт в системе бронирования создан успешно.</p>
    <p><strong>Данные для входа:</strong></p>
    <ul>
        <li>Email: {{email}}</li>
        <li>Пароль: {{password}}</li>
    </ul>
    <p><a href="{{login_url}}">Войти в систему</a></p>
</body>
</html>
```

## 🔄 Автоматическая синхронизация

### Настройка cron задачи

Добавьте в crontab:
```bash
# Синхронизация пользователей каждый день в 02:00
0 2 * * * /usr/bin/php /path/to/scripts/sync_getcourse_users.php
```

### Создание скрипта синхронизации

```php
<?php
// scripts/sync_getcourse_users.php
require_once __DIR__ . '/../includes/GetCourseAPI.php';

try {
    $getcourse = new GetCourseAPI();
    
    // Получаем пользователей из GetCourse
    $users = $getcourse->getUsers(['limit' => 1000]);
    
    foreach ($users as $user) {
        // Проверяем, есть ли пользователь в нашей системе
        $existingUser = $db->fetchOne(
            "SELECT id FROM users WHERE email = ? OR getcourse_user_id = ?",
            [$user['email'], $user['id']]
        );
        
        if (!$existingUser) {
            // Создаем нового пользователя
            $getcourse->createUserFromGetCourse($user['email'], $user);
        }
    }
    
    echo "Синхронизация завершена успешно!\n";
    
} catch (Exception $e) {
    echo "Ошибка синхронизации: " . $e->getMessage() . "\n";
}
?>
```

## 🧪 Тестирование интеграции

### Создание тестового скрипта

```php
<?php
// test_getcourse_integration.php
require_once 'includes/GetCourseAPI.php';

class GetCourseTester {
    private $getcourse;
    
    public function __construct() {
        $this->getcourse = new GetCourseAPI();
    }
    
    public function testConnection() {
        echo "🔍 Тестирование подключения...\n";
        
        try {
            $users = $this->getcourse->getUsers(['limit' => 1]);
            echo "✅ Подключение успешно!\n";
            return true;
        } catch (Exception $e) {
            echo "❌ Ошибка подключения: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    public function testAddUser() {
        echo "👤 Тестирование добавления пользователя...\n";
        
        try {
            $testEmail = 'test_' . time() . '@example.com';
            $result = $this->getcourse->addUser([
                'user' => [
                    'email' => $testEmail,
                    'first_name' => 'Тест',
                    'last_name' => 'Пользователь'
                ]
            ]);
            
            echo "✅ Пользователь добавлен успешно!\n";
            return true;
        } catch (Exception $e) {
            echo "❌ Ошибка добавления пользователя: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    public function testWebhook() {
        echo "🔔 Тестирование webhook...\n";
        
        $testData = [
            'action' => 'user_payment',
            'user' => [
                'email' => 'test@example.com',
                'first_name' => 'Тест',
                'last_name' => 'Пользователь'
            ]
        ];
        
        try {
            $result = $this->getcourse->handleWebhook($testData);
            echo "✅ Webhook обработан успешно!\n";
            return true;
        } catch (Exception $e) {
            echo "❌ Ошибка обработки webhook: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    public function runAllTests() {
        echo "🚀 Запуск всех тестов GetCourse API...\n\n";
        
        $tests = [
            'testConnection' => $this->testConnection(),
            'testAddUser' => $this->testAddUser(),
            'testWebhook' => $this->testWebhook()
        ];
        
        echo "\n📊 Результаты тестирования:\n";
        foreach ($tests as $testName => $result) {
            echo "- $testName: " . ($result ? "✅ PASS" : "❌ FAIL") . "\n";
        }
        
        $passed = array_sum($tests);
        $total = count($tests);
        
        echo "\n🎯 Итого: $passed/$total тестов пройдено\n";
        
        if ($passed === $total) {
            echo "🎉 Все тесты пройдены успешно!\n";
        } else {
            echo "⚠️ Некоторые тесты не пройдены. Проверьте настройки.\n";
        }
    }
}

// Запуск тестов
$tester = new GetCourseTester();
$tester->runAllTests();
?>
```

## 🔧 Отладка проблем

### Частые проблемы и решения:

#### 1. **Ошибка "Invalid API key"**
```bash
# Проверьте правильность API ключа в .env
cat .env | grep GETCOURSE_SECRET_KEY

# Убедитесь, что ключ скопирован полностью
```

#### 2. **Ошибка "Account not found"**
```bash
# Проверьте название аккаунта
# URL должен быть: https://ВАШ_АККАУНТ.getcourse.ru
```

#### 3. **Ошибка "Permission denied"**
```bash
# Убедитесь, что у API ключа есть необходимые права:
# - Чтение пользователей
# - Добавление пользователей
# - Создание сделок
```

#### 4. **Webhook не работает**
```bash
# Проверьте доступность URL
curl -X POST https://ваш_домен.com/api/getcourse_webhook.php

# Проверьте логи Apache
tail -f /var/log/apache2/error.log
```

### Логирование для отладки

```php
// Включите подробное логирование
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/getcourse_debug.log');

// Логируйте все запросы
error_log("GetCourse API Request: " . json_encode($requestData));
error_log("GetCourse API Response: " . json_encode($responseData));
```

## 📚 Дополнительные ресурсы

### Полезные ссылки:
- [Документация GetCourse API](https://help.getcourse.ru/ru/articles/5212346-api-getcourse)
- [Примеры запросов](https://help.getcourse.ru/ru/articles/5212347-primery-zaprosov-k-api)
- [Настройка webhooks](https://help.getcourse.ru/ru/articles/5212348-nastroyka-webhooks)

### Поддержка:
- Email: api-support@getcourse.ru
- Телефон: +7 (800) 555-35-35

---

## 🎯 Следующие шаги

После настройки GetCourse API:

1. ✅ **Протестируйте подключение**
2. ✅ **Настройте webhooks**
3. ✅ **Запустите синхронизацию пользователей**
4. ✅ **Настройте email уведомления**
5. ✅ **Протестируйте полный цикл**

**Удачной интеграции! 🚀**
