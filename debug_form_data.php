<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 ДИАГНОСТИКА ДАННЫХ ФОРМЫ</h2>";

echo "<h3>POST данные:</h3>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h3>GET данные:</h3>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

echo "<h3>Переменные окружения:</h3>";
echo "<pre>";
print_r($_ENV);
echo "</pre>";

echo "<h3>Серверные переменные (часть):</h3>";
$server_vars = [
    'REQUEST_METHOD',
    'REQUEST_URI',
    'HTTP_HOST',
    'SERVER_NAME',
    'PHP_SELF',
    'SCRIPT_NAME',
    'DOCUMENT_ROOT'
];

echo "<pre>";
foreach ($server_vars as $var) {
    echo "$var: " . ($_SERVER[$var] ?? 'НЕ УСТАНОВЛЕНА') . "\n";
}
echo "</pre>";

// Тестируем подключение с данными из POST
if ($_POST['db_host']) {
    echo "<h3>Тест подключения с данными из формы:</h3>";
    
    $dbHost = $_POST['db_host'] ?? 'localhost';
    $dbName = $_POST['db_name'] ?? 'book_smeta';
    $dbUser = $_POST['db_user'] ?? 'root';
    $dbPass = $_POST['db_password'] ?? '';
    
    echo "Хост: $dbHost<br>";
    echo "БД: $dbName<br>";
    echo "Пользователь: $dbUser<br>";
    echo "Пароль: " . (strlen($dbPass) > 0 ? "УКАЗАН (" . strlen($dbPass) . " символов)" : "НЕ УКАЗАН") . "<br>";
    
    try {
        $pdo = new PDO("mysql:host={$dbHost}", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "✅ Подключение работает!<br>";
        
        $pdo->exec("USE `{$dbName}`");
        echo "✅ Переключение на БД работает!<br>";
        
    } catch (PDOException $e) {
        echo "❌ Ошибка подключения: " . $e->getMessage() . "<br>";
        echo "Код ошибки: " . $e->getCode() . "<br>";
    }
}

echo "<br>🎉 ДИАГНОСТИКА ЗАВЕРШЕНА!";
?>
