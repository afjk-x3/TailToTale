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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$currentUserId = $_SESSION['user_id'];
$conversationId = $_GET['conversationId'] ?? null;

if (!$conversationId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameter: conversationId']);
    exit;
}

// Ensure conversationId is an integer
$conversationId = (int) $conversationId;

try {
    // Verify that the current user is a participant in this conversation
    $stmt = $pdo->prepare("SELECT id FROM conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
    $stmt->execute([$conversationId, $currentUserId, $currentUserId]);
    $conversation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$conversation) {
        http_response_code(403);
        echo json_encode(['error' => 'User is not a participant in this conversation']);
        exit;
    }

    // Fetch messages for the conversation, ordered by time
    $stmt = $pdo->prepare("SELECT id, conversation_id, sender_id, message_text, sent_at FROM messages WHERE conversation_id = ? ORDER BY sent_at ASC");
    $stmt->execute([$conversationId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Database error fetching messages: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
}

?>