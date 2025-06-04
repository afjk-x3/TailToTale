<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php'; // Include the new PDO database connection

header('Content-Type: application/json'); // Change header to JSON for consistent API responses

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (!$email || !$password) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'error' => 'Missing email or password']);
    exit;
}

try {
    // First, check if the user exists and get the password hash and user details
    $stmt = $pdo->prepare("SELECT id, password, user_type, fullname, address, contact FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['address'] = $user['address'];
            $_SESSION['contact'] = $user['contact'];
            $_SESSION['username'] = $user['fullname']; // Assuming you want to use fullname as username
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['email'] = $email;

            // Return user type as part of the JSON response
            echo json_encode(['success' => true, 'user_type' => $user['user_type']]);

        } else {
            http_response_code(401); // Unauthorized
            echo json_encode(['success' => false, 'error' => 'Invalid password']);
        }
    } else {
        http_response_code(401); // Unauthorized
        echo json_encode(['success' => false, 'error' => 'User not found']);
    }

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    error_log("Database error during login: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

?>
