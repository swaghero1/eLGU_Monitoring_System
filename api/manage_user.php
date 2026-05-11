<?php
session_start(); header('Content-Type: application/json'); require_once '../db.php';
$data = json_decode(file_get_contents("php://input")); $action = isset($_GET['action']) ? $_GET['action'] : '';
$admin = isset($_SESSION['username']) ? $_SESSION['username'] : 'System Admin';

// EXACT LOCALHOST SENDER EMAIL CONFIGURED HERE
$senderEmail = "ae202403685@wmsu.edu.ph"; 
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-type:text/html;charset=UTF-8\r\n";
$headers .= "From: eLGU Command Center <" . $senderEmail . ">\r\n";

try {
    if ($action === 'delete') {
        $uStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?"); $uStmt->execute([$data->id]);
        $targetUser = $uStmt->fetchColumn() ?: 'Unknown User';

        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$data->id]);
        $pdo->prepare("INSERT INTO audit_logs (username, action) VALUES (?, ?)")->execute([$admin, "Permanently deleted user account: " . $targetUser]);
        echo json_encode(['success' => true]);
    } 
    elseif ($action === 'update') {
        $uStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?"); $uStmt->execute([$data->id]);
        $targetUser = $uStmt->fetchColumn() ?: 'Unknown User';

        $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?"); $stmt->execute([$data->username, $data->id]);
        if(!empty($data->password)) {
            $hash = password_hash($data->password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $data->id]);
        }
        $pdo->prepare("INSERT INTO audit_logs (username, action) VALUES (?, ?)")->execute([$admin, "Updated credentials for system user: " . $targetUser]);
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'status') {
        $uStmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?"); $uStmt->execute([$data->id]);
        $res = $uStmt->fetch(); $targetUser = $res ? $res['username'] : 'Unknown User';

        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?"); $stmt->execute([$data->status, $data->id]);
        
        $act = ($data->status === 'Approved') ? 'Approved' : 'Disapproved';
        $pdo->prepare("INSERT INTO audit_logs (username, action) VALUES (?, ?)")->execute([$admin, $act . " " . $targetUser . " as a system user."]);
        
        if($res && !empty($res['email'])) {
            $to = $res['email'];
            $subject = "eLGU Command Center - Account " . $data->status;
            
            if ($data->status === 'Approved') {
                $msg = "<html><body style='font-family:Arial,sans-serif; color:#333;'><h2 style='color:#10b981;'>Account Approved</h2><p>Hello <strong>{$targetUser}</strong>,</p><p>Your account has been officially approved by the administrator.</p><p>You may now log in to the eLGU Command Center.</p></body></html>";
            } else {
                $msg = "<html><body style='font-family:Arial,sans-serif; color:#333;'><h2 style='color:#ef4444;'>Account Disapproved</h2><p>Hello <strong>{$targetUser}</strong>,</p><p>We regret to inform you that your account registration was disapproved by the administrator and you cannot access the system.</p></body></html>";
            }
            
            @mail($to, $subject, $msg, $headers);
        }
        
        echo json_encode(['success' => true]);
    }
} catch (PDOException $e) { echo json_encode(['success' => false, 'message' => $e->getMessage()]); }
?> 