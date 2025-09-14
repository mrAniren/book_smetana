<?php

require_once __DIR__ . '/../config/database.php';

class Database {
    private $db;
    private static $instance = null;
    
    private function __construct() {
        $config = DatabaseConfig::getInstance();
        $this->db = $config->getConnection();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->db;
    }
    
    // Универсальный метод для выполнения запросов
    public function query($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            throw new Exception("Ошибка выполнения запроса к базе данных");
        }
    }
    
    // Получить одну запись
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    // Получить все записи
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    // Вставить запись и вернуть ID
    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = str_repeat('?,', count($data) - 1) . '?';
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $this->query($sql, array_values($data));
        
        return $this->db->lastInsertId();
    }
    
    // Обновить запись
    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $setParts[] = "{$key} = ?";
            $params[] = $value;
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        $params = array_merge($params, $whereParams);
        
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    // Удалить запись
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
    
    // Проверить существование записи
    public function exists($table, $where, $params = []) {
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        $result = $this->fetchOne($sql, $params);
        return $result['COUNT(*)'] > 0;
    }
    
    // Получить количество записей
    public function count($table, $where = '1=1', $params = []) {
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
        $result = $this->fetchOne($sql, $params);
        return (int)$result['COUNT(*)'];
    }
    
    // Получить одно значение из первого столбца
    public function fetchColumn($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : null;
    }
    
    // Начать транзакцию
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }
    
    // Подтвердить транзакцию
    public function commit() {
        return $this->db->commit();
    }
    
    // Откатить транзакцию
    public function rollback() {
        return $this->db->rollBack();
    }
    
    // Проверить, что транзакция активна
    public function inTransaction() {
        return $this->db->inTransaction();
    }
}
