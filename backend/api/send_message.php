<?php
session_start();
require_once '../config/database.php'; // Correct path to database.php

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
$messageText = $data['messageText'] ?? null;

// Ensure conversationId and messageText are provided
if (!$conversationId || !isset($messageText)) { // Check for isset for messageText to allow empty string if needed, though trim is used later
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: conversationId or messageText']);
    exit;
}

// Ensure conversationId is an integer
$conversationId = (int) $conversationId;

// Trim whitespace from message text
$messageText = trim($messageText);

// Don't send empty messages after trimming
if (empty($messageText)) {
    http_response_code(400);
    echo json_encode(['error' => 'Message text cannot be empty']);
    exit();
}


try {
    // Verify that the current user is a participant in this conversation
    $stmt = $pdo->prepare("SELECT id, user1_id, user2_id FROM conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
    $stmt->execute([$conversationId, $currentUserId, $currentUserId]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$conversation) {
        http_response_code(403);
        echo json_encode(['error' => 'User is not a participant in this conversation']);
        exit;
    }

    // Determine the recipient ID
    $recipientId = ($conversation['user1_id'] == $currentUserId) ? $conversation['user2_id'] : $conversation['user1_id'];

    // Insert the new message
    $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, recipient_id, message_text, is_read) VALUES (?, ?, ?, ?, 0)");
    $stmt->execute([$conversationId, $currentUserId, $recipientId, $messageText]);
    $newMessageId = $pdo->lastInsertId();

    // Update the conversation with the last message ID and timestamp
    $stmt = $pdo->prepare("UPDATE conversations SET last_message_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$newMessageId, $conversationId]);

    echo json_encode([
        'success' => true,
        'messageId' => $newMessageId,
        'conversationId' => $conversationId,
        'sent_at' => date('Y-m-d H:i:s') // Provide the server timestamp for immediate display
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Database error sending message: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
}

?>