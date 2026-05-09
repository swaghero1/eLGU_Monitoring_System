<?php
// api/update_status.php
header('Content-Type: application/json');
require_once '../db.php';

$rawData = file_get_contents("php://input");
$data = json_decode($rawData);

if (isset($data->id) && isset($data->status)) {
    try {
        // If a score is provided, update it. Otherwise, keep it null.
        $score = (isset($data->score) && $data->score !== '') ? $data->score : null;
        
        $stmt = $pdo->prepare("UPDATE lgus SET overall_status = :status, ereadiness_score = :score WHERE id = :id");
        $stmt->execute([
            ':status' => $data->status,
            ':score' => $score,
            ':id' => $data->id
        ]);
        
        echo json_encode(['success' => true]);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Missing ID or Status']);
}
?>          