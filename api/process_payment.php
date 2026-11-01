<?php
include 'cors.php';
include 'db.php';
header('Content-Type: application/json');

// Получаем данные POST-запроса
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Проверяем, что все нужные данные переданы
if ($data && isset($data['id_tg'], $data['restaurantId'], $data['productIds'], $data['totalAmount'], $data['cardNumber'])) {
    $id_tg = $data['id_tg'];
    $restaurantId = $data['restaurantId'];
    $productIds = $data['productIds'];
    $totalAmount = $data['totalAmount'];
    $cardNumber = $data['cardNumber'];

    try {
        // Подключение к базе данных
        $pdo = getDbConnection();

        // Получение token_pay из таблицы rest_bot_data
        $stmt = $pdo->prepare("SELECT token_pay,cash_box_id FROM rest_bot_data WHERE code = :restaurantId");
        $stmt->bindParam(':restaurantId', $restaurantId);
        $stmt->execute();
        $tokens = $stmt->fetch(PDO::FETCH_ASSOC);

        $token_pay = $tokens['token_pay'];
        $cash_box_id = $tokens['cash_box_id'];

        if (!$token_pay) {
            echo json_encode(['success' => false, 'message' => 'Auth token not found']);
            exit;
        }

        // Проверка баланса карты через API
        $cashBoxId = $cash_box_id; // Фиксированный cashBoxId
        $url = "https://api-test.foodcards.ru/v1/universal/telegram/card-info";
        $payload = json_encode([
            'cashBoxId' => $cashBoxId,
            'authToken' => $token_pay,
            'identifierBase64' => base64_encode($cardNumber),
            'currency' => 'RUB'
        ]);

        $options = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => $payload,
            ]
        ];
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        $result = json_decode($response, true);

        if (!$result || !isset($result['sum'])) {
            echo json_encode(['success' => false, 'message' => 'Карта не найдена']);
            exit;
        }

        $cardBalance = $result['sum'] / 100; // Баланс в рублях

        // Проверяем, достаточно ли средств на карте
        if ($cardBalance < $totalAmount) {
            echo json_encode([
                'success' => false,
                'message' => 'Недостаточно средств для заказа',
                'cardBalance' => $cardBalance,
                'totalAmount' => $totalAmount
            ]);
            exit;
        }

        // Проверяем наличие пользователя в базе данных
        $stmt = $pdo->prepare("SELECT full_name, address FROM users WHERE id_tg = :id_tg");
        $stmt->bindParam(':id_tg', $id_tg);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode([
                'success' => false,
                'message' => 'User not found'
            ]);
            exit;
        }

        $full_name = $user['full_name'];
        $address = $user['address'];

        // Обновляем номер карты пользователя
        $updateCardStmt = $pdo->prepare("UPDATE users SET card_number = :card_number WHERE id_tg = :id_tg");
        $updateCardStmt->bindParam(':card_number', $cardNumber);
        $updateCardStmt->bindParam(':id_tg', $id_tg);
        $updateCardStmt->execute();

        // Подсчитываем количество каждого продукта в массиве productIds
        $productCounts = array_count_values($productIds); // Массив с количеством каждого продукта

        // Преобразуем массив productIds в строку для SQL запроса
        $placeholders = implode(',', array_fill(0, count($productCounts), '?'));

        // Получаем товары по переданным id и restaurantId, а также их цены и названия
        $stmt = $pdo->prepare("SELECT id, name, price FROM products WHERE id IN ($placeholders) AND restaurant_id = ?");
        $params = array_merge(array_keys($productCounts), [$restaurantId]);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Суммируем цены товаров с учетом количества каждого продукта
        $totalPrice = 0;
        $productNamesAndCounts = [];
        foreach ($products as $product) {
            $productId = $product['id'];
            $price = $product['price'];
            $name = $product['name'];
            $count = $productCounts[$productId];

            $totalPrice += $price * $count;

            // Формируем массив с названиями продуктов и их количеством
            $productNamesAndCounts[] = '"' . $name . '":' . $count;
        }

        // Преобразуем массив названий и количеств в строку для вставки в базу данных
        $productDetailsString = implode(';', $productNamesAndCounts);

        // Проверяем, совпадает ли итоговая сумма с переданной
        if ($totalPrice != $totalAmount) {
            echo json_encode([
                'success' => false,
                'message' => 'Сумма заказа не совпадает',
                '1' =>  $totalPrice,
                '2' =>  $totalAmount
            ]);
            exit;
        }

        // Если всё проверено, сохраняем заказ в таблицу orders
        $orderStmt = $pdo->prepare("
            INSERT INTO orders (full_name, address, restaurant_id, total_amount, id_tg, product_ids, product_details, status, order_time, card_number) 
            VALUES (:full_name, :address, :restaurant_id, :total_amount, :id_tg, :product_ids, :product_details, :status, :order_time, :card_number)
        ");
        $productIdsString = implode(',', $productIds); // Сохраняем исходный массив productIds в строковом формате
        $status = 'in_progress';
        $orderTime = date('Y-m-d H:i:s'); // Текущее время оформления

        $orderStmt->bindParam(':full_name', $full_name);
        $orderStmt->bindParam(':address', $address);
        $orderStmt->bindParam(':restaurant_id', $restaurantId);
        $orderStmt->bindParam(':total_amount', $totalAmount);
        $orderStmt->bindParam(':id_tg', $id_tg);
        $orderStmt->bindParam(':product_ids', $productIdsString);
        $orderStmt->bindParam(':product_details', $productDetailsString);
        $orderStmt->bindParam(':status', $status);
        $orderStmt->bindParam(':order_time', $orderTime);
        $orderStmt->bindParam(':card_number', $cardNumber);

        $orderStmt->execute();

        // Получаем ID только что созданного заказа
        $lastInsertId = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'message' => 'Order placed successfully',
            'orderId' => $lastInsertId, // Отправляем ID заказа
            'cardBalance' => $cardBalance
        ]);
    } catch (PDOException $e) {
        // Обрабатываем ошибки базы данных
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    // Ошибка, если не указаны необходимые параметры
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
}
?>
