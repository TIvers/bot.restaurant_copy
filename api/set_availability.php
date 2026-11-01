<?php
include 'cors.php';
include 'db.php';
header('Content-Type: application/json');

// Получаем данные POST-запроса
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data && isset($data['restaurantId'], $data['openTime'], $data['closeTime'])) {
    $restaurantId = $data['restaurantId'];
    $openTime = $data['openTime'];
    $closeTime = $data['closeTime'];

    try {
        // Подключение к базе данных
        $pdo = getDbConnection();

        // Обновление времени открытия и закрытия ресторана
        $stmt = $pdo->prepare("UPDATE rest_bot_data SET open = :openTime, close = :closeTime WHERE code = :restaurantId");
        $stmt->bindParam(':restaurantId', $restaurantId);
        $stmt->bindParam(':openTime', $openTime);
        $stmt->bindParam(':closeTime', $closeTime);
        $stmt->execute();

        // Проверка успешности обновления
        if ($stmt->rowCount() > 0) {
            $response = [
                'success' => true,
                'message' => 'Availability time updated successfully'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Failed to update availability time'
            ];
        }

        echo json_encode($response);
    } catch (PDOException $e) {
        // Обработка ошибок базы данных
        $response = [
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ];
        echo json_encode($response);
    }
} else {
    // Ошибка, если не указаны restaurantId, openTime или closeTime
    $response = [
        'success' => false,
        'message' => 'Missing restaurantId, openTime or closeTime'
    ];
    echo json_encode($response);
}
?>
