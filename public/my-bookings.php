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
    <title>Мои бронирования - Book Smeta</title>
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
                        <a class="nav-link" href="calendar.php">Календарь</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="my-bookings.php">Мои бронирования</a>
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
                    <h2>
                        <i class="fas fa-list me-2"></i>
                        Мои бронирования
                    </h2>
                    <a href="calendar.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>
                        Новое бронирование
                    </a>
                </div>
                
                <!-- Статистика -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h4 id="total-bookings">-</h4>
                                <p class="mb-0">Всего бронирований</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h4 id="active-bookings">-</h4>
                                <p class="mb-0">Активных</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h4 id="completed-bookings">-</h4>
                                <p class="mb-0">Завершенных</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h4 id="remaining-bookings">-</h4>
                                <p class="mb-0">Осталось</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Фильтры -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="status-filter" class="form-label">Статус:</label>
                                <select class="form-select" id="status-filter" onchange="filterBookings()">
                                    <option value="">Все статусы</option>
                                    <option value="active">Активные</option>
                                    <option value="completed">Завершенные</option>
                                    <option value="cancelled">Отмененные</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="date-filter" class="form-label">Дата:</label>
                                <input type="date" class="form-control" id="date-filter" onchange="filterBookings()">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button class="btn btn-outline-primary" onclick="loadBookings()">
                                        <i class="fas fa-refresh me-1"></i>
                                        Обновить
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Список бронирований -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-check me-2"></i>
                            Список бронирований
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="bookings-container">
                            <div class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Загрузка...</span>
                                </div>
                                <p class="mt-3">Загрузка бронирований...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>


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
        let currentBookings = [];
        
        // Загружаем бронирования при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            loadBookings();
            loadNotificationsCount();
        });
        
        // Загрузка бронирований
        async function loadBookings() {
            try {
                const response = await fetch('../api/bookings.php?action=my');
                const data = await response.json();
                
                if (data.success) {
                    currentBookings = data.bookings;
                    displayBookings(currentBookings);
                    updateStats(currentBookings);
                } else {
                    showNotification('error', data.error || 'Ошибка загрузки бронирований');
                }
            } catch (error) {
                console.error('Ошибка загрузки бронирований:', error);
                showNotification('error', 'Ошибка загрузки бронирований');
            }
        }
        
        // Отображение бронирований
        function displayBookings(bookings) {
            const container = document.getElementById('bookings-container');
            
            if (bookings.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Нет бронирований</h5>
                        <p class="text-muted">У вас пока нет бронирований</p>
                        <a href="calendar.php" class="btn btn-primary">Забронировать слот</a>
                    </div>
                `;
                return;
            }
            
            let html = `
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Время</th>
                                <th>Статус</th>
                                <th>Создано</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            bookings.forEach(booking => {
                const statusBadge = getStatusBadge(booking.booking_status);
                
                html += `
                    <tr>
                        <td>${formatDate(booking.slot_date)}</td>
                        <td>${formatTime(booking.start_time)} - ${formatTime(booking.end_time)}</td>
                        <td>${statusBadge}</td>
                        <td>${formatDate(booking.created_at)}</td>
                        <td>
                            ${booking.booking_status === 'active' && booking.server_login ? `
                                <button class="btn btn-sm btn-outline-info" onclick="showServerAccess(${booking.id})">
                                    <i class="fas fa-server me-1"></i>
                                    Данные сервера
                                </button>
                            ` : ''}
                        </td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            container.innerHTML = html;
        }
        
        // Получение бейджа статуса
        function getStatusBadge(status) {
            const badges = {
                'active': '<span class="badge bg-success">Активно</span>',
                'completed': '<span class="badge bg-primary">Завершено</span>',
                'cancelled': '<span class="badge bg-secondary">Отменено</span>',
                'expired': '<span class="badge bg-warning">Истекло</span>'
            };
            return badges[status] || '<span class="badge bg-secondary">Неизвестно</span>';
        }
        
        // Показ данных сервера
        function showServerAccess(bookingId) {
            const booking = currentBookings.find(b => b.id === bookingId);
            console.log('Booking found:', booking);
            console.log('Server login:', booking?.server_login);
            console.log('Server password:', booking?.server_password);
            
            if (booking && booking.server_login) {
                const modal = new bootstrap.Modal(document.getElementById('serverAccessModal'));
                document.getElementById('server-login').value = booking.server_login;
                document.getElementById('server-password').value = booking.server_password;
                modal.show();
            } else {
                showNotification('error', 'Данные сервера не найдены для этого бронирования');
            }
        }
        
        // Обновление статистики
        function updateStats(bookings) {
            const total = bookings.length;
            const active = bookings.filter(b => b.booking_status === 'active').length;
            const completed = bookings.filter(b => b.booking_status === 'completed').length;
            const remaining = <?= $_SESSION['booking_limit'] ?> - <?= $_SESSION['booking_count'] ?>;
            
            document.getElementById('total-bookings').textContent = total;
            document.getElementById('active-bookings').textContent = active;
            document.getElementById('completed-bookings').textContent = completed;
            document.getElementById('remaining-bookings').textContent = remaining;
        }
        
        // Фильтрация бронирований
        function filterBookings() {
            const statusFilter = document.getElementById('status-filter').value;
            const dateFilter = document.getElementById('date-filter').value;
            
            let filteredBookings = [...currentBookings];
            
            if (statusFilter) {
                filteredBookings = filteredBookings.filter(booking => booking.booking_status === statusFilter);
            }
            
            if (dateFilter) {
                filteredBookings = filteredBookings.filter(booking => booking.slot_date === dateFilter);
            }
            
            displayBookings(filteredBookings);
        }
    </script>

    <!-- Модальное окно данных сервера -->
    <div class="modal fade" id="serverAccessModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Данные доступа к серверу</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Используйте эти данные для подключения к серверу
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Логин:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="server-login" readonly>
                            <button class="btn btn-outline-secondary" onclick="copyToClipboard('server-login')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Пароль:</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="server-password" readonly>
                            <button class="btn btn-outline-secondary" onclick="togglePassword('server-password')">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-outline-secondary" onclick="copyToClipboard('server-password')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            element.select();
            element.setSelectionRange(0, 99999);
            document.execCommand('copy');
            showNotification('success', 'Скопировано в буфер обмена');
        }
        
        function togglePassword(elementId) {
            const element = document.getElementById(elementId);
            const button = element.nextElementSibling;
            const icon = button.querySelector('i');
            
            if (element.type === 'password') {
                element.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                element.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
    </script>
</body>
</html>
