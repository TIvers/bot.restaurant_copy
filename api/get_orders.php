<?php
include 'cors.php';
include 'db.php';
header('Content-Type: application/json');

// Получаем данные POST-запроса
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data && isset($data['restaurantId'])) {
    $restaurantId = $data['restaurantId'];

    try {
        // Подключение к базе данных
        $pdo = getDbConnection();

        // Запрос: выбираем заказы со статусом "completed"
        $stmt = $pdo->prepare("
            SELECT 
                unique_order_id, 
                id_tg, 
                full_name AS customer_name, 
                address, 
                total_amount, 
                order_time AS created_at, 
                product_details 
            FROM orders 
            WHERE restaurant_id = :restaurantId 
            AND status = 'completed'  -- Только completed заказы
            ORDER BY created_at DESC
        ");
        $stmt->bindParam(':restaurantId', $restaurantId);
        $stmt->execute();

        // Получаем все заказы
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Формируем новый массив с нужной структурой
        $formattedOrders = [];
        foreach ($orders as $order) {
            // Дополнительный запрос для получения номера телефона из таблицы users
            $stmtUser = $pdo->prepare("
                SELECT phone_number 
                FROM users 
                WHERE id_tg = :id_tg 
                AND restaurant_id = :restaurant_id
            ");
            $stmtUser->bindParam(':id_tg', $order['id_tg']);
            $stmtUser->bindParam(':restaurant_id', $restaurantId);
            $stmtUser->execute();
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            // Добавляем телефон в заказ (если найден)
            $phone = $user ? $user['phone_number'] : null;

            $formattedOrders[] = [
                'date' => $order['created_at'],  // Время заказа
                'orderNumber' => $order['unique_order_id'], // Уникальный номер заказа
                'customerId' => $order['id_tg'], // ID клиента
                'address' => $order['address'],  // Адрес
                'fullName' => $order['customer_name'], // Имя клиента
                'totalAmount' => $order['total_amount'], // Общая сумма
                'product_details' => $order['product_details'], // Детали продуктов
                'phone' => $phone // Телефон клиента
            ];
        }

        // Возвращаем отформатированные заказы в формате JSON
        $response = [
            'status' => 'success',
            'orders' => $formattedOrders
        ];
        echo json_encode($response);
    } catch (PDOException $e) {
        // Обработка ошибок
        $response = [
            'status' => 'error',
            'message' => 'Ошибка при получении заказов: ' . $e->getMessage()
        ];
        echo json_encode($response);
    }
} else {
    // Ошибка, если не передан restaurantId
    $response = [
        'status' => 'error',
        'message' => 'Не указан restaurantId'
    ];
    echo json_encode($response);
}
?>
