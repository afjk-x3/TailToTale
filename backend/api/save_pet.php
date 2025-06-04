<?php
session_start();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Set default error handling
set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$response) {
    $response['message'] = 'An internal server error occurred.';
    ob_clean();
    echo json_encode($response);
    exit();
});

// Enable error reporting for debugging (turn off in production)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php_error.log');

try {
    // --- Database Connection ---
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "tailtotale";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception('Database Connection failed: ' . $conn->connect_error);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get form data
        $name = isset($_POST['pet-name']) ? htmlspecialchars($_POST['pet-name']) : '';
        $type = isset($_POST['pet-type']) ? htmlspecialchars($_POST['pet-type']) : '';
        $breed = isset($_POST['breed']) ? htmlspecialchars($_POST['breed']) : '';
        if (empty($breed) && isset($_POST['other-breed'])) {
            $breed = htmlspecialchars($_POST['other-breed']);
        }
        $age = isset($_POST['age']) ? htmlspecialchars($_POST['age']) : '';
        $gender = isset($_POST['gender']) ? htmlspecialchars($_POST['gender']) : '';
        $vaccination_status = isset($_POST['vaccination']) ? htmlspecialchars($_POST['vaccination']) : '';
        $spay_neuter_status = isset($_POST['spay-neuter']) ? htmlspecialchars($_POST['spay-neuter']) : '';
        $reason = isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : '';
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

        // File Upload Handling
        $primary_image = null;
        $uploaded_files = $_FILES['pet-photos'];

        if (isset($uploaded_files) && isset($uploaded_files['tmp_name'])) {
            $tmp_names = is_array($uploaded_files['tmp_name']) ? $uploaded_files['tmp_name'] : [$uploaded_files['tmp_name']];
            $file_names = is_array($uploaded_files['name']) ? $uploaded_files['name'] : [$uploaded_files['name']];
            $file_errors = is_array($uploaded_files['error']) ? $uploaded_files['error'] : [$uploaded_files['error']];

            $allowed_types = ['jpg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'];

            foreach ($tmp_names as $index => $tmp_name) {
                if ($file_errors[$index] === UPLOAD_ERR_OK) {
                    $file_info = finfo_open(FILEINFO_MIME_TYPE);
                    if (!$file_info) continue;
                    
                    $uploaded_file_type = finfo_file($file_info, $tmp_name);
                    finfo_close($file_info);

                    $fileName = $file_names[$index];
                    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                    if (!array_key_exists($fileExtension, $allowed_types) || !in_array($uploaded_file_type, $allowed_types)) {
                        continue;
                    }

                    $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                    $uploadFileDir = __DIR__ . '/../../frontend/assets/images/';
                    $dest_path = $uploadFileDir . $newFileName;

                    if (!is_dir($uploadFileDir)) {
                        mkdir($uploadFileDir, 0777, true);
                    }

                    if(move_uploaded_file($tmp_name, $dest_path)) {
                        $primary_image = '/TAILTOTALE/frontend/assets/images/' . $newFileName;
                        break;
                    }
                }
            }
        }

        // Database Insertion
        $sql = "INSERT INTO pets (user_id, name, type, breed, age, gender, vaccination_status, spay_neuter_status, reason, primary_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            throw new Exception('Database prepare failed: ' . $conn->error);
        }

        $bind_result = $stmt->bind_param("isssssssss", $user_id, $name, $type, $breed, $age, $gender, $vaccination_status, $spay_neuter_status, $reason, $primary_image);

        if ($bind_result === false) {
            throw new Exception('Database bind_param failed: ' . $stmt->error);
        }

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Pet listing submitted successfully!';
        } else {
            throw new Exception('Error saving pet data: ' . $stmt->error);
        }

        $stmt->close();
    } else {
        $response['message'] = 'Invalid request method.';
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

if (isset($conn) && $conn) {
    $conn->close();
}

echo json_encode($response);
?> 