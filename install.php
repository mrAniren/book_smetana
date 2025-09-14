<?php

/**
 * Скрипт установки системы Book Smeta
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Установка Book Smeta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-cog me-2"></i>
                            Установка Book Smeta
                        </h3>
                    </div>
                    <div class="card-body">
                        
                        <?php
                        
                        $step = $_GET['step'] ?? 1;
                        $errors = [];
                        $success = [];
                        
                        // Проверка требований
                        if ($step == 1) {
                            echo '<h4>Шаг 1: Проверка требований</h4>';
                            
                            // Проверка PHP версии
                            if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
                                echo '<div class="alert alert-success">✓ PHP версия: ' . PHP_VERSION . '</div>';
                            } else {
                                echo '<div class="alert alert-danger">✗ PHP версия ' . PHP_VERSION . ' (требуется 7.4+)</div>';
                                $errors[] = 'Неверная версия PHP';
                            }
                            
                            // Проверка расширений
                            $requiredExtensions = ['pdo', 'pdo_mysql', 'curl', 'json', 'openssl'];
                            foreach ($requiredExtensions as $ext) {
                                if (extension_loaded($ext)) {
                                    echo '<div class="alert alert-success">✓ Расширение ' . $ext . ' установлено</div>';
                                } else {
                                    echo '<div class="alert alert-danger">✗ Расширение ' . $ext . ' не установлено</div>';
                                    $errors[] = 'Отсутствует расширение: ' . $ext;
                                }
                            }
                            
                            // Проверка прав на запись
                            $writableDirs = ['logs', 'config'];
                            foreach ($writableDirs as $dir) {
                                if (is_writable($dir)) {
                                    echo '<div class="alert alert-success">✓ Директория ' . $dir . ' доступна для записи</div>';
                                } else {
                                    echo '<div class="alert alert-warning">⚠ Директория ' . $dir . ' недоступна для записи</div>';
                                    $errors[] = 'Нет прав записи в директорию: ' . $dir;
                                }
                            }
                            
                            if (empty($errors)) {
                                echo '<div class="alert alert-success"><strong>Все требования выполнены!</strong></div>';
                                echo '<a href="?step=2" class="btn btn-primary">Продолжить</a>';
                            } else {
                                echo '<div class="alert alert-danger"><strong>Исправьте ошибки перед продолжением</strong></div>';
                            }
                        }
                        
                        // Настройка базы данных
                        if ($step == 2) {
                            echo '<h4>Шаг 2: Настройка базы данных</h4>';
                            
                            if ($_POST) {
                                $dbHost = $_POST['db_host'] ?? 'localhost';
                                $dbName = $_POST['db_name'] ?? 'book_smeta';
                                $dbUser = $_POST['db_user'] ?? 'root';
                                $dbPass = $_POST['db_password'] ?? '';
                                
                                try {
                                    $pdo = new PDO("mysql:host={$dbHost}", $dbUser, $dbPass);
                                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                                    
                                    // Создаем базу данных
                                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                                    $pdo->exec("USE `{$dbName}`");
                                    
                                    // Импортируем схему
                                    $schema = file_get_contents('database/schema.sql');
                                    $pdo->exec($schema);
                                    
                                    // Сохраняем настройки
                                    $envContent = file_get_contents('env.example');
                                    $envContent = str_replace('DB_HOST=localhost', "DB_HOST={$dbHost}", $envContent);
                                    $envContent = str_replace('DB_NAME=book_smeta', "DB_NAME={$dbName}", $envContent);
                                    $envContent = str_replace('DB_USER=root', "DB_USER={$dbUser}", $envContent);
                                    $envContent = str_replace('DB_PASSWORD=', "DB_PASSWORD={$dbPass}", $envContent);
                                    
                                    file_put_contents('.env', $envContent);
                                    
                                    echo '<div class="alert alert-success">База данных настроена успешно!</div>';
                                    echo '<a href="?step=3" class="btn btn-primary">Продолжить</a>';
                                    
                                } catch (PDOException $e) {
                                    echo '<div class="alert alert-danger">Ошибка подключения к базе данных: ' . $e->getMessage() . '</div>';
                                }
                            } else {
                                ?>
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="db_host" class="form-label">Хост базы данных</label>
                                        <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="db_name" class="form-label">Имя базы данных</label>
                                        <input type="text" class="form-control" id="db_name" name="db_name" value="book_smeta" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="db_user" class="form-label">Пользователь базы данных</label>
                                        <input type="text" class="form-control" id="db_user" name="db_user" value="root" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="db_password" class="form-label">Пароль базы данных</label>
                                        <input type="password" class="form-control" id="db_password" name="db_password">
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">Настроить базу данных</button>
                                </form>
                                <?php
                            }
                        }
                        
                        // Настройка приложения
                        if ($step == 3) {
                            echo '<h4>Шаг 3: Настройка приложения</h4>';
                            
                            if ($_POST) {
                                $appName = $_POST['app_name'] ?? 'Book Smeta';
                                $appUrl = $_POST['app_url'] ?? 'http://localhost/Book_smeta';
                                $adminEmail = $_POST['admin_email'] ?? 'admin@book-smeta.com';
                                $adminPassword = $_POST['admin_password'] ?? '';
                                $getcourseAccount = $_POST['getcourse_account'] ?? '';
                                $getcourseKey = $_POST['getcourse_key'] ?? '';
                                
                                // Обновляем .env файл
                                $envContent = file_get_contents('.env');
                                $envContent = str_replace('APP_NAME="Book Smeta"', "APP_NAME=\"{$appName}\"", $envContent);
                                $envContent = str_replace('APP_URL=http://localhost/Book_smeta', "APP_URL={$appUrl}", $envContent);
                                $envContent = str_replace('GETCOURSE_ACCOUNT=your_account_name', "GETCOURSE_ACCOUNT={$getcourseAccount}", $envContent);
                                $envContent = str_replace('GETCOURSE_SECRET_KEY=your_secret_key', "GETCOURSE_SECRET_KEY={$getcourseKey}", $envContent);
                                
                                file_put_contents('.env', $envContent);
                                
                                // Обновляем пароль администратора
                                if (!empty($adminPassword)) {
                                    $db = new PDO("mysql:host={$_POST['db_host']};dbname={$_POST['db_name']}", $_POST['db_user'], $_POST['db_password']);
                                    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
                                    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
                                    $stmt->execute([$hashedPassword, $adminEmail]);
                                }
                                
                                echo '<div class="alert alert-success">Настройки приложения сохранены!</div>';
                                echo '<a href="?step=4" class="btn btn-primary">Завершить установку</a>';
                                
                            } else {
                                ?>
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="app_name" class="form-label">Название приложения</label>
                                        <input type="text" class="form-control" id="app_name" name="app_name" value="Book Smeta" required>
                                    </div>
                                    
                                    <?php
                                    $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']);
                                    $currentUrl = rtrim($currentUrl, '/');
                                    ?>
                                    <div class="mb-3">
                                        <label for="app_url" class="form-label">URL приложения</label>
                                        <input type="url" class="form-control" id="app_url" name="app_url" value="<?= $currentUrl ?>">
                                        <div class="form-text">Автоматически определен текущий URL. Можете изменить при необходимости.</div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="admin_email" class="form-label">Email администратора</label>
                                        <input type="email" class="form-control" id="admin_email" name="admin_email" value="admin@book-smeta.com" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="admin_password" class="form-label">Пароль администратора</label>
                                        <input type="password" class="form-control" id="admin_password" name="admin_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="getcourse_account" class="form-label">Аккаунт GetCourse</label>
                                        <input type="text" class="form-control" id="getcourse_account" name="getcourse_account" placeholder="your-account">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="getcourse_key" class="form-label">Секретный ключ GetCourse</label>
                                        <input type="password" class="form-control" id="getcourse_key" name="getcourse_key">
                                    </div>
                                    
                                    <input type="hidden" name="db_host" value="<?= $_GET['db_host'] ?? 'localhost' ?>">
                                    <input type="hidden" name="db_name" value="<?= $_GET['db_name'] ?? 'book_smeta' ?>">
                                    <input type="hidden" name="db_user" value="<?= $_GET['db_user'] ?? 'root' ?>">
                                    <input type="hidden" name="db_password" value="<?= $_GET['db_password'] ?? '' ?>">
                                    
                                    <button type="submit" class="btn btn-primary">Сохранить настройки</button>
                                </form>
                                <?php
                            }
                        }
                        
                        // Завершение установки
                        if ($step == 4) {
                            echo '<h4>Установка завершена!</h4>';
                            echo '<div class="alert alert-success">';
                            echo '<h5>Поздравляем! Система Book Smeta установлена и готова к использованию.</h5>';
                            echo '<ul>';
                            echo '<li>База данных настроена</li>';
                            echo '<li>Конфигурационные файлы созданы</li>';
                            echo '<li>Администратор создан</li>';
                            echo '</ul>';
                            echo '</div>';
                            
                            echo '<div class="alert alert-info">';
                            echo '<h6>Следующие шаги:</h6>';
                            echo '<ol>';
                            echo '<li>Настройте cron задачи для автоматизации</li>';
                            echo '<li>Настройте webhook в GetCourse</li>';
                            echo '<li>Проверьте работу системы</li>';
                            echo '</ol>';
                            echo '</div>';
                            
                            echo '<div class="d-grid gap-2">';
                            echo '<a href="../public/" class="btn btn-success btn-lg">Перейти к системе</a>';
                            echo '<a href="../admin/" class="btn btn-outline-primary">Панель администратора</a>';
                            echo '</div>';
                            
                            echo '<div class="mt-4">';
                            echo '<h6>Важно!</h6>';
                            echo '<p>Удалите файл <code>install.php</code> после завершения установки в целях безопасности.</p>';
                            echo '</div>';
                        }
                        
                        ?>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
