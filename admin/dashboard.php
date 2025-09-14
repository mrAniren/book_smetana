<?php

session_start();
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Проверяем авторизацию администратора
try {
    $auth = new Auth();
    if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
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
    <title>Панель администратора - Book Smeta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Боковая панель -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Админ панель
                        </h4>
                        <small class="text-muted"><?= escape($user['first_name'] . ' ' . $user['last_name']) ?></small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active text-white" href="dashboard.php">
                                <i class="fas fa-home me-2"></i>
                                Главная
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="users.php">
                                <i class="fas fa-users me-2"></i>
                                Пользователи
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="slots.php">
                                <i class="fas fa-calendar-alt me-2"></i>
                                Слоты
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="bookings.php">
                                <i class="fas fa-list me-2"></i>
                                Бронирования
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="settings.php">
                                <i class="fas fa-cog me-2"></i>
                                Настройки
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="logs.php">
                                <i class="fas fa-file-alt me-2"></i>
                                Логи
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link text-white" href="../public/">
                                <i class="fas fa-external-link-alt me-2"></i>
                                Публичная часть
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="#" onclick="logout()">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                Выход
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Основной контент -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Панель администратора</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
                                <i class="fas fa-sync-alt me-1"></i>
                                Обновить
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Статистика -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Всего пользователей
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-users">-</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Активных слотов
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="active-slots">-</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Всего бронирований
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-bookings">-</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-list fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Доход за месяц
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="monthly-revenue">-</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Графики и таблицы -->
                <div class="row">
                    <!-- График бронирований -->
                    <div class="col-xl-8 col-lg-7">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Статистика бронирований</h6>
                                <div class="dropdown no-arrow">
                                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-right shadow">
                                        <a class="dropdown-item" href="#" onclick="loadChart('week')">За неделю</a>
                                        <a class="dropdown-item" href="#" onclick="loadChart('month')">За месяц</a>
                                        <a class="dropdown-item" href="#" onclick="loadChart('year')">За год</a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-area">
                                    <canvas id="bookingsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Последние активности -->
                    <div class="col-xl-4 col-lg-5">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Последние активности</h6>
                            </div>
                            <div class="card-body">
                                <div id="recent-activities">
                                    <div class="text-center py-3">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Загрузка...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Быстрые действия -->
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Быстрые действия</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <a href="users.php?action=add" class="btn btn-primary w-100">
                                            <i class="fas fa-user-plus me-2"></i>
                                            Добавить пользователя
                                        </a>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <a href="slots.php?action=add" class="btn btn-success w-100">
                                            <i class="fas fa-calendar-plus me-2"></i>
                                            Создать слот
                                        </a>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <a href="bookings.php" class="btn btn-info w-100">
                                            <i class="fas fa-list me-2"></i>
                                            Все бронирования
                                        </a>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <a href="settings.php" class="btn btn-warning w-100">
                                            <i class="fas fa-cog me-2"></i>
                                            Настройки
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Системная информация -->
                    <div class="col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Системная информация</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-6">
                                        <p class="mb-1"><strong>Версия PHP:</strong></p>
                                        <p class="mb-1"><strong>Версия MySQL:</strong></p>
                                        <p class="mb-1"><strong>Размер БД:</strong></p>
                                        <p class="mb-1"><strong>Последний бэкап:</strong></p>
                                    </div>
                                    <div class="col-6">
                                        <p class="mb-1"><?= PHP_VERSION ?></p>
                                        <p class="mb-1" id="mysql-version">-</p>
                                        <p class="mb-1" id="db-size">-</p>
                                        <p class="mb-1" id="last-backup">-</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="../public/assets/js/app.js"></script>
    <script>
        let bookingsChart;

        // Загружаем данные при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardStats();
            loadRecentActivities();
            loadSystemInfo();
            loadChart('week');
        });

        // Загрузка статистики дашборда
        async function loadDashboardStats() {
            try {
                const response = await fetch('../api/admin.php?action=stats');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('total-users').textContent = data.stats.total_users || 0;
                    document.getElementById('active-slots').textContent = data.stats.active_slots || 0;
                    document.getElementById('total-bookings').textContent = data.stats.total_bookings || 0;
                    document.getElementById('monthly-revenue').textContent = data.stats.monthly_revenue || '0 ₽';
                }
            } catch (error) {
                console.error('Ошибка загрузки статистики:', error);
            }
        }

        // Загрузка последних активностей
        async function loadRecentActivities() {
            try {
                const response = await fetch('../api/admin.php?action=activities&limit=10');
                const data = await response.json();
                
                if (data.success) {
                    const container = document.getElementById('recent-activities');
                    
                    if (data.activities.length === 0) {
                        container.innerHTML = '<p class="text-muted text-center">Нет активностей</p>';
                        return;
                    }
                    
                    let html = '';
                    data.activities.forEach(activity => {
                        const icon = getActivityIcon(activity.type);
                        const time = formatTime(activity.created_at);
                        html += `
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-shrink-0">
                                    <i class="${icon} text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="small text-gray-900">${activity.description}</div>
                                    <div class="small text-muted">${time}</div>
                                </div>
                            </div>
                        `;
                    });
                    
                    container.innerHTML = html;
                }
            } catch (error) {
                console.error('Ошибка загрузки активностей:', error);
            }
        }

        // Загрузка системной информации
        async function loadSystemInfo() {
            try {
                const response = await fetch('../api/admin.php?action=system_info');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('mysql-version').textContent = data.info.mysql_version || '-';
                    document.getElementById('db-size').textContent = data.info.db_size || '-';
                    document.getElementById('last-backup').textContent = data.info.last_backup || 'Никогда';
                }
            } catch (error) {
                console.error('Ошибка загрузки системной информации:', error);
            }
        }

        // Загрузка графика
        async function loadChart(period) {
            try {
                const response = await fetch(`../api/admin.php?action=chart&period=${period}`);
                const data = await response.json();
                
                if (data.success) {
                    const ctx = document.getElementById('bookingsChart').getContext('2d');
                    
                    if (bookingsChart) {
                        bookingsChart.destroy();
                    }
                    
                    bookingsChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.chart.labels,
                            datasets: [{
                                label: 'Бронирования',
                                data: data.chart.data,
                                borderColor: 'rgb(75, 192, 192)',
                                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                tension: 0.1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }
            } catch (error) {
                console.error('Ошибка загрузки графика:', error);
            }
        }

        // Получение иконки для типа активности
        function getActivityIcon(type) {
            const icons = {
                'user_registered': 'fas fa-user-plus',
                'booking_created': 'fas fa-calendar-plus',
                'booking_cancelled': 'fas fa-calendar-times',
                'slot_created': 'fas fa-clock',
                'admin_login': 'fas fa-sign-in-alt'
            };
            return icons[type] || 'fas fa-info-circle';
        }

        // Форматирование времени
        function formatTime(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            
            if (diff < 60000) return 'только что';
            if (diff < 3600000) return Math.floor(diff / 60000) + ' мин назад';
            if (diff < 86400000) return Math.floor(diff / 3600000) + ' ч назад';
            return Math.floor(diff / 86400000) + ' дн назад';
        }

        // Функция выхода
        async function logout() {
            if (!confirm('Вы действительно хотите выйти?')) {
                return;
            }
            
            try {
                const response = await fetch('../api/auth.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=logout'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = 'login.php';
                } else {
                    alert('Ошибка при выходе из системы');
                }
            } catch (error) {
                console.error('Ошибка выхода:', error);
                window.location.href = 'login.php';
            }
        }
    </script>
</body>
</html>
