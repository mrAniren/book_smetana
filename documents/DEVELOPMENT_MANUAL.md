# Мануал разработки системы бронирования серверного времени

## Обзор проекта

Система бронирования времени использования сервера для учеников с интеграцией GetCourse.ru для автоматической регистрации оплативших клиентов.

## Архитектура системы

### Основные компоненты:
1. **Frontend** - веб-интерфейс для бронирования
2. **Backend API** - PHP API для обработки запросов
3. **База данных MySQL** - хранение данных пользователей, слотов, бронирований
4. **GetCourse интеграция** - автоматическая регистрация оплативших клиентов
5. **Панель администратора** - управление пользователями и слоты
6. **Автоматизация сервера** - еженедельное создание слотов

## Структура базы данных

### Таблица `users`
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- email (VARCHAR(255), UNIQUE)
- password_hash (VARCHAR(255))
- first_name (VARCHAR(100))
- last_name (VARCHAR(100))
- phone (VARCHAR(20))
- role (ENUM: 'user', 'admin', 'super_admin')
- booking_limit (INT, DEFAULT 3)
- booking_count (INT, DEFAULT 0)
- getcourse_user_id (INT, NULLABLE)
- is_paid_client (BOOLEAN, DEFAULT FALSE)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
- last_login (TIMESTAMP, NULLABLE)
```

### Таблица `server_slots`
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- slot_date (DATE)
- start_time (TIME)
- end_time (TIME)
- server_login (VARCHAR(100))
- server_password (VARCHAR(255))
- is_available (BOOLEAN, DEFAULT TRUE)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

### Таблица `bookings`
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- user_id (INT, FOREIGN KEY -> users.id)
- slot_id (INT, FOREIGN KEY -> server_slots.id)
- booking_status (ENUM: 'active', 'cancelled', 'completed')
- booking_notes (TEXT, NULLABLE)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
- expires_at (TIMESTAMP)
```

### Таблица `getcourse_logs`
```sql
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- action (VARCHAR(50))
- getcourse_user_id (INT)
- email (VARCHAR(255))
- request_data (JSON)
- response_data (JSON)
- status (ENUM: 'success', 'error')
- created_at (TIMESTAMP)
```

## Технические требования

### Серверные требования:
- PHP 7.4+ (рекомендуется 8.0+)
- MySQL 5.7+ или MariaDB 10.2+
- Apache/Nginx с поддержкой .htaccess
- cURL extension
- JSON extension
- OpenSSL extension

### Зависимости:
- GetCourse PHP SDK
- Composer для управления зависимостями
- PHPMailer для отправки email
- Password hashing (встроенный в PHP 7.0+)

## Структура файлов проекта

```
Book_smeta/
├── config/
│   ├── database.php
│   ├── getcourse.php
│   └── mail.php
├── includes/
│   ├── auth.php
│   ├── database.php
│   ├── getcourse_api.php
│   ├── mail_sender.php
│   └── functions.php
├── api/
│   ├── auth.php
│   ├── bookings.php
│   ├── slots.php
│   ├── users.php
│   └── getcourse_webhook.php
├── admin/
│   ├── index.php
│   ├── users.php
│   ├── slots.php
│   ├── bookings.php
│   └── settings.php
├── public/
│   ├── index.php
│   ├── login.php
│   ├── register.php
│   ├── calendar.php
│   └── booking.php
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── scripts/
│   ├── create_weekly_slots.php
│   ├── cleanup_expired_bookings.php
│   └── sync_getcourse_users.php
├── vendor/
├── logs/
├── composer.json
├── .env.example
├── .htaccess
└── README.md
```

## Основные функции системы

### 1. Аутентификация и авторизация
- Регистрация только для оплативших клиентов GetCourse
- Автоматическая генерация паролей
- Отправка паролей на email
- Роли пользователей (user, admin, super_admin)

### 2. Система бронирования
- Календарь с доступными слотами
- Ограничение на количество бронирований (по умолчанию 3)
- Автоматическая отмена неактивных бронирований
- Уведомления о бронировании

### 3. Управление слотами
- Автоматическое создание слотов на неделю
- Ручное управление слотами администратором
- Генерация логинов/паролей для сервера

### 4. Интеграция GetCourse
- Webhook для получения уведомлений об оплате
- Автоматическая регистрация новых пользователей
- Синхронизация статуса оплаты

### 5. Панель администратора
- Управление пользователями
- Настройка лимитов бронирований
- Просмотр статистики
- Управление слотами

## Безопасность

### Меры безопасности:
- Хеширование паролей (password_hash)
- CSRF токены для форм
- Валидация всех входных данных
- Ограничение доступа к админ-панели
- Логирование всех действий
- Защита от SQL-инъекций (prepared statements)

## API Endpoints

### Публичные endpoints:
- `POST /api/auth/login` - авторизация
- `GET /api/slots/available` - получение доступных слотов
- `POST /api/bookings/create` - создание бронирования
- `POST /api/bookings/cancel/{id}` - отмена бронирования

### Административные endpoints:
- `GET /api/admin/users` - список пользователей
- `PUT /api/admin/users/{id}/limit` - изменение лимита бронирований
- `POST /api/admin/slots/create` - создание новых слотов
- `GET /api/admin/statistics` - статистика системы

## Автоматизация

### Cron задачи:
1. **Еженедельное создание слотов** (воскресенье):
   - Создание слотов на всю неделю
   - Генерация логинов/паролей для сервера
   - Отправка данных на сервер

2. **Очистка истекших бронирований** (ежедневно):
   - Отмена неактивных бронирований
   - Освобождение слотов

3. **Синхронизация с GetCourse** (ежечасно):
   - Проверка новых оплат
   - Обновление статусов пользователей

## Развертывание

### Настройка хостинга:
1. Загрузка файлов на хостинг
2. Настройка базы данных MySQL
3. Настройка .env файла с конфигурацией
4. Установка зависимостей через Composer
5. Настройка cron задач
6. Настройка SSL сертификата

### Конфигурационные файлы:
- `.env` - основные настройки (база данных, API ключи)
- `config/database.php` - настройки подключения к БД
- `config/getcourse.php` - настройки GetCourse API
- `config/mail.php` - настройки почтового сервера

## Мониторинг и логирование

### Логи системы:
- Логи аутентификации
- Логи API запросов
- Логи интеграции с GetCourse
- Логи автоматизации
- Логи ошибок

### Мониторинг:
- Отслеживание доступности слотов
- Мониторинг успешности интеграции с GetCourse
- Статистика использования системы

## Тестирование

### Типы тестов:
1. **Unit тесты** - тестирование отдельных функций
2. **Integration тесты** - тестирование интеграции с GetCourse
3. **API тесты** - тестирование REST API
4. **UI тесты** - тестирование пользовательского интерфейса

## Документация

### Документы для разработки:
- API документация
- Руководство администратора
- Руководство пользователя
- Техническая документация интеграции с GetCourse

## Следующие шаги разработки

1. ✅ Создание мануала разработки
2. 🔄 Настройка структуры проекта
3. ⏳ Создание базы данных
4. ⏳ Реализация базовой аутентификации
5. ⏳ Интеграция с GetCourse API
6. ⏳ Создание системы бронирования
7. ⏳ Разработка панели администратора
8. ⏳ Настройка автоматизации
9. ⏳ Тестирование и отладка
10. ⏳ Развертывание на хостинге

---

*Этот мануал будет обновляться по мере разработки проекта.*
