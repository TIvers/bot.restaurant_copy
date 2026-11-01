<?php
require_once __DIR__ . '/../api_bot/vendor/autoload.php';
include 'cors.php';
include 'db.php';
header('Content-Type: application/json');

use Ramsey\Uuid\Uuid;

// Получаем данные POST-запроса
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data && isset($data['id_tg'], $data['restaurantId'], $data['productIds'], $data['totalAmount'], $data['cardNumber'], $data['idOrder'], $data['confirmationCode'])) {
    $id_tg = $data['id_tg'];
    $restaurantId = $data['restaurantId'];
    $productIds = $data['productIds'];
    $totalAmount = $data['totalAmount'];
    $cardNumber = $data['cardNumber'];
    $idOrder = $data['idOrder'];
    $confirmationCode = $data['confirmationCode'];

    $uniqueOrderId = Uuid::uuid4()->toString();
    // Создаем уникальный идентификатор заказа
    // preg_match('/\d+$/', $restaurantId, $matches);
    // $restaurantNumericId = $matches[0];

    // Создаем уникальный идентификатор заказа
    // $timestamp = time();
    // $uniqueOrderId = "{$timestamp}{$restaurantNumericId}{$idOrder}";

    try {
        $pdo = getDbConnection();
    
        $stmt = $pdo->prepare("
            SELECT o.id, o.total_amount, o.id_tg, o.product_details, o.product_ids, o.restaurant_id, o.card_number, o.address, o.full_name, u.phone_number
            FROM orders o
            JOIN users u ON CAST(o.id_tg AS TEXT) = u.id_tg AND o.restaurant_id = u.restaurant_id
            WHERE o.id = :idOrder AND o.id_tg = :id_tg AND o.restaurant_id = :restaurantId
        ");
        $stmt->bindParam(':idOrder', $idOrder);
        $stmt->bindParam(':id_tg', $id_tg);
        $stmt->bindParam(':restaurantId', $restaurantId);
        $stmt->execute();
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Заказ не найден или данные неверны']);
            exit;
        }

        $address = $order['address'];
        $fullName = $order['full_name'];
        $phone = $order['phone_number'];
        $dbCardNumber = $order['card_number'];
        $dbProductDetails = $order['product_details'];
        $dbProductIds = explode(',', $order['product_ids']);
        $dbTotalAmount = $order['total_amount'];

        if ($dbCardNumber !== $cardNumber) {
            echo json_encode(['success' => false, 'message' => 'Номер карты не совпадает']);
            exit;
        }

        if (array_diff($productIds, $dbProductIds) || array_diff($dbProductIds, $productIds)) {
            echo json_encode(['success' => false, 'message' => 'Список товаров не совпадает']);
            exit;
        }

        if ($dbTotalAmount != $totalAmount) {
            echo json_encode(['success' => false, 'message' => 'Сумма заказа не совпадает']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT token_pay, token_bot, cash_box_id, admin_1, admin_2, admin_3 FROM rest_bot_data WHERE code = :restaurantId");
        $stmt->bindParam(':restaurantId', $restaurantId);
        $stmt->execute();
        $tokens = $stmt->fetch(PDO::FETCH_ASSOC);
        $token_pay = $tokens['token_pay'];
        $token_bot = $tokens['token_bot'];
        $cash_box_id = $tokens['cash_box_id'];
        $admins = [$tokens['admin_1'], $tokens['admin_2'], $tokens['admin_3']];

        if (!$token_pay || !$token_bot || !$admins[0]) {
            echo json_encode(['success' => false, 'message' => 'Не удалось найти необходимые данные для отправки']);
            exit;
        }

        $orderUrl = "https://api-test.foodcards.ru/v1/universal/telegram/order";
        $orderPayload = json_encode([
            'cashBoxId' => $cash_box_id,
            'authToken' => $token_pay,
            'identifierBase64' => base64_encode($cardNumber),
            'confirmCode' => $confirmationCode,
            'operationList' => [
                ['type' => 1, 'sum' => $totalAmount * 100, 'currency' => 'RUB']
            ],
            'orderUid' => $uniqueOrderId,
            'orderInfoBase64' => base64_encode(json_encode(['orderId' => $idOrder]))
        ]);

        $orderOptions = [
            'http' => [
                'header' => "Content-Type: application/json\r\n",
                'method' => 'POST',
                'content' => $orderPayload,
            ]
        ];
        
        $orderContext = stream_context_create($orderOptions);
        $orderResponse = file_get_contents($orderUrl, false, $orderContext);
        $orderResult = json_decode($orderResponse, true);

        if ($orderResult['respCode'] !== 0) {
            switch ($orderResult['respCode']) {
                case 1:
                    echo json_encode(['success' => false, 'message' => 'Неверный код подтверждения.']);
                    break;
                case 2:
                    echo json_encode(['success' => false, 'message' => 'Неизвестный идентификатор.']);
                    break;
                case 3:
                    echo json_encode(['success' => false, 'message' => 'Недостаточно средств на счете.']);
                    break;
                default:
                    echo json_encode(['success' => false, 'message' => 'Неизвестная ошибка (код ' . $orderResult['respCode'] . ').']);
                    break;
            }
            exit;
        }

        $updateStmt = $pdo->prepare("
            UPDATE orders
            SET status = 'completed', order_time = NOW(), unique_order_id = :uniqueOrderId
            WHERE id = :idOrder
        ");
        $updateStmt->bindParam(':idOrder', $idOrder);
        $updateStmt->bindParam(':uniqueOrderId', $uniqueOrderId);
        $updateStmt->execute();

        $telegramUrl = "https://api.telegram.org/bot{$token_bot}/sendMessage";
        $clientMessage = "<b>Заказ подтвержден</b>\n\n" . 
                         "Номер заказа: <b>{$uniqueOrderId}</b>\n" .
                         "Товары: <b>{$dbProductDetails}</b>\n\n" .
                         "Адрес: <b>{$address}</b>\n\n" . 
                         "Общая сумма: <b>{$totalAmount} рублей</b>\n";

        $clientPostData = [
            'chat_id'    => $id_tg,
            'text'       => $clientMessage,
            'parse_mode' => 'HTML'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $telegramUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($clientPostData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $clientResponse = curl_exec($ch);
        curl_close($ch);

        if (!$clientResponse) {
            echo json_encode(['success' => false, 'message' => 'Ошибка отправки сообщения клиенту в Telegram']);
            exit;
        }

        $adminMessage = "<b>Заказ клиента подтверждён</b>\n\n" .
                        "Номер заказа: <b>{$uniqueOrderId}</b>\n" .
                        "Товары: <b>{$dbProductDetails}</b>\n\n" .
                        "Адрес: <b>{$address}</b>\n" . 
                        "Телефон клиента: <b>{$phone}</b>\n" .
                        "ФИО клиента: <b>{$fullName}</b>\n\n" .
                        "Общая сумма: <b>{$totalAmount} рублей</b>\n";

        foreach ($admins as $admin_id) {
            if ($admin_id) {
                $adminPostData = [
                    'chat_id'    => $admin_id,
                    'text'       => $adminMessage,
                    'parse_mode' => 'HTML'
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $telegramUrl);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($adminPostData));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $adminResponse = curl_exec($ch);
                curl_close($ch);

                if (!$adminResponse) {
                    echo json_encode(['success' => false, 'message' => "Ошибка отправки сообщения администратору с ID {$admin_id} в Telegram"]);
                    exit;
                }
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Оплата успешно проведена и заказ обновлен',
            'orderId' => $uniqueOrderId
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
}
?>
