# 📡 Руководство по отправке данных с сервера

## 🗄️ Структура базы данных

### Основные таблицы:

#### 1. **users** - Пользователи
```sql
- id (INT, PRIMARY KEY)
- email (VARCHAR(255), UNIQUE)
- password_hash (VARCHAR(255))
- first_name (VARCHAR(100))
- last_name (VARCHAR(100))
- phone (VARCHAR(20))
- role (ENUM: 'user', 'admin', 'super_admin')
- booking_limit (INT, DEFAULT 3)
- booking_count (INT, DEFAULT 0)
- getcourse_user_id (INT, NULL)
- is_paid_client (BOOLEAN, DEFAULT FALSE)
- email_verified (BOOLEAN, DEFAULT FALSE)
- is_active (BOOLEAN, DEFAULT TRUE)
- created_at, updated_at, last_login (TIMESTAMP)
```

#### 2. **server_slots** - Серверные слоты
```sql
- id (INT, PRIMARY KEY)
- slot_date (DATE)
- start_time (TIME)
- end_time (TIME)
- server_login (VARCHAR(100))
- server_password (VARCHAR(255))
- is_available (BOOLEAN, DEFAULT TRUE)
- max_users (INT, DEFAULT 1)
- current_users (INT, DEFAULT 0)
- created_at, updated_at (TIMESTAMP)
```

#### 3. **bookings** - Бронирования
```sql
- id (INT, PRIMARY KEY)
- user_id (INT, FOREIGN KEY → users.id)
- slot_id (INT, FOREIGN KEY → server_slots.id)
- booking_status (ENUM: 'active', 'cancelled', 'completed', 'expired')
- booking_notes (TEXT)
- server_access_granted (BOOLEAN, DEFAULT FALSE)
- access_granted_at (TIMESTAMP, NULL)
- created_at, updated_at (TIMESTAMP)
- expires_at (TIMESTAMP, NULL)
```

## 🔌 API Endpoints

### 1. **GET /api/bookings.php?action=available**
**Назначение:** Получить доступные слоты для бронирования

**Ответ:**
```json
{
  "success": true,
  "slots": [
    {
      "id": 1,
      "slot_date": "2025-09-15",
      "start_time": "10:00:00",
      "end_time": "11:00:00",
      "server_login": "user123",
      "server_password": "pass123",
      "is_available": true,
      "max_users": 1,
      "booking_count": 0,
      "available_spots": 1
    }
  ]
}
```

### 2. **GET /api/bookings.php?action=my**
**Назначение:** Получить бронирования текущего пользователя

**Ответ:**
```json
{
  "success": true,
  "bookings": [
    {
      "id": 1,
      "user_id": 2,
      "slot_id": 1,
      "booking_status": "active",
      "booking_notes": "Автоматическое бронирование",
      "slot_date": "2025-09-15",
      "start_time": "10:00:00",
      "end_time": "11:00:00",
      "server_login": "user123",
      "server_password": "pass123",
      "created_at": "2025-09-14 15:30:00",
      "expires_at": "2025-09-15 11:00:00"
    }
  ]
}
```

### 3. **GET /api/bookings.php?action=stats**
**Назначение:** Получить статистику бронирований пользователя

**Ответ:**
```json
{
  "success": true,
  "stats": {
    "total": 5,
    "active": 2,
    "completed": 2,
    "cancelled": 1,
    "remaining": 1
  }
}
```

### 4. **POST /api/bookings.php**
**Назначение:** Создать новое бронирование

**Запрос:**
```json
{
  "action": "create",
  "slot_id": 1,
  "notes": "Комментарий к бронированию"
}
```

**Ответ:**
```json
{
  "success": true,
  "booking_id": 123,
  "message": "Бронирование создано успешно"
}
```

### 5. **GET /api/auth.php**
**Назначение:** Получить информацию о текущем пользователе

**Ответ:**
```json
{
  "success": true,
  "user": {
    "id": 2,
    "email": "user@example.com",
    "first_name": "Иван",
    "last_name": "Петров",
    "role": "user",
    "booking_limit": 3,
    "booking_count": 2,
    "remaining": 1
  }
}
```

## 🔧 Настройка сервера

### 1. **Файл .env**
Создайте файл `.env` в корне проекта:

```env
# Настройки базы данных
DB_HOST=localhost
DB_NAME=book_smeta
DB_USER=root
DB_PASSWORD=your_password
DB_CHARSET=utf8mb4

# GetCourse API настройки
GETCOURSE_ACCOUNT=your_account_name
GETCOURSE_SECRET_KEY=your_secret_key
GETCOURSE_API_URL=https://your_account.getcourse.ru/pl/api

# Настройки почты
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_FROM_NAME="Book Smeta System"
MAIL_FROM_EMAIL=noreply@book-smeta.com

# Настройки приложения
APP_NAME="Book Smeta"
APP_URL=http://localhost/Book_smeta
APP_DEBUG=true
APP_TIMEZONE=Europe/Moscow

# Безопасность
JWT_SECRET=your_jwt_secret_key_here
ENCRYPTION_KEY=your_encryption_key_here

# Настройки сервера для слотов
SERVER_API_URL=http://your-server.com/api
SERVER_API_KEY=your_server_api_key

# Настройки бронирования
DEFAULT_BOOKING_LIMIT=3
BOOKING_EXPIRE_HOURS=24
SLOT_DURATION_MINUTES=60
```

### 2. **Создание базы данных**
```bash
# Подключитесь к MySQL
mysql -u root -p

# Выполните SQL скрипт
source /path/to/database/schema.sql
```

### 3. **Настройка Apache (.htaccess)**
```apache
RewriteEngine On

# Перенаправление на public
RewriteCond %{REQUEST_URI} !^/public/
RewriteRule ^(.*)$ /public/$1 [L]

# Безопасность
<Files ".env">
    Order allow,deny
    Deny from all
</Files>

# Кэширование статических файлов
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
</IfModule>
```

## 📊 Типы данных для отправки

### 1. **Данные пользователя**
```php
$userData = [
    'id' => 2,
    'email' => 'user@example.com',
    'first_name' => 'Иван',
    'last_name' => 'Петров',
    'phone' => '+7 (999) 123-45-67',
    'role' => 'user',
    'booking_limit' => 3,
    'booking_count' => 2,
    'is_paid_client' => true,
    'email_verified' => true,
    'is_active' => true,
    'created_at' => '2025-09-01 10:00:00',
    'last_login' => '2025-09-14 15:30:00'
];
```

### 2. **Данные слота**
```php
$slotData = [
    'id' => 1,
    'slot_date' => '2025-09-15',
    'start_time' => '10:00:00',
    'end_time' => '11:00:00',
    'server_login' => 'user123',
    'server_password' => 'pass123',
    'is_available' => true,
    'max_users' => 1,
    'current_users' => 0,
    'created_at' => '2025-09-14 12:00:00'
];
```

### 3. **Данные бронирования**
```php
$bookingData = [
    'id' => 1,
    'user_id' => 2,
    'slot_id' => 1,
    'booking_status' => 'active',
    'booking_notes' => 'Автоматическое бронирование',
    'server_access_granted' => false,
    'access_granted_at' => null,
    'created_at' => '2025-09-14 15:30:00',
    'expires_at' => '2025-09-15 11:00:00'
];
```

## 🔒 Безопасность

### 1. **Защита от SQL-инъекций**
```php
// ✅ Правильно - используем подготовленные запросы
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);

// ❌ Неправильно - прямая подстановка
$query = "SELECT * FROM users WHERE id = $userId";
```

### 2. **Валидация данных**
```php
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    return preg_match('/^\+?[1-9]\d{1,14}$/', $phone);
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
```

### 3. **CSRF защита**
```php
// Генерация токена
$csrfToken = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;

// Проверка токена
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    throw new Exception('CSRF токен недействителен');
}
```

## 📧 Email уведомления

### 1. **Настройка SMTP**
```php
$mailConfig = [
    'host' => $_ENV['MAIL_HOST'],
    'port' => $_ENV['MAIL_PORT'],
    'username' => $_ENV['MAIL_USERNAME'],
    'password' => $_ENV['MAIL_PASSWORD'],
    'from_name' => $_ENV['MAIL_FROM_NAME'],
    'from_email' => $_ENV['MAIL_FROM_EMAIL']
];
```

### 2. **Шаблоны писем**
```php
$emailTemplates = [
    'booking_confirmation' => [
        'subject' => 'Подтверждение бронирования',
        'body' => 'Ваше бронирование на {date} в {time} подтверждено.'
    ],
    'booking_reminder' => [
        'subject' => 'Напоминание о бронировании',
        'body' => 'Через час у вас запланировано бронирование.'
    ],
    'access_granted' => [
        'subject' => 'Доступ к серверу предоставлен',
        'body' => 'Ваш доступ к серверу активирован. Логин: {login}, Пароль: {password}'
    ]
];
```

## 🚀 Автоматизация

### 1. **Cron задачи**
```bash
# Создание слотов каждое воскресенье в 00:00
0 0 * * 0 /usr/bin/php /path/to/scripts/create_weekly_slots.php

# Очистка истекших бронирований каждый час
0 * * * * /usr/bin/php /path/to/scripts/cleanup_expired_bookings.php

# Синхронизация с GetCourse каждый день в 02:00
0 2 * * * /usr/bin/php /path/to/scripts/sync_getcourse_users.php
```

### 2. **Логирование**
```php
function logActivity($action, $userId, $details = []) {
    $logData = [
        'action' => $action,
        'user_id' => $userId,
        'details' => json_encode($details),
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Сохранение в базу данных или файл
    error_log(json_encode($logData));
}
```

## 📱 Мобильная адаптация

### 1. **Responsive дизайн**
```css
@media (max-width: 768px) {
    .slot-card {
        width: 100%;
        margin-bottom: 1rem;
    }
    
    .navbar-nav {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
```

### 2. **PWA поддержка**
```json
{
  "name": "Book Smeta",
  "short_name": "BS",
  "description": "Система бронирования серверов",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#2563eb",
  "icons": [
    {
      "src": "icon-192.png",
      "sizes": "192x192",
      "type": "image/png"
    }
  ]
}
```

## 🔧 Отладка

### 1. **Логи ошибок**
```php
// Включение отображения ошибок для разработки
if ($_ENV['APP_DEBUG'] === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Логирование в файл
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');
```

### 2. **Мониторинг производительности**
```php
function measureExecutionTime($callback) {
    $start = microtime(true);
    $result = $callback();
    $end = microtime(true);
    
    error_log("Execution time: " . ($end - $start) . " seconds");
    return $result;
}
```

---

## 📞 Поддержка

Если у вас возникли вопросы по настройке или использованию системы:

1. **Проверьте логи ошибок** в `/logs/error.log`
2. **Убедитесь в правильности настроек** в файле `.env`
3. **Проверьте подключение к базе данных**
4. **Убедитесь в корректности прав доступа** к файлам

**Удачной настройки! 🚀**
