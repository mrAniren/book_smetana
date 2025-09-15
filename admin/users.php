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
        case 'add_user':
            $email = trim($_POST['email'] ?? '');
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $role = $_POST['role'] ?? 'user';
            $bookingLimit = (int)($_POST['booking_limit'] ?? 3);
            $isPaidClient = isset($_POST['is_paid_client']) ? 1 : 0;
            
            if (empty($email) || empty($firstName) || empty($lastName)) {
                $error = 'Email, имя и фамилия обязательны для заполнения';
            } else {
                try {
                    $auth->createUser([
                        'email' => $email,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'phone' => $phone,
                        'role' => $role,
                        'booking_limit' => $bookingLimit,
                        'is_paid_client' => $isPaidClient
                    ]);
                    $message = 'Пользователь успешно создан';
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
            }
            break;
            
        case 'update_user':
            $userId = (int)($_POST['user_id'] ?? 0);
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $role = $_POST['role'] ?? 'user';
            $bookingLimit = (int)($_POST['booking_limit'] ?? 3);
            $isPaidClient = isset($_POST['is_paid_client']) ? 1 : 0;
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            if ($userId && !empty($firstName) && !empty($lastName)) {
                try {
                    $auth->updateUser($userId, [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'phone' => $phone,
                        'role' => $role,
                        'booking_limit' => $bookingLimit,
                        'is_paid_client' => $isPaidClient,
                        'is_active' => $isActive
                    ]);
                    $message = 'Пользователь успешно обновлен';
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
            } else {
                $error = 'Не все обязательные поля заполнены';
            }
            break;
            
        case 'change_password':
            $userId = (int)($_POST['user_id'] ?? 0);
            $newPassword = trim($_POST['new_password'] ?? '');
            
            if ($userId && !empty($newPassword)) {
                try {
                    $db = Database::getInstance();
                    $hashedPassword = md5($newPassword);
                    
                    $db->update('users', 
                        ['password_hash' => $hashedPassword], 
                        'id = ?', 
                        [$userId]
                    );
                    
                    $message = 'Пароль успешно изменен';
                } catch (Exception $e) {
                    $error = 'Ошибка изменения пароля: ' . $e->getMessage();
                }
            } else {
                $error = 'Не все поля заполнены';
            }
            break;
            
        case 'delete_user':
            $userId = (int)($_POST['user_id'] ?? 0);
            if ($userId && $userId !== $_SESSION['user_id']) {
                try {
                    $auth->deleteUser($userId);
                    $message = 'Пользователь успешно удален';
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
            } else {
                $error = 'Нельзя удалить самого себя';
            }
            break;
    }
}

// Получаем список пользователей
try {
    $db = Database::getInstance();
    $users = $db->fetchAll("
        SELECT u.*, 
               COUNT(b.id) as booking_count,
               MAX(b.created_at) as last_booking
        FROM users u
        LEFT JOIN bookings b ON u.id = b.user_id
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
} catch (Exception $e) {
    $users = [];
    $error = 'Ошибка загрузки пользователей: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями - Book Smeta</title>
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
                            <a class="nav-link active text-white" href="users.php">
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
                    <h1 class="h2">Управление пользователями</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-user-plus me-1"></i>
                            Добавить пользователя
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

                <!-- Таблица пользователей -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Список пользователей</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-admin" id="usersTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Email</th>
                                        <th>Имя</th>
                                        <th>Роль</th>
                                        <th>Бронирований</th>
                                        <th>Лимит</th>
                                        <th>Статус</th>
                                        <th>Оплата</th>
                                        <th>Регистрация</th>
                                        <th>Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td><?= $u['id'] ?></td>
                                            <td><?= escape($u['email']) ?></td>
                                            <td><?= escape($u['first_name'] . ' ' . $u['last_name']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $u['role'] === 'super_admin' ? 'danger' : ($u['role'] === 'admin' ? 'warning' : 'info') ?>">
                                                    <?= escape($u['role']) ?>
                                                </span>
                                            </td>
                                            <td><?= $u['booking_count'] ?></td>
                                            <td><?= $u['booking_limit'] ?></td>
                                            <td>
                                                <span class="badge bg-<?= $u['is_active'] ? 'success' : 'danger' ?>">
                                                    <?= $u['is_active'] ? 'Активен' : 'Заблокирован' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $u['is_paid_client'] ? 'success' : 'warning' ?>">
                                                    <?= $u['is_paid_client'] ? 'Оплачено' : 'Не оплачено' ?>
                                                </span>
                                            </td>
                                            <td><?= formatDate($u['created_at']) ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="editUser(<?= htmlspecialchars(json_encode($u)) ?>)" title="Редактировать">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning" onclick="changePassword(<?= $u['id'] ?>, '<?= escape($u['email']) ?>')" title="Изменить пароль">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?= $u['id'] ?>)" title="Удалить">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
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

    <!-- Модальное окно добавления пользователя -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Добавить пользователя</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_user">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Телефон</label>
                                    <input type="tel" class="form-control" id="phone" name="phone">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">Имя *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Фамилия *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">Роль</label>
                                    <select class="form-select" id="role" name="role">
                                        <option value="user">Пользователь</option>
                                        <option value="admin">Администратор</option>
                                        <option value="super_admin">Супер администратор</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="booking_limit" class="form-label">Лимит бронирований</label>
                                    <input type="number" class="form-control" id="booking_limit" name="booking_limit" value="3" min="1" max="100">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_paid_client" name="is_paid_client">
                                    <label class="form-check-label" for="is_paid_client">
                                        Оплативший клиент
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">
                                        Активный пользователь
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-primary">Добавить пользователя</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно редактирования пользователя -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Редактировать пользователя</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_user">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="edit_email" readonly>
                                    <div class="form-text">Email нельзя изменить</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_phone" class="form-label">Телефон</label>
                                    <input type="tel" class="form-control" id="edit_phone" name="phone">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_first_name" class="form-label">Имя *</label>
                                    <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_last_name" class="form-label">Фамилия *</label>
                                    <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_role" class="form-label">Роль</label>
                                    <select class="form-select" id="edit_role" name="role">
                                        <option value="user">Пользователь</option>
                                        <option value="admin">Администратор</option>
                                        <option value="super_admin">Супер администратор</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_booking_limit" class="form-label">Лимит бронирований</label>
                                    <input type="number" class="form-control" id="edit_booking_limit" name="booking_limit" min="1" max="100">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_is_paid_client" name="is_paid_client">
                                    <label class="form-check-label" for="edit_is_paid_client">
                                        Оплативший клиент
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
                                    <label class="form-check-label" for="edit_is_active">
                                        Активный пользователь
                                    </label>
                                </div>
                            </div>
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

    <!-- Модальное окно смены пароля -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Изменить пароль</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="change_password">
                        <input type="hidden" name="user_id" id="change_password_user_id">
                        
                        <div class="mb-3">
                            <label for="change_password_email" class="form-label">Email пользователя</label>
                            <input type="email" class="form-control" id="change_password_email" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Новый пароль *</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                            <div class="form-text">Минимум 6 символов</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Подтвердите пароль *</label>
                            <input type="password" class="form-control" id="confirm_password" required minlength="6">
                            <div class="form-text" id="password-match"></div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Внимание!</strong> После изменения пароля пользователю нужно будет войти в систему заново.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-warning" id="change-password-btn" disabled>
                            <i class="fas fa-key me-1"></i>
                            Изменить пароль
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Модальное окно подтверждения удаления -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Подтверждение удаления</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Вы действительно хотите удалить этого пользователя?</p>
                    <p class="text-danger"><strong>Это действие нельзя отменить!</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" id="delete_user_id">
                        <button type="submit" class="btn btn-danger">Удалить</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../public/assets/js/app.js"></script>
    <script>
        // Редактирование пользователя
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_phone').value = user.phone || '';
            document.getElementById('edit_first_name').value = user.first_name;
            document.getElementById('edit_last_name').value = user.last_name;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_booking_limit').value = user.booking_limit;
            document.getElementById('edit_is_paid_client').checked = user.is_paid_client == 1;
            document.getElementById('edit_is_active').checked = user.is_active == 1;
            
            const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
            modal.show();
        }
        
        // Смена пароля пользователя
        function changePassword(userId, email) {
            document.getElementById('change_password_user_id').value = userId;
            document.getElementById('change_password_email').value = email;
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';
            document.getElementById('change-password-btn').disabled = true;
            document.getElementById('password-match').textContent = '';
            
            const modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
            modal.show();
        }
        
        // Удаление пользователя
        function deleteUser(userId) {
            document.getElementById('delete_user_id').value = userId;
            const modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
            modal.show();
        }
        
        // Проверка совпадения паролей
        document.addEventListener('DOMContentLoaded', function() {
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            const passwordMatch = document.getElementById('password-match');
            const changePasswordBtn = document.getElementById('change-password-btn');
            
            function checkPasswords() {
                const newPass = newPassword.value;
                const confirmPass = confirmPassword.value;
                
                if (newPass.length >= 6 && confirmPass.length >= 6) {
                    if (newPass === confirmPass) {
                        passwordMatch.innerHTML = '<span class="text-success"><i class="fas fa-check me-1"></i>Пароли совпадают</span>';
                        changePasswordBtn.disabled = false;
                    } else {
                        passwordMatch.innerHTML = '<span class="text-danger"><i class="fas fa-times me-1"></i>Пароли не совпадают</span>';
                        changePasswordBtn.disabled = true;
                    }
                } else {
                    passwordMatch.textContent = '';
                    changePasswordBtn.disabled = true;
                }
            }
            
            newPassword.addEventListener('input', checkPasswords);
            confirmPassword.addEventListener('input', checkPasswords);
        });
        
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
