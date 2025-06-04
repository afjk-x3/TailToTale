<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['profile_picture'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$userId = $_SESSION['user_id'];
$uploadDir = '../../frontend/assets/images/profile_pics/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$file = $_FILES['profile_picture'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
if (!in_array($ext, $allowed)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type']);
    exit;
}

// Generate a unique filename
$filename = 'user_' . $userId . '_' . time() . '.' . $ext;
$targetPath = $uploadDir . $filename;
$relativePath = '/TAILTOTALE/frontend/assets/images/profile_pics/' . $filename;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // Update the user's profile_picture in the database
    $conn = new mysqli('localhost', 'root', '', 'tailtotale');
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'error' => 'DB error']);
        exit;
    }
    $stmt = $conn->prepare('UPDATE users SET profile_picture = ? WHERE id = ?');
    $stmt->bind_param('si', $relativePath, $userId);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'profile_picture_url' => $relativePath]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update profile picture']);
    }
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to upload file']);
} 