<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');
$host = "localhost";
$user = "root";
$pass = "";
$db = "tailtotale";
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    echo "error";
    exit;
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
$address = isset($_POST['address']) ? trim($_POST['address']) : '';
$contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
$user_type = isset($_POST['user_type']) ? $_POST['user_type'] : 'adopter';

if (!$email || !$password) {
    echo "error";
    exit;
}

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo "exists";
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

$default_profile_picture = '/TAILTOTALE/frontend/assets/images/profile_pics/default.png';

// Insert new user
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (fullname, address, contact, email, password, user_type, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $fullname, $address, $contact, $email, $hash, $user_type, $default_profile_picture);
if ($stmt->execute()) {
    session_start();
    $user_id = $stmt->insert_id;
    $_SESSION['loggedin'] = true;
    $_SESSION['user_id'] = $user_id;
    $_SESSION['fullname'] = $fullname;
    $_SESSION['address'] = $address;
    $_SESSION['contact'] = $contact;
    $_SESSION['username'] = $email;
    $_SESSION['user_type'] = $user_type;
    $_SESSION['email'] = $email;
    $_SESSION['profile_picture'] = $default_profile_picture;
    echo "success";
} else {
    echo "error";
}
$stmt->close();
$conn->close();
