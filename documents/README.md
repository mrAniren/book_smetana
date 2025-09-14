# Book Smeta - Система бронирования серверного времени

Система бронирования времени использования сервера для учеников с интеграцией GetCourse.ru для автоматической регистрации оплативших клиентов.

## Возможности

- ✅ **Автоматическая регистрация** оплативших клиентов через GetCourse API
- ✅ **Система бронирования** с календарем доступных слотов
- ✅ **Управление лимитами** бронирований для каждого пользователя
- ✅ **Автоматизация** создания слотов на неделю
- ✅ **Панель администратора** для управления системой
- ✅ **Безопасность** с CSRF защитой и валидацией данных
- ✅ **Мобильная адаптация** и современный UI

## Требования

- PHP 7.4+ (рекомендуется 8.0+)
- MySQL 5.7+ или MariaDB 10.2+
- Apache/Nginx с поддержкой .htaccess
- cURL extension
- JSON extension
- OpenSSL extension
- Composer

## Установка

### 1. Клонирование проекта

```bash
git clone <repository-url> book-smeta
cd book-smeta
```

### 2. Установка зависимостей

```bash
composer install
```

### 3. Настройка базы данных

1. Создайте базу данных MySQL:
```sql
CREATE DATABASE book_smeta CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Импортируйте схему базы данных:
```bash
mysql -u root -p book_smeta < database/schema.sql
```

### 4. Настройка конфигурации

1. Скопируйте файл конфигурации:
```bash
cp env.example .env
```

2. Отредактируйте файл `.env`:
```env
# Настройки базы данных
DB_HOST=localhost
DB_NAME=book_smeta
DB_USER=your_db_user
DB_PASSWORD=your_db_password

# GetCourse API настройки
GETCOURSE_ACCOUNT=your_account_name
GETCOURSE_SECRET_KEY=your_secret_key
GETCOURSE_API_URL=https://your_account.getcourse.ru/pl/api

# Настройки почты
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password

# Настройки приложения
APP_NAME="Book Smeta"
APP_URL=http://your-domain.com
APP_DEBUG=false
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

### 5. Настройка веб-сервера

#### Apache
Убедитесь, что включен модуль `mod_rewrite`:
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Nginx
Добавьте конфигурацию:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/book-smeta;
    index index.php;

    location / {
        try_files $uri $uri/ /public/index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(ht|env) {
        deny all;
    }
}
```

### 6. Настройка прав доступа

```bash
chmod 755 logs/
chmod 644 .env
chmod 644 composer.json
```

### 7. Настройка cron задач

Добавьте следующие задачи в crontab:
```bash
crontab -e
```

```cron
# Создание слотов на неделю (каждое воскресенье в 02:00)
0 2 * * 0 cd /path/to/book-smeta && php scripts/create_weekly_slots.php

# Очистка истекших бронирований (ежедневно в 01:00)
0 1 * * * cd /path/to/book-smeta && php scripts/cleanup_expired_bookings.php

# Синхронизация с GetCourse (каждый час)
0 * * * * cd /path/to/book-smeta && php scripts/sync_getcourse_users.php
```

## Настройка GetCourse

### 1. Получение API ключей

1. Войдите в панель управления GetCourse
2. Перейдите в раздел "Настройки" → "API"
3. Создайте новый API ключ
4. Скопируйте секретный ключ в файл `.env`

### 2. Настройка webhook

1. В панели GetCourse настройте webhook:
   - URL: `https://your-domain.com/api/getcourse_webhook.php`
   - События: `user_payment`, `deal_created`
   - Метод: POST

2. Установите секретный ключ webhook в `.env`:
```env
GETCOURSE_WEBHOOK_SECRET=your_webhook_secret
```

## Использование

### Первый запуск

1. Откройте браузер и перейдите на ваш домен
2. Войдите как суперадминистратор:
   - Email: `admin@book-smeta.com`
   - Пароль: `password` (смените после первого входа!)

### Управление пользователями

1. Перейдите в панель администратора
2. В разделе "Пользователи" можете:
   - Изменять лимиты бронирований
   - Активировать/деактивировать пользователей
   - Просматривать статистику

### Создание слотов

Слоты создаются автоматически каждое воскресенье. Для ручного создания:

```bash
php scripts/create_weekly_slots.php
```

### Мониторинг

Проверяйте логи в директории `logs/`:
- `error.log` - ошибки приложения
- `info.log` - информационные сообщения

## API Документация

### Аутентификация

```bash
POST /api/auth
{
    "action": "login",
    "email": "user@example.com",
    "password": "password"
}
```

### Бронирование

```bash
# Получение доступных слотов
GET /api/bookings?action=available&start_date=2024-01-01&end_date=2024-01-07

# Создание бронирования
POST /api/bookings
{
    "action": "create",
    "slot_id": 123,
    "notes": "Дополнительная информация"
}

# Отмена бронирования
DELETE /api/bookings/123
```

### Административные функции

```bash
# Получение статистики
GET /api/admin/statistics?start_date=2024-01-01&end_date=2024-01-31

# Изменение лимита пользователя
PUT /api/admin/users/123/limit
{
    "limit": 5
}
```

## Структура проекта

```
Book_smeta/
├── api/                    # REST API endpoints
├── admin/                  # Панель администратора
├── public/                 # Публичные страницы
├── includes/               # Основные классы
├── config/                 # Конфигурационные файлы
├── scripts/                # Скрипты автоматизации
├── assets/                 # CSS, JS, изображения
├── logs/                   # Логи приложения
├── database/               # SQL схемы
└── vendor/                 # Composer зависимости
```

## Безопасность

- Все пароли хешируются с использованием `password_hash()`
- CSRF токены для всех форм
- Валидация всех входных данных
- Защита от SQL-инъекций через prepared statements
- Ограничение доступа к системным файлам через .htaccess

## Обновление

1. Создайте резервную копию базы данных
2. Обновите код:
```bash
git pull origin main
composer install
```

3. Примените миграции базы данных (если есть)
4. Очистите кэш (если используется)

## Поддержка

При возникновении проблем:

1. Проверьте логи в директории `logs/`
2. Убедитесь в корректности настроек в `.env`
3. Проверьте права доступа к файлам
4. Убедитесь в работоспособности cron задач

## Лицензия

MIT License

## Авторы

- Разработчик: [Ваше имя]
- Email: [your-email@example.com]

---

*Документация обновлена: <?= date('Y-m-d') ?>*
