<?php
error_reporting(0);
include 'cors.php';
include 'db.php';
header('Content-Type: application/json');

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data && isset($data['restaurantId'], $data['name'], $data['image'])) {
    $restaurantId = $data['restaurantId'];
    $name = $data['name'];
    $imageBase64 = $data['image'];

    // Decode base64 image
    $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $imageBase64));

    // Define the uploads directory
    $uploadsDir = dirname(__FILE__, 2) . '/uploads';
    if (!file_exists($uploadsDir)) {
        mkdir($uploadsDir, 0777, true); // Create the directory if it doesn't exist
    }

    // Generate a unique file name
    $fileName = uniqid() . '.png';
    $filePath = $uploadsDir . '/' . $fileName;

    // Save the image file
    if (file_put_contents($filePath, $imageData) !== false) {
        // Save only the relative path to the database
        $relativeFilePath = 'uploads/' . $fileName;

        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare("INSERT INTO categories (restaurant_id, name, image) VALUES (:restaurantId, :name, :imagePath)");
            $stmt->bindParam(':restaurantId', $restaurantId);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':imagePath', $relativeFilePath);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Category added successfully']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error adding category: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save image']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'restaurantId, name, or image not provided']);
}
?>
