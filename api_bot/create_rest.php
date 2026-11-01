<?php
include 'cors.php';
include 'db.php';
header('Content-Type: application/json');
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data) {
    $restaurant_name = $data['restaurant_name'];
    $bot_token = $data['bot_token'];
    $admin_1 = $data['admin_id_1'] ?? null;
    $admin_2 = $data['admin_id_2'] ?? null;
    $admin_3 = $data['admin_id_3'] ?? null;
    $auth_token = $data['auth_token'];
    $cash_box_id = $data['CashBoxId'] ?? null;

    // Convert address to formatted string if it's an array
    $address = $data['address'];
    if (is_array($address)) {
        $formattedAddress = [];
        foreach ($address as $index => $addr) {
            $formattedAddress[] = ($index + 1) . ': "' . $addr . '"';
        }
        $address = implode('; ', $formattedAddress);
    }

    try {
        $pdo = getDbConnection();

        $stmt = $pdo->prepare("
            INSERT INTO rest_bot_data (name, code, token_pay, token_bot, url_rest, admin_1, admin_2, admin_3, cash_box_id, address) 
            VALUES (:name, :code, :token_pay, :token_bot, :url_rest, :admin_1, :admin_2, :admin_3, :cash_box_id, :address)
        ");
        $stmt->execute([
            ':name' => $restaurant_name,
            ':code' => 'temp',
            ':token_pay' => $auth_token,
            ':token_bot' => $bot_token,
            ':url_rest' => 'temp_url',
            ':admin_1' => $admin_1,
            ':admin_2' => $admin_2,
            ':admin_3' => $admin_3,
            ':cash_box_id' => $cash_box_id,
            ':address' => $address  
        ]);

        $insertedId = $pdo->lastInsertId();
        $code = 'rest' . $insertedId;
        $url_rest = "https://testingnil7.ru/$code/";

        $stmt = $pdo->prepare("UPDATE rest_bot_data SET code = :code, url_rest = :url_rest WHERE id = :id");
        $stmt->execute([
            ':code' => $code,
            ':url_rest' => $url_rest,
            ':id' => $insertedId
        ]);

        function handleAdmin($pdo, $admin_id, $restaurant_code) {
            if ($admin_id) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE id_tg = :admin_id AND restaurant_id = :restaurant_id");
                $stmt->execute([
                    ':admin_id' => $admin_id,
                    ':restaurant_id' => $restaurant_code
                ]);

                $user = $stmt->fetch();

                if ($user) {
                    $stmt = $pdo->prepare("UPDATE users SET user_status = 'admin' WHERE id_tg = :admin_id AND restaurant_id = :restaurant_id");
                    $stmt->execute([
                        ':admin_id' => $admin_id,
                        ':restaurant_id' => $restaurant_code
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO users (id_tg, restaurant_id, user_status) 
                        VALUES (:admin_id, :restaurant_id, 'admin')
                    ");
                    $stmt->execute([
                        ':admin_id' => $admin_id,
                        ':restaurant_id' => $restaurant_code
                    ]);
                }
            }
        }

        handleAdmin($pdo, $admin_1, $code);
        handleAdmin($pdo, $admin_2, $code);
        handleAdmin($pdo, $admin_3, $code);

        $response = [
            'status' => 'success',
            'message' => 'Данные успешно добавлены в базу данных',
            'restaurant_id' => $insertedId,
            'link' => $url_rest,
            'address' => $address  // Include address in the response
        ];
    } catch (PDOException $e) {
        $response = [
            'status' => 'error',
            'message' => 'Ошибка при добавлении данных в базу: ' . $e->getMessage()
        ];
    }

    echo json_encode($response);
} else {
    $response = [
        'status' => 'error',
        'message' => 'Не удалось получить данные'
    ];
    echo json_encode($response);
}
?>
