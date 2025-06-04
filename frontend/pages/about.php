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
    <title>About Us - Tail to Tale</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="index.php" style="text-decoration: none; color: inherit;">
                    <h1>Tail to Tale</h1>
                </a>
            </div>
            <ul class="nav-links">
                <li><a href="about.php" class="nav-link"><i class="fas fa-info-circle"></i> About Us</a></li>
                <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                    <li><a href="messages.php" class="nav-link"><i class="fas fa-envelope"></i> Messages<?php if ($total_unread_messages > 0): ?><span class="notification-badge" style="background:#e74c3c;color:#fff;font-size:0.8rem;font-weight:bold;padding:2px 7px;border-radius:10px;margin-left:5px;vertical-align:top;display:inline-block;"> <?php echo $total_unread_messages; ?></span><?php endif; ?></a></li>
                    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'rehomer'): ?>
                        <li><a href="mylistings.php" class="nav-link"><i class="fas fa-list"></i> My Listings<?php if ($total_new_applications > 0): ?><span class="notification-badge" style="background:#e74c3c;color:#fff;font-size:0.8rem;font-weight:bold;padding:2px 7px;border-radius:10px;margin-left:5px;vertical-align:top;display:inline-block;"> <?php echo $total_new_applications; ?></span><?php endif; ?></a></li>
                    <?php else: ?>
                        <li><a href="adoption-history.php" class="nav-link"><i class="fas fa-history"></i> Adoption History<?php if ($total_adopter_notifications > 0): ?><span class="notification-badge" style="background:#e74c3c;color:#fff;font-size:0.8rem;font-weight:bold;padding:2px 7px;border-radius:10px;margin-left:5px;vertical-align:top;display:inline-block;"> <?php echo $total_adopter_notifications; ?></span><?php endif; ?></a></li>
                    <?php endif; ?>
                    <li><a href="profile.php" class="nav-link"><i class="fas fa-user"></i> My Profile</a></li>
                <?php else: ?>
                    <li><a href="../../backend/api/signin.php" class="nav-link login-btn"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="../../backend/api/signup.php" class="nav-link register-btn"><i class="fas fa-user-plus"></i> Register</a></li>
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
            <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
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
            <?php endif; ?>
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'adopter'): ?>
                    <a href="adoption-history.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'adoption-history.php' ? 'active' : ''; ?>">
                        <i class="fas fa-history"></i> Adoption History<?php if ($total_adopter_notifications > 0): ?><span class="notification-badge" style="background:#e74c3c;color:#fff;font-size:0.8rem;font-weight:bold;padding:2px 7px;border-radius:10px;margin-left:5px;vertical-align:top;display:inline-block;"> <?php echo $total_adopter_notifications; ?></span><?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="../../backend/api/signin.php" class="nav-link login-btn"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="../../backend/api/signup.php" class="nav-link register-btn"><i class="fas fa-user-plus"></i> Register</a>
            <?php endif; ?>
            <a href="about.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>">
                <i class="fas fa-info-circle"></i> About Us
            </a>
        </nav>
    </aside>
    <main>
        <section class="about-hero">
            <div class="about-hero-content">
                <h1>Our Story</h1>
                <p>Making pet adoption and rehoming easier, one tail at a time.</p>
            </div>
        </section>

        <section class="about-content">
            <div class="about-grid">
                <div class="about-text">
                    <h2>About Tail to Tale</h2>
                    <p>
                        Tail to Tale is dedicated to connecting loving families with pets in need of a home.
                        Our mission is to make pet adoption and rehoming easy, safe, and compassionate for everyone
                        involved.
                        Whether you're looking to adopt a new friend or find a new home for your beloved pet, we're here
                        to
                        help.
                    </p>
                    <p>
                        We work closely with shelters, rescue groups, and individual pet owners to ensure every animal
                        has
                        the best chance at a happy life.
                        Our platform is designed to be user-friendly and secure, making it simple to browse available
                        pets,
                        submit adoption applications, or list a pet for rehoming.
                    </p>
                </div>

                <div class="about-mission">
                    <div class="mission-card">
                        <i class="fas fa-heart"></i>
                        <h3>Our Mission</h3>
                        <p>
                            At Tail to Tale, we believe every pet deserves a loving home and every family deserves the
                            joy a pet
                            brings. Join our community and help us make a difference, one tail at a time!
                        </p>
                    </div>
                </div>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-check-circle"></i>
                    <h3>Trusted Platform</h3>
                    <p>Trusted by hundreds of families and pet owners</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Secure & Easy</h3>
                    <p>Secure and easy-to-use platform for all users</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-users"></i>
                    <h3>Community Support</h3>
                    <p>Supportive community and resources for pet care</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-paw"></i>
                    <h3>Animal Welfare</h3>
                    <p>Dedicated to animal welfare and responsible rehoming</p>
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
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="pet-care-tips.php">Pet Care Tips</a></li>
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
    </script>
</body>

</html>