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
        case 'add_slot':
            $slotDate = $_POST['slot_date'] ?? '';
            $startTime = $_POST['start_time'] ?? '';
            $endTime = $_POST['end_time'] ?? '';
            $maxUsers = (int)($_POST['max_users'] ?? 1);
            $description = trim($_POST['description'] ?? '');
            $isAvailable = isset($_POST['is_available']) ? 1 : 0;
            
            if (empty($slotDate) || empty($startTime) || empty($endTime)) {
                $error = 'Дата, время начала и окончания обязательны для заполнения';
            } elseif ($startTime >= $endTime) {
                $error = 'Время начала должно быть раньше времени окончания';
            } else {
                try {
                    $db = Database::getInstance();
                    $db->insert('server_slots', [
                        'slot_date' => $slotDate,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'max_users' => $maxUsers,
                        'server_login' => 'user' . time(),
                        'server_password' => 'pass' . time(),
                        'is_available' => $isAvailable,
                        'current_users' => 0
                    ]);
                    $message = 'Слот успешно создан';
                } catch (Exception $e) {
                    $error = 'Ошибка создания слота: ' . $e->getMessage();
                }
            }
            break;
            
        case 'update_slot':
            $slotId = (int)($_POST['slot_id'] ?? 0);
            $slotDate = $_POST['slot_date'] ?? '';
            $startTime = $_POST['start_time'] ?? '';
            $endTime = $_POST['end_time'] ?? '';
            $maxUsers = (int)($_POST['max_users'] ?? 1);
            $description = trim($_POST['description'] ?? '');
            $isActive = isset($_POST['is_available']) ? 1 : 0;
            
            if ($slotId && !empty($slotDate) && !empty($startTime) && !empty($endTime)) {
                if ($startTime >= $endTime) {
                    $error = 'Время начала должно быть раньше времени окончания';
                } else {
                    try {
                        $db = Database::getInstance();
                        $db->update('server_slots', [
                            'slot_date' => $slotDate,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                            'max_users' => $maxUsers,
                            'is_available' => $isActive
                        ], 'id = ?', [$slotId]);
                        $message = 'Слот успешно обновлен';
                    } catch (Exception $e) {
                        $error = 'Ошибка обновления слота: ' . $e->getMessage();
                    }
                }
            } else {
                $error = 'Не все обязательные поля заполнены';
            }
            break;
            
        case 'delete_slot':
            $slotId = (int)($_POST['slot_id'] ?? 0);
            if ($slotId) {
                try {
                    $db = Database::getInstance();
                    // Проверяем, есть ли бронирования для этого слота
                    $bookings = $db->fetchAll('SELECT COUNT(*) as count FROM bookings WHERE slot_id = ?', [$slotId]);
                    if ($bookings[0]['count'] > 0) {
                        $error = 'Нельзя удалить слот, для которого есть бронирования';
                    } else {
                        $db->delete('server_slots', 'id = ?', [$slotId]);
                        $message = 'Слот успешно удален';
                    }
                } catch (Exception $e) {
                    $error = 'Ошибка удаления слота: ' . $e->getMessage();
                }
            }
            break;
    }
}

// Получаем список слотов
try {
    $db = Database::getInstance();
    $slots = $db->fetchAll("
        SELECT s.*, 
               COUNT(b.id) as booking_count
        FROM server_slots s
        LEFT JOIN bookings b ON s.id = b.slot_id AND b.booking_status = 'active'
        GROUP BY s.id
        ORDER BY s.slot_date DESC, s.start_time DESC
    ");
} catch (Exception $e) {
    $slots = [];
    $error = 'Ошибка загрузки слотов: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление слотами - Book Smeta</title>
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
                            <a class="nav-link active text-white" href="slots.php">
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
                    <h1 class="h2">Управление слотами</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSlotModal">
                            <i class="fas fa-plus me-1"></i>
                            Добавить слот
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

                <!-- Фильтры -->
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="date-filter" class="form-label">Дата:</label>
                                <input type="date" class="form-control" id="date-filter" onchange="filterSlots()">
                            </div>
                            <div class="col-md-3">
                                <label for="status-filter" class="form-label">Статус:</label>
                                <select class="form-select" id="status-filter" onchange="filterSlots()">
                                    <option value="">Все статусы</option>
                                    <option value="active">Активные</option>
                                    <option value="inactive">Неактивные</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="availability-filter" class="form-label">Доступность:</label>
                                <select class="form-select" id="availability-filter" onchange="filterSlots()">
                                    <option value="">Все слоты</option>
                                    <option value="available">Доступные</option>
                                    <option value="full">Заполненные</option>
                                </select>
                            </div>
                            <div class="col-md-3">
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

                <!-- Таблица слотов -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Список слотов</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-admin" id="slotsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Дата</th>
                                        <th>Время</th>
                                        <th>Макс. пользователей</th>
                                        <th>Забронировано</th>
                                        <th>Доступно</th>
                                        <th>Статус</th>
                                        <th>Создал</th>
                                        <th>Создан</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($slots as $slot): ?>
                                        <?php 
                                        $available = $slot['max_users'] - $slot['booking_count'];
                                        $isPast = strtotime($slot['slot_date'] . ' ' . $slot['start_time']) < time();
                                        $isFull = $available <= 0;
                                        ?>
                                        <tr class="<?= $isPast ? 'table-secondary' : '' ?>">
                                            <td><?= $slot['id'] ?></td>
                                            <td><?= formatDate($slot['slot_date']) ?></td>
                                            <td><?= formatTime($slot['start_time']) ?> - <?= formatTime($slot['end_time']) ?></td>
                                            <td><?= $slot['max_users'] ?></td>
                                            <td><?= $slot['booking_count'] ?></td>
                                            <td>
                                                <span class="badge bg-<?= $available > 0 ? 'success' : 'danger' ?>">
                                                    <?= $available ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($isPast): ?>
                                                    <span class="badge bg-secondary">Прошедший</span>
                                                <?php elseif (!$slot['is_available']): ?>
                                                    <span class="badge bg-warning">Неактивный</span>
                                                <?php elseif ($isFull): ?>
                                                    <span class="badge bg-danger">Заполнен</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Доступен</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>Система</td>
                                            <td><?= formatDate($slot['created_at']) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="editSlot(<?= htmlspecialchars(json_encode($slot)) ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($slot['booking_count'] == 0): ?>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteSlot(<?= $slot['id'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-outline-info" onclick="viewBookings(<?= $slot['id'] ?>)">
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

    <!-- Модальное окно добавления слота -->
    <div class="modal fade" id="addSlotModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Добавить слот</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_slot">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="slot_date" class="form-label">Дата *</label>
                                    <input type="date" class="form-control" id="slot_date" name="slot_date" 
                                           min="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="max_users" class="form-label">Максимум пользователей *</label>
                                    <input type="number" class="form-control" id="max_users" name="max_users" 
                                           value="1" min="1" max="50" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start_time" class="form-label">Время начала *</label>
                                    <input type="time" class="form-control" id="start_time" name="start_time" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end_time" class="form-label">Время окончания *</label>
                                    <input type="time" class="form-control" id="end_time" name="end_time" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Описание</label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      placeholder="Дополнительная информация о слоте..."></textarea>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_available" name="is_available" checked>
                            <label class="form-check-label" for="is_available">
                                Доступный слот
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Добавить слот</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно редактирования слота -->
    <div class="modal fade" id="editSlotModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Редактировать слот</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_slot">
                        <input type="hidden" name="slot_id" id="edit_slot_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_slot_date" class="form-label">Дата *</label>
                                    <input type="date" class="form-control" id="edit_slot_date" name="slot_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_max_users" class="form-label">Максимум пользователей *</label>
                                    <input type="number" class="form-control" id="edit_max_users" name="max_users" 
                                           min="1" max="50" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_start_time" class="form-label">Время начала *</label>
                                    <input type="time" class="form-control" id="edit_start_time" name="start_time" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_end_time" class="form-label">Время окончания *</label>
                                    <input type="time" class="form-control" id="edit_end_time" name="end_time" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Описание</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_available" name="is_available">
                            <label class="form-check-label" for="edit_is_available">
                                Доступный слот
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно подтверждения удаления -->
    <div class="modal fade" id="deleteSlotModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Подтверждение удаления</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Вы действительно хотите удалить этот слот?</p>
                    <p class="text-danger"><strong>Это действие нельзя отменить!</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_slot">
                        <input type="hidden" name="slot_id" id="delete_slot_id">
                        <button type="submit" class="btn btn-danger">Удалить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно просмотра бронирований -->
    <div class="modal fade" id="viewBookingsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Бронирования слота</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="bookings-list">
                        <div class="text-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Загрузка...</span>
                            </div>
                        </div>
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
        // Редактирование слота
        function editSlot(slot) {
            document.getElementById('edit_slot_id').value = slot.id;
            document.getElementById('edit_slot_date').value = slot.slot_date;
            document.getElementById('edit_start_time').value = slot.start_time;
            document.getElementById('edit_end_time').value = slot.end_time;
            document.getElementById('edit_max_users').value = slot.max_users;
            document.getElementById('edit_description').value = slot.description || '';
            document.getElementById('edit_is_available').checked = slot.is_available == 1;
            
            const modal = new bootstrap.Modal(document.getElementById('editSlotModal'));
            modal.show();
        }
        
        // Удаление слота
        function deleteSlot(slotId) {
            document.getElementById('delete_slot_id').value = slotId;
            const modal = new bootstrap.Modal(document.getElementById('deleteSlotModal'));
            modal.show();
        }
        
        // Просмотр бронирований
        function viewBookings(slotId) {
            document.getElementById('bookings-list').innerHTML = `
                <div class="text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Загрузка...</span>
                    </div>
                </div>
            `;
            
            const modal = new bootstrap.Modal(document.getElementById('viewBookingsModal'));
            modal.show();
            
            // Здесь можно добавить загрузку бронирований через AJAX
            setTimeout(() => {
                document.getElementById('bookings-list').innerHTML = '<p class="text-muted text-center">Функция в разработке</p>';
            }, 1000);
        }
        
        // Фильтрация слотов
        function filterSlots() {
            // Здесь можно добавить логику фильтрации
            console.log('Фильтрация слотов');
        }
        
        // Загрузка слотов
        function loadSlots() {
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
