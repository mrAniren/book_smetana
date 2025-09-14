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
$message = '';
$error = '';

// Обработка изменения пароля
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'Все поля обязательны для заполнения';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Новые пароли не совпадают';
    } elseif (strlen($newPassword) < 8) {
        $error = 'Новый пароль должен содержать минимум 8 символов';
    } else {
        try {
            $auth->changePassword($_SESSION['user_id'], $currentPassword, $newPassword);
            $message = 'Пароль успешно изменен';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Обработка изменения профиля
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($firstName) || empty($lastName)) {
        $error = 'Имя и фамилия обязательны для заполнения';
    } else {
        try {
            $auth->updateProfile($_SESSION['user_id'], [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone
            ]);
            $message = 'Профиль успешно обновлен';
            // Обновляем данные в сессии
            $_SESSION['user_name'] = $firstName . ' ' . $lastName;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль - Book Smeta</title>
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
                            <li><a class="dropdown-item active" href="profile.php">Профиль</a></li>
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
                <h2 class="mb-4">
                    <i class="fas fa-user me-2"></i>
                    Профиль пользователя
                </h2>
                
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
                
                <div class="row">
                    <!-- Информация о профиле -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Информация о профиле
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" value="<?= escape($user['email']) ?>" readonly>
                                        <div class="form-text">Email нельзя изменить</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label">Имя *</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?= escape($user['first_name']) ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label">Фамилия *</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?= escape($user['last_name']) ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Телефон</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?= escape($user['phone']) ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Роль</label>
                                        <input type="text" class="form-control" value="<?= escape($user['role']) ?>" readonly>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>
                                        Сохранить изменения
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Статистика и настройки -->
                    <div class="col-md-6">
                        <!-- Статистика бронирований -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>
                                    Статистика бронирований
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <h4 class="text-primary"><?= $user['booking_count'] ?></h4>
                                        <small class="text-muted">Использовано</small>
                                    </div>
                                    <div class="col-6">
                                        <h4 class="text-success"><?= $user['booking_limit'] ?></h4>
                                        <small class="text-muted">Лимит</small>
                                    </div>
                                </div>
                                <div class="progress mt-3">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?= ($user['booking_count'] / $user['booking_limit']) * 100 ?>%">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Смена пароля -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-lock me-2"></i>
                                    Смена пароля
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="password-form">
                                    <input type="hidden" name="action" value="change_password">
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Текущий пароль *</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">Новый пароль *</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" 
                                               minlength="8" required>
                                        <div class="form-text">Минимум 8 символов</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Подтверждение пароля *</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                               minlength="8" required>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-key me-1"></i>
                                        Изменить пароль
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Дополнительная информация -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Дополнительная информация
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Дата регистрации:</strong> <?= formatDate($user['created_at']) ?></p>
                                        <p><strong>Последний вход:</strong> <?= $user['last_login'] ? formatDate($user['last_login']) : 'Никогда' ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Статус оплаты:</strong> 
                                            <?php if ($user['is_paid_client']): ?>
                                                <span class="badge bg-success">Оплачено</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Не оплачено</span>
                                            <?php endif; ?>
                                        </p>
                                        <p><strong>Статус аккаунта:</strong> 
                                            <?php if ($user['is_active']): ?>
                                                <span class="badge bg-success">Активен</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Заблокирован</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
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
        // Загружаем уведомления при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            loadNotificationsCount();
        });
        
        // Валидация формы смены пароля
        document.getElementById('password-form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Новые пароли не совпадают');
                return false;
            }
            
            if (newPassword.length < 8) {
                e.preventDefault();
                alert('Новый пароль должен содержать минимум 8 символов');
                return false;
            }
        });
    </script>
</body>
</html>
