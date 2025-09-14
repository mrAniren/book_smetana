<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 ПОЛНЫЙ АУДИТ ПРОЕКТА</h1>";

// 1. Проверка структуры файлов
echo "<h2>1. СТРУКТУРА ФАЙЛОВ</h2>";
$requiredFiles = [
    'install.php' => 'Установщик',
    'public/index.php' => 'Главная страница',
    'admin/index.php' => 'Админ панель',
    'api/auth.php' => 'API авторизации',
    'api/bookings.php' => 'API бронирований',
    'includes/Auth.php' => 'Класс авторизации',
    'includes/Database.php' => 'Класс базы данных',
    'includes/functions.php' => 'Функции системы',
    'config/database.php' => 'Конфигурация БД',
    'database/schema.sql' => 'Схема БД',
    'documents/env.example' => 'Пример .env'
];

foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        echo "✅ $description ($file)<br>";
    } else {
        echo "❌ ОТСУТСТВУЕТ: $description ($file)<br>";
    }
}

// 2. Проверка PHP совместимости
echo "<h2>2. PHP СОВМЕСТИМОСТЬ</h2>";
echo "PHP версия: " . PHP_VERSION . "<br>";

// Проверяем использование современных функций
$problematicFiles = [];
$searchPatterns = [
    'password_hash' => 'Не поддерживается в PHP < 5.5',
    'password_verify' => 'Не поддерживается в PHP < 5.5',
    '??' => 'Не поддерживается в PHP < 7.0',
    '?->' => 'Не поддерживается в PHP < 8.0',
    'match' => 'Не поддерживается в PHP < 8.0'
];

foreach ($searchPatterns as $pattern => $description) {
    echo "<h3>Поиск: $pattern ($description)</h3>";
    
    $files = glob('*.php');
    $files = array_merge($files, glob('*/*.php'));
    $files = array_merge($files, glob('*/*/*.php'));
    
    foreach ($files as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if (strpos($content, $pattern) !== false) {
                echo "⚠️ Найден в: $file<br>";
                $problematicFiles[$file][] = $description;
            }
        }
    }
}

// 3. Проверка конфигурации БД
echo "<h2>3. КОНФИГУРАЦИЯ БД</h2>";

// Проверяем database.php
if (file_exists('config/database.php')) {
    $content = file_get_contents('config/database.php');
    
    // Проверяем использование $_ENV
    if (strpos($content, '$_ENV') !== false) {
        echo "✅ Использует \$_ENV для конфигурации<br>";
    } else {
        echo "❌ НЕ использует \$_ENV<br>";
    }
    
    // Проверяем обработку ошибок
    if (strpos($content, 'PDO::ERRMODE_EXCEPTION') !== false) {
        echo "✅ Правильная обработка ошибок PDO<br>";
    } else {
        echo "❌ НЕПРАВИЛЬНАЯ обработка ошибок PDO<br>";
    }
}

// 4. Проверка Auth.php
echo "<h2>4. ПРОВЕРКА AUTH.PHP</h2>";
if (file_exists('includes/Auth.php')) {
    $content = file_get_contents('includes/Auth.php');
    
    // Проверяем поля БД
    $dbFields = [
        'password_hash' => 'Должно быть password',
        'first_name' => 'Должно быть full_name',
        'last_name' => 'Должно быть full_name',
        'is_paid_client' => 'Может отсутствовать в БД',
        'email_verified' => 'Может отсутствовать в БД'
    ];
    
    foreach ($dbFields as $field => $issue) {
        if (strpos($content, $field) !== false) {
            echo "⚠️ ПРОБЛЕМА: $field - $issue<br>";
        }
    }
    
    // Проверяем методы
    $methods = ['login', 'register', 'logout', 'isLoggedIn', 'isAdmin'];
    foreach ($methods as $method) {
        if (strpos($content, "function $method") !== false || strpos($content, "public function $method") !== false) {
            echo "✅ Метод $method существует<br>";
        } else {
            echo "❌ Метод $method отсутствует<br>";
        }
    }
}

// 5. Проверка API endpoints
echo "<h2>5. ПРОВЕРКА API ENDPOINTS</h2>";
$apiFiles = glob('api/*.php');
foreach ($apiFiles as $file) {
    $filename = basename($file);
    echo "<h3>$filename</h3>";
    
    $content = file_get_contents($file);
    
    // Проверяем заголовки
    if (strpos($content, 'Content-Type: application/json') !== false) {
        echo "✅ Правильные заголовки JSON<br>";
    } else {
        echo "❌ НЕПРАВИЛЬНЫЕ заголовки<br>";
    }
    
    // Проверяем обработку ошибок
    if (strpos($content, 'try {') !== false && strpos($content, 'catch') !== false) {
        echo "✅ Есть обработка ошибок<br>";
    } else {
        echo "❌ НЕТ обработки ошибок<br>";
    }
    
    // Проверяем CSRF защиту
    if (strpos($content, 'csrf') !== false || strpos($content, 'CSRF') !== false) {
        echo "✅ Есть CSRF защита<br>";
    } else {
        echo "⚠️ НЕТ CSRF защиты<br>";
    }
}

// 6. Проверка админ панели
echo "<h2>6. ПРОВЕРКА АДМИН ПАНЕЛИ</h2>";
$adminFiles = glob('admin/*.php');
foreach ($adminFiles as $file) {
    $filename = basename($file);
    echo "<h3>$filename</h3>";
    
    $content = file_get_contents($file);
    
    // Проверяем авторизацию
    if (strpos($content, 'isAdmin()') !== false || strpos($content, 'isLoggedIn()') !== false) {
        echo "✅ Есть проверка авторизации<br>";
    } else {
        echo "❌ НЕТ проверки авторизации<br>";
    }
    
    // Проверяем перенаправления
    if (strpos($content, 'header(') !== false) {
        echo "✅ Есть перенаправления<br>";
    } else {
        echo "⚠️ НЕТ перенаправлений<br>";
    }
}

// 7. Проверка public секции
echo "<h2>7. ПРОВЕРКА PUBLIC СЕКЦИИ</h2>";
$publicFiles = glob('public/*.php');
foreach ($publicFiles as $file) {
    $filename = basename($file);
    echo "<h3>$filename</h3>";
    
    $content = file_get_contents($file);
    
    // Проверяем Bootstrap
    if (strpos($content, 'bootstrap') !== false) {
        echo "✅ Использует Bootstrap<br>";
    } else {
        echo "⚠️ НЕ использует Bootstrap<br>";
    }
    
    // Проверяем JavaScript
    if (strpos($content, '<script') !== false) {
        echo "✅ Есть JavaScript<br>";
    } else {
        echo "⚠️ НЕТ JavaScript<br>";
    }
}

// 8. Проверка .htaccess
echo "<h2>8. ПРОВЕРКА .HTACCESS</h2>";
if (file_exists('.htaccess')) {
    echo "✅ .htaccess существует<br>";
    $content = file_get_contents('.htaccess');
    
    if (strpos($content, 'RewriteEngine') !== false) {
        echo "✅ URL rewriting включен<br>";
    } else {
        echo "❌ URL rewriting НЕ включен<br>";
    }
} else {
    echo "❌ .htaccess отсутствует<br>";
}

// 9. Проверка composer
echo "<h2>9. ПРОВЕРКА COMPOSER</h2>";
if (file_exists('composer.json')) {
    echo "✅ composer.json существует<br>";
    $content = file_get_contents('composer.json');
    $data = json_decode($content, true);
    
    if (isset($data['require'])) {
        echo "Зависимости:<br>";
        foreach ($data['require'] as $package => $version) {
            echo "- $package: $version<br>";
        }
    }
} else {
    echo "❌ composer.json отсутствует<br>";
}

// 10. Итоговая сводка проблем
echo "<h2>10. ИТОГОВАЯ СВОДКА ПРОБЛЕМ</h2>";

if (!empty($problematicFiles)) {
    echo "<h3 style='color: red;'>🚨 КРИТИЧЕСКИЕ ПРОБЛЕМЫ:</h3>";
    foreach ($problematicFiles as $file => $problems) {
        echo "<strong>$file:</strong><br>";
        foreach ($problems as $problem) {
            echo "- $problem<br>";
        }
        echo "<br>";
    }
} else {
    echo "<h3 style='color: green;'>✅ Критических проблем не найдено</h3>";
}

echo "<h3>📋 РЕКОМЕНДАЦИИ ДЛЯ СЕРВЕРА:</h3>";
echo "<ul>";
echo "<li>Убедитесь что PHP версия >= 7.4</li>";
echo "<li>Установите все необходимые расширения (PDO, MySQL, CURL, JSON)</li>";
echo "<li>Настройте права доступа к файлам (755 для директорий, 644 для файлов)</li>";
echo "<li>Создайте .htaccess для URL rewriting</li>";
echo "<li>Настройте базу данных MySQL</li>";
echo "<li>Запустите install.php для настройки системы</li>";
echo "</ul>";

echo "<h1 style='color: green;'>🎉 АУДИТ ЗАВЕРШЕН!</h1>";
?>
