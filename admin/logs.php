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
$message = '';
$error = '';

// Обработка действий
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'clear_logs':
            $days = (int)($_POST['days'] ?? 30);
            try {
                $db = Database::getInstance();
                $db->query("DELETE FROM getcourse_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)", [$days]);
                $message = "Логи старше {$days} дней удалены";
            } catch (Exception $e) {
                $error = 'Ошибка очистки логов: ' . $e->getMessage();
            }
            break;
    }
}

// Получаем список логов
try {
    $db = Database::getInstance();
    $logs = $db->fetchAll("
        SELECT * FROM getcourse_logs 
        ORDER BY created_at DESC 
        LIMIT 1000
    ");
} catch (Exception $e) {
    $logs = [];
    $error = 'Ошибка загрузки логов: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Логи системы - Book Smeta</title>
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
                            <a class="nav-link text-white" href="dashboard.php">
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
                            <a class="nav-link active text-white" href="logs.php">
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
                    <h1 class="h2">Логи системы</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#clearLogsModal">
                            <i class="fas fa-trash me-1"></i>
                            Очистить логи
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="location.reload()">
                            <i class="fas fa-refresh me-1"></i>
                            Обновить
                        </button>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= escape($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= escape($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Статистика логов -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h4><?= count($logs) ?></h4>
                                <p class="mb-0">Всего записей</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h4><?= count(array_filter($logs, fn($l) => $l['status'] === 'success')) ?></h4>
                                <p class="mb-0">Успешных</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center">
                                <h4><?= count(array_filter($logs, fn($l) => $l['status'] === 'error')) ?></h4>
                                <p class="mb-0">Ошибок</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h4><?= count(array_filter($logs, fn($l) => $l['status'] === 'info')) ?></h4>
                                <p class="mb-0">Информационных</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Фильтры -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="status-filter" class="form-label">Статус:</label>
                                <select class="form-select" id="status-filter" onchange="filterLogs()">
                                    <option value="">Все статусы</option>
                                    <option value="success">Успешные</option>
                                    <option value="error">Ошибки</option>
                                    <option value="info">Информационные</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="date-filter" class="form-label">Дата:</label>
                                <input type="date" class="form-control" id="date-filter" onchange="filterLogs()">
                            </div>
                            <div class="col-md-3">
                                <label for="search-filter" class="form-label">Поиск:</label>
                                <input type="text" class="form-control" id="search-filter" placeholder="Поиск в логах" onchange="filterLogs()">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button class="btn btn-outline-primary" onclick="loadLogs()">
                                        <i class="fas fa-refresh me-1"></i>
                                        Обновить
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Таблица логов -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Список логов</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-admin" id="logsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Дата</th>
                                        <th>Статус</th>
                                        <th>Тип</th>
                                        <th>Сообщение</th>
                                        <th>Данные</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                        <?php 
                                        $statusClass = [
                                            'success' => 'success',
                                            'error' => 'danger',
                                            'info' => 'info',
                                            'warning' => 'warning'
                                        ][$log['status']] ?? 'secondary';
                                        
                                        $statusText = [
                                            'success' => 'Успешно',
                                            'error' => 'Ошибка',
                                            'info' => 'Информация',
                                            'warning' => 'Предупреждение'
                                        ][$log['status']] ?? 'Неизвестно';
                                        ?>
                                        <tr>
                                            <td><?= $log['id'] ?></td>
                                            <td><?= formatDate($log['created_at']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $statusClass ?>">
                                                    <?= $statusText ?>
                                                </span>
                                            </td>
                                            <td><?= escape($log['action_type']) ?></td>
                                            <td><?= escape($log['message']) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-info" onclick="viewLogData(<?= htmlspecialchars(json_encode($log)) ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Модальное окно очистки логов -->
    <div class="modal fade" id="clearLogsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Очистка логов</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="clear_logs">
                        
                        <div class="mb-3">
                            <label for="days" class="form-label">Удалить логи старше (дней):</label>
                            <select class="form-select" id="days" name="days">
                                <option value="7">7 дней</option>
                                <option value="30" selected>30 дней</option>
                                <option value="90">90 дней</option>
                                <option value="180">180 дней</option>
                                <option value="365">1 год</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Внимание!</strong> Это действие нельзя отменить. Все логи старше указанного периода будут удалены.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-warning">Удалить логи</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно просмотра данных лога -->
    <div class="modal fade" id="viewLogDataModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Данные лога</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="log-data-content">
                        <!-- Содержимое будет загружено через JavaScript -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../public/assets/js/app.js"></script>
    <script>
        // Просмотр данных лога
        function viewLogData(log) {
            let content = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Основная информация:</h6>
                        <table class="table table-sm">
                            <tr><td><strong>ID:</strong></td><td>${log.id}</td></tr>
                            <tr><td><strong>Дата:</strong></td><td>${log.created_at}</td></tr>
                            <tr><td><strong>Статус:</strong></td><td><span class="badge bg-${log.status === 'success' ? 'success' : log.status === 'error' ? 'danger' : 'info'}">${log.status}</span></td></tr>
                            <tr><td><strong>Тип:</strong></td><td>${log.action_type}</td></tr>
                            <tr><td><strong>Сообщение:</strong></td><td>${log.message}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Дополнительные данные:</h6>
                        <pre class="bg-light p-3" style="max-height: 300px; overflow-y: auto;">${log.request_data ? JSON.stringify(JSON.parse(log.request_data), null, 2) : 'Нет данных'}</pre>
                    </div>
                </div>
            `;
            
            document.getElementById('log-data-content').innerHTML = content;
            
            const modal = new bootstrap.Modal(document.getElementById('viewLogDataModal'));
            modal.show();
        }
        
        // Фильтрация логов
        function filterLogs() {
            const statusFilter = document.getElementById('status-filter').value;
            const dateFilter = document.getElementById('date-filter').value;
            const searchFilter = document.getElementById('search-filter').value.toLowerCase();
            
            const rows = document.querySelectorAll('#logsTable tbody tr');
            
            rows.forEach(row => {
                const status = row.querySelector('td:nth-child(3) span').textContent.trim();
                const date = row.querySelector('td:nth-child(2)').textContent.trim();
                const content = row.textContent.toLowerCase();
                
                let show = true;
                
                if (statusFilter && status !== statusFilter) {
                    show = false;
                }
                
                if (dateFilter && date !== dateFilter) {
                    show = false;
                }
                
                if (searchFilter && !content.includes(searchFilter)) {
                    show = false;
                }
                
                row.style.display = show ? '' : 'none';
            });
        }
        
        // Загрузка логов
        function loadLogs() {
            location.reload();
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
