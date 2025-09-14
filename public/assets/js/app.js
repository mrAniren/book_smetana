// Основной JavaScript для приложения Book Smeta

// Глобальные переменные
const API_BASE_URL = '/api';
let csrfToken = '';

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Получаем CSRF токен
    fetchCSRFToken();
    
    // Инициализируем обработчики событий
    initializeEventHandlers();
});

/**
 * Получение CSRF токена
 */
async function fetchCSRFToken() {
    try {
        const response = await fetch('../api/csrf.php');
        const data = await response.json();
        if (data.success) {
            csrfToken = data.csrf_token;
        }
    } catch (error) {
        console.error('Ошибка получения CSRF токена:', error);
    }
}

/**
 * Инициализация обработчиков событий
 */
function initializeEventHandlers() {
    // Обработчики для форм
    const forms = document.querySelectorAll('form[data-ajax]');
    forms.forEach(form => {
        form.addEventListener('submit', handleFormSubmit);
    });
    
    // Обработчики для кнопок с data-action
    const actionButtons = document.querySelectorAll('[data-action]');
    actionButtons.forEach(button => {
        button.addEventListener('click', handleActionClick);
    });
}

/**
 * Обработка отправки форм
 */
async function handleFormSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    // Добавляем CSRF токен
    if (csrfToken) {
        data.csrf_token = csrfToken;
    }
    
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    
    try {
        // Показываем индикатор загрузки
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Обработка...';
        
        const response = await fetch(form.action || window.location.href, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', result.message || 'Операция выполнена успешно');
            
            // Если указан redirect, перенаправляем
            if (result.redirect) {
                window.location.href = result.redirect;
            }
        } else {
            showNotification('error', result.error || 'Произошла ошибка');
            
            // Показываем ошибки валидации
            if (result.errors) {
                showValidationErrors(result.errors);
            }
        }
        
    } catch (error) {
        console.error('Ошибка отправки формы:', error);
        showNotification('error', 'Произошла ошибка при отправке запроса');
    } finally {
        // Восстанавливаем кнопку
        submitButton.disabled = false;
        submitButton.textContent = originalText;
    }
}

/**
 * Обработка кликов по кнопкам действий
 */
async function handleActionClick(event) {
    event.preventDefault();
    
    const button = event.target.closest('[data-action]');
    const action = button.dataset.action;
    const url = button.dataset.url || `${API_BASE_URL}/${action}`;
    const confirmMessage = button.dataset.confirm;
    
    // Подтверждение действия
    if (confirmMessage && !confirm(confirmMessage)) {
        return;
    }
    
    const originalText = button.textContent;
    
    try {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Обработка...';
        
        const response = await fetch(url, {
            method: button.dataset.method || 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                csrf_token: csrfToken
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', result.message || 'Операция выполнена успешно');
            
            // Обновляем страницу если указано
            if (button.dataset.reload) {
                window.location.reload();
            }
        } else {
            showNotification('error', result.error || 'Произошла ошибка');
        }
        
    } catch (error) {
        console.error('Ошибка выполнения действия:', error);
        showNotification('error', 'Произошла ошибка при выполнении действия');
    } finally {
        button.disabled = false;
        button.textContent = originalText;
    }
}

/**
 * Показ уведомлений
 */
function showNotification(type, message, duration = 5000) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    
    const notification = document.createElement('div');
    notification.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="${icon} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Автоматическое скрытие
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, duration);
}

/**
 * Показ ошибок валидации
 */
function showValidationErrors(errors) {
    // Очищаем предыдущие ошибки
    document.querySelectorAll('.is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });
    document.querySelectorAll('.invalid-feedback').forEach(el => {
        el.remove();
    });
    
    // Показываем новые ошибки
    Object.keys(errors).forEach(field => {
        const input = document.querySelector(`[name="${field}"]`);
        if (input) {
            input.classList.add('is-invalid');
            
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            feedback.textContent = errors[field];
            input.parentNode.appendChild(feedback);
        }
    });
}

/**
 * Загрузка ближайших доступных слотов
 */
async function loadNextSlots() {
    try {
        const response = await fetch('../api/bookings.php?action=available&limit=5');
        const data = await response.json();
        
        if (data.success && data.slots.length > 0) {
            const container = document.getElementById('next-slots');
            container.innerHTML = data.slots.map(slot => `
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <div>
                        <strong>${formatDate(slot.slot_date)}</strong> в ${slot.start_time}
                        <br>
                        <small class="text-muted">${slot.available_spots} из ${slot.max_users} мест свободно</small>
                    </div>
                    <button class="btn btn-sm btn-outline-primary" onclick="bookSlot(${slot.id})">
                        Забронировать
                    </button>
                </div>
            `).join('');
        } else {
            document.getElementById('next-slots').innerHTML = 
                '<p class="text-muted text-center">Нет доступных слотов</p>';
        }
    } catch (error) {
        console.error('Ошибка загрузки слотов:', error);
        document.getElementById('next-slots').innerHTML = 
            '<p class="text-danger text-center">Ошибка загрузки слотов</p>';
    }
}

/**
 * Бронирование слота
 */
async function bookSlot(slotId) {
    if (!confirm('Забронировать этот слот?')) {
        return;
    }
    
    try {
        const response = await fetch('../api/bookings.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'create',
                slot_id: slotId,
                csrf_token: csrfToken
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', 'Слот успешно забронирован!');
            loadNextSlots(); // Обновляем список слотов
        } else {
            showNotification('error', result.error);
        }
    } catch (error) {
        console.error('Ошибка бронирования:', error);
        showNotification('error', 'Ошибка при бронировании слота');
    }
}

/**
 * Загрузка количества уведомлений
 */
async function loadNotificationsCount() {
    try {
        const response = await fetch('../api/notifications.php?action=count');
        const data = await response.json();
        
        if (data.success) {
            const countElement = document.getElementById('notifications-count');
            if (countElement) {
                countElement.textContent = data.count;
                countElement.style.display = data.count > 0 ? 'inline' : 'none';
            }
        }
    } catch (error) {
        console.error('Ошибка загрузки уведомлений:', error);
    }
}

/**
 * Выход из системы
 */
async function logout() {
    if (!confirm('Вы действительно хотите выйти?')) {
        return;
    }
    
    try {
        const response = await fetch(`../api/auth.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=logout&csrf_token=${csrfToken}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            window.location.href = '../';
        } else {
            showNotification('error', 'Ошибка при выходе из системы');
        }
    } catch (error) {
        console.error('Ошибка выхода:', error);
        // Даже если есть ошибка, перенаправляем на главную
        window.location.href = '../';
    }
}

/**
 * Форматирование даты
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ru-RU', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

/**
 * Форматирование времени
 */
function formatTime(timeString) {
    return timeString.substring(0, 5); // HH:MM
}

/**
 * Отмена бронирования
 */
async function cancelBooking(bookingId) {
    if (!confirm('Вы действительно хотите отменить это бронирование?')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE_URL}/bookings/${bookingId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                csrf_token: csrfToken
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('success', 'Бронирование отменено');
            window.location.reload();
        } else {
            showNotification('error', result.error);
        }
    } catch (error) {
        console.error('Ошибка отмены бронирования:', error);
        showNotification('error', 'Ошибка при отмене бронирования');
    }
}

/**
 * Обновление счетчика бронирований
 */
function updateBookingCounter() {
    const counter = document.querySelector('.booking-counter');
    if (counter) {
        // Обновляем счетчик через API
        fetch('../api/auth.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    counter.textContent = `${data.user.booking_count || 0} / ${data.user.booking_limit || 3}`;
                }
            })
            .catch(error => console.error('Ошибка обновления счетчика:', error));
    }
}
