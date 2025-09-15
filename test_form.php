<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 ТЕСТ ФОРМЫ</h2>";

if ($_POST) {
    echo "<h3>POST данные получены:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Тестируем подключение
    if (isset($_POST['db_host'])) {
        $dbHost = $_POST['db_host'];
        $dbUser = $_POST['db_user'];
        $dbPass = $_POST['db_password'];
        $dbName = $_POST['db_name'];
        
        echo "<h3>Тест подключения:</h3>";
        echo "Хост: $dbHost<br>";
        echo "Пользователь: $dbUser<br>";
        echo "Пароль: " . (strlen($dbPass) > 0 ? "УКАЗАН (" . strlen($dbPass) . " символов)" : "НЕ УКАЗАН") . "<br>";
        echo "БД: $dbName<br><br>";
        
        try {
            $pdo = new PDO("mysql:host={$dbHost}", $dbUser, $dbPass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            echo "✅ Подключение работает!<br>";
            
            $pdo->exec("USE `{$dbName}`");
            echo "✅ Переключение на БД работает!<br>";
            
        } catch (PDOException $e) {
            echo "❌ Ошибка подключения: " . $e->getMessage() . "<br>";
        }
    }
} else {
    echo "<h3>Заполните форму:</h3>";
    echo "<form method='post'>";
    echo "<label>Хост: <input type='text' name='db_host' value='localhost'></label><br><br>";
    echo "<label>БД: <input type='text' name='db_name' value='martin9a_book'></label><br><br>";
    echo "<label>Пользователь: <input type='text' name='db_user' value='martin9a_book'></label><br><br>";
    echo "<label>Пароль: <input type='password' name='db_password'></label><br><br>";
    echo "<input type='submit' value='Отправить форму'>";
    echo "</form>";
}

echo "<br>🎉 ТЕСТ ЗАВЕРШЕН!";
?>
