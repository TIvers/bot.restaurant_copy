<?php
include 'cors.php';
include 'db.php';
header('Content-Type: application/json');

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data && isset($data['restaurantId'], $data['categoryId'], $data['name'], $data['price'], $data['description'], $data['image'])) {
    $restaurantId = $data['restaurantId'];
    $categoryId = $data['categoryId'];
    $name = $data['name'];
    $price = $data['price'];
    $description = $data['description'];
    $base64Image = $data['image'];

    try {
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

        // Save the image file
        if (file_put_contents($imagePath, $imageData) !== false) {
            // Insert product data with image path
            $pdo = getDbConnection();
            $stmt = $pdo->prepare("INSERT INTO products (restaurant_id, name, category_id, price, description, image) VALUES (:restaurantId, :name, :categoryId, :price, :description, :image)");
            $stmt->bindParam(':restaurantId', $restaurantId);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':categoryId', $categoryId);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':image', $relativeImagePath);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Product added successfully']);
        } else {
            throw new Exception('Failed to save image');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error adding product: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Incomplete product data']);
}
?>