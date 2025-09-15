<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 ИСПРАВЛЕНИЕ ПРАВ ДОСТУПА К ЛОГАМ</h1>";

// Проверяем текущую директорию
echo "<h2>Текущая директория:</h2>";
echo getcwd() . "<br>";

// Проверяем существование директории logs
echo "<h2>Проверка директории logs:</h2>";
if (is_dir('logs')) {
    echo "✅ Директория logs существует<br>";
} else {
    echo "❌ Директория logs не существует<br>";
    echo "Создаем директорию logs...<br>";
    
    if (mkdir('logs', 0755, true)) {
        echo "✅ Директория logs создана<br>";
    } else {
        echo "❌ Не удалось создать директорию logs<br>";
    }
}

// Проверяем права доступа
echo "<h2>Проверка прав доступа:</h2>";
if (is_dir('logs')) {
    $perms = fileperms('logs');
    echo "Права на директорию logs: " . substr(sprintf('%o', $perms), -4) . "<br>";
    
    if (is_writable('logs')) {
        echo "✅ Директория logs доступна для записи<br>";
    } else {
        echo "❌ Директория logs НЕ доступна для записи<br>";
        echo "Пытаемся исправить права...<br>";
        
        if (chmod('logs', 0755)) {
            echo "✅ Права изменены на 755<br>";
        } else {
            echo "❌ Не удалось изменить права<br>";
        }
    }
}

// Создаем файлы логов
echo "<h2>Создание файлов логов:</h2>";
$logFiles = ['error.log', 'info.log'];

foreach ($logFiles as $logFile) {
    $logPath = 'logs/' . $logFile;
    
    if (file_exists($logPath)) {
        echo "✅ $logFile уже существует<br>";
    } else {
        echo "Создаем $logFile...<br>";
        
        if (file_put_contents($logPath, "Log file created at " . date('Y-m-d H:i:s') . "\n")) {
            echo "✅ $logFile создан<br>";
            
            // Устанавливаем права на файл
            if (chmod($logPath, 0644)) {
                echo "✅ Права на $logFile установлены (644)<br>";
            } else {
                echo "⚠️ Не удалось установить права на $logFile<br>";
            }
        } else {
            echo "❌ Не удалось создать $logFile<br>";
        }
    }
}

// Проверяем запись в лог
echo "<h2>Тест записи в лог:</h2>";
$testMessage = "Test log entry at " . date('Y-m-d H:i:s') . "\n";

if (file_put_contents('logs/info.log', $testMessage, FILE_APPEND | LOCK_EX)) {
    echo "✅ Запись в info.log работает<br>";
} else {
    echo "❌ Запись в info.log НЕ работает<br>";
}

if (file_put_contents('logs/error.log', $testMessage, FILE_APPEND | LOCK_EX)) {
    echo "✅ Запись в error.log работает<br>";
} else {
    echo "❌ Запись в error.log НЕ работает<br>";
}

// Проверяем права на корневую директорию
echo "<h2>Проверка прав на корневую директорию:</h2>";
$rootPerms = fileperms('.');
echo "Права на корневую директорию: " . substr(sprintf('%o', $rootPerms), -4) . "<br>";

if (is_writable('.')) {
    echo "✅ Корневая директория доступна для записи<br>";
} else {
    echo "❌ Корневая директория НЕ доступна для записи<br>";
}

// Информация о системе
echo "<h2>Информация о системе:</h2>";
echo "PHP версия: " . PHP_VERSION . "<br>";
echo "Пользователь: " . get_current_user() . "<br>";
echo "Группа: " . (function_exists('posix_getgrgid') ? posix_getgrgid(posix_getgid())['name'] : 'Неизвестно') . "<br>";

echo "<h1 style='color: green;'>🎉 ПРОВЕРКА ЗАВЕРШЕНА!</h1>";
echo "<p><a href='install.php'>Вернуться к установке</a></p>";
?>
