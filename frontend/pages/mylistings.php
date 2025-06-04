<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'rehomer') {
    header('Location: ../../backend/api/login.php');
    exit;
}
if (isset($_SESSION['pet_posted_success']) && $_SESSION['pet_posted_success'] === true) {
    echo '<div class="success-message">PET LISTED SUCCESSFULLY</div>';
    unset($_SESSION['pet_posted_success']); // Clear the session variable
}
// Count total unseen applications for all pets listed by this rehomer
$total_new_applications = 0;
$conn_notify = new mysqli('localhost', 'root', '', 'tailtotale');
if (!$conn_notify->connect_error) {
    $user_id = $_SESSION['user_id'];
    $sql_notify = "SELECT COUNT(*) as total_new FROM applications a JOIN pets p ON a.pet_id = p.id WHERE p.user_id = ? AND a.seen = 0";
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Listings - Tail to Tale</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { 
            margin: 0;
            background: #f7f7f7;
            min-height: 100vh;
        }
        .dashboard-container { 
            display: flex; 
            margin-top: 80px;
            min-height: 100vh;
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
        .pet-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
            width: 100%;
            padding: 1rem;
        }
        .pet-card { 
            background: #fff; 
            border-radius: 12px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.08); 
            padding: 2rem; 
            display: flex; 
            flex-direction: column;
            width: 100%;
        }
        .card-image-container { position: relative; width: 100%; height: 200px; margin-bottom: 1.5rem; }
        .card-image-bg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; filter: blur(8px) brightness(0.7); border-radius: 12px; z-index: 1; }
        .card-image-main { position: relative; width: 100%; height: 100%; object-fit: contain; border-radius: 12px; z-index: 2; }
        .card-image-circular { position: absolute; bottom: 10px; right: 10px; width: 48px; height: 48px; border-radius: 50%; overflow: hidden; border: 2px solid #fff; z-index: 3; background: #fff; }
        .card-image-circular img { width: 100%; height: 100%; object-fit: cover; }
        .pet-info { margin-top: 1rem; }
        .pet-info h3 { margin: 0 0 0.5rem 0; color: #2e7d32; }
        .pet-info p { margin: 0.2rem 0; color: #444; }
        .btn.primary-btn { background: #4CAF50; color: #fff; border: none; border-radius: 8px; padding: 0.7rem 1.5rem; font-size: 1rem; margin-top: 0.7rem; cursor: pointer; transition: background 0.2s; width: 100%; }
        .btn.primary-btn:hover { background: #388e3c; }
        .badge { display: inline-block; margin-top: 0.5rem; }
        @media (max-width: 1200px) {
            .pet-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        .hamburger {
            display: flex; /* Default display for mobile */
            flex-direction: column;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
            background: transparent;
            border: none;
            cursor: pointer;
            z-index: 999;
            padding: 0; /* Ensure no default padding */
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

        @media (min-width: 901px) {
            .hamburger {
                display: none;
            }
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
                margin-top: 5rem;
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
            /* Overlay for sidebar */
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
        @media (max-width: 768px) {
            .pet-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
                padding: 0.5rem;
            }
            .sidebar {
                flex-direction: column;
                gap: 1rem;
                padding: 0.5rem 0.5rem 1rem 0.5rem;
            }
            .sidebar .menu {
                flex-direction: column;
                gap: 0.5rem;
            }
            .main-content {
                padding: 0.5rem 0.2rem 1rem 0.2rem;
            }
        }
        @media (max-width: 600px) {
            .dashboard-header .welcome {
                font-size: 1.3rem;
            }
            .pet-card {
                padding: 1rem;
            }
            .filter-form {
                flex-direction: column;
                gap: 0.7rem;
                padding: 0.7rem 0.5rem;
            }
            .no-pets-modern {
                padding: 1.5rem 0.5rem;
            }
        }
        @media (max-width: 400px) {
            .sidebar {
                padding: 0.2rem 0.1rem 0.5rem 0.1rem;
            }
            .main-content {
                padding: 0.1rem 0.05rem 0.5rem 0.05rem;
            }
            .pet-card {
                padding: 0.5rem;
            }
        }
        .filter-form {
            margin-bottom: 2rem;
            display: flex;
            gap: 1.5rem;
            align-items: flex-end;
            background: #fff;
            padding: 1.2rem 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(76,175,80,0.07);
        }
        .filter-form label {
            display: block;
            font-size: 1rem;
            color: #2e7d32;
            font-weight: 600;
            margin-bottom: 0.4rem;
        }
        .filter-form select {
            width: 140px;
            padding: 0.5rem 1rem;
            border: 1.5px solid #c8e6c9;
            border-radius: 8px;
            font-size: 1rem;
            background: #f9fafb;
            color: #222;
            transition: border 0.2s;
        }
        .filter-form select:focus {
            border-color: #43b05c;
            outline: none;
        }
        .filter-form button.btn.primary-btn {
            margin-top: 1.2rem;
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-size: 1rem;
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
        .no-pets-modern {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 320px;
            width: 100%;
            max-width: 500px;
            background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%);
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(76,175,80,0.10);
            margin: 2.5rem auto;
            padding: 3rem 1rem;
            animation: fadeIn 1s;
        }
        .no-pets-illustration {
            background: #fff;
            border-radius: 50%;
            width: 90px;
            height: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.2rem;
            box-shadow: 0 2px 12px rgba(76,175,80,0.10);
            animation: bounceIn 1.2s;
        }
        .no-pets-illustration i {
            font-size: 3rem;
            color: #43b05c;
        }
        .no-pets-text h2 {
            color: #388e3c;
            font-family: 'Segoe UI', 'Arial', sans-serif;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            letter-spacing: 1px;
        }
        .no-pets-text p {
            color: #555;
            font-size: 1.15rem;
            text-align: center;
            margin: 0;
        }
        .no-pets-text a {
            color: #43b05c;
            text-decoration: underline;
            font-weight: 500;
        }
        .no-pets-text a:hover {
            color: #2e7d32;
        }
        .pet-grid .no-pets-modern {
            grid-column: 1 / -1; /* Span across all grid columns */
            justify-self: center; /* Center horizontally within the grid */
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 320px;
            width: 100%;
            max-width: 500px;
            background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%);
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(76,175,80,0.10);
            margin: 2.5rem auto;
            padding: 3rem 1rem;
            animation: fadeIn 1s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px);}
            to { opacity: 1; transform: translateY(0);}
        }
        @keyframes bounceIn {
            0% { transform: scale(0.7);}
            60% { transform: scale(1.1);}
            100% { transform: scale(1);}
        }
        /* Filter form mobile improvements */
        .filter-form {
            margin-bottom: 2rem;
            display: flex;
            gap: 1.5rem;
            align-items: flex-end;
            background: #fff;
            padding: 1.2rem 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(76,175,80,0.07);
        }
        @media (max-width: 900px) {
            .filter-form {
                flex-direction: column;
                align-items: stretch;
                gap: 0.7rem;
                padding: 1rem 0.7rem;
            }
            .filter-form label {
                margin-bottom: 0.2rem;
            }
            .filter-form select,
            .filter-form button,
            .filter-form a.btn {
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="/TAILTOTALE/frontend/pages/index-rehomer.php" style="text-decoration: none; color: inherit;">
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
    <div class="dashboard-container">
        <aside class="sidebar" id="sidebarNav">
            <nav class="menu">
                <a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i> My Profile
                </a>
                <a href="mylistings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'mylistings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> My Listings
                    <?php if ($total_new_applications > 0): ?>
                        <span class="notification-badge"><?php echo $total_new_applications; ?></span>
                    <?php endif; ?>
                </a>
                <a href="messages.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i> Messages
                    <span class="notification-badge" id="unread-messages-badge" style="display: none;"></span>
                </a>
                <button onclick="window.location.href='/TAILTOTALE/backend/api/logout.php'"><i class="fas fa-sign-out-alt"></i> Log Out</button>
            </nav>
        </aside>
        <div class="main-content">
            <div class="dashboard-header">
                <div class="welcome">My Listed Pets and Applications</div>
            </div>
            <form method="get" class="filter-form">
                <div>
                    <label for="pet_type">Pet Type</label>
                    <select name="pet_type" id="pet_type">
                        <option value="">All</option>
                        <option value="dog" <?php if(isset($_GET['pet_type']) && $_GET['pet_type']=='dog') echo 'selected'; ?>>Dog</option>
                        <option value="cat" <?php if(isset($_GET['pet_type']) && $_GET['pet_type']=='cat') echo 'selected'; ?>>Cat</option>
                        <option value="other" <?php if(isset($_GET['pet_type']) && $_GET['pet_type']=='other') echo 'selected'; ?>>Other</option>
                    </select>
                </div>
                <div>
                    <label for="gender">Gender</label>
                    <select name="gender" id="gender">
                        <option value="">Any</option>
                        <option value="male" <?php if(isset($_GET['gender']) && $_GET['gender']=='male') echo 'selected'; ?>>Male</option>
                        <option value="female" <?php if(isset($_GET['gender']) && $_GET['gender']=='female') echo 'selected'; ?>>Female</option>
                    </select>
                </div>
                <div>
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="">All</option>
                        <option value="pending" <?php if(isset($_GET['status']) && $_GET['status']=='pending') echo 'selected'; ?>>Pending</option>
                        <option value="upcoming" <?php if(isset($_GET['status']) && $_GET['status']=='upcoming') echo 'selected'; ?>>Upcoming</option>
                        <option value="adopted" <?php if(isset($_GET['status']) && $_GET['status']=='adopted') echo 'selected'; ?>>Adopted</option>
                    </select>
                </div>
                <button type="submit" class="btn primary-btn" style="width:auto;">Filter</button>
                <a href="/TAILTOTALE/frontend/pages/rehome.php" class="btn primary-btn" style="width:auto; padding: 0.8rem 1.5rem; margin-left: auto;"><i class="fas fa-plus-circle"></i> List New Pet</a>
            </form>
            <div class="pet-grid">
                <?php
                if (!isset($_SESSION['user_id'])) {
                    echo '<p>You must be logged in to see your listed pets.</p>';
                } else {
                    $servername = "localhost";
                    $username = "root";
                    $password = "";
                    $dbname = "tailtotale";
                    $conn = new mysqli($servername, $username, $password, $dbname);
                    if ($conn->connect_error) {
                        error_log("Database Connection failed: " . $conn->connect_error, 0);
                        echo '<p>Error fetching pets. Please try again later.</p>';
                    } else {
                        $user_id = $_SESSION['user_id'];
                        $where = "user_id = ?";
                        $params = [$user_id];
                        $types = "i";
                        if (!empty($_GET['pet_type'])) {
                            $where .= " AND type = ?";
                            $params[] = $_GET['pet_type'];
                            $types .= "s";
                        }
                        if (!empty($_GET['gender'])) {
                            $where .= " AND gender = ?";
                            $params[] = $_GET['gender'];
                            $types .= "s";
                        }
                        if (!empty($_GET['status'])) {
                            $status = $_GET['status'];
                            if ($status === 'pending') {
                                // Pets with at least one pending application and no confirmed applications
                                $where .= " AND id IN (SELECT pet_id FROM applications WHERE status = 'pending') AND id NOT IN (SELECT pet_id FROM applications WHERE status = 'confirmed')";
                            } elseif ($status === 'adopted') {
                                // Pets with a confirmed application
                                $where .= " AND id IN (SELECT pet_id FROM applications WHERE status = 'confirmed')";
                            } elseif ($status === 'upcoming') {
                                // Pets with no applications
                                $where .= " AND id NOT IN (SELECT pet_id FROM applications)";
                            } // If status is 'all' (empty), no additional WHERE clause is needed for status.
                        }
                        $sql = "SELECT id, name, type, breed, age, gender, primary_image, reason, vaccination_status FROM pets WHERE $where ORDER BY created_at DESC";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param($types, ...$params);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result && $result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                $pet_id = htmlspecialchars($row['id']);
                                $pet_name = htmlspecialchars($row['name']);
                                $pet_type = htmlspecialchars($row['type']);
                                $pet_breed = htmlspecialchars($row['breed']);
                                $pet_age = htmlspecialchars($row['age']);
                                $pet_image = htmlspecialchars($row['primary_image']);
                                $pet_reason = htmlspecialchars($row['reason']);
                                $pet_health = isset($row['vaccination_status']) ? htmlspecialchars($row['vaccination_status']) : '';
                                echo '<div class="pet-card">';
                                echo '<div class="card-image-container">';
                                echo '<img class="card-image-bg" src="' . $pet_image . '" alt="" aria-hidden="true" />';
                                echo '<img class="card-image-main" src="' . $pet_image . '" alt="' . $pet_name . '" />';
                                echo '<div class="card-image-circular">';
                                echo '<img src="' . $pet_image . '" alt="' . $pet_name . ' - small" />';
                                echo '</div>';
                                echo '</div>';
                                echo '<div class="pet-info">';
                                echo '<h3>' . $pet_name . '</h3>';
                                echo '<p><strong>Type:</strong> ' . ucfirst($pet_type) . '</p>';
                                echo '<p><strong>Breed:</strong> ' . ucwords($pet_breed) . '</p>';
                                echo '<p><strong>Age:</strong> ' . $pet_age . '</p>';
                                echo '<p><strong>Reason for Rehoming:</strong> ' . $pet_reason . '</p>';
                                echo '<p><strong>Health/Vaccination Status:</strong> ' . $pet_health . '</p>';
                                // Status badge
                                $pet_status = 'upcoming';
                                $status_badge = '<span style="background:#e0e0e0;color:#666;padding:3px 14px;border-radius:12px;font-size:0.95rem;font-weight:600;margin-bottom:8px;display:inline-block;">Upcoming</span>';

                                // Check for any pending or approved applications
                                $app_sql_pending = "SELECT status FROM applications WHERE pet_id = ? AND (status = 'pending' OR status = 'approved') LIMIT 1";
                                $app_stmt_pending = $conn->prepare($app_sql_pending);
                                $app_stmt_pending->bind_param("i", $row['id']);
                                $app_stmt_pending->execute();
                                $app_stmt_pending->store_result();

                                if ($app_stmt_pending->num_rows > 0) {
                                    $pet_status = 'pending';
                                    $status_badge = '<span style="background:#fff8e1;color:#f9a825;padding:3px 14px;border-radius:12px;font-size:0.95rem;font-weight:600;margin-bottom:8px;display:inline-block;">Pending</span>';
                                }
                                $app_stmt_pending->close();

                                $app_sql = "SELECT status FROM applications WHERE pet_id = ? AND status = 'confirmed' LIMIT 1";
                                $app_stmt = $conn->prepare($app_sql);
                                $app_stmt->bind_param("i", $row['id']);
                                $app_stmt->execute();
                                $app_stmt->store_result();
                                if ($app_stmt->num_rows > 0) {
                                    $pet_status = 'adopted';
                                    $status_badge = '<span style="background:#e8f5e9;color:#388e3c;padding:3px 14px;border-radius:12px;font-size:0.95rem;font-weight:600;margin-bottom:8px;display:inline-block;">Adopted</span>';
                                }
                                $app_stmt->close();
                                echo $status_badge;
                                echo '<button class="btn primary-btn" onclick="window.location.href=\'/TAILTOTALE/frontend/pages/edit-pet.php?id=' . $pet_id . '\'">Edit</button>';
                                echo '<button class="btn primary-btn" style="margin-top:10px;" onclick="window.location.href=\'/TAILTOTALE/frontend/pages/applications.php?pet_id=' . $pet_id . '\'">View Application</button>';
                                // Count unseen/pending applications
                                $app_sql = "SELECT COUNT(*) as new_apps FROM applications WHERE pet_id = ? AND seen = 0";
                                $app_stmt = $conn->prepare($app_sql);
                                $app_stmt->bind_param("i", $pet_id);
                                $app_stmt->execute();
                                $app_result = $app_stmt->get_result();
                                $new_apps = $app_result->fetch_assoc()['new_apps'];
                                $app_stmt->close();
                                if ($new_apps > 0) {
                                    echo '<span class="badge new-apps" style="background:#e74c3c;color:#fff;padding:2px 8px;border-radius:12px;margin-left:8px;">'.$new_apps.' New Application(s)</span>';
                                }
                                echo '</div>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="no-pets-modern">
                                <div class="no-pets-illustration">
                                    <i class="fas fa-paw"></i>
                                </div>
                                <div class="no-pets-text">
                                    <h2 style="text-align: center;">No Pets Listed</h2>
                                    <p>You haven\'t listed any pets for rehoming yet.<br>
                                    Click <a href="/TAILTOTALE/frontend/pages/rehome.php">here</a> to list your first pet!</p>
                                </div>
                            </div>';
                        }
                        $stmt->close();
                        $conn->close();
                    }
                }
                ?>
            </div>
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
    <script src="../assets/js/script.js"></script>
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

    // Hamburger menu toggle
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const sidebarNav = document.getElementById('sidebarNav');
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