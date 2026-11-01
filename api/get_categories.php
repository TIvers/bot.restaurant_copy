<?php
include 'cors.php';
include 'db.php';
header('Content-Type: application/json');

// Get the raw POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data && isset($data['restaurantId'])) {
    $restaurantId = $data['restaurantId'];

    try {
        // Database connection
        $pdo = getDbConnection();

        // Query to get categories for the given restaurantId
        $stmt = $pdo->prepare("SELECT CAST(id AS TEXT) AS id, name, image FROM categories WHERE restaurant_id = :restaurantId");
        $stmt->bindParam(':restaurantId', $restaurantId);
        $stmt->execute();

        // Fetch all categories
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Reformat categories to rename fields
        $formattedCategories = array_map(function($category) {
            return [
                'id' => $category['id'],  
                'name' => $category['name'],  
                'image' => 'https://testingnil7.ru:8000/' . $category['image'] 
            ];
        }, $categories);

        // Return formatted categories as JSON
        $response = [
            'status' => 'success',
            'categories' => $formattedCategories
        ];
        echo json_encode($response);
    } catch (PDOException $e) {
        // Error handling
        $response = [
            'status' => 'error',
            'message' => 'Ошибка при получении категорий: ' . $e->getMessage()
        ];
        echo json_encode($response);
    }
} else {
    // Error if no restaurantId provided
    $response = [
        'status' => 'error',
        'message' => 'Не указан restaurantId'
    ];
    echo json_encode($response);
}
?>