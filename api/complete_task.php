<?php
header('Content-Type: application/json'); require_once '../db.php'; session_start();
$data = json_decode(file_get_contents("php://input")); $admin = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'System Admin';

if (isset($data->id)) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE tasks SET status = 'Completed', date_completed = CURDATE() WHERE id = :id"); $stmt->execute([':id' => $data->id]);
        $auditStmt = $pdo->prepare("INSERT INTO audit_logs (admin_user, action, details) VALUES (:admin, 'Task Completed', CONCAT('Marked Task ID ', :id, ' as Completed.'))"); $auditStmt->execute([':admin' => $admin, ':id' => $data->id]);
        $pdo->commit(); echo json_encode(['success' => true]);
    } catch (PDOException $e) { $pdo->rollBack(); echo json_encode(['success'=>false, 'message'=>$e->getMessage()]); }
}
?> 