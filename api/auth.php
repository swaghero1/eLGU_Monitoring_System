<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';

// Ensure default timezone is set to sync PHP and DB
date_default_timezone_set('Asia/Manila');

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

$data = json_decode(file_get_contents("php://input"));

// ==========================================
// REGISTRATION (Admin Approval Required)
// ==========================================
if ($action === 'register') {
    if(!isset($data->username) || !isset($data->password) || !isset($data->email)) {
        echo json_encode(['success' => false, 'message' => 'Missing fields']); exit;
    }
    
    // NEW RULE: Password must be greater than 7 characters
    if(strlen(trim($data->password)) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']); exit;
    }

    try {
        $hash = password_hash($data->password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, status) VALUES (?, ?, ?, 'Pending')");
        $stmt->execute([$data->username, $hash, $data->email]);
        
        $logStmt = $pdo->prepare("INSERT INTO audit_logs (username, action) VALUES (?, ?)");
        $logStmt->execute([$data->username, "Registered new account. Pending admin approval."]);
        
        echo json_encode(['success' => true, 'message' => 'Account created! Please wait for Admin approval.']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
    }
    exit;
}

// ==========================================
// LOGIN: STRICT 5 MINUTE LOCKOUT (PHP CLOCK)
// ==========================================
if ($action === 'login') {
    if(!isset($data->username) || !isset($data->password)) {
        echo json_encode(['success' => false, 'message' => 'Missing fields']); exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$data->username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Incorrect Username or Password.']); exit;
        }

        $current_time = time();
        if ($user['lockout_time']) {
            $lock_time = strtotime($user['lockout_time']);
            if ($lock_time > $current_time) {
                $diff = ceil(($lock_time - $current_time) / 60);
                echo json_encode(['success' => false, 'message' => "Account locked. Try again in $diff minute(s)."]); exit;
            } else {
                if ($user['failed_attempts'] >= 3) {
                    $pdo->prepare("UPDATE users SET failed_attempts = 0, lockout_time = NULL WHERE id = ?")->execute([$user['id']]);
                    $user['failed_attempts'] = 0;
                }
            }
        }

        if ($user['status'] === 'Pending') {
            echo json_encode(['success' => false, 'message' => 'Account is pending Admin approval.']); exit;
        }
        if ($user['status'] === 'Disapproved') {
            echo json_encode(['success' => false, 'message' => 'Account has been deactivated/disapproved by Admin.']); exit;
        }

        if (password_verify($data->password, $user['password'])) {
            $pdo->prepare("UPDATE users SET failed_attempts = 0, lockout_time = NULL WHERE id = ?")->execute([$user['id']]);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $pdo->prepare("INSERT INTO audit_logs (username, action) VALUES (?, ?)")->execute([$user['username'], "Logged into the system."]);
            echo json_encode(['success' => true]);
        } else {
            $attempts = (int)$user['failed_attempts'] + 1;
            if ($attempts >= 3) {
                $future_lockout = date('Y-m-d H:i:s', time() + (5 * 60)); 
                $pdo->prepare("UPDATE users SET failed_attempts = ?, lockout_time = ? WHERE id = ?")->execute([$attempts, $future_lockout, $user['id']]);
                echo json_encode(['success' => false, 'message' => '3 failed attempts. Account locked for 5 minutes.']);
            } else {
                $pdo->prepare("UPDATE users SET failed_attempts = ? WHERE id = ?")->execute([$attempts, $user['id']]);
                $left = 3 - $attempts;
                echo json_encode(['success' => false, 'message' => "Incorrect Password. You have $left attempt(s) left."]);
            }
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
    }
    exit;
}

// ==========================================
// OTP REQUEST ENGINE
// ==========================================
if ($action === 'request_otp') {
    if(!isset($data->username)) { echo json_encode(['success' => false, 'message' => 'Username required']); exit; }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$data->username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || empty($user['email'])) { 
        echo json_encode(['success' => false, 'message' => 'No recovery email found for this user.']); exit; 
    } 

    $current_time = time();

    if ($user['otp_lockout']) {
        $lock_time = strtotime($user['otp_lockout']);
        if ($lock_time > $current_time) {
            $diff = ceil(($lock_time - $current_time) / 60);
            echo json_encode(['success' => false, 'message' => "Maximum requests reached. Wait $diff minutes."]); exit;
        }
    }

    if ($user['otp_expires']) {
        $exp_time = strtotime($user['otp_expires']);
        $timeSinceLastReq = 180 - ($exp_time - $current_time); 
        if ($timeSinceLastReq > 0 && $timeSinceLastReq < 60) {
            $wait = 60 - $timeSinceLastReq;
            echo json_encode(['success' => false, 'message' => "Please wait $wait seconds before requesting another code."]); exit;
        }
    }

    $requests = (int)$user['otp_requests'] + 1;
    if ($requests > 3) {
        $future_lockout = date('Y-m-d H:i:s', time() + (20 * 60)); // 20 minutes
        $pdo->prepare("UPDATE users SET otp_requests = 0, otp_lockout = ? WHERE id = ?")->execute([$future_lockout, $user['id']]);
        echo json_encode(['success' => false, 'message' => 'Maximum requests reached. Wait 20 minutes.']); exit;
    }

    $code = rand(100000, 999999);
    $otp_expires = date('Y-m-d H:i:s', time() + (3 * 60));
    
    $pdo->prepare("UPDATE users SET otp_code = ?, otp_expires = ?, otp_requests = ? WHERE id = ?")
        ->execute([$code, $otp_expires, $requests, $user['id']]);

    $senderEmail = "ae202403685@wmsu.edu.ph"; 
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: eLGU Security <" . $senderEmail . ">\r\n";

    $subject = "Your eLGU Security Code";
    $msg = "<html><body style='font-family:Arial,sans-serif; color:#333;'><h2 style='color:#1A44E8;'>Security Verification</h2><p>Hello <strong>{$user['username']}</strong>,</p><p>Your password reset code is: <strong style='font-size:1.5rem; color:#A81C1C;'>{$code}</strong></p><p>This code expires in exactly 3 minutes.</p></body></html>";

    @mail($user['email'], $subject, $msg, $headers);
    echo json_encode(['success' => true]); exit;
}

// ==========================================
// RESET PASSWORD
// ==========================================
if ($action === 'reset_password') {
    // NEW RULE: Password must be greater than 7 characters
    if(strlen(trim($data->new_password)) < 8) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters long.']); exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$data->username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['otp_code'] !== $data->code) {
        echo json_encode(['success' => false, 'message' => 'Invalid Verification Code.']); exit;
    }
    
    $current_time = time();
    $exp_time = strtotime($user['otp_expires']);
    if ($exp_time < $current_time) {
        echo json_encode(['success' => false, 'message' => 'Code has expired. Please request a new one.']); exit;
    }

    $hash = password_hash($data->new_password, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password = ?, otp_code = NULL, otp_expires = NULL, otp_requests = 0 WHERE id = ?")
        ->execute([$hash, $user['id']]);
    
    $pdo->prepare("INSERT INTO audit_logs (username, action) VALUES (?, ?)")->execute([$user['username'], "Password changed via email OTP."]);
    echo json_encode(['success' => true]); exit;
}

// ==========================================
// SETTINGS APP GET/SET EMAILS
// ==========================================
if ($action === 'get_my_email') {
    if(!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false]); exit; }
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success'=>true, 'email'=>$res['email']]);
    exit;
}

if ($action === 'update_my_email') {
    if(!isset($_SESSION['user_id']) || !isset($data->email)) { echo json_encode(['success'=>false]); exit; }
    $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
    $stmt->execute([$data->email, $_SESSION['user_id']]);
    $pdo->prepare("INSERT INTO audit_logs (username, action) VALUES (?, ?)")->execute([$_SESSION['username'], "Updated personal recovery email address."]);
    echo json_encode(['success'=>true]);
    exit;
}
?> 