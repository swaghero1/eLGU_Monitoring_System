<?php
header('Content-Type: application/json');
require_once '../db.php';

$rawData = file_get_contents("php://input");
$data = json_decode($rawData);

if (isset($data->id)) {
    try {
        $stmt = $pdo->prepare("UPDATE tasks SET status = 'Pending' WHERE id = :id");
        $stmt->bindParam(':id', $data->id, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false]);
    }
}
?>         