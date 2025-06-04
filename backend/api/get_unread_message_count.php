<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$currentUserId = $_SESSION['user_id'];

try {
    // Count unread messages for the current user where they are the recipient
    $stmt = $pdo->prepare("SELECT COUNT(*) as unread_count FROM messages WHERE recipient_id = ? AND is_read = 0");
    $stmt->execute([$currentUserId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $unreadCount = $result['unread_count'] ?? 0;

    echo json_encode(['success' => true, 'unread_count' => $unreadCount]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Database error getting unread message count: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
}
?>
