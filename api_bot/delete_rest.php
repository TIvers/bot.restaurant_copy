<?php
include 'cors.php';
include 'db.php';
header('Content-Type: application/json');

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data && isset($data['id'])) {
    $restaurant_id = $data['id'];

    try {
        $pdo = getDbConnection();

        // Подготовка и выполнение запроса на удаление
        $stmt = $pdo->prepare("DELETE FROM rest_bot_data WHERE id = :id");
        $stmt->bindParam(':id', $restaurant_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            $response = [
                'status' => 'success',
                'message' => 'Ресторан успешно удалён'
            ];
        } else {
            $response = [
                'status' => 'error',
                'message' => 'Не удалось удалить ресторан'
            ];
        }
    } catch (PDOException $e) {
        $response = [
            'status' => 'error',
            'message' => 'Ошибка базы данных: ' . $e->getMessage()
        ];
    }
} else {
    $response = [
        'status' => 'error',
        'message' => 'Не удалось получить ID ресторана'
    ];
}

// Отправка ответа
echo json_encode($response);
?>
