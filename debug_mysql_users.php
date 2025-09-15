<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 ДИАГНОСТИКА ПОЛЬЗОВАТЕЛЕЙ MYSQL</h2>";

// Проверяем, переданы ли данные формы
if ($_POST['test_connection']) {
    $dbHost = $_POST['db_host'] ?? 'localhost';
    $dbUser = $_POST['db_user'] ?? 'martin9a_book';
    $dbPass = $_POST['db_pass'] ?? '';
    
    echo "<h3>Тестируем подключение к MySQL:</h3>";
    echo "Хост: $dbHost<br>";
    echo "Пользователь: $dbUser<br>";
    echo "Пароль: " . (strlen($dbPass) > 0 ? "УКАЗАН (" . strlen($dbPass) . " символов)" : "НЕ УКАЗАН") . "<br><br>";
} else {
    // Показываем форму
    echo "<form method='post'>";
    echo "<h3>Введите данные подключения к MySQL:</h3>";
    echo "Хост: <input type='text' name='db_host' value='localhost'><br><br>";
    echo "Пользователь: <input type='text' name='db_user' value='martin9a_book'><br><br>";
    echo "Пароль: <input type='password' name='db_pass'><br><br>";
    echo "<input type='submit' name='test_connection' value='Тестировать подключение'>";
    echo "</form>";
    exit;
}

// Тест 1: Подключение к MySQL без указания БД
echo "<h3>Тест 1: Подключение к MySQL (без БД)</h3>";
try {
    $pdo = new PDO("mysql:host={$dbHost}", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Подключение к MySQL успешно<br>";
    
    // Показываем все БД
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<h4>Доступные базы данных:</h4>";
    foreach ($databases as $db) {
        echo "- $db<br>";
    }
    
    // Показываем информацию о пользователе
    $stmt = $pdo->query("SELECT USER(), CURRENT_USER()");
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<h4>Информация о пользователе:</h4>";
    echo "USER(): " . $userInfo['USER()'] . "<br>";
    echo "CURRENT_USER(): " . $userInfo['CURRENT_USER()'] . "<br>";
    
    // Проверяем привилегии
    $stmt = $pdo->query("SHOW GRANTS");
    $grants = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<h4>Привилегии пользователя:</h4>";
    foreach ($grants as $grant) {
        echo "- " . htmlspecialchars($grant) . "<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Ошибка подключения к MySQL: " . $e->getMessage() . "<br>";
    echo "Код ошибки: " . $e->getCode() . "<br>";
    
    // Пробуем подключиться с другими вариантами
    echo "<h3>Тест 2: Альтернативные варианты подключения</h3>";
    
    // Тест с root пользователем (если есть)
    try {
        $pdoRoot = new PDO("mysql:host={$dbHost}", 'root', '');
        echo "✅ Подключение с root/пустой пароль работает<br>";
        
        // Проверяем пользователей MySQL
        $stmt = $pdoRoot->query("SELECT User, Host FROM mysql.user WHERE User LIKE '%martin%' OR User LIKE '%book%'");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h4>Найденные пользователи MySQL:</h4>";
        foreach ($users as $user) {
            echo "- " . $user['User'] . "@" . $user['Host'] . "<br>";
        }
        
    } catch (PDOException $e2) {
        echo "❌ Подключение с root тоже не работает: " . $e2->getMessage() . "<br>";
    }
}

// Тест 3: Проверка с разными хостами
echo "<h3>Тест 3: Разные хосты</h3>";

$hosts = ['localhost', '127.0.0.1', '::1'];
foreach ($hosts as $host) {
    try {
        $pdo = new PDO("mysql:host={$host}", $dbUser, $dbPass);
        echo "✅ Подключение к $host работает<br>";
        break;
    } catch (PDOException $e) {
        echo "❌ Подключение к $host не работает: " . $e->getMessage() . "<br>";
    }
}

echo "<br>🎉 ДИАГНОСТИКА ЗАВЕРШЕНА!";
?>
