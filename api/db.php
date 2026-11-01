<?php

function getDbConnection() {
    $host = ""; 
    $port = ""; 
    $dbname = "; 
    $user = ""; 
    $password = ""; 
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;";
    
    try {
        $pdo = new PDO($dsn, $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Connection failed: " . $e->getMessage()]);
        exit;
    }
}
?>