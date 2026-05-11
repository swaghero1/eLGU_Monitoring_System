<?php
header('Content-Type: application/json');
session_start();

// Security check: ensure only logged in users can upload
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Set the directory where files will be saved (one level up from /api into /uploads)
$target_dir = "../uploads/"; 

// Automatically create the folder if it doesn't exist yet
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    // Check for standard upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Upload error code: ' . $file['error']]);
        exit;
    }

    // Clean the filename to remove spaces and special characters
    $original_name = basename($file["name"]);
    $clean_name = preg_replace("/[^A-Za-z0-9_\-\.]/", '_', $original_name);
    
    // Add a unique timestamp so files with the same name don't overwrite each other
    $unique_name = time() . "_" . $clean_name;
    $target_file = $target_dir . $unique_name;

    // Move the file from temporary server memory to your hard drive
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        // Return the clean string path back to app.js so it can be saved in the database
        echo json_encode(['success' => true, 'file_path' => 'uploads/' . $unique_name]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Server failed to save the physical file.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No file received by the server.']);
}
?> 