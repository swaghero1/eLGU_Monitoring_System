<?php
// api/update_version.php
header('Content-Type: application/json');
require_once '../db.php';

$rawData = file_get_contents("php://input");
$data = json_decode($rawData);

if (isset($data->id) && isset($data->version) && isset($data->status)) {
    try {
        // Securely determine which column to update to prevent SQL injection
        $column = ($data->version === 'v1') ? 'v1_operational' : 'v2_operational';
        $statusVal = ($data->status === true) ? 1 : 0;
        
        $sql = "UPDATE lgus SET $column = :status WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':status', $statusVal, PDO::PARAM_INT);
        $stmt->bindParam(':id', $data->id, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update version status.']);
        }
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request.']);
}
?>              