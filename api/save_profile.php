<?php
include 'cors.php';
include 'db.php';
header('Content-Type: application/json');

// Получаем данные POST-запроса
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data && isset($data['id_tg'], $data['full_name'], $data['phone_number'], $data['address'])) {
    $id_tg = $data['id_tg'];
    $full_name = $data['full_name'];
    $phone_number = $data['phone_number'];
    $address = $data['address'];
    $card_number = isset($data['cardNumber']) ? $data['cardNumber'] : null;

    try {
        // Подключение к базе данных
        $pdo = getDbConnection();

        // Проверка наличия пользователя в базе данных
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id_tg = :id_tg");
        $stmt->bindParam(':id_tg', $id_tg);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Обновляем данные пользователя
            $updateStmt = $pdo->prepare("UPDATE users SET full_name = :full_name, phone_number = :phone_number, address = :address" . ($card_number ? ", card_number = :card_number" : "") . " WHERE id_tg = :id_tg");
            $updateStmt->bindParam(':full_name', $full_name);
            $updateStmt->bindParam(':phone_number', $phone_number);
            $updateStmt->bindParam(':address', $address);
            if ($card_number) {
                $updateStmt->bindParam(':card_number', $card_number);
            }
            $updateStmt->bindParam(':id_tg', $id_tg);
            $updateStmt->execute();

            $response = ['success' => true, 'message' => 'User data updated successfully'];
        } else {
            // Создаем нового пользователя
            $insertStmt = $pdo->prepare("INSERT INTO users (id_tg, full_name, phone_number, address" . ($card_number ? ", card_number" : "") . ") VALUES (:id_tg, :full_name, :phone_number, :address" . ($card_number ? ", :card_number" : "") . ")");
            $insertStmt->bindParam(':id_tg', $id_tg);
            $insertStmt->bindParam(':full_name', $full_name);
            $insertStmt->bindParam(':phone_number', $phone_number);
            $insertStmt->bindParam(':address', $address);
            if ($card_number) {
                $insertStmt->bindParam(':card_number', $card_number);
            }
            $insertStmt->execute();

            $response = ['success' => true, 'message' => 'User data saved successfully'];
        }

        echo json_encode($response);
    } catch (PDOException $e) {
        $response = ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        echo json_encode($response);
    }
} else {
    $response = ['success' => false, 'message' => 'Missing required fields'];
    echo json_encode($response);
}
?>