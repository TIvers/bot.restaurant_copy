<?php
include 'cors.php';
include 'db.php';
header('Content-Type: application/json');

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data) {
    $restaurant_id = $data['id'];
    $column = $data['column'];
    $value = $data['value'];

    // Allowed columns for update
    $allowed_columns = [
        'edit_restaurant_name' => 'name',
        'edit_bot_token' => 'token_bot',
        'edit_api_foodcard_token' => 'token_pay',
        'edit_admin_id_1' => 'admin_1',
        'edit_admin_id_2' => 'admin_2',
        'edit_admin_id_3' => 'admin_3',
        'edit_cash_box_id' => 'cash_box_id',
        'edit_address' => 'address',
    ];

    if (array_key_exists($column, $allowed_columns)) {
        $db_column = $allowed_columns[$column];

        try {
            $pdo = getDbConnection();
            
            // Check if updating admin columns
            if (strpos($db_column, 'admin_') !== false) {
                $stmt = $pdo->prepare("SELECT $db_column, code FROM rest_bot_data WHERE id = :id");
                $stmt->bindParam(':id', $restaurant_id);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $previous_admin_id = $row[$db_column];
                $restaurant_code = $row['code'];

                if ($previous_admin_id) {
                    $stmt = $pdo->prepare("UPDATE users SET user_status = 'user' WHERE id_tg = :id AND restaurant_id = :restaurant_id");
                    $stmt->bindParam(':id', $previous_admin_id);
                    $stmt->bindParam(':restaurant_id', $restaurant_code);
                    $stmt->execute();
                }

                $stmt = $pdo->prepare("SELECT * FROM users WHERE id_tg = :id AND restaurant_id = :restaurant_id");
                $stmt->bindParam(':id', $value);
                $stmt->bindParam(':restaurant_id', $restaurant_code);
                $stmt->execute();
                $new_admin = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($new_admin) {
                    $stmt = $pdo->prepare("UPDATE users SET user_status = 'admin' WHERE id_tg = :id AND restaurant_id = :restaurant_id");
                    $stmt->bindParam(':id', $value);
                    $stmt->bindParam(':restaurant_id', $restaurant_code);
                    $stmt->execute();
                } else {
                    $stmt = $pdo->prepare("INSERT INTO users (id_tg, restaurant_id, user_status) VALUES (:id, :restaurant_id, 'admin')");
                    $stmt->bindParam(':id', $value);
                    $stmt->bindParam(':restaurant_id', $restaurant_code);
                    $stmt->execute();
                }
            }

            // Handle address formatting if updating address
            if ($db_column === 'address') {
                if (is_array($value)) {
                    $formattedAddress = [];
                    foreach ($value as $index => $addr) {
                        $formattedAddress[] = ($index + 1) . ': "' . $addr . '"';
                    }
                    $value = implode('; ', $formattedAddress);
                } else {
                    // Если адрес — это одиночная строка, оформляем её также с номером 1
                    $value = '1: "' . $value . '"';
                }
            }

            // Update data in rest_bot_data
            $stmt = $pdo->prepare("UPDATE rest_bot_data SET $db_column = :value WHERE id = :id");
            $stmt->bindParam(':value', $value);
            $stmt->bindParam(':id', $restaurant_id);

            if ($stmt->execute()) {
                $response = [
                    'status' => 'success',
                    'message' => 'Запись успешно обновлена'
                ];
            } else {
                $response = [
                    'status' => 'error',
                    'message' => 'Не удалось обновить запись'
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
            'message' => 'Недопустимый столбец для обновления'
        ];
    }
} else {
    $response = [
        'status' => 'error',
        'message' => 'Не удалось получить данные'
    ];
}

echo json_encode($response);
?>
