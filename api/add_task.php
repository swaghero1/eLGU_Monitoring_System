<?php
header('Content-Type: application/json');
require_once '../db.php';
$data = json_decode(file_get_contents("php://input"));

if (isset($data->lgu_id)) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO tasks (lgu_id, task_name, description, due_date, status, date_started, target_system) 
            VALUES (:lgu_id, :personnel, :desc, :due, 'Pending', CURDATE(), :target)
        ");
        $stmt->execute([
            ':lgu_id' => $data->lgu_id,
            ':personnel' => $data->personnel,
            ':desc' => $data->description,
            ':due' => $data->due_date,
            ':target' => isset($data->target_system) ? $data->target_system : 'General'
        ]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
}
?> 