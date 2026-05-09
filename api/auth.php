<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'check') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode(['logged_in' => true, 'username' => $_SESSION['username']]);
    } else {
        echo json_encode(['logged_in' => false]);
    }
    exit;
}

if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

// Decode incoming JSON
$data = json_decode(file_get_contents("php://input"));

if ($action === 'register') {
    if(!isset($data->username) || !isset($data->password)) {
        echo json_encode(['success' => false, 'message' => 'Missing fields']); exit;
    }
    try {
        $hash = password_hash($data->password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute([$data->username, $hash]);
        
        // Setup session
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['username'] = $data->username;
        
        // Log it
        $logStmt = $pdo->prepare("INSERT INTO audit_logs (username, action) VALUES (?, ?)");
        $logStmt->execute([$data->username, "Created a new account and logged in."]);
        
        session_write_close(); // Force session to save before redirect
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        // THIS IS THE FIX: Show the EXACT error instead of guessing
        echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
    }
    exit;
}

if ($action === 'login') {
    if(!isset($data->username) || !isset($data->password)) {
        echo json_encode(['success' => false, 'message' => 'Missing fields']); exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->execute([$data->username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify Hash
        if ($user && password_verify($data->password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            
            // Log it
            $logStmt = $pdo->prepare("INSERT INTO audit_logs (username, action) VALUES (?, ?)");
            $logStmt->execute([$user['username'], "Logged into the system."]);

            session_write_close(); // Force session to save before redirect
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Incorrect Username or Password.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
    }
    exit;
}
?> 