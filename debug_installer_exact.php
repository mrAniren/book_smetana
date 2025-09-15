<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 ТОЧНАЯ ОТЛАДКА УСТАНОВЩИКА</h2>";

// Данные из формы (как в установщике)
$dbHost = 'localhost';
$dbName = 'martin9a_book';
$dbUser = 'martin9a_book';
$dbPass = 'ваш_пароль_здесь'; // ЗАМЕНИТЕ НА РЕАЛЬНЫЙ ПАРОЛЬ

echo "<h3>Данные подключения:</h3>";
echo "Хост: $dbHost<br>";
echo "БД: $dbName<br>";
echo "Пользователь: $dbUser<br>";
echo "Пароль: " . (strlen($dbPass) > 0 ? "УКАЗАН (" . strlen($dbPass) . " символов)" : "НЕ УКАЗАН") . "<br><br>";

// Тест 1: Подключение как в установщике (точная копия кода)
echo "<h3>Тест 1: Подключение как в установщике</h3>";

try {
    $pdo = new PDO("mysql:host={$dbHost}", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Подключение к MySQL успешно<br>";
    
    // Проверяем существование БД
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbName'");
    if ($stmt->rowCount() > 0) {
        echo "✅ БД '$dbName' существует<br>";
    } else {
        echo "❌ БД '$dbName' НЕ существует<br>";
    }
    
    // Создаем БД (как в установщике)
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ БД создана/проверена<br>";
    
    // Переключаемся на БД
    $pdo->exec("USE `{$dbName}`");
    echo "✅ Переключение на БД '$dbName' успешно<br>";
    
    // Проверяем таблицы
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Таблиц в БД: " . count($tables) . "<br>";
    if (count($tables) > 0) {
        echo "Таблицы: " . implode(', ', $tables) . "<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Ошибка подключения: " . $e->getMessage() . "<br>";
    echo "Код ошибки: " . $e->getCode() . "<br>";
    exit;
}

// Тест 2: Импорт схемы (точная копия кода установщика)
echo "<h3>Тест 2: Импорт схемы</h3>";

if (file_exists('database/schema.sql')) {
    echo "✅ Файл schema.sql существует<br>";
    $schema = file_get_contents('database/schema.sql');
    echo "Размер схемы: " . strlen($schema) . " символов<br>";
    
    try {
        // Выполняем схему
        $pdo->exec($schema);
        echo "✅ Схема выполнена успешно<br>";
        
        // Проверяем таблицы после импорта
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Таблиц после импорта: " . count($tables) . "<br>";
        if (count($tables) > 0) {
            echo "Таблицы: " . implode(', ', $tables) . "<br>";
        }
        
    } catch (PDOException $e) {
        echo "❌ Ошибка выполнения схемы: " . $e->getMessage() . "<br>";
        echo "Код ошибки: " . $e->getCode() . "<br>";
        
        // Показываем часть схемы для анализа
        echo "<h4>Первые 500 символов схемы:</h4>";
        echo "<pre>" . htmlspecialchars(substr($schema, 0, 500)) . "</pre>";
    }
} else {
    echo "❌ Файл schema.sql не найден<br>";
}

// Тест 3: Создание .env файла (точная копия кода установщика)
echo "<h3>Тест 3: Создание .env файла</h3>";

if (file_exists('documents/env.example')) {
    echo "✅ Файл env.example существует<br>";
    
    try {
        $envContent = file_get_contents('documents/env.example');
        $envContent = str_replace('DB_HOST=localhost', "DB_HOST={$dbHost}", $envContent);
        $envContent = str_replace('DB_NAME=book_smeta', "DB_NAME={$dbName}", $envContent);
        $envContent = str_replace('DB_USER=root', "DB_USER={$dbUser}", $envContent);
        $envContent = str_replace('DB_PASSWORD=', "DB_PASSWORD={$dbPass}", $envContent);
        
        file_put_contents('.env', $envContent);
        echo "✅ .env файл создан<br>";
        
        // Проверяем содержимое .env
        $envContent = file_get_contents('.env');
        echo "<h4>Содержимое .env:</h4>";
        echo "<pre>" . htmlspecialchars($envContent) . "</pre>";
        
    } catch (Exception $e) {
        echo "❌ Ошибка создания .env: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Файл documents/env.example не найден<br>";
}

echo "<br>🎉 ТОЧНАЯ ОТЛАДКА ЗАВЕРШЕНА!";
?>
