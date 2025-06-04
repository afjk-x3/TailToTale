<?php
session_start();
header('Content-Type: application/json');
if (!isset($_POST['application_id'])) {
    echo json_encode(['success' => false, 'error' => 'No application ID provided']);
    exit;
}
$application_id = intval($_POST['application_id']);
$conn = new mysqli('localhost', 'root', '', 'tailtotale');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB error']);
    exit;
}
$stmt = $conn->prepare("UPDATE applications SET status = 'confirmed' WHERE id = ?");
$stmt->bind_param("i", $application_id);
$stmt->execute();
if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to confirm application']);
}
$stmt->close();
$conn->close();
?> 