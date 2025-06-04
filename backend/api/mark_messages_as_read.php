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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$currentUserId = $_SESSION['user_id'];

$data = json_decode(file_get_contents('php://input'), true);
$conversationId = $data['conversationId'] ?? null;

// Ensure conversationId is provided
if (!$conversationId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required field: conversationId']);
    exit;
}

// Ensure conversationId is an integer
$conversationId = (int) $conversationId;

try {
    // Mark messages as read for the current user in the specified conversation
    // Only mark messages sent by the other participant as read
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND recipient_id = ? AND is_read = 0");
    $stmt->execute([$conversationId, $currentUserId]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Database error marking messages as read: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
}
?>
