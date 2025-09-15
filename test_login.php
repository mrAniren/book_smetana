<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 ТЕСТ ЛОГИНА</h2>";

// Подключаемся к БД
try {
    require_once 'includes/Database.php';
    require_once 'includes/Auth.php';
    require_once 'includes/functions.php';
    
    $db = Database::getInstance();
    $auth = new Auth();
    
    echo "<h3>1. Проверка пользователей в БД:</h3>";
    
    $stmt = $db->query("SELECT id, email, password_hash, role FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Email</th><th>Password Hash</th><th>Role</th><th>Hash Length</th><th>Is MD5?</th></tr>";
    foreach ($users as $user) {
        $isMd5 = (strlen($user['password_hash']) === 32 && ctype_xdigit($user['password_hash']));
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['email'] . "</td>";
        echo "<td>" . substr($user['password_hash'], 0, 20) . "...</td>";
        echo "<td>" . $user['role'] . "</td>";
        echo "<td>" . strlen($user['password_hash']) . "</td>";
        echo "<td>" . ($isMd5 ? "✅ ДА" : "❌ НЕТ") . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>2. Тест функции verifyPassword:</h3>";
    
    $testPassword = 'admin123';
    echo "Тестовый пароль: $testPassword<br>";
    
    foreach ($users as $user) {
        echo "<h4>Пользователь: " . $user['email'] . "</h4>";
        echo "Хеш в БД: " . substr($user['password_hash'], 0, 20) . "...<br>";
        
        // Тест MD5
        $md5Hash = md5($testPassword);
        echo "MD5 от '$testPassword': $md5Hash<br>";
        echo "MD5 совпадает: " . ($md5Hash === $user['password_hash'] ? "✅ ДА" : "❌ НЕТ") . "<br>";
        
        // Тест verifyPassword функции
        $verifyResult = verifyPassword($testPassword, $user['password_hash']);
        echo "verifyPassword результат: " . ($verifyResult ? "✅ ДА" : "❌ НЕТ") . "<br><br>";
    }
    
    echo "<h3>3. Тест Auth->login:</h3>";
    
    foreach ($users as $user) {
        echo "<h4>Тест логина для: " . $user['email'] . "</h4>";
        try {
            $result = $auth->login($user['email'], $testPassword);
            if ($result) {
                echo "✅ Логин успешен<br>";
                echo "Полученный пользователь: " . json_encode($result) . "<br>";
            } else {
                echo "❌ Логин неуспешен<br>";
            }
        } catch (Exception $e) {
            echo "❌ Ошибка: " . $e->getMessage() . "<br>";
        }
        echo "<br>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Ошибка: " . $e->getMessage() . "</div>";
}

echo "<br>🎉 ТЕСТ ЗАВЕРШЕН!";
?>
