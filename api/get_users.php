<?php
header('Content-Type: application/json');
require_once '../db.php';
session_start();

try {
    // FIXED: Added 'status' and 'email' to the SELECT query
    $stmt = $pdo->query("SELECT id, username, email, status, created_at FROM users ORDER BY username ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $users]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 