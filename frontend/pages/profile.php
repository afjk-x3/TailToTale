<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: /TAILTOTALE/backend/api/signin.php');
    exit;
}

// Count total unseen approved/rejected applications for the logged-in adopter
$total_adopter_notifications = 0;
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'adopter') {
    $conn_notify = new mysqli('localhost', 'root', '', 'tailtotale');
    if (!$conn_notify->connect_error) {
        $user_id = $_SESSION['user_id'];
        $sql_notify = "SELECT COUNT(*) as total_new FROM applications WHERE adopter_id = ? AND (status = 'rejected' OR status = 'confirmed') AND adopter_seen = 0";
        $stmt_notify = $conn_notify->prepare($sql_notify);
        $stmt_notify->bind_param("i", $user_id);
        $stmt_notify->execute();
        $result_notify = $stmt_notify->get_result();
        if ($result_notify && $result_notify->num_rows > 0) {
            $total_adopter_notifications = $result_notify->fetch_assoc()['total_new'];
        }
        $stmt_notify->close();
        $conn_notify->close();
    }
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Database connection
$servername = "localhost"; // Replace with your DB server name

$recent_rejection = false;
$rejection_message = '';
$adopted_pets_count = 0;
$adopted_pets_list = [];
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;
$listed_pets_count = 0;

$conn = new mysqli('localhost', 'root', '', 'tailtotale');
if ($conn->connect_error) {
    // handle error
} else {
    $user_id = $_SESSION['user_id'];

    // Fetch user details, including registration date
    $user_sql = "SELECT fullname, email, address, contact, registration_date, profile_picture FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_sql);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_data = $user_result->fetch_assoc();
    $user_stmt->close();

    // Assign fetched data to variables, falling back to session data if needed
    $fullname = $user_data['fullname'] ?? ($_SESSION['fullname'] ?? 'User');
    $email = $user_data['email'] ?? ($_SESSION['username'] ?? 'N/A'); // Assuming username is email
    $address = $user_data['address'] ?? ($_SESSION['address'] ?? 'N/A');
    $contact = $user_data['contact'] ?? ($_SESSION['contact'] ?? 'N/A');
    $registration_date = $user_data['registration_date'] ?? null;
    $profile_picture = $user_data['profile_picture'] ?? null;

    // Format registration date
    $member_since_date = 'N/A';
    if ($registration_date) {
        $date_obj = new DateTime($registration_date);
        $member_since_date = $date_obj->format('F Y'); // e.g., January 2024
    }

    // 1. Check for recent rejection
    $sql = "SELECT p.name AS pet_name, a.application_date
            FROM applications a
            JOIN pets p ON a.pet_id = p.id
            WHERE a.adopter_id = ? AND a.status = 'rejected'
            ORDER BY a.application_date DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($pet_name, $application_date);
    if ($stmt->fetch()) {
        $date = new DateTime($application_date);
        $now = new DateTime();
        $interval = $now->diff($date);
        if ($interval->days < 1) {
            $recent_rejection = true;
            $rejection_message = "Your application for <strong>" . htmlspecialchars($pet_name) . "</strong> was rejected.";
        }
    }
    $stmt->close();

    // 2. Get adopted pets count
    $sql = "SELECT COUNT(*) as count FROM applications WHERE adopter_id = ? AND status = 'confirmed'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($adopted_pets_count);
    $stmt->fetch();
    $stmt->close();

    // 3. Get pending applications count
    $sql_pending = "SELECT COUNT(*) as count FROM applications WHERE adopter_id = ? AND status = 'pending'";
    $stmt = $conn->prepare($sql_pending);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($pending_count);
    $stmt->fetch();
    $stmt->close();

    // 4. Get approved applications count
    $sql_approved = "SELECT COUNT(*) as count FROM applications WHERE adopter_id = ? AND (status = 'approved' OR status = 'confirmed')";
    $stmt = $conn->prepare($sql_approved);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($approved_count);
    $stmt->fetch();
    $stmt->close();

    // 5. Get rejected applications count
    $sql_rejected = "SELECT COUNT(*) as count FROM applications WHERE adopter_id = ? AND status = 'rejected'";
    $stmt = $conn->prepare($sql_rejected);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($rejected_count);
    $stmt->fetch();
    $stmt->close();

    // 6. Get adopted pets details
    $sql2 = "SELECT p.name, p.type, p.breed, p.primary_image, a.application_date, u.fullname as rehomer_name
             FROM applications a
             JOIN pets p ON a.pet_id = p.id
             JOIN users u ON p.user_id = u.id
             WHERE a.adopter_id = ? AND a.status = 'confirmed'
             ORDER BY a.application_date DESC";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    while ($row = $result2->fetch_assoc()) {
        $adopted_pets_list[] = $row;
    }
    $stmt2->close();

    if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'rehomer') {
        $sql = "SELECT COUNT(*) as count FROM pets WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($listed_pets_count);
        $stmt->fetch();
        $stmt->close();

        // Count pending applications
        $sql = "SELECT COUNT(*) FROM applications a JOIN pets p ON a.pet_id = p.id WHERE p.user_id = ? AND a.status = 'pending'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($pending_count);
        $stmt->fetch();
        $stmt->close();

        // Count approved applications
        $sql = "SELECT COUNT(*) FROM applications a JOIN pets p ON a.pet_id = p.id WHERE p.user_id = ? AND (a.status = 'approved' OR a.status = 'confirmed')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($approved_count);
        $stmt->fetch();
        $stmt->close();

        // Count rejected applications
        $sql = "SELECT COUNT(*) FROM applications a JOIN pets p ON a.pet_id = p.id WHERE p.user_id = ? AND a.status = 'rejected'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($rejected_count);
        $stmt->fetch();
        $stmt->close();
    }

    // Get total applications count
    $sql_total = "SELECT COUNT(*) FROM applications a JOIN pets p ON a.pet_id = p.id WHERE p.user_id = ?";
    $stmt_total = $conn->prepare($sql_total);
    $stmt_total->bind_param("i", $user_id);
    $stmt_total->execute();
    $stmt_total->bind_result($total_applications);
    $stmt_total->fetch();
    $stmt_total->close();

    // Fetch recent applications for summary display (limited to 3)
    $sql = "SELECT a.id as app_id, a.adopter_id, a.adopter_name as applicant_name, a.adopter_email as applicant_email, a.status, a.application_date, a.pet_id, p.name as pet_name, p.primary_image
            FROM applications a
            JOIN pets p ON a.pet_id = p.id
            WHERE p.user_id = ?
            ORDER BY a.application_date DESC LIMIT 3";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $applications_summary = [];
    while ($row = $result->fetch_assoc()) {
        $applications_summary[] = $row;
    }
    $stmt->close();

    $conn->close();
}

// Count total unseen applications for all pets listed by this rehomer
$total_new_applications = 0;
$conn_notify = new mysqli('localhost', 'root', '', 'tailtotale');
if (!$conn_notify->connect_error) {
    $user_id = $_SESSION['user_id'];
    // Subquery to get pet IDs listed by the current rehomer
    $sql_notify = "SELECT COUNT(*) as total_new FROM applications WHERE pet_id IN (SELECT id FROM pets WHERE user_id = ?) AND seen = 0";
    $stmt_notify = $conn_notify->prepare($sql_notify);
    $stmt_notify->bind_param("i", $user_id);
    $stmt_notify->execute();
    $result_notify = $stmt_notify->get_result();
    if ($result_notify->num_rows > 0) {
        $total_new_applications = $result_notify->fetch_assoc()['total_new'];
    }
    $stmt_notify->close();
    $conn_notify->close();
}

if ($recent_rejection) {
    echo '<div style="background:#ffebee;color:#c62828;padding:1rem 1.5rem;border-radius:8px;margin-bottom:1.5rem;font-size:1.1rem;font-weight:500;">';
    echo '<i class="fas fa-exclamation-circle" style="margin-right:8px;"></i> ' . $rejection_message;
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adopter Dashboard - Tail to Tale</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            background: #f7f7f7;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .dashboard-container {
            display: flex;
            flex: 1;
            position: relative;
            margin-top: -5px; /*gap for sidebar and navbar */
        }
        header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
        .sidebar {
            width: 250px;
            background: #fff;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.05);
            padding: 0.5rem 1rem 1.5rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 2rem;
            position: sticky;
            top: 80px;
            height: calc(100vh - 80px);
            z-index: 99;
        }

        .sidebar .menu {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .sidebar .menu a,
        .sidebar .menu button {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            background: none;
            border: none;
            text-align: left;
            font-size: 1.1rem;
            padding: 1rem 1.2rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #222;
            text-decoration: none;
            font-weight: 500;
        }

        .sidebar .menu a i,
        .sidebar .menu button i {
            font-size: 1.25rem;
            width: 26px;
            color: #4CAF50;
        }

        .sidebar .menu a.active,
        .sidebar .menu a:hover,
        .sidebar .menu button:hover {
            background: #43b05c;
            color: #fff;
        }

        .sidebar .menu a.active i,
        .sidebar .menu a:hover i,
        .sidebar .menu button:hover i {
            color: #fff;
        }

        .main-content {
            flex: 1;
            padding: 1.5rem 2.5rem 2.5rem 2.5rem;
            background: #f7f7f7;
            min-height: 100vh;
        }

        .dashboard-header {
            margin-bottom: 2.5rem;
        }

        .dashboard-header .welcome {
            font-size: 2.2rem;
            font-weight: bold;
            color: #2e7d32;
            margin-bottom: 1.5rem;
            letter-spacing: 0.5px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
        }

        .stat-card.pending .stat-icon {
            background: none;
            color: #f57c00;
        }

        .stat-card.approved .stat-icon {
            background: none;
            color: #2e7d32;
        }

        .stat-card.rejected .stat-icon {
            background: none;
            color: #c62828;
        }

        .stat-card.pending:hover {
            background: #fff8e1;
        }

        .stat-card.approved:hover {
            background: #f1f8e9;
        }

        .stat-card.rejected:hover {
            background: #ffebee;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: none;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4CAF50;
            font-size: 1.5rem;
        }

        .stat-info h3 {
            font-size: 1.8rem;
            color: #2e7d32;
            margin: 0;
            font-weight: 600;
        }

        .stat-info p {
            color: #666;
            margin: 0;
            font-size: 0.9rem;
        }

        .stat-card.pending .stat-info h3 {
            color: #f57c00;
        }

        .stat-card.approved .stat-info h3 {
            color: #2e7d32;
        }

        .stat-card.rejected .stat-info h3 {
            color: #c62828;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 2rem;
            margin-bottom: 2.5rem;
        }

        .user-info-box {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            text-align: center;
        }

        .user-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 1.5rem auto;
            background: #e8f5e9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #4CAF50;
            overflow: hidden;
        }
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            display: block;
        }

        .user-info-box h2 {
            color: #333;
            margin: 0 0 1rem;
            font-size: 1.5rem;
        }

        .user-info-list {
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem;
            border-radius: 8px;
            transition: background-color 0.2s;
        }

        .info-row:hover {
            background-color: #f5f5f5;
        }

        .info-row i {
            color: #4CAF50;
            font-size: 1.2rem;
            width: 24px;
        }

        .info-row strong {
            color: #2e7d32;
            margin-right: 0.5rem;
            min-width: 80px;
        }

        .history-section {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 2rem;
        }

        .history-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .history-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background: #f9f9f9;
            border-bottom: 1px solid #e0e0e0;
        }

        .history-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .history-status.completed {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .history-status.pending {
            background: #fff3e0;
            color: #ef6c00;
        }

        .history-date {
            color: #666;
            font-size: 0.9rem;
        }

        .history-body {
            padding: 1.5rem;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 1.5rem;
            align-items: center;
        }

        .history-img {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
        }

        .history-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .history-title {
            font-size: 1.1rem;
            font-weight: 500;
            color: #333;
        }

        .history-detail {
            color: #666;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .history-actions {
            display: flex;
            gap: 1rem;
        }

        .history-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .history-btn:not(.secondary) {
            background: #4CAF50;
            color: white;
        }

        .history-btn.secondary {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .history-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        @media (max-width: 1200px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
            .user-info-box {
                max-width: 600px;
                margin: 0 auto;
            }
            .adopted-pets-cards-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 900px) {
            .dashboard-container {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                height: auto;
                position: static;
                flex-direction: row;
                gap: 1rem;
                justify-content: space-around;
                box-shadow: none;
                padding: 0.5rem 0.5rem 0.5rem 0.5rem;
            }
            .sidebar .menu {
                flex-direction: row;
                gap: 0.5rem;
                width: 100%;
                justify-content: space-around;
            }
            .main-content {
                padding: 1rem 0.5rem 2rem 0.5rem;
            }
            .profile-adopted-flex {
                flex-direction: column;
                gap: 1.5rem;
            }
            .adopted-pets-grid {
                padding: 1.2rem 0.5rem 1.5rem 0.5rem;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            .user-info-box {
                max-width: 350px;
                margin: 0 auto 1.5rem auto;
                padding: 1.2rem 1rem;
                box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            }
            .user-info-list {
                gap: 0.7rem;
            }
            .info-row {
                flex-direction: row;
                gap: 0.7rem;
                font-size: 1rem;
            }
            .user-avatar {
                width: 90px;
                height: 90px;
                font-size: 2rem;
            }
            .user-info-box h2 {
                font-size: 1.2rem;
            }
            .adopted-heart {
                position: absolute;
                top: 1rem;
                right: 1.2rem;
                color: #43b05c !important;
                font-size: 2rem !important;
                background: transparent;
                border-radius: 50%;
                width: auto;
                height: auto;
                display: flex;
                align-items: center;
                justify-content: flex-end;
                box-shadow: none;
            }
            .adopted-heart i {
                color: #43b05c !important;
                font-size: 2rem !important;
            }
            .main-content {
                padding-bottom: 180px;
            }
            footer {
                margin-top: 300px;
                width: 408px;
            }
        }
        @media (max-width: 768px) {
            .main-content {
                padding: 0.5rem 0.2rem 1rem 0.2rem;
            }
            .profile-adopted-flex {
                flex-direction: column;
                gap: 1rem;
            }
            .adopted-pets-grid {
                padding: 1rem 0.2rem 1rem 0.2rem;
            }
            .adopted-pets-cards-grid {
                grid-template-columns: 1fr;
            }
            .user-info-box {
                max-width: 100%;
                padding: 1rem;
            }
            footer {
                padding: 2rem 1rem;
            }
            .footer-content {
                flex-direction: column;
                gap: 1.5rem;
            }
            .footer-section {
                text-align: left;
            }
            .footer-section .social-links {
                justify-content: flex-start;
            }
            .footer-bottom {
                 padding: 1rem 1rem;
            }
             .footer-bottom .footer-links {
                 justify-content: flex-start;
             }
        }
        @media (max-width: 600px) {
            .dashboard-header .welcome {
                font-size: 1.3rem;
            }
            .user-info-box {
                padding: 0.7rem;
            }
            .adopted-pets-grid {
                padding: 0.7rem 0.1rem 1rem 0.1rem;
            }
            .adopted-pet-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.7rem;
                padding: 1rem 0.5rem 1rem 0.5rem;
            }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.7rem;
            }
            .user-info-box {
                max-width: 100%;
                margin: 0 auto 1rem auto;
                padding: 0.7rem 0.3rem;
            }
            .user-info-list {
                gap: 0.5rem;
            }
            .info-row {
                font-size: 0.97rem;
            }
            .user-avatar {
                width: 70px;
                height: 70px;
                font-size: 1.3rem;
                margin: 1rem auto 1rem auto;
            }
        }
        @media (max-width: 400px) {
            .sidebar {
                padding: 0.2rem 0.1rem 0.5rem 0.1rem;
            }
            .main-content {
                padding: 0.1rem 0.05rem 0.5rem 0.05rem;
            }
            .user-info-box {
                padding: 0.3rem;
            }
        }
        /* Add these new styles for the profile header */
        .message-badge {
            background: #4CAF50;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.8rem;
            margin-left: 5px;
        }

        .logout-btn-nav {
            background: none;
            border: none;
            color: #dc3545;
            padding: 0.5rem 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            transition: color 0.3s;
        }

        .logout-btn-nav:hover {
            color: #bd2130;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .nav-links .nav-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #333;
            text-decoration: none;
            font-size: 1rem;
            transition: color 0.3s;
        }

        .nav-links .nav-link:hover {
            color: #4CAF50;
        }

        .profile-adopted-flex {
            display: flex;
            gap: 3.5rem;
            align-items: flex-start;
            margin-top: 2.5rem;
            margin-bottom: 2.5rem;
        }
        .profile-adopted-flex .user-info-box {
            flex: 1 1 320px;
            max-width: 350px;
        }
        .profile-adopted-flex .adopted-pets-grid {
            flex: 2 1 800px;
            max-width: 1100px;
            min-width: 400px;
            padding: 2.5rem 2.5rem 2.5rem 2.5rem;
        }
        @media (max-width: 1300px) {
            .profile-adopted-flex .adopted-pets-grid {
                max-width: 100%;
                padding: 1.5rem 0.5rem 1.5rem 0.5rem;
            }
        }
        .adopted-pets-grid {
            background: #f9fafb;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(76,175,80,0.07);
            padding: 2rem 2rem 2.5rem 2rem;
            min-height: 200px;
            margin-left: 0;
            margin-right: 0;
        }
        .adopted-pets-grid h3 {
            font-size: 1.4rem;
            color: #2e7d32;
            font-weight: 700;
            margin-bottom: 2rem;
            letter-spacing: 0.5px;
            border-bottom: 1.5px solid #e0e0e0;
            padding-bottom: 0.7rem;
        }
        .adopted-pet-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(76,175,80,0.09);
            padding: 1.5rem 1.2rem 1.2rem 1.2rem;
            display: flex;
            align-items: center;
            gap: 1.3rem;
            border: 1.5px solid #e0f2f1;
            position: relative;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .adopted-pet-card:hover {
            box-shadow: 0 8px 24px rgba(76,175,80,0.16);
            transform: translateY(-2px) scale(1.01);
        }
        .adopted-pet-img {
            flex-shrink: 0;
            width: 100px;
            height: 100px;
            border-radius: 12px;
            background: #e8f5e9;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 1.5px solid #c8e6c9;
        }
        .adopted-pet-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 12px;
        }
        .adopted-pet-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
        }
        .adopted-pet-name {
            font-size: 1.15rem;
            font-weight: 600;
            color: #388e3c;
            margin-bottom: 0.1rem;
        }
        .adopted-pet-type {
            color: #555;
            font-size: 1.01rem;
            margin-bottom: 0.1rem;
        }
        .adopted-pet-date {
            color: #888;
            font-size: 0.97rem;
        }
        .adopted-pet-rehomer-name {
            color: #666;
            font-size: 0.97rem;
            margin-top: 0.1rem;
            font-weight: 500;
        }
        .adopted-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #43b05c;
            color: #fff;
            padding: 0.3em 1em;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(76,175,80,0.08);
            display: flex;
            align-items: center;
            gap: 0.4rem;
            letter-spacing: 0.5px;
        }
        @media (max-width: 1100px) {
            .adopted-pets-grid {
                padding: 1.2rem 0.5rem 1.5rem 0.5rem;
                margin-bottom: 30rem;
            }
        }
        .adopted-pets-cards-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }
        @media (max-width: 1100px) {
            .adopted-pets-cards-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 700px) {
            .adopted-pets-cards-grid {
                grid-template-columns: 1fr;
            }
        }
        .application-summary-grid {
            background: #f9fafb;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(76,175,80,0.07);
            padding: 2rem 2rem 2.5rem 2rem;
            min-width: 0;
            max-width: 100%;
            width: 100%;
            box-sizing: border-box;
        }
        .applications-list {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
            width: 100%;
        }
        .application-card {
            position: relative;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(76,175,80,0.08);
            padding: 1.2rem 1.5rem 1.2rem 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.7rem;
            border: 1.5px solid #e0f2f1;
            min-height: 110px;
            max-width: 100%;
            box-sizing: border-box;
        }
        .application-pet-img {
            position: static;
            width: 100px;
            height: 100px;
            border-radius: 16px;
            background: #e8f5e9;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 1.5px solid #c8e6c9;
            margin: 0 auto 0.7rem auto;
        }
        .application-pet-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 16px;
        }
        .application-card-info {
            padding: 0;
            width: 100%;
            text-align: left;
        }
        .application-actions {
            margin-top: 0.7rem;
            display: flex;
            flex-direction: row;
            gap: 0.7rem;
            width: 100%;
            justify-content: center;
        }
        .application-actions .btn {
            min-width: 0;
            flex: 1 1 45%;
            margin-bottom: 0;
            width: 100%;
            font-size: 0.95rem;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.6rem 0.2rem;
            border-radius: 10px;
            height: 40px;
        }
        .btn.secondary-btn{
            background: #e8f5e9;
            color: #2e7d32;
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1.2rem;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            font-weight: 500;
            display: inline-block;
        }

        .btn.primary-btn{

            background: #2e7d32;
            color: #e8f5e9;
            border: none;
            border-radius: 50px;
            padding: 0.5rem 1.2rem;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            font-weight: 500;
            display: inline-block;
        }

        .btn.secondary-btn:hover {
            background: #c8e6c9;
        }

        .btn.primary-btn:hover {
            background:rgb(21, 94, 12);
        }

        /* New Applications Notification Badge in Sidebar */
        .sidebar .menu a .notification-badge {
            display: inline-block;
            background-color: #e74c3c; /* Red color */
            color: white;
            font-size: 0.7rem;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
            vertical-align: top; /* Align badge to the top */
        }
        .hamburger {
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
            background: transparent;
            border: none;
            cursor: pointer;
            z-index: 999;
        }
        .hamburger span {
            display: block;
            width: 28px;
            height: 4px;
            margin: 4px 0;
            background: #43b05c;
            border-radius: 2px;
            transition: 0.3s;
        }
        @media (max-width: 900px) {
            .hamburger {
                display: flex;
            }
            .sidebar {
                position: fixed;
                left: -270px;
                top: 0;
                height: 100vh;
                width: 250px;
                background: #fff;
                box-shadow: 2px 0 8px rgba(0,0,0,0.12);
                transition: left 0.3s;
                z-index: 150;
                flex-direction: column;
                padding-top: 100px;
                align-items: flex-start;
                justify-content: flex-start;
                padding-left: 1rem;
                padding-right: 1rem;
            }
            .sidebar.open {
                left: 0;
            }
            .sidebar .menu {
                flex-direction: column;
                gap: 1.2rem;
                align-items: flex-start;
                justify-content: flex-start;
                width: 100%;
                margin-top: .5rem;
            }
            .dashboard-container {
                flex-direction: column;
            }
            .main-content {
                padding: 1rem 0.5rem 2rem 0.5rem;
            }
            body.sidebar-open {
                overflow: hidden;
            }
            .sidebar-overlay {
                display: block;
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0,0,0,0.2);
                z-index: 100;
            }
        }
        @media (min-width: 900px) {
            .adopted-pets-cards-grid {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 2rem;
            }
            .adopted-pet-card {
                background: #fff;
                border-radius: 16px;
                box-shadow: 0 2px 12px rgba(76,175,80,0.07);
                padding: 1.5rem 1rem 1.2rem 1rem;
                display: flex;
                flex-direction: column;
                align-items: center;
                position: relative;
                min-height: 210px;
                transition: box-shadow 0.2s, transform 0.2s;
            }
            .adopted-pet-card:hover {
                box-shadow: 0 8px 24px rgba(76,175,80,0.16);
                transform: translateY(-2px) scale(1.01);
            }
            .adopted-pet-img {
                width: 90px;
                height: 90px;
                border-radius: 16px;
                background: #e8f5e9;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                margin-bottom: 1rem;
                border: 1.5px solid #c8e6c9;
            }
            .adopted-pet-img img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                border-radius: 16px;
            }
            .adopted-pet-info {
                text-align: left;
                width: 100%;
                display: flex;
                flex-direction: column;
                gap: 0.2rem;
            }
            .adopted-pet-name {
                color: #43b05c;
                font-weight: 700;
                font-size: 1.1rem;
                margin-bottom: 0.2rem;
            }
            .adopted-pet-type {
                color: #555;
                font-size: 1rem;
                margin-bottom: 0.1rem;
            }
            .adopted-pet-rehomer-name {
                color: #222;
                font-size: 0.97rem;
                margin-top: 0.1rem;
                font-weight: 500;
            }
            .adopted-pet-date {
                color: #888;
                font-size: 0.97rem;
            }
            .adopted-heart {
                position: absolute;
                top: 1.2rem;
                right: 1.2rem;
                color: #43b05c;
                font-size: 2.2rem;
                background: none;
                border-radius: 0;
                width: auto;
                height: auto;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: none;
            }
            .adopted-heart i {
                color: #43b05c;
                font-size: 2rem;
            }
        }
        /* Add responsive styles for the footer */
        @media (max-width: 768px) {
            footer {
                padding: 2rem 1rem; /* Adjust overall footer padding */
            }
            .footer-content {
                flex-direction: column; /* Stack footer sections vertically */
                gap: 1.5rem; /* Add space between stacked sections */
            }
            .footer-section {
                text-align: left; /* Align text to the left */
            }
            .footer-section .social-links {
                justify-content: flex-start; /* Align social links to the left */
            }
            .footer-bottom {
                 padding: 1rem 1rem;
            }
             .footer-bottom .footer-links {
                 justify-content: flex-start;
             }
        }

        @media (max-width: 480px) {
             footer {
                 padding: 1.5rem 0.5rem; /* Further reduce padding on smaller mobile */
             }
             .footer-content {
                 gap: 1rem;
             }
              .footer-section h3 {
                  font-size: 1.2rem;
              }
              .footer-section p,
              .footer-section li,
              .footer-bottom a {
                  font-size: 0.9rem;
              }
        }
        /* Styles for No Applications Message */
        .no-applications-message {
            text-align: center;
            padding: 2rem;
            background: #e8f5e9;
            border-radius: 12px;
            color: #2e7d32;
            font-size: 1.1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .no-applications-message i {
            font-size: 2rem;
            color: #4CAF50;
        }
    </style>
</head>

<body <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'rehomer') echo 'class="rehomer-dashboard"'; ?>>
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="/TAILTOTALE/frontend/pages/index.php" style="text-decoration: none; color: inherit;">
                    <h1>Tail to Tale</h1>
                </a>
            </div>
            <button class="hamburger" id="hamburgerBtn" aria-label="Open navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </nav>
    </header>

    <div class="dashboard-container" <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'rehomer') echo 'style="margin-top: 80px;" '; ?>>
        <aside class="sidebar">
            <nav class="menu">
                <a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i> My Profile
                </a>
                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'rehomer'): ?>
                    <a href="mylistings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'mylistings.php' ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i> My Listings
                        <?php if ($total_new_applications > 0): ?>
                            <span class="notification-badge"><?php echo $total_new_applications; ?></span>
                        <?php endif; ?>
                    </a>
                <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'adopter'): ?>
                    <a href="adoption-history.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'adoption-history.php' ? 'active' : ''; ?>">
                        <i class="fas fa-history"></i> Adoption History
                        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'adopter' && $total_adopter_notifications > 0): ?>
                            <span class="notification-badge"><?php echo $total_adopter_notifications; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
                <a href="messages.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i> Messages
                    <span class="notification-badge" id="unread-messages-badge" style="display: none;"></span>
                </a>
                <button onclick="window.location.href='/TAILTOTALE/backend/api/logout.php'"><i class="fas fa-sign-out-alt"></i> Log Out</button>
            </nav>
        </aside>
        <div class="main-content">
            <!-- Profile Info -->
            <div id="profile-section">
                <div class="dashboard-header">
                    <div class="welcome">Welcome,
                        <?php echo isset($_SESSION['fullname']) ? htmlspecialchars($_SESSION['fullname']) : 'Adopter'; ?>!
                    </div>
                </div>

                <div class="stats-grid">
                    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'rehomer'): ?>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-paw"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $listed_pets_count; ?></h3>
                                <p>Listed Pets</p>
                            </div>
                        </div>
                        <div class="stat-card pending">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $pending_count; ?></h3>
                                <p>Pending Applications</p>
                            </div>
                        </div>
                        <div class="stat-card approved">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $approved_count; ?></h3>
                                <p>Approved Applications</p>
                            </div>
                        </div>
                        <div class="stat-card rejected">
                            <div class="stat-icon">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $rejected_count; ?></h3>
                                <p>Rejected Applications</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="stat-card" id="adopted-pets-card" style="cursor:pointer;">
                            <div class="stat-icon">
                                <i class="fas fa-heart"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $adopted_pets_count; ?></h3>
                                <p>Adopted Pets</p>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-comment"></i>
                        </div>
                        <div class="stat-info">
                            <h3 id="active-messages-count">0</h3>
                            <p>Active Messages</p>
                        </div>
                    </div>
                </div>

                <!-- Profile and Adopted Pets Side-by-Side -->
                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'rehomer'): ?>
                    <div class="profile-adopted-flex" id="profileAdoptedFlex">
                        <div class="user-info-box">
                            <div class="user-avatar" id="userAvatarBox">
                                <?php if (!empty($profile_picture)): ?>
                                    <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover;">
                                <?php else: ?>
                                    <img src="/TAILTOTALE/frontend/assets/images/default.png" alt="Profile" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover;">
                                <?php endif; ?>
                            </div>
                            <button id="editProfilePicBtn" style="margin-top: 10px; margin-bottom: 10px; padding: 6px 16px; border-radius: 20px; border: 1.5px solid #4CAF50; background: #fff; color: #4CAF50; font-weight: 600; cursor: pointer; transition: background 0.2s, color 0.2s;">Edit Photo</button>
                            <form id="profilePicForm" style="display:none; margin-top: 10px;" enctype="multipart/form-data">
                                <input type="file" id="profilePicInput" name="profile_picture" accept="image/*" style="margin-bottom: 8px;">
                                <button type="submit" style="padding: 6px 16px; border-radius: 20px; border: none; background: #4CAF50; color: #fff; font-weight: 600; cursor: pointer;">Save</button>
                            </form>
                            <h2><?php echo isset($_SESSION['fullname']) ? htmlspecialchars($_SESSION['fullname']) : 'Adopter'; ?></h2>
                            <div class="user-info-list">
                                <div class="info-row">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <div>
                                        <strong>Address</strong>
                                        <?php echo isset($_SESSION['address']) ? htmlspecialchars($_SESSION['address']) : 'N/A'; ?>
                                    </div>
                                </div>
                                <div class="info-row">
                                    <i class="fas fa-phone"></i>
                                    <div>
                                        <strong>Contact</strong>
                                        <?php echo isset($_SESSION['contact']) ? htmlspecialchars($_SESSION['contact']) : 'N/A'; ?>
                                    </div>
                                </div>
                                <div class="info-row">
                                    <i class="fas fa-envelope"></i>
                                    <div>
                                        <strong>Email</strong>
                                        <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'N/A'; ?>
                                    </div>
                                </div>
                                <div class="info-row">
                                    <i class="fas fa-calendar"></i>
                                    <div>
                                        <strong>Member Since</strong>
                                        <?php echo $member_since_date; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="application-summary-grid">
                            <h3>Application Summary</h3>
                            <div style="font-size:1.1rem;margin-bottom:1.2rem;font-weight:500;">Applications Received: <span style="color:#2e7d32;font-weight:700;"> <?php echo $total_applications; ?> </span></div>
                            <?php if ($total_applications === 0): ?>
                                <div class="no-applications-message">
                                    <i class="fas fa-info-circle"></i>
                                    <p>No applications received for your pets yet.</p>
                                </div>
                            <?php else: ?>
                                <div class="applications-list">
                                <?php foreach ($applications_summary as $app): ?>
                                    <div class="application-card">
                                        <div class="application-pet-img">
                                            <?php if (!empty($app['primary_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($app['primary_image']); ?>" alt="<?php echo htmlspecialchars($app['pet_name']); ?>">
                                            <?php else: ?>
                                                <i class="fas fa-paw" style="font-size:2.2rem;color:#bbb;"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="application-card-info">
                                            <div class="application-pet-name"><strong>Pet:</strong> <?php echo htmlspecialchars($app['pet_name']); ?></div>
                                            <div><strong>Applicant:</strong> <?php echo htmlspecialchars($app['applicant_name']); ?> (<?php echo htmlspecialchars($app['applicant_email']); ?>)</div>
                                            <div><strong>Status:</strong> <?php echo ucfirst($app['status']); ?></div>
                                            <div><strong>Date:</strong> <?php echo htmlspecialchars($app['application_date']); ?></div>
                                            <div class="application-actions">
                                                <a href="/TAILTOTALE/frontend/pages/applications.php?pet_id=<?php echo $app['pet_id']; ?>" class="btn primary-btn" style="margin-right:8px;">View</a>
                                                <a href="/TAILTOTALE/frontend/pages/messages.php?adopter_id=<?php echo $app['adopter_id']; ?>&pet_id=<?php echo $app['pet_id']; ?>" class="btn secondary-btn">Message</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="profile-adopted-flex" id="profileAdoptedFlex">
                        <div class="user-info-box">
                            <div class="user-avatar" id="userAvatarBox">
                                <?php if (!empty($profile_picture)): ?>
                                    <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover;">
                                <?php else: ?>
                                    <img src="/TAILTOTALE/frontend/assets/images/default.png" alt="Profile" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover;">
                                <?php endif; ?>
                            </div>
                            <button id="editProfilePicBtn" style="margin-top: 10px; margin-bottom: 10px; padding: 6px 16px; border-radius: 20px; border: 1.5px solid #4CAF50; background: #fff; color: #4CAF50; font-weight: 600; cursor: pointer; transition: background 0.2s, color 0.2s;">Edit Photo</button>
                            <form id="profilePicForm" style="display:none; margin-top: 10px;" enctype="multipart/form-data">
                                <input type="file" id="profilePicInput" name="profile_picture" accept="image/*" style="margin-bottom: 8px;">
                                <button type="submit" style="padding: 6px 16px; border-radius: 20px; border: none; background: #4CAF50; color: #fff; font-weight: 600; cursor: pointer;">Save</button>
                            </form>
                            <h2><?php echo isset($_SESSION['fullname']) ? htmlspecialchars($_SESSION['fullname']) : 'Adopter'; ?></h2>
                            <div class="user-info-list">
                                <div class="info-row">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <div>
                                        <strong>Address</strong>
                                        <?php echo isset($_SESSION['address']) ? htmlspecialchars($_SESSION['address']) : 'N/A'; ?>
                                    </div>
                                </div>
                                <div class="info-row">
                                    <i class="fas fa-phone"></i>
                                    <div>
                                        <strong>Contact</strong>
                                        <?php echo isset($_SESSION['contact']) ? htmlspecialchars($_SESSION['contact']) : 'N/A'; ?>
                                    </div>
                                </div>
                                <div class="info-row">
                                    <i class="fas fa-envelope"></i>
                                    <div>
                                        <strong>Email</strong>
                                        <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'N/A'; ?>
                                    </div>
                                </div>
                                <div class="info-row">
                                    <i class="fas fa-calendar"></i>
                                    <div>
                                        <strong>Member Since</strong>
                                        <?php echo $member_since_date; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="adopted-pets-list" class="adopted-pets-grid">
                            <h3>My Adopted Pets</h3>
                            <?php if (empty($adopted_pets_list)): ?>
                                <p>You have not adopted any pets yet.</p>
                            <?php else: ?>
                                <div class="adopted-pets-cards-grid">
                                <?php foreach ($adopted_pets_list as $pet): ?>
                                    <div class="adopted-pet-card">
                                        <span class="adopted-heart"><i class="fas fa-heart"></i></span>
                                        <div class="adopted-pet-img">
                                            <?php if (!empty($pet['primary_image'])): ?>
                                                <img src="<?php echo htmlspecialchars($pet['primary_image']); ?>" alt="<?php echo htmlspecialchars($pet['name']); ?>">
                                            <?php else: ?>
                                                <i class="fas fa-paw" style="font-size:2.2rem;color:#bbb;"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="adopted-pet-info">
                                            <div class="adopted-pet-name"><?php echo htmlspecialchars($pet['name']); ?></div>
                                            <div class="adopted-pet-type"><?php echo ucfirst(htmlspecialchars($pet['type'])); ?>, <?php echo ucwords(htmlspecialchars($pet['breed'])); ?></div>
                                            <div class="adopted-pet-rehomer-name">Rehomer: <b><?php echo htmlspecialchars($pet['rehomer_name']); ?></b></div>
                                            <div class="adopted-pet-date">Adopted on: <?php echo htmlspecialchars($pet['application_date']); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Adoption History -->
            <?php /* Removed My Adoption History section for adopters as it is already accessible via the sidebar */ ?>

            <!-- My Listings and Applications Section (For Rehomer) -->
            <?php /*
            if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'rehomer') {
                // This section has been moved to mylistings.php
            }
            */ ?>   

        </div>
    </div>
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Tail to Tale</h3>
                <p>Making pet adoption and rehoming easier, one tail at a time.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="/TAILTOTALE/frontend/pages/about.php">About Us</a></li>
                    <li><a href="/TAILTOTALE/frontend/pages/pet-care-tips.php">Pet Care Tips</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contact Us</h3>
                <ul class="contact-info">
                    <li><i class="fas fa-phone"></i> (555) 123-4567</li>
                    <li><i class="fas fa-envelope"></i> info@tailtotale.com</li>
                    <li><i class="fas fa-map-marker-alt"></i> 123 Pet Street, Animal City, AC 12345</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Cookie Policy</a>
            </div>
        </div>
    </footer>
    <script>
    // Function to fetch and display unread message count (copied from messages.php)
    async function fetchUnreadMessageCount() {
        try {
            const response = await fetch('/TAILTOTALE/backend/api/get_unread_message_count.php');
            const data = await response.json();

            const unreadBadge = document.getElementById('unread-messages-badge');
            if (data.success && data.unread_count > 0) {
                unreadBadge.textContent = data.unread_count;
                unreadBadge.style.display = 'inline-block';
            } else {
                unreadBadge.style.display = 'none';
            }
        } catch (error) {
            console.error('Error fetching unread message count:', error);
            const unreadBadge = document.getElementById('unread-messages-badge');
            unreadBadge.style.display = 'none'; // Hide badge on error
        }
    }

    // Fetch initial unread count on page load
    document.addEventListener('DOMContentLoaded', () => {
        fetchUnreadMessageCount();
        
        // Poll for new messages and unread count periodically (e.g., every 30 seconds)
        setInterval(() => {
            fetchUnreadMessageCount();
        }, 30000); // Poll every 30 seconds
    });
    </script>
    <script>
    // Fetch active messages count (conversations) and update the dashboard
    async function fetchActiveMessagesCount() {
        try {
            const response = await fetch('/TAILTOTALE/backend/api/get_conversations.php');
            const data = await response.json();
            if (data.success && Array.isArray(data.conversations)) {
                document.getElementById('active-messages-count').textContent = data.conversations.length;
            }
        } catch (e) {
            // Fallback: leave as 0
        }
    }
    document.addEventListener('DOMContentLoaded', fetchActiveMessagesCount);
    </script>
    <script>
    // Show file input when edit button is clicked
    document.getElementById('editProfilePicBtn').addEventListener('click', function() {
        document.getElementById('profilePicForm').style.display = 'block';
    });

    // Handle profile picture upload
    document.getElementById('profilePicForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData();
        const fileInput = document.getElementById('profilePicInput');
        if (!fileInput.files[0]) return;
        formData.append('profile_picture', fileInput.files[0]);
        const response = await fetch('/TAILTOTALE/backend/api/upload_profile_picture.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success && data.profile_picture_url) {
            // Update avatar image if present
            const avatarBox = document.getElementById('userAvatarBox');
            avatarBox.innerHTML = `<img src="${data.profile_picture_url}" alt="Profile" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover;" onerror="this.onerror=null;this.src='/TAILTOTALE/frontend/assets/images/default.png';">`;
            document.getElementById('profilePicForm').style.display = 'none';
        } else {
            alert('Failed to upload profile picture.');
        }
    });
    </script>
    <script>
    // Hamburger menu toggle
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const sidebarNav = document.querySelector('.sidebar');
    let sidebarOverlay = null;
    hamburgerBtn && hamburgerBtn.addEventListener('click', function() {
        sidebarNav.classList.toggle('open');
        if (sidebarNav.classList.contains('open')) {
            document.body.classList.add('sidebar-open');
            sidebarOverlay = document.createElement('div');
            sidebarOverlay.className = 'sidebar-overlay';
            sidebarOverlay.onclick = function() {
                sidebarNav.classList.remove('open');
                document.body.classList.remove('sidebar-open');
                if (sidebarOverlay) sidebarOverlay.remove();
            };
            document.body.appendChild(sidebarOverlay);
        } else {
            document.body.classList.remove('sidebar-open');
            if (sidebarOverlay) sidebarOverlay.remove();
        }
    });
    // Close sidebar on resize if desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth > 900) {
            sidebarNav.classList.remove('open');
            document.body.classList.remove('sidebar-open');
            if (sidebarOverlay) sidebarOverlay.remove();
        }
    });
    </script>
</body>

</html>