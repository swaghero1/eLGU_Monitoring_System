<?php
// db.php
$host = '127.0.0.1';
$port = '3307'; // Explicitly set to your custom XAMPP port
$db   = 'elgu_monitoring';
$user = 'root'; 
$pass = '';     
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // If it fails, send a JSON error so the frontend knows what happened
    die(json_encode(["success" => false, "message" => "Connection failed: " . $e->getMessage()]));
}
?> 