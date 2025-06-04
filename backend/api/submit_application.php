<?php
session_start();
header('Content-Type: application/json');

$pet_id = isset($_POST['pet_id']) ? intval($_POST['pet_id']) : 0;
$adopter_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
$adopter_name = trim($_POST['applicantName'] ?? '');
$adopter_email = trim($_POST['applicantEmail'] ?? '');
$message = trim($_POST['reason'] ?? '');
$application_date = date('Y-m-d H:i:s');
$status = 'pending';
$seen = 0;

$conn = new mysqli('localhost', 'root', '', 'tailtotale');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB error']);
    exit;
}

// Check for duplicate application or existing rejected application
$check_sql = "SELECT id, status FROM applications WHERE pet_id = ? AND adopter_email = ? ORDER BY application_date DESC LIMIT 1";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("is", $pet_id, $adopter_email);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $existing_app = $check_result->fetch_assoc();
    $existing_app_id = $existing_app['id'];
    $existing_app_status = $existing_app['status'];
    
    $check_stmt->close();

    if ($existing_app_status === 'rejected') {
        // If the existing application was rejected, update it with the new submission details
        $update_sql = "UPDATE applications SET adopter_name = ?, message = ?, application_date = ?, status = 'pending', seen = 0, adopter_seen = 0 WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sssi", $adopter_name, $message, $application_date, $existing_app_id);
        $update_stmt->execute();

        if ($update_stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Your previous application has been updated.']);
        } else {
            // If affected_rows is 0, it might mean the data was the same, or an error occurred.
            // We can check for error or assume it was updated if no error.
             if ($update_stmt->error) {
                 echo json_encode(['success' => false, 'error' => 'Failed to update application: ' . $update_stmt->error]);
             } else {
                 echo json_encode(['success' => true, 'message' => 'Your previous application was already up to date.']);
             }
        }
        $update_stmt->close();

    } else {
        // If an existing application is not rejected, prevent new submission
        echo json_encode(['success' => false, 'error' => 'You already have an active application for this pet with status: ' . ucfirst($existing_app_status)]);
    }
    
    $conn->close();
    exit;
}

$check_stmt->close();

error_log("pet_id: $pet_id, adopter_id: $adopter_id, adopter_name: $adopter_name, adopter_email: $adopter_email, message: $message, application_date: $application_date, status: $status, seen: $seen");

$sql = "INSERT INTO applications (pet_id, adopter_id, adopter_name, adopter_email, message, application_date, status, seen, adopter_seen) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iisssssii", $pet_id, $adopter_id, $adopter_name, $adopter_email, $message, $application_date, $status, $seen, $seen); // Assuming adopter_seen default is 0
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save application']);
}

if ($stmt->error) {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
    $stmt->close();
    $conn->close();
    exit;
}

$stmt->close();
$conn->close();
?> 