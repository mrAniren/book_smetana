<?php

session_start();
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Проверяем авторизацию
try {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: ../install.php');
    exit;
}

$user = $auth->getCurrentUser();

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Календарь - Book Smeta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-calendar-alt me-2"></i>
                Book Smeta
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Главная</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="calendar.php">Календарь</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my-bookings.php">Мои бронирования</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i>
                            <?= escape($_SESSION['user_name']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Профиль</a></li>
                            <li><a class="dropdown-item" href="notifications.php">
                                Уведомления 
                                <span class="badge bg-danger" id="notifications-count">0</span>
                            </a></li>
                            <?php if ($auth->isAdmin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../admin/">Панель администратора</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="logout()">Выход</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2>
                            <i class="fas fa-calendar-alt me-2"></i>
                            Календарь бронирований
                        </h2>
                        <p class="text-muted mb-0">Выберите удобное время для работы на сервере</p>
                    </div>
                    <div class="text-end">
                        <div class="badge bg-info fs-6">
                            Доступно бронирований: <span class="booking-counter">- / -</span>
                        </div>
                    </div>
                </div>
                
                <!-- Объяснение системы слотов -->
                <div class="alert alert-info mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="alert-heading">
                                <i class="fas fa-info-circle me-2"></i>
                                Как работает система бронирования?
                            </h6>
                            <p class="mb-2">
                                <strong>Каждый слот предназначен для одного пользователя.</strong> 
                                Выберите удобное время и забронируйте слот для работы на сервере.
                            </p>
                            <small class="text-muted">
                                После бронирования вы получите доступ к серверу на указанное время.
                            </small>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="d-flex justify-content-end gap-3">
                                <div class="text-center">
                                    <div class="badge bg-success fs-6 mb-1">Свободно</div>
                                    <div class="small text-muted">Можно забронировать</div>
                                </div>
                                <div class="text-center">
                                    <div class="badge bg-danger fs-6 mb-1">Занято</div>
                                    <div class="small text-muted">Уже забронировано</div>
                                </div>
                                <div class="text-center">
                                    <div class="badge bg-secondary fs-6 mb-1">Прошло</div>
                                    <div class="small text-muted">Время прошло</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Фильтры -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="date-filter" class="form-label">Дата:</label>
                                <input type="date" class="form-control" id="date-filter" onchange="filterSlots()">
                            </div>
                            <div class="col-md-4">
                                <label for="time-filter" class="form-label">Время:</label>
                                <select class="form-select" id="time-filter" onchange="filterSlots()">
                                    <option value="">Все время</option>
                                    <option value="morning">Утром (06:00 - 12:00)</option>
                                    <option value="afternoon">Днем (12:00 - 18:00)</option>
                                    <option value="evening">Вечером (18:00 - 24:00)</option>
                                    <option value="night">Ночью (00:00 - 06:00)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button class="btn btn-outline-primary" onclick="loadSlots()">
                                        <i class="fas fa-refresh me-1"></i>
                                        Обновить
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Слоты -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>
                            Доступные слоты
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="slots-container">
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Загрузка...</span>
                                </div>
                                <p class="mt-3">Загрузка доступных слотов...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Модальное окно подтверждения бронирования -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Подтверждение бронирования</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Вы действительно хотите забронировать этот слот?</p>
                    <div id="slot-details"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-primary" id="confirm-booking">Забронировать</button>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-light mt-5 py-4">
        <div class="container text-center">
            <p class="text-muted mb-0">
                &copy; <?= date('Y') ?> Book Smeta. Все права защищены.
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
    <script>
        let currentSlots = [];
        let selectedSlotId = null;
        
        // Загружаем слоты при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            loadSlots();
            loadNotificationsCount();
            loadBookingCounter();
        });
        
        // Загрузка слотов
        async function loadSlots() {
            try {
                const response = await fetch('../api/bookings.php?action=available');
                const data = await response.json();
                
                if (data.success) {
                    currentSlots = data.slots;
                    displaySlots(currentSlots);
                } else {
                    showNotification('error', data.error || 'Ошибка загрузки слотов');
                }
            } catch (error) {
                console.error('Ошибка загрузки слотов:', error);
                showNotification('error', 'Ошибка загрузки слотов');
            }
        }
        
        // Отображение слотов
        function displaySlots(slots) {
            const container = document.getElementById('slots-container');
            
            if (slots.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Нет доступных слотов</h5>
                        <p class="text-muted">Попробуйте выбрать другую дату или время</p>
                    </div>
                `;
                return;
            }
            
            // Группируем слоты по дате
            const groupedSlots = {};
            slots.forEach(slot => {
                if (!groupedSlots[slot.slot_date]) {
                    groupedSlots[slot.slot_date] = [];
                }
                groupedSlots[slot.slot_date].push(slot);
            });
            
            let html = '';
            Object.keys(groupedSlots).sort().forEach(date => {
                html += `
                    <div class="mb-4">
                        <h6 class="text-primary border-bottom pb-2 mb-3">
                            <i class="fas fa-calendar-day me-2"></i>
                            ${formatDate(date)}
                        </h6>
                        <div class="row">
                `;
                
                groupedSlots[date].forEach(slot => {
                    const slotClass = getSlotClass(slot);
                    const isAvailable = slotClass.includes('available');
                    const isPast = slotClass.includes('past');
                    const isFull = slotClass.includes('full');
                    
                    html += `
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                            <div class="slot-card ${slotClass} h-100" onclick="selectSlot(${slot.id}, '${slotClass}')" style="cursor: ${isAvailable ? 'pointer' : 'not-allowed'};">
                                <div class="card-body text-center p-4">
                                    <div class="slot-time mb-3">
                                        <i class="fas fa-clock me-2"></i>
                                        <strong>${formatTime(slot.start_time)} - ${formatTime(slot.end_time)}</strong>
                                    </div>
                                    
                                    <div class="slot-status mb-3">
                                        ${isAvailable ? 
                                            '<div class="badge bg-success fs-6"><i class="fas fa-check me-1"></i>Доступно</div>' : 
                                            isFull ? 
                                                '<div class="badge bg-danger fs-6"><i class="fas fa-times me-1"></i>Занято</div>' :
                                                '<div class="badge bg-secondary fs-6"><i class="fas fa-clock me-1"></i>Прошло</div>'
                                        }
                                    </div>
                                    
                                    ${isAvailable ? `
                                        <div class="slot-action">
                                            <small class="text-success">
                                                <i class="fas fa-arrow-right me-1"></i>
                                                Нажмите для бронирования
                                            </small>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                html += `
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        // Определение класса слота
        function getSlotClass(slot) {
            const now = new Date();
            const slotDate = new Date(slot.slot_date + ' ' + slot.start_time);
            
            if (slotDate < now) {
                return 'slot-card past';
            } else if (slot.available_spots <= 0) {
                return 'slot-card full';
            } else {
                return 'slot-card available';
            }
        }
        
        // Выбор слота
        function selectSlot(slotId, slotClass) {
            if (slotClass.includes('past') || slotClass.includes('full')) {
                return;
            }
            
            selectedSlotId = slotId;
            const slot = currentSlots.find(s => s.id === slotId);
            
            if (slot) {
                document.getElementById('slot-details').innerHTML = `
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <h6 class="card-title text-primary mb-3">
                                <i class="fas fa-calendar-day me-2"></i>
                                ${formatDate(slot.slot_date)}
                            </h6>
                            <div class="mb-3">
                                <i class="fas fa-clock me-2 text-secondary"></i>
                                <strong class="fs-5">${formatTime(slot.start_time)} - ${formatTime(slot.end_time)}</strong>
                            </div>
                            <div class="mb-3">
                                ${slot.available_spots > 0 ? 
                                    '<i class="fas fa-check-circle fa-3x text-success"></i>' :
                                    '<i class="fas fa-times-circle fa-3x text-danger"></i>'
                                }
                            </div>
                            <div class="mb-3">
                                ${slot.available_spots > 0 ? 
                                    '<span class="badge bg-success fs-6">Слот доступен для бронирования</span>' :
                                    '<span class="badge bg-danger fs-6">Слот уже занят</span>'
                                }
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                После подтверждения бронирования вы получите доступ к серверу на указанное время.
                            </div>
                        </div>
                    </div>
                `;
                
                const modal = new bootstrap.Modal(document.getElementById('bookingModal'));
                modal.show();
            }
        }
        
        // Подтверждение бронирования
        document.getElementById('confirm-booking').addEventListener('click', async function() {
            if (!selectedSlotId) return;
            
            try {
                const response = await fetch('../api/bookings.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'create',
                        slot_id: selectedSlotId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('success', 'Слот успешно забронирован!');
                    bootstrap.Modal.getInstance(document.getElementById('bookingModal')).hide();
                    loadSlots(); // Обновляем список слотов
                    loadBookingCounter(); // Обновляем счетчик
                } else {
                    showNotification('error', result.error);
                }
            } catch (error) {
                console.error('Ошибка бронирования:', error);
                showNotification('error', 'Ошибка при бронировании слота');
            }
        });
        
        // Фильтрация слотов
        function filterSlots() {
            const dateFilter = document.getElementById('date-filter').value;
            const timeFilter = document.getElementById('time-filter').value;
            
            let filteredSlots = [...currentSlots];
            
            if (dateFilter) {
                filteredSlots = filteredSlots.filter(slot => slot.slot_date === dateFilter);
            }
            
            if (timeFilter) {
                filteredSlots = filteredSlots.filter(slot => {
                    const hour = parseInt(slot.start_time.split(':')[0]);
                    switch (timeFilter) {
                        case 'morning': return hour >= 6 && hour < 12;
                        case 'afternoon': return hour >= 12 && hour < 18;
                        case 'evening': return hour >= 18 && hour < 24;
                        case 'night': return hour >= 0 && hour < 6;
                        default: return true;
                    }
                });
            }
            
            displaySlots(filteredSlots);
        }
        
        // Установка сегодняшней даты в фильтр
        document.getElementById('date-filter').value = new Date().toISOString().split('T')[0];
        
        // Загрузка счетчика бронирований
        async function loadBookingCounter() {
            try {
                const response = await fetch('../api/auth.php');
                const data = await response.json();
                
                if (data.success && data.authenticated) {
                    const remaining = data.user.booking_limit - data.user.booking_count;
                    document.querySelector('.booking-counter').textContent = 
                        `${remaining} / ${data.user.booking_limit}`;
                }
            } catch (error) {
                console.error('Ошибка загрузки счетчика бронирований:', error);
            }
        }
    </script>
</body>
</html>
