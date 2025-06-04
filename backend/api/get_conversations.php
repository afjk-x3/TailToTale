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

$currentUserId = $_SESSION['user_id'];

try {
    // Fetch conversations where the current user is user1 or user2
    // Join with users table to get participant details (using fullname)
    // Join with messages table to get the last message
    $stmt = $pdo->prepare("
        SELECT
            c.id AS conversation_id,
            c.user1_id,
            c.user2_id,
            c.updated_at AS last_message_time,
            u1.fullname AS user1_username, -- Changed to fullname
            u1.profile_picture AS user1_profile_picture,
            u2.fullname AS user2_username, -- Changed to fullname
            u2.profile_picture AS user2_profile_picture,
            m.message_text AS last_message_text,
            m.sender_id AS last_message_sender_id,
            COALESCE(um.unread_count, 0) AS unread_count
        FROM conversations c
        JOIN users u1 ON c.user1_id = u1.id
        JOIN users u2 ON c.user2_id = u2.id
        LEFT JOIN messages m ON c.last_message_id = m.id -- Use LEFT JOIN for conversations with no messages yet
        LEFT JOIN (
            SELECT conversation_id, COUNT(*) AS unread_count
            FROM messages
            WHERE recipient_id = ? AND is_read = 0
            GROUP BY conversation_id
        ) um ON c.id = um.conversation_id
        WHERE c.user1_id = ? OR c.user2_id = ?
        ORDER BY c.updated_at DESC -- Order by most recent message
    ");

    $stmt->execute([$currentUserId, $currentUserId, $currentUserId]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the output to include details of the 'other' user in the conversation
    $formattedConversations = [];
    foreach ($conversations as $conv) {
        $otherUserId = ($conv['user1_id'] == $currentUserId) ? $conv['user2_id'] : $conv['user1_id'];
        // Keep 'otherUsername' key for frontend compatibility, but use the fetched fullname
        $otherUsername = ($conv['user1_id'] == $currentUserId) ? $conv['user2_username'] : $conv['user1_username'];
        $otherProfilePicture = ($conv['user1_id'] == $currentUserId) ? $conv['user2_profile_picture'] : $conv['user1_profile_picture'];

        $formattedConversations[] = [
            'conversationId' => $conv['conversation_id'],
            'otherUserId' => $otherUserId,
            'otherUsername' => $otherUsername, // Use the fetched fullname here
            'otherProfilePicture' => $otherProfilePicture,
            'lastMessageText' => $conv['last_message_text'] ?? 'No messages yet', // Display preview or default
            'lastMessageTime' => $conv['last_message_time'],
            'unreadCount' => $conv['unread_count'],
        ];
    }

    echo json_encode([
        'success' => true,
        'conversations' => $formattedConversations
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Database error fetching conversations: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
}

?>