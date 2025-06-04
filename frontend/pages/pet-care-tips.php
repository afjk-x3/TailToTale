<?php
session_start();
// Notification badge logic for sidebar
$total_unread_messages = 0;
$total_new_applications = 0;
$total_adopter_notifications = 0;
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    $conn_notify = new mysqli('localhost', 'root', '', 'tailtotale');
    if (!$conn_notify->connect_error) {
        $user_id = $_SESSION['user_id'];
        if ($_SESSION['user_type'] === 'rehomer') {
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
        }
        // Unread messages (for both user types)
        $sql_unread = "SELECT COUNT(*) as unread_count FROM messages WHERE recipient_id = ? AND is_read = 0";
        $stmt_unread = $conn_notify->prepare($sql_unread);
        $stmt_unread->bind_param("i", $user_id);
        $stmt_unread->execute();
        $result_unread = $stmt_unread->get_result();
        if ($result_unread->num_rows > 0) {
            $total_unread_messages = $result_unread->fetch_assoc()['unread_count'];
        }
        $stmt_unread->close();
        if ($_SESSION['user_type'] === 'adopter') {
            // Unseen approved/rejected applications for adopter
            $sql_notify = "SELECT COUNT(*) as total_new FROM applications WHERE adopter_id = ? AND (status = 'rejected' OR status = 'confirmed') AND adopter_seen = 0";
            $stmt_notify = $conn_notify->prepare($sql_notify);
            $stmt_notify->bind_param("i", $user_id);
            $stmt_notify->execute();
            $result_notify = $stmt_notify->get_result();
            if ($result_notify && $result_notify->num_rows > 0) {
                $total_adopter_notifications = $result_notify->fetch_assoc()['total_new'];
            }
            $stmt_notify->close();
        }
        $conn_notify->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Care Tips - Tail to Tale</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="/TAILTOTALE/frontend/pages/index.php" style="text-decoration: none; color: inherit;">
                    <h1>Tail to Tale</h1>
                </a>
            </div>
            <ul class="nav-links">
                <li><a href="/TAILTOTALE/frontend/pages/about.php" class="nav-link"><i class="fas fa-info-circle"></i> About Us</a></li>
                <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                    <li><a href="messages.php" class="nav-link"><i class="fas fa-envelope"></i> Messages<?php if ($total_unread_messages > 0): ?><span class="notification-badge" style="background:#e74c3c;color:#fff;font-size:0.8rem;font-weight:bold;padding:2px 7px;border-radius:10px;margin-left:5px;vertical-align:top;display:inline-block;"> <?php echo $total_unread_messages; ?></span><?php endif; ?></a></li>
                    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'rehomer'): ?>
                        <li><a href="mylistings.php" class="nav-link"><i class="fas fa-list"></i> My Listings<?php if ($total_new_applications > 0): ?><span class="notification-badge" style="background:#e74c3c;color:#fff;font-size:0.8rem;font-weight:bold;padding:2px 7px;border-radius:10px;margin-left:5px;vertical-align:top;display:inline-block;"> <?php echo $total_new_applications; ?></span><?php endif; ?></a></li>
                    <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'adopter'): ?>
                        <li><a href="adoption-history.php" class="nav-link"><i class="fas fa-history"></i> Adoption History<?php if ($total_adopter_notifications > 0): ?><span class="notification-badge" style="background:#e74c3c;color:#fff;font-size:0.8rem;font-weight:bold;padding:2px 7px;border-radius:10px;margin-left:5px;vertical-align:top;display:inline-block;"> <?php echo $total_adopter_notifications; ?></span><?php endif; ?></a></li>
                    <?php endif; ?>
                    <li><a href="profile.php" class="nav-link"><i class="fas fa-user"></i> My Profile</a></li>
                <?php else: ?>
                    <li><a href="/TAILTOTALE/backend/api/signin.php" class="nav-link login-btn"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="/TAILTOTALE/backend/api/register.php" class="nav-link register-btn"><i class="fas fa-user-plus"></i> Register</a></li>
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
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'rehomer'): ?>
                <a href="mylistings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'mylistings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> My Listings<?php if ($total_new_applications > 0): ?><span class="notification-badge" style="background:#e74c3c;color:#fff;font-size:0.8rem;font-weight:bold;padding:2px 7px;border-radius:10px;margin-left:5px;vertical-align:top;display:inline-block;"> <?php echo $total_new_applications; ?></span><?php endif; ?>
                </a>
            <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'adopter'): ?>
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
        <section class="tips-hero">
            <div class="tips-hero-content">
                <h1>Pet Care Tips</h1>
                <p>Expert advice for keeping your pets happy and healthy</p>
            </div>
        </section>

        <section class="tips-categories">
            <div class="category-grid">
                <div class="category-card">
                    <i class="fas fa-bone"></i>
                    <h3>Nutrition</h3>
                    <ul class="tips-list">
                        <li>
                            <h4>Balanced Diet Essentials</h4>
                            <p>Learn about the key nutrients your pet needs for optimal health.</p>
                            
                        </li>
                        <li>
                            <h4>Feeding Schedule Tips</h4>
                            <p>Establish the right feeding routine for your pet's age and size.</p>
                           
                        </li>
                    </ul>
                </div>

                <div class="category-card">
                    <i class="fas fa-heart"></i>
                    <h3>Health & Wellness</h3>
                    <ul class="tips-list">
                        <li>
                            <h4>Preventive Care Guide</h4>
                            <p>Essential vaccinations and regular check-ups for your pet.</p>
                           
                        </li>
                        <li>
                            <h4>Exercise Requirements</h4>
                            <p>Keep your pet fit with age-appropriate exercise routines.</p>
                     
                        </li>
                    </ul>
                </div>

                <div class="category-card">
                    <i class="fas fa-home"></i>
                    <h3>Training & Behavior</h3>
                    <ul class="tips-list">
                        <li>
                            <h4>Basic Commands</h4>
                            <p>Start with these essential training commands for dogs.</p>
                          
                        </li>
                        <li>
                            <h4>Behavior Solutions</h4>
                            <p>Address common behavioral issues with proven techniques.</p>
                           
                        </li>
                    </ul>
                </div>

                <div class="category-card">
                    <i class="fas fa-shower"></i>
                    <h3>Grooming</h3>
                    <ul class="tips-list">
                        <li>
                            <h4>Grooming Basics</h4>
                            <p>Essential grooming tips for different types of pets.</p>
                        
                        </li>
                        <li>
                            <h4>Dental Care</h4>
                            <p>Maintain your pet's dental health with these tips.</p>
                          
                        </li>
                    </ul>
                </div>
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

    <script>
        function logout() {
            fetch('/TAILTOTALE/backend/api/logout.php')
                .then(() => {
                    window.location.href = '/TAILTOTALE/frontend/pages/index.php';
                });
        }

        // Profile dropdown toggle
        const profileBtn = document.getElementById('profileBtn');
        const profileDropdown = document.querySelector('.profile-dropdown');
        document.addEventListener('click', function(event) {
            if (profileDropdown && profileDropdown.contains(event.target)) {
                profileDropdown.classList.toggle('open');
            } else if (profileDropdown) {
                profileDropdown.classList.remove('open');
            }
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
    </style>
</body>

</html>