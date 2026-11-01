<?php
include 'cors.php';
include 'db.php';
header('Content-Type: application/json');

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data && isset($data['restaurantId'], $data['id'])) {
    $restaurantId = $data['restaurantId'];
    $id = $data['id'];

    try {
        $pdo = getDbConnection();

        // Сначала получаем путь к изображению из базы данных
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = :id AND restaurant_id = :restaurantId");
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':restaurantId', $restaurantId);
        $stmt->execute();

        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $imagePath = $product['image']; // Предполагаем, что здесь хранится относительный путь к изображению
            $fullImagePath = '../' . $imagePath; // Относительный путь к изображению, учитывая, что uploads на один уровень выше

            // Удаляем товар из базы данных
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id AND restaurant_id = :restaurantId");
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':restaurantId', $restaurantId);
            $stmt->execute();

            // Удаляем файл изображения с сервера, если он существует
            if (file_exists($fullImagePath)) {
                unlink($fullImagePath);
            }

            echo json_encode(['success' => true, 'message' => 'Product and associated image deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error deleting product: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'restaurantId or id not provided']);
}
?>