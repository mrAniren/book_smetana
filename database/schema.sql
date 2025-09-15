-- Создание базы данных для системы бронирования Book Smeta
CREATE DATABASE IF NOT EXISTS book_smeta CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE book_smeta;

-- Таблица пользователей
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role ENUM('user', 'admin', 'super_admin') DEFAULT 'user',
    booking_limit INT DEFAULT 3,
    booking_count INT DEFAULT 0,
    getcourse_user_id INT NULL,
    is_paid_client BOOLEAN DEFAULT FALSE,
    email_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    
    INDEX idx_email (email),
    INDEX idx_getcourse_id (getcourse_user_id),
    INDEX idx_role (role),
    INDEX idx_is_paid (is_paid_client)
);

-- Таблица серверных слотов
CREATE TABLE server_slots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    slot_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    server_login VARCHAR(100) NOT NULL,
    server_password VARCHAR(255) NOT NULL,
    is_available BOOLEAN DEFAULT TRUE,
    max_users INT DEFAULT 1,
    current_users INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_slot (slot_date, start_time),
    INDEX idx_date (slot_date),
    INDEX idx_available (is_available)
);

-- Таблица бронирований
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    slot_id INT NOT NULL,
    booking_status ENUM('active', 'cancelled', 'completed', 'expired') DEFAULT 'active',
    booking_notes TEXT,
    server_access_granted BOOLEAN DEFAULT FALSE,
    access_granted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (slot_id) REFERENCES server_slots(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_slot (slot_id),
    INDEX idx_status (booking_status),
    INDEX idx_expires (expires_at)
);

-- Таблица логов GetCourse
CREATE TABLE getcourse_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    action VARCHAR(50) NOT NULL,
    getcourse_user_id INT,
    email VARCHAR(255),
    request_data JSON,
    response_data JSON,
    status ENUM('success', 'error', 'pending') DEFAULT 'pending',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_action (action),
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);

-- Таблица настроек системы
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Таблица уведомлений
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_type (type),
    INDEX idx_read (is_read)
);

-- Вставка начальных настроек
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('default_booking_limit', '3', 'Лимит бронирований по умолчанию для новых пользователей'),
('booking_expire_hours', '24', 'Время истечения бронирования в часах'),
('slot_duration_minutes', '60', 'Длительность слота в минутах'),
('max_weekly_slots', '168', 'Максимальное количество слотов в неделю'),
('server_slot_creation_day', 'sunday', 'День недели для создания слотов'),
('email_notifications_enabled', '1', 'Включены ли email уведомления'),
('system_maintenance_mode', '0', 'Режим технического обслуживания');

-- Создание суперадминистратора по умолчанию
INSERT INTO users (email, password_hash, first_name, last_name, role, is_paid_client, email_verified, booking_limit) VALUES
('admin@book-smeta.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super', 'Admin', 'super_admin', TRUE, TRUE, 999);

-- Создание индексов для оптимизации
CREATE INDEX idx_users_booking_count ON users(booking_count);
CREATE INDEX idx_bookings_created_at ON bookings(created_at);
CREATE INDEX idx_server_slots_datetime ON server_slots(slot_date, start_time);
