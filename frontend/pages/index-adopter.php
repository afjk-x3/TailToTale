<?php
session_start();
// Fetch unread message count for adopter
$total_unread_messages = 0;
$total_adopter_notifications = 0;
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'adopter') {
    $conn_notify = new mysqli('localhost', 'root', '', 'tailtotale');
    if (!$conn_notify->connect_error) {
        $user_id = $_SESSION['user_id'];
        $sql_unread = "SELECT COUNT(*) as unread_count FROM messages WHERE recipient_id = ? AND is_read = 0";
        $stmt_unread = $conn_notify->prepare($sql_unread);
        $stmt_unread->bind_param("i", $user_id);
        $stmt_unread->execute();
        $result_unread = $stmt_unread->get_result();
        if ($result_unread->num_rows > 0) {
            $total_unread_messages = $result_unread->fetch_assoc()['unread_count'];
        }
        $stmt_unread->close();
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

// Fetch logged-in user details
$loggedInEmail = isset($_SESSION['email']) ? $_SESSION['email'] : '';
$loggedInFullname = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : '';
$isLoggedIn = !empty($loggedInEmail); // Assuming email means logged in

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tail to Tale - Adopter Home</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="/TAILTOTALE/frontend/pages/index-adopter.php" style="text-decoration: none; color: inherit;">
                    <h1>Tail to Tale</h1>
                </a>
            </div>
            <ul class="nav-links">
                <li><a href="/TAILTOTALE/frontend/pages/about.php" class="nav-link"><i class="fas fa-info-circle"></i> About Us</a></li>
                <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                    <li><a href="messages.php" class="nav-link"><i class="fas fa-envelope"></i> Messages<?php if ($total_unread_messages > 0): ?><span class="notification-badge" style="background:#e74c3c;color:#fff;font-size:0.8rem;font-weight:bold;padding:2px 7px;border-radius:10px;margin-left:5px;vertical-align:top;display:inline-block;"> <?php echo $total_unread_messages; ?></span><?php endif; ?></a></li>
                    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'rehomer'): ?>
                        <li><a href="mylistings.php" class="nav-link"><i class="fas fa-list"></i> My Listings</a></li>
                    <?php else: ?>
                        <li><a href="adoption-history.php" class="nav-link"><i class="fas fa-history"></i> Adoption History<?php if ($total_adopter_notifications > 0): ?><span class="notification-badge" style="background:#e74c3c;color:#fff;font-size:0.8rem;font-weight:bold;padding:2px 7px;border-radius:10px;margin-left:5px;vertical-align:top;display:inline-block;"> <?php echo $total_adopter_notifications; ?></span><?php endif; ?></a></li>
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
                <i class="fas fa-envelope"></i> Messages<?php if ($total_unread_messages > 0): ?><span class="notification-badge" style="background:#e74c3c;color:#fff;font-size:0.8rem;font-weight:bold;padding:2px 7px;border-radius:10px;margin-left:5px;vertical-align:top;display:inline-block;"> <?php echo $total_unread_messages; ?></span><?php endif; ?>
            </a>
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'adopter'): ?>
                    <a href="adoption-history.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'adoption-history.php' ? 'active' : ''; ?>">
                        <i class="fas fa-history"></i> Adoption History<?php if ($total_adopter_notifications > 0): ?><span class="notification-badge" style="background:#e74c3c;color:#fff;font-size:0.8rem;font-weight:bold;padding:2px 7px;border-radius:10px;margin-left:5px;vertical-align:top;display:inline-block;"> <?php echo $total_adopter_notifications; ?></span><?php endif; ?>
                    </a>
            <?php endif; ?>
            <a href="about.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>">
                <i class="fas fa-info-circle"></i> About Us
            </a>
        </nav>
    </aside>
    <main>
        <section id="hero" class="hero-section">
            <div class="hero-content">
                <h1>Find Your Perfect Companion</h1>
                <p>Give a loving home to a pet in need</p>
                <div class="cta-buttons">
                    <a href="/TAILTOTALE/frontend/pages/adopt.php" class="btn primary-btn">Adopt a Pet</a>
                </div>
            </div>
        </section>
        <section id="available-pets" class="featured-section">
            <h2>Available Pets</h2>
            <div class="pet-grid">
                <?php
                $servername = "localhost";
                $username = "root";
                $password = "";
                $dbname = "tailtotale";
                $conn = new mysqli($servername, $username, $password, $dbname);
                if ($conn->connect_error) {
                    error_log("Database Connection failed: " . $conn->connect_error, 0);
                    echo '<p>Error fetching pets. Please try again later.</p>';
                } else {
                    // Fetch all adopted pet IDs (status 'confirmed')
                    $adopted_pet_ids = [];
                    $conn_adopted = new mysqli($servername, $username, $password, $dbname);
                    if (!$conn_adopted->connect_error) {
                        $sql_adopted = "SELECT pet_id FROM applications WHERE status = 'confirmed'";
                        $result_adopted = $conn_adopted->query($sql_adopted);
                        if ($result_adopted) {
                            while ($row_adopted = $result_adopted->fetch_assoc()) {
                                $adopted_pet_ids[] = $row_adopted['pet_id'];
                            }
                        }
                        $conn_adopted->close();
                    }

                    // Fetch all available pets, excluding adopted ones (status 'confirmed')
                    $sql = "SELECT p.id, p.name, p.type, p.breed, p.age, p.primary_image, p.reason, p.vaccination_status, u.fullname as rehomer_name, u.id as rehomer_id FROM pets p JOIN users u ON p.user_id = u.id";
                    if (!empty($adopted_pet_ids)) {
                        $placeholders = implode(',', array_fill(0, count($adopted_pet_ids), '?'));
                        $sql .= " WHERE p.id NOT IN ($placeholders)";
                    }
                    $sql .= " ORDER BY p.created_at DESC";

                    $stmt = $conn->prepare($sql);

                    if ($stmt === false) {
                        error_log("Database prepare failed: " . $conn->error, 0);
                        echo '<p>Error preparing database query.</p>';
                    } else {
                         if (!empty($adopted_pet_ids)) {
                             $types = str_repeat('i', count($adopted_pet_ids));
                             $stmt->bind_param($types, ...$adopted_pet_ids);
                         }

                        if ($stmt->execute()) {
                            $result = $stmt->get_result();
                            $pets = [];
                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $pets[] = $row;
                                }
                            }
                            $show_view_all = count($pets) > 5;
                            if (!empty($pets)) {
                                foreach(array_slice($pets, 0, 5) as $row) {
                                    $pet_id = htmlspecialchars($row['id']);
                                    $pet_name = htmlspecialchars($row['name']);
                                    $pet_type = htmlspecialchars($row['type']);
                                    $pet_breed = htmlspecialchars($row['breed']);
                                    $pet_age = htmlspecialchars($row['age']);
                                    $pet_image = htmlspecialchars($row['primary_image']);
                                    $pet_reason = htmlspecialchars($row['reason']);
                                    $pet_health = isset($row['vaccination_status']) ? htmlspecialchars($row['vaccination_status']) : '';
                                    $rehomer_name = htmlspecialchars($row['rehomer_name']);
                                    $rehomer_id = htmlspecialchars($row['rehomer_id']);
                                    echo '<div class="pet-card" data-pet-id="' . $pet_id . '">';
                                    echo '<div class="card-image-container">';
                                    echo '<img class="card-image-bg" src="' . $pet_image . '" alt="" aria-hidden="true" />';
                                    echo '<img class="card-image-main" src="' . $pet_image . '" alt="' . $pet_name . '" />';
                                    echo '</div>';
                                    echo '<div class="pet-info">';
                                    echo '<h3>' . $pet_name . '</h3>';
                                    echo '<p class="pet-rehomer-name"><strong>Rehomer:</strong> <strong>' . $rehomer_name . '</strong></p>';
                                    echo '<p><strong>Type:</strong> ' . ucfirst($pet_type) . '</p>';
                                    echo '<p><strong>Breed:</strong> ' . ucwords($pet_breed) . '</p>';
                                    echo '<p><strong>Age:</strong> ' . $pet_age . '</p>';
                                    echo '<p><strong>Reason for Rehoming:</strong> ' . $pet_reason . '</p>';
                                    echo '<p><strong>Health/Vaccination Status:</strong> ' . $pet_health . '</p>';
                                    echo '<div class="pet-actions">';
                                    echo '<button class="btn primary-btn" onclick="openAdoptModal(\'' . addslashes($pet_name) . '\',' . $pet_id . ')">Adopt Me</button>';
                                    echo '<a href="/TAILTOTALE/frontend/pages/messages.php?rehomer_id=' . $rehomer_id . '&pet_id=' . $pet_id . '" class="message-icon"><i class="fas fa-comment"></i></a>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="no-pets-modern">
                                    <div class="no-pets-illustration">
                                        <i class="fas fa-paw"></i>
                                    </div>
                                    <div class="no-pets-text">
                                        <h2>So Empty!</h2>
                                        <p>There are currently no pets available for adoption.<br>
                                        Please check back soon or <a href="/TAILTOTALE/frontend/pages/about.php">learn more about us</a>.</p>
                                    </div>
                                </div>';
                            }
                        }
                    }
                    $conn->close();
                }
                ?>
            </div>
            <div class="view-more">
                <?php if ($show_view_all): ?>
                <a href="/TAILTOTALE/frontend/pages/adopt.php" class="btn primary-btn">View All Pets <i class="fas fa-arrow-right"></i></a>
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
        <div class="footer-bottom">
            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Cookie Policy</a>
            </div>
        </div>
    </footer>
    <!-- Adoption Application Modal (copied from adopt.php) -->
    <div id="adoptModal" class="modal" style="display:none;">
      <div class="modal-content">
        <span class="close" onclick="closeAdoptModal()">×</span>
        <h2>Adoption Application for <span id="modalPetName"></span></h2>
        <form id="adoptForm">
          <input type="hidden" id="modalPetId" name="pet_id">
          <div class="form-group">
            <label for="applicantName">Your Name</label>
            <input type="text" id="applicantName" name="applicantName" value="<?php echo htmlspecialchars($loggedInFullname); ?>" <?php echo $isLoggedIn ? 'readonly' : ''; ?> required>
          </div>
          <div class="form-group">
            <label for="applicantEmail">Your Email</label>
            <input type="email" id="applicantEmail" name="applicantEmail" value="<?php echo htmlspecialchars($loggedInEmail); ?>" <?php echo $isLoggedIn ? 'readonly' : ''; ?> required>
          </div>
          <div class="form-group">
            <label for="reason">Why do you want to adopt?</label>
            <textarea id="reason" name="reason" required></textarea>
          </div>
          <button type="submit" class="btn primary-btn">Submit Application</button>
        </form>
      </div>
    </div>
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
        
        <?php
        $loggedInEmail = isset($_SESSION['email']) ? $_SESSION['email'] : '';
        $loggedInFullname = isset($_SESSION['fullname']) ? $_SESSION['fullname'] : '';
        echo "const LOGGED_IN_EMAIL = " . json_encode($loggedInEmail) . ";";
        echo "const LOGGED_IN_FULLNAME = " . json_encode($loggedInFullname) . ";";
        ?>
    </script>
    <script>
    function openAdoptModal(petName, petId) {
        document.getElementById('adoptModal').style.display = 'block';
        document.getElementById('modalPetName').textContent = petName;
        document.getElementById('modalPetId').value = petId;
        // Pre-fill name and email if logged in (already handled in HTML value attribute)
        // Make fields readonly if logged in (already handled in HTML readonly attribute)
    }

    function closeAdoptModal() {
        document.getElementById('adoptModal').style.display = 'none';
        document.getElementById('adoptForm').reset();
    }

    window.onclick = function(event) {
        var modal = document.getElementById('adoptModal');
        if (event.target == modal) {
            closeAdoptModal();
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const adoptForm = document.getElementById('adoptForm');
        if (adoptForm) {
            adoptForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formEmail = document.getElementById('applicantEmail').value.trim();
                const formName = document.getElementById('applicantName').value.trim();

                console.log('LOGGED_IN_EMAIL:', LOGGED_IN_EMAIL);
                console.log('LOGGED_IN_FULLNAME:', LOGGED_IN_FULLNAME);
                console.log('Form Email:', formEmail);
                console.log('Form Name:', formName);

                if (!LOGGED_IN_EMAIL || !LOGGED_IN_FULLNAME) {
                    alert('You must be logged in to submit an application.');
                    return;
                }

                if (formEmail.toLowerCase() !== LOGGED_IN_EMAIL.toLowerCase()) {
                    alert('Error: Please use the email associated with your account to submit an application.');
                    return;
                }

                // New name validation
                if (formName !== LOGGED_IN_FULLNAME) {
                    alert('Error: Please use the name associated with your account (' + LOGGED_IN_FULLNAME + ')');
                    return;
                }

                // Get form data
                const formData = new FormData(adoptForm);
                // Show loading state
                const submitBtn = adoptForm.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                submitBtn.innerHTML = 'Submitting...';
                submitBtn.disabled = true;
                // Send the request
                fetch('/TAILTOTALE/backend/api/submit_application.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Application submitted successfully!');
                        closeAdoptModal();
                    } else {
                        throw new Error(data.error || 'Failed to submit application');
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                })
                .finally(() => {
                    // Reset button state
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                });
            });
        }
    });
    </script>
</body>
</html>

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
        .pet-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
        }

        .message-icon {
            color: #4CAF50;
            text-decoration: none;
            transition: color 0.3s, transform 0.3s;
            margin-top: 0.8rem;
            font-size: 1.7rem;
            background: none;
            border: none;
            box-shadow: none;
        }

        .message-icon:hover {
            color: #388e3c;
            transform: scale(1.12);
        }

        .btn.outline-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border: 2px solid #4CAF50;
            color: #4CAF50;
            background: transparent;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn.outline-btn:hover {
            background: #4CAF50;
            color: white;
        }

        .view-more {
            margin-top: 2.5rem;
        }

        .adopted-btn {
            background: #e0e0e0;
            color: #888;
            border: 1px solid #ccc;
            cursor: not-allowed;
            font-weight: 600;
        }

        .pet-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            max-width: 100%;
            gap: 2rem;
        }

        /* Modal Background Overlay */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0, 0, 0, 0.5); /* Black with opacity */
            backdrop-filter: blur(2px); /* Optional: adds a blur effect */
        }

        /* Modal Content Box */
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto; /* 5% from the top and centered horizontally */
            padding: 2rem;
            border: none;
            border-radius: 12px;
            width: 90%; /* Could be more or less, depending on screen size */
            max-width: 500px; /* Maximum width */
            max-height: 85vh; /* Maximum height to prevent overflow */
            overflow-y: auto; /* Enable scroll if content is too long */
            position: relative;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease-out;
        }

        /* Modal Animation */
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Close Button */
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            right: 1rem;
            top: 1rem;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover,
        .close:focus {
            color: #000;
            text-decoration: none;
        }

        /* Modal Header */
        .modal-content h2 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            color: #333;
            font-size: 1.5rem;
            padding-right: 2rem; /* Space for close button */
        }

        /* Form Styles within Modal */
        .modal-content .form-group {
            margin-bottom: 1.5rem;
        }

        .modal-content .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }

        .modal-content .form-group input[type="text"],
        .modal-content .form-group input[type="email"],
        .modal-content .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        .modal-content .form-group input[type="text"]:focus,
        .modal-content .form-group input[type="email"]:focus,
        .modal-content .form-group textarea:focus {
            outline: none;
            border-color: #4CAF50;
        }

        .modal-content .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* Submit Button in Modal */
        .modal-content .btn.primary-btn {
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
            margin-top: 1rem;
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                margin: 10% auto;
                padding: 1.5rem;
                max-height: 80vh;
            }
            
            .modal-content h2 {
                font-size: 1.3rem;
                padding-right: 2rem;
            }
            
            .close {
                font-size: 24px;
                right: 0.75rem;
                top: 0.75rem;
            }
        }

        @media (max-width: 480px) {
            .modal-content {
                width: 98%;
                margin: 5% auto;
                padding: 1rem;
            }
            
            .modal-content h2 {
                font-size: 1.2rem;
            }
        }

        .pet-rehomer-name {
            color: #555;
            font-size: 0.97rem;
            margin-top: 0.3rem;
        }

        .pet-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            /* text-align: center; */ /* Removed or commented out if exists */
        }

        .pet-info {
            padding: 1rem;
            text-align: left;
        }

        .pet-info h3 {
            margin-top: 0;
        }

        .no-pets-modern {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 320px;
            width: 100%;
            background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%);
            border-radius: 18px;
            box-shadow: 0 4px 24px rgba(76,175,80,0.10);
            margin: 2.5rem 0;
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
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(30px);}
            to { opacity: 1; transform: translateY(0);}
        }
        @keyframes bounceIn {
            0% { transform: scale(0.7);}
            60% { transform: scale(1.1);}
            100% { transform: scale(1);}
        }
</style> 