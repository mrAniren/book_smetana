<?php

require_once __DIR__ . '/../includes/GetCourseAPI.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Получаем данные из POST запроса
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Неверный формат JSON: " . json_last_error_msg());
    }
    
    // Логируем полученный webhook
    logInfo("Получен webhook от GetCourse", $data);
    
    // Обрабатываем webhook
    $getcourseAPI = new GetCourseAPI();
    $result = $getcourseAPI->handleWebhook($data);
    
    // Отправляем ответ GetCourse
    sendJsonResponse([
        'success' => true,
        'message' => 'Webhook обработан успешно',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    logError("Ошибка обработки webhook GetCourse", [
        'input' => $input,
        'error' => $e->getMessage()
    ]);
    
    sendJsonResponse([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], 500);
}
