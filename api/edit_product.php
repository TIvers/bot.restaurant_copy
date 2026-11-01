<?php
include 'cors.php';
include 'db.php';
header('Content-Type: application/json');

// Получаем данные POST-запроса
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data && isset($data['restaurantId'], $data['productId'], $data['name'], $data['price'], $data['description'])) {
    $restaurantId = $data['restaurantId'];
    $productId = $data['productId'];
    $name = $data['name'];
    $price = $data['price'];
    $description = $data['description'];
    $base64Image = isset($data['image']) ? $data['image'] : null;

    try {
        // Подключение к базе данных
        $pdo = getDbConnection();

        // Получаем текущий путь к изображению
        $stmt = $pdo->prepare("SELECT image FROM products WHERE id = :productId AND restaurant_id = :restaurantId");
        $stmt->bindParam(':productId', $productId);
        $stmt->bindParam(':restaurantId', $restaurantId);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $currentImagePath = $product['image'];
            $fullCurrentImagePath = '../' . $currentImagePath;

            // Обновление информации о продукте
            if ($base64Image !== null && $base64Image !== '') {
                // Decode base64 image
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));

                // Define the uploads directory
                $uploadsDir = dirname(__FILE__, 2) . '/uploads';
                if (!file_exists($uploadsDir)) {
                    mkdir($uploadsDir, 0777, true); // Create the directory if it doesn't exist
                }

                // Generate a unique file name with .png extension
                $imageName = uniqid() . '.png';
                $imagePath = $uploadsDir . '/' . $imageName;
                $relativeImagePath = 'uploads/' . $imageName;

                // Save the new image file
                if (file_put_contents($imagePath, $imageData) !== false) {
                    // Удаляем старое изображение, если оно существует
                    if (file_exists($fullCurrentImagePath)) {
                        unlink($fullCurrentImagePath);
                    }

                    // Update product data with new image path
                    $stmt = $pdo->prepare("UPDATE products SET name = :name, price = :price, description = :description, image = :image WHERE id = :productId AND restaurant_id = :restaurantId");
                    $stmt->bindParam(':image', $relativeImagePath);
                } else {
                    throw new Exception('Failed to save new image');
                }
            } else {
                $stmt = $pdo->prepare("UPDATE products SET name = :name, price = :price, description = :description WHERE id = :productId AND restaurant_id = :restaurantId");
            }

            $stmt->bindParam(':restaurantId', $restaurantId);
            $stmt->bindParam(':productId', $productId);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':description', $description);
            $stmt->execute();

            // Проверка успешности обновления
            if ($stmt->rowCount() > 0) {
                $response = [
                    'success' => true,
                    'message' => 'Product updated successfully'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Failed to update product'
                ];
            }

            echo json_encode($response);
        } else {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
        }
    } catch (Exception $e) {
        // Обработка ошибок базы данных
        $response = [
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ];
        echo json_encode($response);
    }
} else {
    // Ошибка, если не указаны необходимые параметры
    $response = [
        'success' => false,
        'message' => 'Missing restaurantId, productId, name, price or description'
    ];
    echo json_encode($response);
}
?>
