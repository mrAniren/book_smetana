# 🚀 Руководство по развертыванию Book Smeta

## 📋 Требования к серверу

### Минимальные требования:
- **PHP:** 7.4 или выше
- **MySQL:** 5.7 или выше (или MariaDB 10.3+)
- **Apache:** 2.4 или выше (или Nginx)
- **Память:** 256 MB RAM
- **Диск:** 100 MB свободного места

### Рекомендуемые требования:
- **PHP:** 8.0 или выше
- **MySQL:** 8.0 или выше
- **Apache:** 2.4 с mod_rewrite
- **Память:** 512 MB RAM
- **Диск:** 500 MB свободного места

## 🔧 Установка на XAMPP (локальная разработка)

### 1. Установка XAMPP
```bash
# Скачайте XAMPP с https://www.apachefriends.org/
# Установите в /Applications/XAMPP/ (macOS) или C:\xampp\ (Windows)
```

### 2. Клонирование проекта
```bash
# Перейдите в директорию htdocs
cd /Applications/XAMPP/xamppfiles/htdocs/

# Скопируйте проект
cp -r /path/to/Book_smeta ./
```

### 3. Настройка базы данных
```bash
# Запустите XAMPP
# Откройте phpMyAdmin (http://localhost/phpmyadmin)

# Создайте базу данных
CREATE DATABASE book_smeta CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Импортируйте схему
USE book_smeta;
SOURCE /Applications/XAMPP/xamppfiles/htdocs/Book_smeta/database/schema.sql;
```

### 4. Настройка конфигурации
```bash
# Скопируйте пример конфигурации
cp env.example .env

# Отредактируйте .env файл
nano .env
```

### 5. Настройка Apache
```bash
# Скопируйте конфигурацию Apache
cp .htaccess.example .htaccess

# Убедитесь, что mod_rewrite включен
# В httpd.conf раскомментируйте: LoadModule rewrite_module modules/mod_rewrite.so
```

## 🌐 Установка на продакшн сервер

### 1. Подготовка сервера (Ubuntu/Debian)
```bash
# Обновление системы
sudo apt update && sudo apt upgrade -y

# Установка необходимых пакетов
sudo apt install apache2 mysql-server php php-mysql php-curl php-json php-mbstring php-xml php-zip unzip git -y

# Включение mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 2. Настройка MySQL
```bash
# Безопасная настройка MySQL
sudo mysql_secure_installation

# Создание пользователя и базы данных
sudo mysql -u root -p
```

```sql
CREATE DATABASE book_smeta CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'book_smeta_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON book_smeta.* TO 'book_smeta_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Развертывание приложения
```bash
# Переход в директорию веб-сервера
cd /var/www/html/

# Клонирование проекта
sudo git clone https://github.com/your-repo/Book_smeta.git
sudo chown -R www-data:www-data Book_smeta/
sudo chmod -R 755 Book_smeta/
```

### 4. Настройка конфигурации
```bash
# Переход в директорию проекта
cd Book_smeta/

# Создание .env файла
sudo cp env.example .env
sudo nano .env
```

```env
# Настройки базы данных
DB_HOST=localhost
DB_NAME=book_smeta
DB_USER=book_smeta_user
DB_PASSWORD=secure_password
DB_CHARSET=utf8mb4

# Настройки приложения
APP_NAME="Book Smeta"
APP_URL=https://your-domain.com
APP_DEBUG=false
APP_TIMEZONE=Europe/Moscow

# Безопасность (сгенерируйте случайные ключи)
JWT_SECRET=your_very_long_and_secure_jwt_secret_key_here
ENCRYPTION_KEY=your_very_long_and_secure_encryption_key_here

# Остальные настройки...
```

### 5. Импорт базы данных
```bash
# Импорт схемы базы данных
mysql -u book_smeta_user -p book_smeta < database/schema.sql
```

### 6. Настройка Apache
```bash
# Создание виртуального хоста
sudo nano /etc/apache2/sites-available/book-smeta.conf
```

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    DocumentRoot /var/www/html/Book_smeta/public
    
    <Directory /var/www/html/Book_smeta/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/book_smeta_error.log
    CustomLog ${APACHE_LOG_DIR}/book_smeta_access.log combined
</VirtualHost>
```

```bash
# Активация сайта
sudo a2ensite book-smeta.conf
sudo systemctl reload apache2
```

### 7. Настройка SSL (Let's Encrypt)
```bash
# Установка Certbot
sudo apt install certbot python3-certbot-apache -y

# Получение SSL сертификата
sudo certbot --apache -d your-domain.com -d www.your-domain.com
```

## 🔄 Настройка автоматизации

### 1. Настройка Cron задач
```bash
# Открытие crontab
crontab -e

# Добавление задач (скопируйте из crontab.example)
0 0 * * 0 /usr/bin/php /var/www/html/Book_smeta/scripts/create_weekly_slots.php >> /var/log/book_smeta_slots.log 2>&1
0 * * * * /usr/bin/php /var/www/html/Book_smeta/scripts/cleanup_expired_bookings.php >> /var/log/book_smeta_cleanup.log 2>&1
0 2 * * * /usr/bin/php /var/www/html/Book_smeta/scripts/sync_getcourse_users.php >> /var/log/book_smeta_sync.log 2>&1
```

### 2. Создание директорий для логов
```bash
# Создание директорий
sudo mkdir -p /var/log/book_smeta
sudo mkdir -p /var/www/html/Book_smeta/logs
sudo mkdir -p /var/www/html/Book_smeta/public/uploads

# Установка прав доступа
sudo chown -R www-data:www-data /var/log/book_smeta/
sudo chown -R www-data:www-data /var/www/html/Book_smeta/logs/
sudo chown -R www-data:www-data /var/www/html/Book_smeta/public/uploads/
sudo chmod -R 755 /var/log/book_smeta/
sudo chmod -R 755 /var/www/html/Book_smeta/logs/
sudo chmod -R 755 /var/www/html/Book_smeta/public/uploads/
```

### 3. Настройка ротации логов
```bash
# Создание конфигурации logrotate
sudo nano /etc/logrotate.d/book-smeta
```

```
/var/log/book_smeta/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
}
```

## 🔒 Безопасность

### 1. Настройка файрвола
```bash
# Включение UFW
sudo ufw enable

# Разрешение SSH, HTTP и HTTPS
sudo ufw allow ssh
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Проверка статуса
sudo ufw status
```

### 2. Настройка fail2ban
```bash
# Установка fail2ban
sudo apt install fail2ban -y

# Создание конфигурации
sudo nano /etc/fail2ban/jail.local
```

```ini
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 3

[apache-auth]
enabled = true
port = http,https
logpath = /var/log/apache2/*error.log

[apache-badbots]
enabled = true
port = http,https
logpath = /var/log/apache2/*access.log
bantime = 172800
maxretry = 1

[apache-noscript]
enabled = true
port = http,https
logpath = /var/log/apache2/*access.log

[apache-overflows]
enabled = true
port = http,https
logpath = /var/log/apache2/*error.log
maxretry = 2
```

### 3. Регулярные обновления
```bash
# Создание скрипта для обновлений
sudo nano /usr/local/bin/update-book-smeta.sh
```

```bash
#!/bin/bash
cd /var/www/html/Book_smeta
git pull origin main
composer install --no-dev --optimize-autoloader
sudo systemctl reload apache2
echo "Book Smeta updated at $(date)" >> /var/log/book_smeta_updates.log
```

```bash
# Установка прав на выполнение
sudo chmod +x /usr/local/bin/update-book-smeta.sh

# Добавление в cron для еженедельных обновлений
# 0 1 * * 1 /usr/local/bin/update-book-smeta.sh
```

## 📊 Мониторинг

### 1. Настройка мониторинга системы
```bash
# Установка htop для мониторинга процессов
sudo apt install htop -y

# Мониторинг дискового пространства
df -h

# Мониторинг использования памяти
free -h

# Мониторинг логов Apache
sudo tail -f /var/log/apache2/error.log
```

### 2. Проверка здоровья системы
```bash
# Тестирование API здоровья
curl -f http://your-domain.com/api/health.php

# Проверка базы данных
mysql -u book_smeta_user -p -e "SELECT COUNT(*) FROM book_smeta.users;"

# Проверка cron задач
sudo systemctl status cron
```

## 🔧 Резервное копирование

### 1. Автоматическое резервное копирование
```bash
# Создание скрипта резервного копирования
sudo nano /usr/local/bin/backup-book-smeta.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/backups/book_smeta"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="book_smeta"
DB_USER="book_smeta_user"
DB_PASS="secure_password"

# Создание директории для бэкапов
mkdir -p $BACKUP_DIR

# Резервное копирование базы данных
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_backup_$DATE.sql

# Резервное копирование файлов
tar -czf $BACKUP_DIR/files_backup_$DATE.tar.gz /var/www/html/Book_smeta

# Удаление старых бэкапов (старше 30 дней)
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete

echo "Backup completed at $(date)" >> /var/log/book_smeta_backup.log
```

### 2. Восстановление из резервной копии
```bash
# Восстановление базы данных
mysql -u book_smeta_user -p book_smeta < /backups/book_smeta/db_backup_20250914_120000.sql

# Восстановление файлов
sudo tar -xzf /backups/book_smeta/files_backup_20250914_120000.tar.gz -C /
```

## 🚨 Устранение неполадок

### Частые проблемы:

1. **Ошибка 500 Internal Server Error**
   ```bash
   # Проверка логов Apache
   sudo tail -f /var/log/apache2/error.log
   
   # Проверка прав доступа
   sudo chown -R www-data:www-data /var/www/html/Book_smeta/
   sudo chmod -R 755 /var/www/html/Book_smeta/
   ```

2. **Ошибка подключения к базе данных**
   ```bash
   # Проверка статуса MySQL
   sudo systemctl status mysql
   
   # Проверка настроек в .env
   cat .env | grep DB_
   ```

3. **Проблемы с mod_rewrite**
   ```bash
   # Включение mod_rewrite
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   
   # Проверка конфигурации .htaccess
   sudo apache2ctl configtest
   ```

## 📞 Поддержка

При возникновении проблем:

1. Проверьте логи системы
2. Убедитесь в правильности настроек
3. Проверьте права доступа к файлам
4. Обратитесь к документации API

**Удачного развертывания! 🚀**
