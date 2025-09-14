<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Smeta - Система бронирования</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-calendar-alt me-2"></i>
                Book Smeta
            </a>
        </div>
    </nav>

    <main class="container mt-4">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 mb-4">
                    <i class="fas fa-server text-primary"></i>
                    Система бронирования серверного времени
                </h1>
                <p class="lead mb-4">
                    Забронируйте время для использования нашего сервера с установленным программным обеспечением.
                    Система доступна только для оплативших клиентов.
                </p>
                
                <div class="alert alert-info">
                    <h5>Система установлена и готова к настройке!</h5>
                    <p>Для начала работы настройте базу данных и GetCourse интеграцию.</p>
                    <div class="d-grid gap-2 d-md-block">
                        <a href="../install.php" class="btn btn-primary">
                            <i class="fas fa-cog me-2"></i>
                            Запустить установку
                        </a>
                        <a href="../test.php" class="btn btn-outline-secondary">
                            <i class="fas fa-check-circle me-2"></i>
                            Проверить систему
                        </a>
                    </div>
                </div>
                
                <div class="row mt-5">
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-check fa-3x text-primary mb-3"></i>
                                <h5 class="card-title">Простое бронирование</h5>
                                <p class="card-text">Выберите удобное время из доступных слотов и забронируйте его одним кликом.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                                <h5 class="card-title">Безопасность</h5>
                                <p class="card-text">Доступ только для оплативших клиентов с индивидуальными учетными данными.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-clock fa-3x text-info mb-3"></i>
                                <h5 class="card-title">Гибкое время</h5>
                                <p class="card-text">Слоты доступны 24/7 с возможностью бронирования на неделю вперед.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-light mt-5 py-4">
        <div class="container text-center">
            <p class="text-muted mb-0">
                &copy; <?= date('Y') ?> Book Smeta. Все права защищены.
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
