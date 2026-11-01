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

        // SQL-запрос для получения продуктов по restaurantId, приведение id к строке
        $stmt = $pdo->prepare("
            SELECT 
                CAST(p.id AS TEXT) AS id, 
                p.name, 
                p.price, 
                p.image, 
                CAST(p.category_id AS TEXT) AS category_id, 
                p.description
            FROM products p
            WHERE p.restaurant_id = :restaurantId
        ");
        $stmt->bindParam(':restaurantId', $restaurantId);
        $stmt->execute();

        // Получение всех продуктов
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Форматирование продуктов для ответа
        $formattedProducts = [];
        foreach ($products as $product) {
            // Запрос для получения имени категории из таблицы categories
            $stmtCategory = $pdo->prepare("
                SELECT name 
                FROM categories 
                WHERE id = :categoryId 
                AND restaurant_id = :restaurantId
            ");
            $stmtCategory->bindParam(':categoryId', $product['category_id']);
            $stmtCategory->bindParam(':restaurantId', $restaurantId);
            $stmtCategory->execute();
            $category = $stmtCategory->fetch(PDO::FETCH_ASSOC);
            
            // Добавляем данные продукта, включая имя категории
            $formattedProducts[] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => 'https://testingnil7.ru:8000/' . $product['image'],
                'categoryId' => $product['category_id'],
                'categoryName' => $category ? $category['name'] : null, // Имя категории или null, если не найдено
                'description' => $product['description']
            ];
        }

        // Возвращаем продукты в виде JSON
        $response = [
            'status' => 'success',
            'products' => $formattedProducts
        ];
        echo json_encode($response);
    } catch (PDOException $e) {
        // Обработка ошибок
        $response = [
            'status' => 'error',
            'message' => 'Ошибка при получении продуктов: ' . $e->getMessage()
        ];
        echo json_encode($response);
    }
} else {
    // Ошибка, если не указан restaurantId
    $response = [
        'status' => 'error',
        'message' => 'Не указан restaurantId'
    ];
    echo json_encode($response);
}
?>
