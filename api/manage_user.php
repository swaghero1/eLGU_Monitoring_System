<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

$data = json_decode(file_get_contents("php://input"));
$action = isset($_GET['action']) ? $_GET['action'] : '';
$admin = isset($_SESSION['username']) ? $_SESSION['username'] : 'System';

try {
    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$data->id]);
        $pdo->prepare("INSERT INTO audit_logs (username, action) VALUES (?, ?)")->execute([$admin, "Deleted user ID: " . $data->id]);
        echo json_encode(['success' => true]);
    } 
    elseif ($action === 'update') {
        if(!empty($data->password)) {
            $hash = password_hash($data->password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
            $stmt->execute([$data->username, $hash, $data->id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
            $stmt->execute([$data->username, $data->id]);
        }
        $pdo->prepare("INSERT INTO audit_logs (username, action) VALUES (?, ?)")->execute([$admin, "Updated user: " . $data->username]);
        echo json_encode(['success' => true]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 