<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once '../db.php';

try {
    // Pulls 'task_name' as personnel, gets dates to show Started/Completed
    $sql = "SELECT t.id, t.lgu_id, t.task_name as personnel, t.description, t.due_date, t.date_started, t.date_completed, t.status, 
                   l.municipality, l.province 
            FROM tasks t 
            LEFT JOIN lgus l ON t.lgu_id = l.id 
            ORDER BY t.id DESC";

    $stmt = $pdo->query($sql);
    $tasks = $stmt->fetchAll();
    echo json_encode(["success" => true, "data" => $tasks]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database Error: " . $e->getMessage()]);
}
?> 