<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 ПРОВЕРКА ТАБЛИЦЫ USERS</h2>";

// Подключаемся к БД
try {
    require_once 'includes/Database.php';
    $db = Database::getInstance();
    
    echo "<h3>Структура таблицы users:</h3>";
    
    // Показываем структуру таблицы
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Поле</th><th>Тип</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Пользователи в БД:</h3>";
    
    // Показываем пользователей
    $stmt = $db->query("SELECT id, email, role, created_at FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Email</th><th>Role</th><th>Created</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . $user['email'] . "</td>";
        echo "<td>" . $user['role'] . "</td>";
        echo "<td>" . $user['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Ошибка: " . $e->getMessage() . "</div>";
}

echo "<br>🎉 ПРОВЕРКА ЗАВЕРШЕНА!";
?>
