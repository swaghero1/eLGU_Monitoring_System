<?php
header('Content-Type: application/json'); require_once '../db.php'; session_start();
$data = json_decode(file_get_contents("php://input")); $admin = isset($_SESSION['username']) ? $_SESSION['username'] : 'System Admin';

if (isset($data->id)) {
    try {
        $pdo->beginTransaction();
        
        // Fetch specific task name/description for the Audit Log
        $stmtDesc = $pdo->prepare("SELECT description FROM tasks WHERE id = ?");
        $stmtDesc->execute([$data->id]);
        $task = $stmtDesc->fetch();
        $desc = $task ? $task['description'] : 'Unknown Task';
        
        $stmt = $pdo->prepare("UPDATE tasks SET status = 'Completed', date_completed = CURDATE() WHERE id = :id"); $stmt->execute([':id' => $data->id]);
        
        $auditStmt = $pdo->prepare("INSERT INTO audit_logs (username, action) VALUES (:admin, :msg)"); 
        $auditStmt->execute([':admin' => $admin, ':msg' => "Marked task: '" . $desc . "' as completed."]);
        
        $pdo->commit(); echo json_encode(['success' => true]);
    } catch (PDOException $e) { $pdo->rollBack(); echo json_encode(['success'=>false, 'message'=>$e->getMessage()]); }
}
?> 