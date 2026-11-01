<?php
include 'cors.php';
include 'db.php';
header('Content-Type: application/json');

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data && isset($data['restaurantId'], $data['id'])) {
    $restaurantId = $data['restaurantId'];
    $categoryId = $data['id'];

    try {
        $pdo = getDbConnection();

        // First, delete the category's image if it exists
        $stmt = $pdo->prepare("SELECT image FROM categories WHERE id = :id AND restaurant_id = :restaurantId");
        $stmt->bindParam(':id', $categoryId);
        $stmt->bindParam(':restaurantId', $restaurantId);
        $stmt->execute();
        $category = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($category && !empty($category['image'])) {
            $imagePath = dirname(__FILE__, 2) . '/' . $category['image'];
            if (file_exists($imagePath)) {
                unlink($imagePath); // Delete the image file
            }
        }

        // Delete associated product images and products
        $stmt = $pdo->prepare("SELECT image FROM products WHERE category_id = :categoryId AND restaurant_id = :restaurantId");
        $stmt->bindParam(':categoryId', $categoryId);
        $stmt->bindParam(':restaurantId', $restaurantId);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as $product) {
            if (!empty($product['image'])) {
                $productImagePath = dirname(__FILE__, 2) . '/' . $product['image'];
                if (file_exists($productImagePath)) {
                    unlink($productImagePath); // Delete each product image
                }
            }
        }

        // Delete products from the products table
        $stmt = $pdo->prepare("DELETE FROM products WHERE category_id = :categoryId AND restaurant_id = :restaurantId");
        $stmt->bindParam(':categoryId', $categoryId);
        $stmt->bindParam(':restaurantId', $restaurantId);
        $stmt->execute();

        // Finally, delete the category
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id AND restaurant_id = :restaurantId");
        $stmt->bindParam(':id', $categoryId);
        $stmt->bindParam(':restaurantId', $restaurantId);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Category and associated products deleted successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error deleting category or products: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'restaurantId or categoryId not provided']);
}
?>
