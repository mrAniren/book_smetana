<?php

session_start();

// Проверяем, настроена ли система
if (!file_exists(__DIR__ . '/../.env') || !file_exists(__DIR__ . '/../includes/Auth.php')) {
    header('Location: ../install.php');
    exit;
}

try {
    require_once __DIR__ . '/../includes/Auth.php';
    require_once __DIR__ . '/../includes/functions.php';

    $auth = new Auth();
    $isLoggedIn = $auth->isLoggedIn();
} catch (Exception $e) {
    // Если есть ошибка с базой данных, перенаправляем на установку
    header('Location: ../install.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= getSetting('app_name', 'Book Smeta') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-calendar-alt me-2"></i>
                <?= getSetting('app_name', 'Book Smeta') ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Главная</a>
                    </li>
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="calendar.php">Календарь</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my-bookings.php">Мои бронирования</a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if ($isLoggedIn): ?>
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
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Вход</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="container mt-4">
        <?php if (!$isLoggedIn): ?>
            <!-- Форма входа для неавторизованных пользователей -->
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white text-center">
                            <h4 class="mb-0">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Вход в систему
                            </h4>
                        </div>
                        <div class="card-body p-4">
                            <?php if (isset($_GET['error'])): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <?= escape($_GET['error']) ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="login.php">
                                <div class="mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-1"></i>
                                        Email
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock me-1"></i>
                                        Пароль
                                    </label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-sign-in-alt me-2"></i>
                                        Войти
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <div class="alert alert-info">
                            <small>
                                <i class="fas fa-info-circle me-1"></i>
                                Доступ только для оплативших клиентов
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Главная страница для авторизованных пользователей -->
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <h2 class="mb-4">Добро пожаловать, <?= escape($_SESSION['user_name']) ?>!</h2>
                    
                    <!-- Быстрые действия -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-primary text-white h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-calendar-plus fa-2x mb-3"></i>
                                    <h5>Новое бронирование</h5>
                                    <p class="mb-3">Забронируйте время для работы на сервере</p>
                                    <a href="calendar.php" class="btn btn-light">
                                        <i class="fas fa-plus me-1"></i>
                                        Забронировать
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-list fa-2x mb-3"></i>
                                    <h5>Мои бронирования</h5>
                                    <p class="mb-3">Просмотрите свои активные и завершенные бронирования</p>
                                    <a href="my-bookings.php" class="btn btn-light">
                                        <i class="fas fa-eye me-1"></i>
                                        Просмотреть
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-user fa-2x mb-3"></i>
                                    <h5>Профиль</h5>
                                    <p class="mb-3">Управляйте настройками своего аккаунта</p>
                                    <a href="profile.php" class="btn btn-light">
                                        <i class="fas fa-cog me-1"></i>
                                        Настройки
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Статистика -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3 class="text-primary" id="total-bookings">-</h3>
                                    <p class="text-muted mb-0">Всего бронирований</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3 class="text-success" id="active-bookings">-</h3>
                                    <p class="text-muted mb-0">Активных</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3 class="text-info" id="remaining-bookings">-</h3>
                                    <p class="text-muted mb-0">Осталось</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h3 class="text-warning"><?= $_SESSION['booking_limit'] ?></h3>
                                    <p class="text-muted mb-0">Лимит</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer class="bg-light mt-5 py-4">
        <div class="container text-center">
            <p class="text-muted mb-0">
                &copy; <?= date('Y') ?> <?= getSetting('app_name', 'Book Smeta') ?>. Все права защищены.
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
    
    <?php if ($isLoggedIn): ?>
    <script>
        // Загружаем количество уведомлений
        loadNotificationsCount();
        
        // Загружаем статистику бронирований
        loadBookingStats();
        
        // Загружаем последние бронирования
        loadRecentBookings();
        
        // Функция загрузки статистики бронирований
        async function loadBookingStats() {
            try {
                const response = await fetch('../api/bookings.php?action=stats');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('total-bookings').textContent = data.stats.total || 0;
                    document.getElementById('active-bookings').textContent = data.stats.active || 0;
                    document.getElementById('remaining-bookings').textContent = data.stats.remaining || 0;
                }
            } catch (error) {
                console.error('Ошибка загрузки статистики:', error);
            }
        }
        
        // Функция загрузки последних бронирований
        async function loadRecentBookings() {
            try {
                const response = await fetch('../api/bookings.php?action=recent&limit=5');
                const data = await response.json();
                
                if (data.success) {
                    const container = document.getElementById('recent-bookings');
                    
                    if (data.bookings.length === 0) {
                        container.innerHTML = `
                            <div class="text-center py-3">
                                <i class="fas fa-calendar-times fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">У вас пока нет бронирований</p>
                            </div>
                        `;
                        return;
                    }
                    
                    let html = '<div class="list-group list-group-flush">';
                    data.bookings.forEach(booking => {
                        const statusBadge = getStatusBadge(booking.booking_status);
                        html += `
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${formatDate(booking.slot_date)}</strong><br>
                                    <small class="text-muted">${formatTime(booking.start_time)} - ${formatTime(booking.end_time)}</small>
                                </div>
                                ${statusBadge}
                            </div>
                        `;
                    });
                    html += '</div>';
                    
                    container.innerHTML = html;
                }
            } catch (error) {
                console.error('Ошибка загрузки последних бронирований:', error);
            }
        }
        
        // Функция получения бейджа статуса
        function getStatusBadge(status) {
            const badges = {
                'active': '<span class="badge bg-success">Активно</span>',
                'completed': '<span class="badge bg-primary">Завершено</span>',
                'cancelled': '<span class="badge bg-secondary">Отменено</span>',
                'expired': '<span class="badge bg-warning">Истекло</span>'
            };
            return badges[status] || '<span class="badge bg-secondary">Неизвестно</span>';
        }
    </script>
    <?php endif; ?>
</body>
</html>
