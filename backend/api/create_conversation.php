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
$otherUserId = $data['otherUserId'] ?? null;

if (!$otherUserId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required field: otherUserId']);
    exit;
}

// Ensure user IDs are integers
$currentUserId = (int) $currentUserId;
$otherUserId = (int) $otherUserId;

// Prevent creating a conversation with self
if ($currentUserId === $otherUserId) {
     http_response_code(400);
     echo json_encode(['error' => 'Cannot create conversation with self']);
     exit(); // Added exit() for clarity
}

// Ensure user1_id is always less than user2_id for unique conversation key
$user1_id = min($currentUserId, $otherUserId);
$user2_id = max($currentUserId, $otherUserId);

try {
    // Check if a conversation already exists between these two users
    // Using the min/max IDs ensures we find the conversation regardless of who is user1 or user2 in the table
    $stmt = $pdo->prepare("SELECT id FROM conversations WHERE user1_id = ? AND user2_id = ?");
    $stmt->execute([$user1_id, $user2_id]);
    $existingConversation = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingConversation) {
        // Conversation already exists, return its ID
        echo json_encode([
            'success' => true,
            'conversationId' => $existingConversation['id'],
            'message' => 'Conversation already exists'
        ]);
    } else {
        // No existing conversation, create a new one
        $stmt = $pdo->prepare("INSERT INTO conversations (user1_id, user2_id) VALUES (?, ?)");
        $stmt->execute([$user1_id, $user2_id]);
        $newConversationId = $pdo->lastInsertId();

        // Optionally fetch participant details for the frontend response
         $stmt = $pdo->prepare("SELECT id, fullname, profile_picture FROM users WHERE id IN (?, ?)");
         $stmt->execute([$user1_id, $user2_id]);
         $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

         $participantDetails = [];
         foreach ($participants as $participant) {
             $participantDetails[$participant['id']] = [
                 'username' => $participant['fullname'],
                 'profile_picture' => $participant['profile_picture']
             ];
         }


        echo json_encode([
            'success' => true,
            'conversationId' => $newConversationId,
            'message' => 'Conversation created successfully',
            'participantDetails' => $participantDetails // Include participant details
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Database error creating conversation: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
}

?>