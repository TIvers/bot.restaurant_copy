<?php
include 'cors.php';
include 'db.php';
header('Content-Type: application/json');

// Получаем данные POST-запроса
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Проверяем, что id передан
if (isset($data['id'])) {
    $restaurantId = $data['id'];

    try {
        // Подключение к базе данных
        $pdo = getDbConnection();

        // Запрос данных о ресторане по id
        $stmt = $pdo->prepare("SELECT name, url_rest, token_bot, admin_1, admin_2, admin_3, token_pay,cash_box_id FROM rest_bot_data WHERE id = :restaurantId");
        $stmt->bindParam(':restaurantId', $restaurantId, PDO::PARAM_INT);
        $stmt->execute();
        $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);

        // Проверка наличия ресторана
        if ($restaurant) {
            // Формирование ответа
            $response = [
                "restaurant_name" => $restaurant['name'],
                "restaurant_url" => $restaurant['url_rest'],
                "bot_token" => $restaurant['token_bot'],
                "admin_id_1" => $restaurant['admin_1'],
                "admin_id_2" => $restaurant['admin_2'],
                "admin_id_3" => $restaurant['admin_3'],
                "api_foodcard_token" => $restaurant['token_pay'],
                "cash_box_id"  => $restaurant['cash_box_id'],
            ];

            echo json_encode([
                'success' => true,
                'restaurant' => $response
            ]);
        } else {
            // Если ресторан не найден
            echo json_encode([
                'success' => false,
                'message' => 'Ресторан не найден'
            ]);
        }
    } catch (PDOException $e) {
        // Обработка ошибок базы данных
        echo json_encode([
            'success' => false,
            'message' => 'Ошибка базы данных: ' . $e->getMessage()
        ]);
    }
} else {
    // Ошибка, если не передан id
    echo json_encode([
        'success' => false,
        'message' => 'Не передан id ресторана'
    ]);
}
?>