<?php
header('Content-Type: application/json');
require_once '../db.php';
try {
    $stmt = $pdo->query("SELECT * FROM audit_logs ORDER BY timestamp DESC LIMIT 200");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $logs]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 