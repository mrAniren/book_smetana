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
        case 'update_booking':
            $bookingId = (int)($_POST['booking_id'] ?? 0);
            $status = $_POST['status'] ?? 'active';
            $notes = trim($_POST['notes'] ?? '');
            
            if ($bookingId) {
                try {
                    $db = Database::getInstance();
                    $db->update('bookings', [
                        'booking_status' => $status,
                        'notes' => $notes
                    ], 'id = ?', [$bookingId]);
                    $message = 'Бронирование успешно обновлено';
                } catch (Exception $e) {
                    $error = 'Ошибка обновления бронирования: ' . $e->getMessage();
                }
            }
            break;
            
        case 'cancel_booking':
            $bookingId = (int)($_POST['booking_id'] ?? 0);
            if ($bookingId) {
                try {
                    $db = Database::getInstance();
                    $db->update('bookings', [
                        'booking_status' => 'cancelled'
                    ], 'id = ?', [$bookingId]);
                    $message = 'Бронирование отменено';
                } catch (Exception $e) {
                    $error = 'Ошибка отмены бронирования: ' . $e->getMessage();
                }
            }
            break;
            
        case 'delete_booking':
            $bookingId = (int)($_POST['booking_id'] ?? 0);
            if ($bookingId) {
                try {
                    $db = Database::getInstance();
                    $db->delete('bookings', 'id = ?', [$bookingId]);
                    $message = 'Бронирование удалено';
                } catch (Exception $e) {
                    $error = 'Ошибка удаления бронирования: ' . $e->getMessage();
                }
            }
            break;
    }
}

// Получаем список бронирований
try {
    $db = Database::getInstance();
    $bookings = $db->fetchAll("
        SELECT b.*, 
               u.first_name, u.last_name, u.email,
               s.slot_date, s.start_time, s.end_time, s.max_users
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN server_slots s ON b.slot_id = s.id
        ORDER BY b.created_at DESC
    ");
} catch (Exception $e) {
    $bookings = [];
    $error = 'Ошибка загрузки бронирований: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление бронированиями - Book Smeta</title>
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
                            <a class="nav-link active text-white" href="bookings.php">
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
                    <h1 class="h2">Управление бронированиями</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
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

                <!-- Статистика -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h4><?= count($bookings) ?></h4>
                                <p class="mb-0">Всего бронирований</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h4><?= count(array_filter($bookings, fn($b) => $b['booking_status'] === 'active')) ?></h4>
                                <p class="mb-0">Активных</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h4><?= count(array_filter($bookings, fn($b) => $b['booking_status'] === 'completed')) ?></h4>
                                <p class="mb-0">Завершенных</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h4><?= count(array_filter($bookings, fn($b) => $b['booking_status'] === 'cancelled')) ?></h4>
                                <p class="mb-0">Отмененных</p>
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
                                <select class="form-select" id="status-filter" onchange="filterBookings()">
                                    <option value="">Все статусы</option>
                                    <option value="active">Активные</option>
                                    <option value="completed">Завершенные</option>
                                    <option value="cancelled">Отмененные</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="date-filter" class="form-label">Дата:</label>
                                <input type="date" class="form-control" id="date-filter" onchange="filterBookings()">
                            </div>
                            <div class="col-md-3">
                                <label for="user-filter" class="form-label">Пользователь:</label>
                                <input type="text" class="form-control" id="user-filter" placeholder="Поиск по имени или email" onchange="filterBookings()">
                            </div>
                            <div class="col-md-3">
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

                <!-- Таблица бронирований -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Список бронирований</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-admin" id="bookingsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Пользователь</th>
                                        <th>Дата слота</th>
                                        <th>Время</th>
                                        <th>Статус</th>
                                        <th>Создано</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookings as $booking): ?>
                                        <?php 
                                        $isPast = strtotime($booking['slot_date'] . ' ' . $booking['start_time']) < time();
                                        $statusClass = [
                                            'active' => 'success',
                                            'completed' => 'primary',
                                            'cancelled' => 'danger',
                                            'expired' => 'warning'
                                        ][$booking['booking_status']] ?? 'secondary';
                                        
                                        $statusText = [
                                            'active' => 'Активно',
                                            'completed' => 'Завершено',
                                            'cancelled' => 'Отменено',
                                            'expired' => 'Истекло'
                                        ][$booking['booking_status']] ?? 'Неизвестно';
                                        ?>
                                        <tr class="<?= $isPast ? 'table-secondary' : '' ?>">
                                            <td><?= $booking['id'] ?></td>
                                            <td>
                                                <div>
                                                    <strong><?= escape($booking['first_name'] . ' ' . $booking['last_name']) ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?= escape($booking['email']) ?></small>
                                                </div>
                                            </td>
                                            <td><?= formatDate($booking['slot_date']) ?></td>
                                            <td><?= formatTime($booking['start_time']) ?> - <?= formatTime($booking['end_time']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $statusClass ?>">
                                                    <?= $statusText ?>
                                                </span>
                                            </td>
                                            <td><?= formatDate($booking['created_at']) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="editBooking(<?= htmlspecialchars(json_encode($booking)) ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($booking['booking_status'] === 'active'): ?>
                                                    <button class="btn btn-sm btn-outline-warning" onclick="cancelBooking(<?= $booking['id'] ?>)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteBooking(<?= $booking['id'] ?>)">
                                                    <i class="fas fa-trash"></i>
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

    <!-- Модальное окно редактирования бронирования -->
    <div class="modal fade" id="editBookingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Редактировать бронирование</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_booking">
                        <input type="hidden" name="booking_id" id="edit_booking_id">
                        
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Статус</label>
                            <select class="form-select" id="edit_status" name="status">
                                <option value="active">Активно</option>
                                <option value="completed">Завершено</option>
                                <option value="cancelled">Отменено</option>
                                <option value="expired">Истекло</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_notes" class="form-label">Комментарии</label>
                            <textarea class="form-control" id="edit_notes" name="notes" rows="3"></textarea>
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

    <!-- Модальное окно подтверждения отмены -->
    <div class="modal fade" id="cancelBookingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Подтверждение отмены</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Вы действительно хотите отменить это бронирование?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="cancel_booking">
                        <input type="hidden" name="booking_id" id="cancel_booking_id">
                        <button type="submit" class="btn btn-warning">Отменить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно подтверждения удаления -->
    <div class="modal fade" id="deleteBookingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Подтверждение удаления</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Вы действительно хотите удалить это бронирование?</p>
                    <p class="text-danger"><strong>Это действие нельзя отменить!</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_booking">
                        <input type="hidden" name="booking_id" id="delete_booking_id">
                        <button type="submit" class="btn btn-danger">Удалить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../public/assets/js/app.js"></script>
    <script>
        // Редактирование бронирования
        function editBooking(booking) {
            document.getElementById('edit_booking_id').value = booking.id;
            document.getElementById('edit_status').value = booking.booking_status;
            document.getElementById('edit_notes').value = booking.notes || '';
            
            const modal = new bootstrap.Modal(document.getElementById('editBookingModal'));
            modal.show();
        }
        
        // Отмена бронирования
        function cancelBooking(bookingId) {
            document.getElementById('cancel_booking_id').value = bookingId;
            const modal = new bootstrap.Modal(document.getElementById('cancelBookingModal'));
            modal.show();
        }
        
        // Удаление бронирования
        function deleteBooking(bookingId) {
            document.getElementById('delete_booking_id').value = bookingId;
            const modal = new bootstrap.Modal(document.getElementById('deleteBookingModal'));
            modal.show();
        }
        
        // Фильтрация бронирований
        function filterBookings() {
            const statusFilter = document.getElementById('status-filter').value;
            const dateFilter = document.getElementById('date-filter').value;
            const userFilter = document.getElementById('user-filter').value.toLowerCase();
            
            const rows = document.querySelectorAll('#bookingsTable tbody tr');
            
            rows.forEach(row => {
                const status = row.querySelector('td:nth-child(5) span').textContent.trim();
                const date = row.querySelector('td:nth-child(3)').textContent.trim();
                const user = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                
                let show = true;
                
                if (statusFilter && status !== statusFilter) {
                    show = false;
                }
                
                if (dateFilter && date !== dateFilter) {
                    show = false;
                }
                
                if (userFilter && !user.includes(userFilter)) {
                    show = false;
                }
                
                row.style.display = show ? '' : 'none';
            });
        }
        
        // Загрузка бронирований
        function loadBookings() {
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
