<?php
include 'cors.php';
include 'db.php';
header('Content-Type: application/json');

// Получаем данные POST-запроса
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Список id, которые должны иметь статус "admin"
$adminIds = [75259786, 598570615, 665111465];

if ($data && isset($data['id'], $data['restaurantId'])) {
    $userId = $data['id'];
    $restaurantId = $data['restaurantId'];

    try {
        // Подключение к базе данных
        $pdo = getDbConnection();

        // Получение названия ресторана, адреса и времени открытия/закрытия по restaurantId (code в таблице rest_bot_data)
        $stmtRest = $pdo->prepare("SELECT name, address, open, close FROM rest_bot_data WHERE code = :restaurantId");
        $stmtRest->bindParam(':restaurantId', $restaurantId);
        $stmtRest->execute();
        $restaurant = $stmtRest->fetch(PDO::FETCH_ASSOC);

        if ($restaurant) {
            $restaurantName = $restaurant['name'];
            $restaurantAddress = $restaurant['address'];
            $openTime = $restaurant['open'];
            $closeTime = $restaurant['close'];
        } else {
            $restaurantName = null; // Если ресторан не найден
            $restaurantAddress = null;
            $openTime = null;
            $closeTime = null;
        }

        // Проверка наличия пользователя в базе данных
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id_tg = :userId AND restaurant_id = :restaurantId");
        $stmt->bindParam(':userId', $userId);
        $stmt->bindParam(':restaurantId', $restaurantId);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Пользователь найден, возвращаем все данные пользователя
            $response = [
                'success' => true,
                'user' => $user, // Возвращаем всю строку с данными пользователя
                'restaurant_name' => $restaurantName, // Добавляем название ресторана
                'restaurant_address' => $restaurantAddress,
                'open_time' => $openTime, // Добавляем время открытия
                'close_time' => $closeTime, // Добавляем время закрытия
                'message' => 'User found'
            ];
        } else {
            // Пользователь не найден, создаём нового
            $userStatus = in_array($userId, $adminIds) ? "admin" : "user"; // Если id в списке, статус "admin"

            $insertStmt = $pdo->prepare("INSERT INTO users (id_tg, restaurant_id, user_status) VALUES (:userId, :restaurantId, :userStatus)");
            $insertStmt->bindParam(':userId', $userId);
            $insertStmt->bindParam(':restaurantId', $restaurantId);
            $insertStmt->bindParam(':userStatus', $userStatus);
            $insertStmt->execute();

            // Получаем данные нового пользователя
            $newUserStmt = $pdo->prepare("SELECT * FROM users WHERE id_tg = :userId AND restaurant_id = :restaurantId");
            $newUserStmt->bindParam(':userId', $userId);
            $newUserStmt->bindParam(':restaurantId', $restaurantId);
            $newUserStmt->execute();
            $newUser = $newUserStmt->fetch(PDO::FETCH_ASSOC);

            // Возвращаем успешный ответ с информацией о новом пользователе
            $response = [
                'success' => true,
                'user' => $newUser, // Возвращаем все данные нового пользователя
                'restaurant_name' => $restaurantName, // Добавляем название ресторана
                'restaurant_address' => $restaurantAddress,
                'open_time' => $openTime, // Добавляем время открытия
                'close_time' => $closeTime, // Добавляем время закрытия
                'message' => 'User created successfully'
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
    // Ошибка, если не указаны id и restaurantId
    $response = [
        'success' => false,
        'message' => 'Missing id or restaurantId'
    ];
    echo json_encode($response);
}
?>
