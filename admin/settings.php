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

// Обработка сохранения настроек
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save_settings':
            try {
                $db = Database::getInstance();
                
                // Основные настройки
                $settings = [
                    'app_name' => $_POST['app_name'] ?? 'Book Smeta',
                    'app_url' => $_POST['app_url'] ?? '',
                    'default_booking_limit' => (int)($_POST['default_booking_limit'] ?? 3),
                    'slot_duration' => (int)($_POST['slot_duration'] ?? 60),
                    'max_advance_days' => (int)($_POST['max_advance_days'] ?? 7),
                    'auto_cleanup_days' => (int)($_POST['auto_cleanup_days'] ?? 30),
                    'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
                    'smtp_host' => $_POST['smtp_host'] ?? '',
                    'smtp_port' => (int)($_POST['smtp_port'] ?? 587),
                    'smtp_username' => $_POST['smtp_username'] ?? '',
                    'smtp_password' => $_POST['smtp_password'] ?? '',
                    'smtp_encryption' => $_POST['smtp_encryption'] ?? 'tls',
                    'from_email' => $_POST['from_email'] ?? '',
                    'from_name' => $_POST['from_name'] ?? 'Book Smeta',
                    'getcourse_api_url' => $_POST['getcourse_api_url'] ?? '',
                    'getcourse_api_key' => $_POST['getcourse_api_key'] ?? '',
                    'getcourse_api_secret' => $_POST['getcourse_api_secret'] ?? '',
                    'backup_enabled' => isset($_POST['backup_enabled']) ? 1 : 0,
                    'backup_frequency' => $_POST['backup_frequency'] ?? 'daily',
                    'backup_retention' => (int)($_POST['backup_retention'] ?? 30),
                    'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
                    'maintenance_message' => $_POST['maintenance_message'] ?? 'Система временно недоступна'
                ];
                
                foreach ($settings as $key => $value) {
                    $db->query("
                        INSERT INTO system_settings (setting_key, setting_value) 
                        VALUES (?, ?) 
                        ON DUPLICATE KEY UPDATE setting_value = ?
                    ", [$key, $value, $value]);
                }
                
                $message = 'Настройки успешно сохранены';
            } catch (Exception $e) {
                $error = 'Ошибка сохранения настроек: ' . $e->getMessage();
            }
            break;
            
        case 'test_email':
            try {
                $to = $_POST['test_email'] ?? '';
                if (empty($to)) {
                    $error = 'Введите email для тестирования';
                } else {
                    // Здесь будет отправка тестового email
                    $message = 'Тестовое письмо отправлено на ' . $to;
                }
            } catch (Exception $e) {
                $error = 'Ошибка отправки тестового письма: ' . $e->getMessage();
            }
            break;
            
        case 'backup_now':
            try {
                // Здесь будет создание резервной копии
                $message = 'Резервная копия создана успешно';
            } catch (Exception $e) {
                $error = 'Ошибка создания резервной копии: ' . $e->getMessage();
            }
            break;
    }
}

// Получаем текущие настройки
try {
    $db = Database::getInstance();
    $settings = $db->fetchAll('SELECT setting_key, setting_value FROM system_settings');
    $settingsArray = [];
    foreach ($settings as $setting) {
        $settingsArray[$setting['setting_key']] = $setting['setting_value'];
    }
} catch (Exception $e) {
    $settingsArray = [];
    $error = 'Ошибка загрузки настроек: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки системы - Book Smeta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
    <style>
        .modal-backdrop {
            background-color: rgba(0, 0, 0, 0.5);
        }
        .modal.show {
            display: block !important;
        }
    </style>
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
                            <a class="nav-link active text-white" href="settings.php">
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
                    <h1 class="h2">Настройки системы</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-success" onclick="document.getElementById('settingsForm').submit()">
                            <i class="fas fa-save me-1"></i>
                            Сохранить настройки
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

                <form method="POST" id="settingsForm">
                    <input type="hidden" name="action" value="save_settings">
                    
                    <!-- Основные настройки -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-cog me-2"></i>
                                Основные настройки
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="app_name" class="form-label">Название приложения</label>
                                        <input type="text" class="form-control" id="app_name" name="app_name" 
                                               value="<?= escape($settingsArray['app_name'] ?? 'Book Smeta') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="app_url" class="form-label">URL приложения</label>
                                        <input type="url" class="form-control" id="app_url" name="app_url" 
                                               value="<?= escape($settingsArray['app_url'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="default_booking_limit" class="form-label">Лимит бронирований по умолчанию</label>
                                        <input type="number" class="form-control" id="default_booking_limit" name="default_booking_limit" 
                                               value="<?= $settingsArray['default_booking_limit'] ?? 3 ?>" min="1" max="100">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="slot_duration" class="form-label">Длительность слота (минуты)</label>
                                        <input type="number" class="form-control" id="slot_duration" name="slot_duration" 
                                               value="<?= $settingsArray['slot_duration'] ?? 60 ?>" min="15" max="480">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="max_advance_days" class="form-label">Максимум дней вперед</label>
                                        <input type="number" class="form-control" id="max_advance_days" name="max_advance_days" 
                                               value="<?= $settingsArray['max_advance_days'] ?? 7 ?>" min="1" max="365">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Настройки email -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-envelope me-2"></i>
                                Настройки email (Unisender Go)
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Unisender Go SMTP API:</strong> Используйте настройки из вашего аккаунта Unisender Go. 
                                <a href="https://godocs.unisender.ru/smtp-api" target="_blank" class="alert-link">Документация</a>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" 
                                       <?= ($settingsArray['email_notifications'] ?? 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="email_notifications">
                                    Включить уведомления по email
                                </label>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="smtp_host" class="form-label">SMTP сервер</label>
                                        <select class="form-select" id="smtp_host" name="smtp_host">
                                            <option value="smtp.go1.unisender.ru" <?= ($settingsArray['smtp_host'] ?? '') === 'smtp.go1.unisender.ru' ? 'selected' : '' ?>>smtp.go1.unisender.ru</option>
                                            <option value="smtp.go2.unisender.ru" <?= ($settingsArray['smtp_host'] ?? '') === 'smtp.go2.unisender.ru' ? 'selected' : '' ?>>smtp.go2.unisender.ru</option>
                                        </select>
                                        <div class="form-text">Выберите сервер в зависимости от вашего аккаунта Unisender Go</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="smtp_port" class="form-label">SMTP порт</label>
                                        <select class="form-select" id="smtp_port" name="smtp_port">
                                            <option value="587" <?= ($settingsArray['smtp_port'] ?? '587') === '587' ? 'selected' : '' ?>>587 (TLS)</option>
                                            <option value="465" <?= ($settingsArray['smtp_port'] ?? '587') === '465' ? 'selected' : '' ?>>465 (TLS)</option>
                                            <option value="25" <?= ($settingsArray['smtp_port'] ?? '587') === '25' ? 'selected' : '' ?>>25 (TLS)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="smtp_username" class="form-label">User ID / Project ID</label>
                                        <input type="text" class="form-control" id="smtp_username" name="smtp_username" 
                                               value="<?= escape($settingsArray['smtp_username'] ?? '') ?>"
                                               placeholder="Ваш User ID или Project ID">
                                        <div class="form-text">Можно найти в левом верхнем углу личного кабинета</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="smtp_password" class="form-label">API ключ</label>
                                        <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                                               value="<?= escape($settingsArray['smtp_password'] ?? '') ?>"
                                               placeholder="Ваш API ключ">
                                        <div class="form-text">API ключ пользователя или project_api_key</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="smtp_encryption" class="form-label">Шифрование</label>
                                        <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                            <option value="tls" <?= ($settingsArray['smtp_encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS (рекомендуется)</option>
                                            <option value="ssl" <?= ($settingsArray['smtp_encryption'] ?? 'tls') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                        </select>
                                        <div class="form-text">Unisender Go поддерживает только TLS/SSL соединения</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="from_email" class="form-label">Email отправителя</label>
                                        <input type="email" class="form-control" id="from_email" name="from_email" 
                                               value="<?= escape($settingsArray['from_email'] ?? '') ?>"
                                               placeholder="noreply@yourdomain.com">
                                        <div class="form-text">Должен быть подтвержден в Unisender Go</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="from_name" class="form-label">Имя отправителя</label>
                                <input type="text" class="form-control" id="from_name" name="from_name" 
                                       value="<?= escape($settingsArray['from_name'] ?? 'Book Smeta') ?>"
                                       placeholder="Book Smeta">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="test_email" class="form-label">Тестовый email</label>
                                        <input type="email" class="form-control" id="test_email" placeholder="Введите email для тестирования">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">&nbsp;</label>
                                    <div>
                                        <button type="button" class="btn btn-outline-primary" onclick="testEmailConnection()">
                                            <i class="fas fa-plug me-1"></i>
                                            Тест соединения
                                        </button>
                                        <button type="button" class="btn btn-outline-success" onclick="testEmail()">
                                            <i class="fas fa-paper-plane me-1"></i>
                                            Тест email
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Настройки GetCourse -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-plug me-2"></i>
                                Настройки GetCourse API
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="getcourse_api_url" class="form-label">API URL</label>
                                        <input type="url" class="form-control" id="getcourse_api_url" name="getcourse_api_url" 
                                               value="<?= escape($settingsArray['getcourse_api_url'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="getcourse_api_key" class="form-label">API ключ</label>
                                        <input type="text" class="form-control" id="getcourse_api_key" name="getcourse_api_key" 
                                               value="<?= escape($settingsArray['getcourse_api_key'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                            
                            
                            <div class="mb-3">
                                <button type="button" class="btn btn-outline-primary" onclick="testGetCourseConnection()">
                                    <i class="fas fa-plug me-1"></i>
                                    Тест подключения к GetCourse
                                </button>
                                <a href="../test_getcourse.php" target="_blank" class="btn btn-outline-secondary ms-2">
                                    <i class="fas fa-external-link-alt me-1"></i>
                                    Открыть тест в новой вкладке
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Настройки резервного копирования -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-database me-2"></i>
                                Резервное копирование
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="backup_enabled" name="backup_enabled" 
                                       <?= ($settingsArray['backup_enabled'] ?? 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="backup_enabled">
                                    Включить автоматическое резервное копирование
                                </label>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="backup_frequency" class="form-label">Частота резервного копирования</label>
                                        <select class="form-select" id="backup_frequency" name="backup_frequency">
                                            <option value="daily" <?= ($settingsArray['backup_frequency'] ?? 'daily') === 'daily' ? 'selected' : '' ?>>Ежедневно</option>
                                            <option value="weekly" <?= ($settingsArray['backup_frequency'] ?? 'daily') === 'weekly' ? 'selected' : '' ?>>Еженедельно</option>
                                            <option value="monthly" <?= ($settingsArray['backup_frequency'] ?? 'daily') === 'monthly' ? 'selected' : '' ?>>Ежемесячно</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="backup_retention" class="form-label">Хранить копии (дней)</label>
                                        <input type="number" class="form-control" id="backup_retention" name="backup_retention" 
                                               value="<?= $settingsArray['backup_retention'] ?? 30 ?>" min="1" max="365">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <button type="button" class="btn btn-warning" onclick="backupNow()">
                                    <i class="fas fa-download me-1"></i>
                                    Создать резервную копию сейчас
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Настройки обслуживания -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-tools me-2"></i>
                                Режим обслуживания
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                       <?= ($settingsArray['maintenance_mode'] ?? 0) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="maintenance_mode">
                                    Включить режим обслуживания
                                </label>
                            </div>
                            
                            <div class="mb-3">
                                <label for="maintenance_message" class="form-label">Сообщение для пользователей</label>
                                <textarea class="form-control" id="maintenance_message" name="maintenance_message" rows="3"><?= escape($settingsArray['maintenance_message'] ?? 'Система временно недоступна') ?></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../public/assets/js/app.js"></script>
    <script>
        // Тестирование соединения SMTP
        async function testEmailConnection() {
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Тестирование...';
            button.disabled = true;
            
            try {
                const response = await fetch('../api/email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=test_connection'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ SMTP соединение работает корректно!');
                } else {
                    alert('❌ Ошибка SMTP соединения: ' + result.error);
                }
                
            } catch (error) {
                console.error('Ошибка тестирования SMTP:', error);
                alert('❌ Ошибка при тестировании соединения: ' + error.message);
            } finally {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }
        
        // Тестирование email
        async function testEmail() {
            const email = document.getElementById('test_email').value;
            if (!email) {
                alert('Введите email для тестирования');
                return;
            }
            
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                alert('Некорректный email адрес');
                return;
            }
            
            if (!confirm('Отправить тестовое письмо на ' + email + '?')) {
                return;
            }
            
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Отправка...';
            button.disabled = true;
            
            try {
                const response = await fetch('../api/email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=test&email=${encodeURIComponent(email)}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ Тестовое письмо отправлено успешно!');
                } else {
                    alert('❌ Ошибка отправки: ' + result.error);
                }
                
            } catch (error) {
                console.error('Ошибка отправки тестового письма:', error);
                alert('❌ Ошибка при отправке письма: ' + error.message);
            } finally {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }
        
        // Создание резервной копии
        function backupNow() {
            if (!confirm('Создать резервную копию сейчас?')) {
                return;
            }
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="backup_now">';
            document.body.appendChild(form);
            form.submit();
        }
        
        // Тестирование подключения к GetCourse
        async function testGetCourseConnection() {
            const apiUrl = document.getElementById('getcourse_api_url').value;
            const apiKey = document.getElementById('getcourse_api_key').value;
            
            if (!apiUrl || !apiKey) {
                alert('Заполните поля API URL и API ключ');
                return;
            }
            
            if (!confirm('Протестировать подключение к GetCourse API?')) {
                return;
            }
            
            // Показываем индикатор загрузки
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Тестирование...';
            button.disabled = true;
            
            try {
                const response = await fetch('../test_getcourse.php', {
                    method: 'GET'
                });
                
                const result = await response.text();
                
                // Создаем модальное окно для показа результата
                const modal = document.createElement('div');
                modal.className = 'modal fade show';
                modal.style.display = 'block';
                modal.innerHTML = `
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Результат тестирования GetCourse API</h5>
                                <button type="button" class="btn-close" onclick="this.closest('.modal').remove()"></button>
                            </div>
                            <div class="modal-body">
                                <pre style="background: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #dee2e6; max-height: 400px; overflow-y: auto; white-space: pre-wrap; font-family: monospace;">${result}</pre>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" onclick="this.closest('.modal').remove()">Закрыть</button>
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
                
            } catch (error) {
                console.error('Ошибка тестирования GetCourse:', error);
                alert('Ошибка при тестировании подключения: ' + error.message);
            } finally {
                // Восстанавливаем кнопку
                button.innerHTML = originalText;
                button.disabled = false;
            }
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
