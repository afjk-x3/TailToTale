<?php
session_start();
require_once '../config/database.php'; // Correct path to database.php

header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$currentUserId = $_SESSION['user_id'];
$currentUserType = $_SESSION['user_type'];

try {
    $targetUserType = ($currentUserType === 'adopter') ? 'rehomer' : 'adopter';

    // Fetch all users except the current user
    $stmt = $pdo->prepare("SELECT id, fullname, email, profile_picture FROM users WHERE id != ?");
    $stmt->execute([$currentUserId]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Change 'username' to 'fullname' in the output array keys for consistency with DB
    $formattedUsers = [];
    foreach ($users as $user) {
        $formattedUsers[] = [
            'id' => $user['id'],
            'username' => $user['fullname'], // Continue using 'username' key for frontend compatibility
            'email' => $user['email'],
            'profile_picture' => $user['profile_picture']
        ];
    }

    echo json_encode([
        'success' => true,
        'users' => $formattedUsers
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Database error fetching users to message: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
}

?>