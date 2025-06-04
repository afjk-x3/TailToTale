<?php
session_start();
// Notification badge logic for sidebar
$total_unread_messages = 0;
$total_new_applications = 0;
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'rehomer') {
    $conn_notify = new mysqli('localhost', 'root', '', 'tailtotale');
    if (!$conn_notify->connect_error) {
        $user_id = $_SESSION['user_id'];
        // New applications for My Listings
        $sql_notify = "SELECT COUNT(*) as total_new FROM applications WHERE pet_id IN (SELECT id FROM pets WHERE user_id = ?) AND seen = 0";
        $stmt_notify = $conn_notify->prepare($sql_notify);
        $stmt_notify->bind_param("i", $user_id);
        $stmt_notify->execute();
        $result_notify = $stmt_notify->get_result();
        if ($result_notify->num_rows > 0) {
            $total_new_applications = $result_notify->fetch_assoc()['total_new'];
        }
        $stmt_notify->close();
        // Unread messages
        $sql_unread = "SELECT COUNT(*) as unread_count FROM messages WHERE recipient_id = ? AND is_read = 0";
        $stmt_unread = $conn_notify->prepare($sql_unread);
        $stmt_unread->bind_param("i", $user_id);
        $stmt_unread->execute();
        $result_unread = $stmt_unread->get_result();
        if ($result_unread->num_rows > 0) {
            $total_unread_messages = $result_unread->fetch_assoc()['unread_count'];
        }
        $stmt_unread->close();
        $conn_notify->close();
    }
}

$pet_id = null;
$pet_name = "Listed Pet"; // Placeholder pet
$applications_list = []; // Array to hold all application data
$error_message = null;

// Get pet ID from URL
if (isset($_GET['pet_id'])) {
    $pet_id = intval($_GET['pet_id']);
} else {
    $error_message = "No pet ID provided to view applications.";
}

// --- Fetch Pet Name (NEW) ---
if ($pet_id) {
    $conn_pet_name = new mysqli('localhost', 'root', '', 'tailtotale');
    if (!$conn_pet_name->connect_error) {
        $sql_pet_name = "SELECT name FROM pets WHERE id = ?";
        $stmt_pet_name = $conn_pet_name->prepare($sql_pet_name);
        if ($stmt_pet_name) {
            $stmt_pet_name->bind_param("i", $pet_id);
            $stmt_pet_name->execute();
            $result_pet_name = $stmt_pet_name->get_result();
            if ($result_pet_name && $result_pet_name->num_rows > 0) {
                $pet_data = $result_pet_name->fetch_assoc();
                $pet_name = htmlspecialchars($pet_data['name']); // Update $pet_name with fetched name
            }
            $stmt_pet_name->close();
        }
        $conn_pet_name->close();
    } else {
         error_log("Error connecting to DB for pet name: " . $conn_pet_name->connect_error, 0);
    }
}
// --- End Fetch Pet Name ---

// Mark all as seen and fetch applications into $applications_list
$conn = new mysqli('localhost', 'root', '', 'tailtotale');
if ($conn->connect_error) die('DB error');

if ($pet_id) {
    // Mark all as seen
    $conn->query("UPDATE applications SET seen = 1 WHERE pet_id = $pet_id");
    
    // Fetch applications
    $sql = "SELECT * FROM applications WHERE pet_id = ? ORDER BY application_date DESC";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $pet_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            // Fetch all results into an array
            $applications_list = $result->fetch_all(MYSQLI_ASSOC);
        }
        $stmt->close();
    } else {
         $error_message = "Error preparing application query.";
    }
} else {
    $error_message = "No pet ID provided to view applications."; // Redundant check, but good for clarity
}

$conn->close(); // Close DB connection after fetching

// Check if any application is confirmed
$confirmed_application_exists = false;
if (!empty($applications_list)) {
    foreach ($applications_list as $app) {
        if (isset($app['status']) && $app['status'] === 'confirmed') {
            $confirmed_application_exists = true;
            break; // No need to check further once one is confirmed
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications for <?php echo $pet_name; ?> - Tail to Tale</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hamburger, .sidebar { display: none; }
        @media (max-width: 900px) {
            .nav-links { display: none; }
            .hamburger { display: flex !important; }
            .sidebar { display: block; position: fixed; left: -270px; top: 0; height: 100vh; width: 250px; background: #fff; box-shadow: 2px 0 8px rgba(0,0,0,0.12); transition: left 0.3s; z-index: 150; flex-direction: column; padding-top: 100px; align-items: flex-start; justify-content: flex-start; padding-left: 1rem; padding-right: 1rem; }
            .sidebar.open { left: 0; }
            .sidebar:not(.open) { left: -270px; }
            .sidebar .menu { flex-direction: column; gap: 1.2rem; align-items: flex-start; justify-content: flex-start; width: 100%; margin-top: .5rem; }
            body.sidebar-open { overflow: hidden; }
            .sidebar-overlay { display: block; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.2); z-index: 100; }
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
        /* Basic styling for application cards */
        .application-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .application-card h3 {
            color: #333;
            margin-top: 0;
            margin-bottom: 0.8rem;
        }

        .application-card p {
            color: #666;
            margin-bottom: 0.5rem;
            line-height: 1.6;
        }

        .application-details {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .application-details p strong {
            color: #333;
        }

        .no-applications {
            text-align: center;
            color: #666;
            font-size: 1.1rem;
        }

        .application-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(450px, 100%), 1fr));
            gap: 1.5rem;
        }

       /* Application Card Button Layout */
        .application-card .form-actions {
            display: flex;
            justify-content: center !important;
            align-items: center;
            gap: 15px;
            margin-top: 1rem;
            width: 100%; /* Ensure flex container takes full width */
        }

         /* Make buttons consistent size */
         .application-card .form-actions .btn {
            min-width: 100px;
            padding: 8px 16px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .application-card .form-actions .primary-btn {
            background-color: #4CAF50;
            color: white;
        }

        .application-card .form-actions .primary-btn:hover {
            background-color: #45a049;
        }

        .application-card .form-actions .secondary-btn {
            background-color: #f44336;
            color: white;
        }

        .application-card .form-actions .secondary-btn:hover {
            background-color: #da190b;
        }

        /* Badge styling */
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            text-align: center;
        }

        /* Responsive adjustment for smaller screens */
        @media (max-width: 480px) {
            .application-card .form-actions {
                flex-direction: column;
                gap: 10px;
            }
            
            .application-card .form-actions .btn {
                width: 100%;
                min-width: auto;
            }
        }

        /* New style for the back button container */
        .back-button-container {
            margin-top: 0; /* Removed large margin-top as it's inside the container */
            margin-bottom: 20px; /* Keep some space below the button */
            text-align: left;
            padding-left: 0; /* Remove extra left padding */
            /* Consider adding some padding or margin to the button itself if needed */
        }

        .back-button-container .btn.secondary-btn {
            /* Adjust button styling if needed */
            outline: none;
            background: none;
            border: none;
            padding: 0;
            box-shadow: none;
            color: #4CAF50; /* Website green color */
            font-size: 1rem; /* Adjust font size if necessary */
            transition: color 0.3s ease;
        }

         .back-button-container .btn.secondary-btn:focus,
         .back-button-container .btn.secondary-btn:hover {
             outline: none;
             background: none;
             color: #388e3c; /* Darker green on hover/focus */
             transform: translateX(-5px);
         }

         .back-button-container .btn.secondary-btn i {
             color: #4CAF50; /* Ensure icon color is also green */
             margin-right: 5px; /* Space between icon and text */
             transition: transform 0.3s ease;
         }

        /* Adjust padding of the featured-section is still needed but maybe less or differently */
        /* .featured-section { */
            /* padding: 2rem 5%; */ /* Ensure horizontal padding */
        /* } */
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
            <ul class="nav-links">
                <li><a href="/TAILTOTALE/frontend/pages/about.php" class="nav-link"><i class="fas fa-info-circle"></i> About Us</a></li>
                <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                    <li><a href="messages.php" class="nav-link"><i class="fas fa-envelope"></i> Messages<?php if ($total_unread_messages > 0): ?><span class="notification-badge" style="background:#e74c3c;color:#fff;font-size:0.8rem;font-weight:bold;padding:2px 7px;border-radius:10px;margin-left:5px;vertical-align:top;display:inline-block;"><?php echo $total_unread_messages; ?></span><?php endif; ?></a></li>
                    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'rehomer'): ?>
                        <li><a href="mylistings.php" class="nav-link"><i class="fas fa-list"></i> My Listings<?php if ($total_new_applications > 0): ?><span class="notification-badge" style="background:#e74c3c;color:#fff;font-size:0.8rem;font-weight:bold;padding:2px 7px;border-radius:10px;margin-left:5px;vertical-align:top;display:inline-block;"> <?php echo $total_new_applications; ?></span><?php endif; ?></a></li>
                    <?php else: ?>
                        <li><a href="adoption-history.php" class="nav-link"><i class="fas fa-history"></i> Adoption History</a></li>
                    <?php endif; ?>
                    <li><a href="profile.php" class="nav-link"><i class="fas fa-user"></i> My Profile</a></li>
                <?php else: ?>
                    <li><a href="/TAILTOTALE/backend/api/signin.php" class="nav-link login-btn"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="/TAILTOTALE/backend/api/signup.php" class="nav-link register-btn"><i class="fas fa-user-plus"></i> Register</a></li>
                <?php endif; ?>
            </ul>
            <button class="hamburger" id="hamburgerBtn" aria-label="Open navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </nav>
    </header>
    <aside class="sidebar" id="sidebarNav">
        <nav class="menu">
            <a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i> My Profile
            </a>
            <a href="messages.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>">
                <i class="fas fa-envelope"></i> Messages
                <?php if ($total_unread_messages > 0): ?>
                    <span class="notification-badge" style="background:#e74c3c;color:#fff;font-size:0.8rem;font-weight:bold;padding:2px 7px;border-radius:10px;margin-left:5px;vertical-align:top;display:inline-block;">
                        <?php echo $total_unread_messages; ?>
                    </span>
                <?php endif; ?>
            </a>
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'rehomer'): ?>
                <a href="mylistings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'mylistings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> My Listings
                    <?php if ($total_new_applications > 0): ?>
                        <span class="notification-badge" style="background:#e74c3c;color:#fff;font-size:0.8rem;font-weight:bold;padding:2px 7px;border-radius:10px;margin-left:5px;vertical-align:top;display:inline-block;">
                            <?php echo $total_new_applications; ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
            <a href="about.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>">
                <i class="fas fa-info-circle"></i> About Us
            </a>
        </nav>
    </aside>

    <main>
        <!-- Removed Back Button from here -->

        <section class="featured-section">
            <div class="form-container">
                <!-- Back Button Moved Here -->
                <div class="back-button-container">
                    <a href="mylistings.php" class="btn secondary-btn"><i class="fas fa-arrow-left"></i> Back to Listings</a>
                </div>
                <h2>Applications for <?php echo $pet_name; ?></h2>

                <?php if (isset($error_message)): ?>
                    <div class="message info"><?php echo $error_message; ?></div>
                <?php elseif (empty($applications_list)): ?>
                     <div class="no-applications">No applications found for this pet yet.</div>
                <?php else: ?>
                    <?php
                    $current_app_status = $app['status']; // Use the status fetched initially
                    ?>
                    
                    <div class="application-list">
                        <?php foreach ($applications_list as $app): ?>
                            <div class="application-card">
                                <h3>Application #<?php echo htmlspecialchars($app['id'] ?? 'N/A'); ?></h3>
                                <p><strong>Applicant:</strong> <?php echo !empty($app['adopter_name']) ? htmlspecialchars($app['adopter_name']) : 'N/A'; ?></p>
                                <p><strong>Email:</strong> <?php echo !empty($app['adopter_email']) ? htmlspecialchars($app['adopter_email']) : 'N/A'; ?></p>
                                <div class="application-details">
                                    <p><strong>Message:</strong> <?php echo nl2br(htmlspecialchars($app['message'] ?? 'N/A')); ?></p>
                                    <p><strong>Application Date:</strong> <?php echo htmlspecialchars($app['application_date'] ?? 'N/A'); ?></p>
                                    <p><strong>Status:</strong> <?php echo htmlspecialchars($app['status'] ?? 'N/A'); ?></p>
                                    <!-- Add more application details here as needed -->
                                </div>
                                <!-- Add action buttons here, e.g., Contact, Accept, Reject -->
                                <div class="form-actions" style="justify-content: flex-end; margin-top: 1rem;">
                                    <?php if ($app['status'] == 'pending'): ?>
                                        <?php if ($confirmed_application_exists): ?>
                                            <!-- If another application is already confirmed, only allow rejecting this one -->
                                            <button class="btn secondary-btn" onclick="handleRejectApplication(<?php echo htmlspecialchars($app['id'] ?? '0'); ?>)">Reject</button>
                                        <?php else: ?>
                                            <button class="btn primary-btn" onclick="handleConfirmApplication(<?php echo htmlspecialchars($app['id'] ?? '0'); ?>)">Confirm</button>
                                            <button class="btn secondary-btn" onclick="handleRejectApplication(<?php echo htmlspecialchars($app['id'] ?? '0'); ?>)">Reject</button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($app['status'] == 'confirmed'): ?>
                                            <span class="badge" style="background:#e8f5e9;color:#388e3c;padding:3px 14px;border-radius:12px;font-size:0.95rem;font-weight:600;">Confirmed</span>
                                        <?php elseif ($app['status'] == 'rejected'): ?>
                                            <span class="badge" style="background:#ffebee;color:#c62828;padding:3px 14px;border-radius:12px;font-size:0.95rem;font-weight:600;">Rejected</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        </section>
    </main>

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
    </footer>
    <script src="../assets/js/script.js"></script>
    <script>
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
    function handleRejectApplication(applicationId) {
        if (!confirm('Are you sure you want to reject this application?')) return;
        fetch('/TAILTOTALE/backend/api/reject_application.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'application_id=' + encodeURIComponent(applicationId)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Application rejected.');
                location.reload();
            } else {
                alert('Failed to reject application: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            alert('Error: ' + error);
        });
    }
    function handleConfirmApplication(applicationId) {
        // Send an AJAX request to a backend script to confirm the application
        fetch('/TAILTOTALE/backend/api/confirm_application.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'application_id=' + encodeURIComponent(applicationId)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Application confirmed!');
                // Optionally refresh the page or update the UI
                window.location.reload(); 
            } else {
                alert('Error confirming application: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while confirming the application.');
        });
    }
    </script>
</body>
</html> 