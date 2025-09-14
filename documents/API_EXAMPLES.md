# 📡 Примеры API запросов Book Smeta

## 🔐 Аутентификация

### Вход в систему
```bash
curl -X POST http://localhost/Book_smeta/api/auth.php \
  -H "Content-Type: application/json" \
  -d '{
    "action": "login",
    "email": "user@example.com",
    "password": "password123"
  }'
```

**Ответ:**
```json
{
  "success": true,
  "message": "Вход выполнен успешно",
  "user": {
    "id": 2,
    "email": "user@example.com",
    "first_name": "Иван",
    "last_name": "Петров",
    "role": "user",
    "booking_limit": 3,
    "booking_count": 2
  }
}
```

### Получение информации о пользователе
```bash
curl -X GET http://localhost/Book_smeta/api/auth.php \
  -H "Cookie: PHPSESSID=your_session_id"
```

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

### Выход из системы
```bash
curl -X POST http://localhost/Book_smeta/api/auth.php \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d '{"action": "logout"}'
```

## 📅 Управление слотами

### Получение доступных слотов
```bash
curl -X GET "http://localhost/Book_smeta/api/bookings.php?action=available" \
  -H "Cookie: PHPSESSID=your_session_id"
```

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
      "server_login": "user_20250915_10",
      "server_password": "pass123",
      "is_available": true,
      "max_users": 1,
      "booking_count": 0,
      "available_spots": 1
    },
    {
      "id": 2,
      "slot_date": "2025-09-15",
      "start_time": "11:00:00",
      "end_time": "12:00:00",
      "server_login": "user_20250915_11",
      "server_password": "pass456",
      "is_available": true,
      "max_users": 1,
      "booking_count": 0,
      "available_spots": 1
    }
  ]
}
```

### Получение слотов с лимитом
```bash
curl -X GET "http://localhost/Book_smeta/api/bookings.php?action=available&limit=5" \
  -H "Cookie: PHPSESSID=your_session_id"
```

## 📋 Управление бронированиями

### Создание бронирования
```bash
curl -X POST http://localhost/Book_smeta/api/bookings.php \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d '{
    "action": "create",
    "slot_id": 1,
    "notes": "Важное бронирование"
  }'
```

**Ответ:**
```json
{
  "success": true,
  "booking_id": 123,
  "message": "Бронирование создано успешно"
}
```

### Получение моих бронирований
```bash
curl -X GET "http://localhost/Book_smeta/api/bookings.php?action=my" \
  -H "Cookie: PHPSESSID=your_session_id"
```

**Ответ:**
```json
{
  "success": true,
  "bookings": [
    {
      "id": 123,
      "user_id": 2,
      "slot_id": 1,
      "booking_status": "active",
      "booking_notes": "Важное бронирование",
      "slot_date": "2025-09-15",
      "start_time": "10:00:00",
      "end_time": "11:00:00",
      "server_login": "user_20250915_10",
      "server_password": "pass123",
      "created_at": "2025-09-14 15:30:00",
      "expires_at": "2025-09-15 11:00:00"
    }
  ]
}
```

### Получение статистики бронирований
```bash
curl -X GET "http://localhost/Book_smeta/api/bookings.php?action=stats" \
  -H "Cookie: PHPSESSID=your_session_id"
```

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

### Получение последних бронирований
```bash
curl -X GET "http://localhost/Book_smeta/api/bookings.php?action=recent&limit=3" \
  -H "Cookie: PHPSESSID=your_session_id"
```

**Ответ:**
```json
{
  "success": true,
  "bookings": [
    {
      "id": 123,
      "user_id": 2,
      "slot_id": 1,
      "booking_status": "active",
      "booking_notes": "Важное бронирование",
      "slot_date": "2025-09-15",
      "start_time": "10:00:00",
      "end_time": "11:00:00",
      "created_at": "2025-09-14 15:30:00"
    }
  ]
}
```

## 🔔 Уведомления

### Получение количества непрочитанных уведомлений
```bash
curl -X GET "http://localhost/Book_smeta/api/notifications.php?action=count" \
  -H "Cookie: PHPSESSID=your_session_id"
```

**Ответ:**
```json
{
  "success": true,
  "count": 3
}
```

### Получение списка уведомлений
```bash
curl -X GET "http://localhost/Book_smeta/api/notifications.php?action=list&limit=10" \
  -H "Cookie: PHPSESSID=your_session_id"
```

**Ответ:**
```json
{
  "success": true,
  "notifications": [
    {
      "id": 1,
      "type": "booking_confirmation",
      "title": "Бронирование подтверждено",
      "message": "Ваше бронирование на 2025-09-15 в 10:00 подтверждено.",
      "is_read": false,
      "created_at": "2025-09-14 15:30:00"
    }
  ]
}
```

### Отметка уведомления как прочитанного
```bash
curl -X POST http://localhost/Book_smeta/api/notifications.php \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d '{
    "action": "mark_read",
    "notification_id": 1
  }'
```

**Ответ:**
```json
{
  "success": true,
  "message": "Уведомление отмечено как прочитанное"
}
```

## 🛡️ CSRF защита

### Получение CSRF токена
```bash
curl -X GET http://localhost/Book_smeta/api/csrf.php \
  -H "Cookie: PHPSESSID=your_session_id"
```

**Ответ:**
```json
{
  "success": true,
  "csrf_token": "abc123def456ghi789"
}
```

### Использование CSRF токена в запросе
```bash
curl -X POST http://localhost/Book_smeta/api/bookings.php \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d '{
    "action": "create",
    "slot_id": 1,
    "notes": "Важное бронирование",
    "csrf_token": "abc123def456ghi789"
  }'
```

## 🔍 Мониторинг системы

### Проверка здоровья системы
```bash
curl -X GET http://localhost/Book_smeta/api/health.php
```

**Ответ:**
```json
{
  "status": "ok",
  "timestamp": "2025-09-14 16:00:00",
  "version": "1.0.0",
  "checks": {
    "database": {
      "status": "ok",
      "tables": {
        "users": "ok",
        "server_slots": "ok",
        "bookings": "ok",
        "notifications": "ok"
      }
    },
    "file_system": {
      "logs_dir": "ok",
      "uploads_dir": "ok",
      "config_file": "ok"
    },
    "php": {
      "status": "ok",
      "version": "8.1.0"
    }
  },
  "statistics": {
    "users_total": 25,
    "users_active": 23,
    "slots_total": 56,
    "slots_available": 12,
    "bookings_active": 8,
    "notifications_unread": 3
  },
  "uptime": {
    "server_time": "2025-09-14 16:00:00",
    "timezone": "Europe/Moscow",
    "memory_usage": 16777216,
    "memory_peak": 25165824
  }
}
```

## 🚨 Обработка ошибок

### Ошибка аутентификации (401)
```bash
curl -X GET "http://localhost/Book_smeta/api/bookings.php?action=my"
```

**Ответ:**
```json
{
  "success": false,
  "error": "Необходима авторизация"
}
```

### Ошибка валидации (400)
```bash
curl -X POST http://localhost/Book_smeta/api/bookings.php \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your_session_id" \
  -d '{
    "action": "create",
    "slot_id": "invalid"
  }'
```

**Ответ:**
```json
{
  "success": false,
  "error": "Неверный ID слота"
}
```

### Ошибка сервера (500)
```json
{
  "success": false,
  "error": "Ошибка выполнения запроса к базе данных: Connection failed"
}
```

## 📱 JavaScript примеры

### Асинхронный запрос с Fetch API
```javascript
// Получение доступных слотов
async function getAvailableSlots() {
  try {
    const response = await fetch('/api/bookings.php?action=available', {
      method: 'GET',
      credentials: 'include'
    });
    
    const data = await response.json();
    
    if (data.success) {
      console.log('Доступные слоты:', data.slots);
      return data.slots;
    } else {
      console.error('Ошибка:', data.error);
      return [];
    }
  } catch (error) {
    console.error('Ошибка сети:', error);
    return [];
  }
}

// Создание бронирования
async function createBooking(slotId, notes = '') {
  try {
    const response = await fetch('/api/bookings.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      credentials: 'include',
      body: JSON.stringify({
        action: 'create',
        slot_id: slotId,
        notes: notes
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      console.log('Бронирование создано:', data.booking_id);
      return data;
    } else {
      console.error('Ошибка создания бронирования:', data.error);
      throw new Error(data.error);
    }
  } catch (error) {
    console.error('Ошибка сети:', error);
    throw error;
  }
}

// Использование
getAvailableSlots().then(slots => {
  console.log('Загружено слотов:', slots.length);
});

createBooking(1, 'Важное бронирование').then(result => {
  console.log('Бронирование успешно создано!');
}).catch(error => {
  console.error('Не удалось создать бронирование:', error);
});
```

### Работа с уведомлениями
```javascript
// Получение количества уведомлений
async function getNotificationCount() {
  try {
    const response = await fetch('/api/notifications.php?action=count', {
      credentials: 'include'
    });
    
    const data = await response.json();
    
    if (data.success) {
      return data.count;
    }
    return 0;
  } catch (error) {
    console.error('Ошибка получения уведомлений:', error);
    return 0;
  }
}

// Обновление счетчика уведомлений в UI
async function updateNotificationBadge() {
  const count = await getNotificationCount();
  const badge = document.getElementById('notification-badge');
  
  if (count > 0) {
    badge.textContent = count;
    badge.style.display = 'inline';
  } else {
    badge.style.display = 'none';
  }
}

// Обновление каждые 30 секунд
setInterval(updateNotificationBadge, 30000);
```

## 🔧 Настройка клиента

### Настройка базового URL
```javascript
const API_BASE_URL = 'http://localhost/Book_smeta/api';

// Функция для выполнения API запросов
async function apiRequest(endpoint, options = {}) {
  const url = `${API_BASE_URL}${endpoint}`;
  
  const defaultOptions = {
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json'
    }
  };
  
  const mergedOptions = { ...defaultOptions, ...options };
  
  try {
    const response = await fetch(url, mergedOptions);
    const data = await response.json();
    
    if (!response.ok) {
      throw new Error(data.error || 'Ошибка сервера');
    }
    
    return data;
  } catch (error) {
    console.error('API ошибка:', error);
    throw error;
  }
}

// Использование
const slots = await apiRequest('/bookings.php?action=available');
```

---

## 📞 Поддержка

Для получения дополнительной информации:

1. **Документация API:** `/api/` endpoints
2. **Руководство по развертыванию:** `DEPLOYMENT_GUIDE.md`
3. **Руководство по данным сервера:** `SERVER_DATA_GUIDE.md`
4. **Проверка здоровья:** `/api/health.php`

**Удачной интеграции! 🚀**
