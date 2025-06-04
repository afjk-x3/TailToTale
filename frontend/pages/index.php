<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tail to Tale - Pet Adoption & Rehoming</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <?php
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && isset($_SESSION['user_type'])) {
        if ($_SESSION['user_type'] === 'adopter') {
            header('Location: /TAILTOTALE/frontend/pages/index-adopter.php');
            exit();
        } elseif ($_SESSION['user_type'] === 'rehomer') {
            header('Location: /TAILTOTALE/frontend/pages/index-rehomer.php');
            exit();
        }
    }
    ?>
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
                    <li class="profile-dropdown">
                        <button class="profile-btn" id="profileBtn">
                            <i class="fas fa-user-circle"></i> Profile <i class="fas fa-caret-down"></i>
                        </button>
                        <div class="dropdown-content" id="dropdownContent">
                            <a href="/TAILTOTALE/frontend/pages/profile.php"><i class="fas fa-user"></i> My Profile</a>
                            <a href="/TAILTOTALE/frontend/pages/favorites.php"><i class="fas fa-heart"></i> Favorites</a>
                            <a href="/TAILTOTALE/frontend/pages/messages.php"><i class="fas fa-envelope"></i> Messages</a>
                            <a href="/TAILTOTALE/frontend/pages/adoption-history.php"><i class="fas fa-history"></i> Adoption History</a>
                            <button class="logout-btn" onclick="logout()"><i class="fas fa-sign-out-alt"></i>
                                Logout</button>
                        </div>
                    </li>
                <?php else: ?>
                    <li class="hide-on-mobile"><a href="/TAILTOTALE/backend/api/signin.php" class="nav-link login-btn"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li class="hide-on-mobile"><a href="/TAILTOTALE/backend/api/signup.php" class="nav-link register-btn"><i class="fas fa-user-plus"></i> Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main>
        <section id="hero" class="hero-section">
            <div class="hero-content">
                <h1>Find Your Perfect Companion</h1>
                <p>Give a loving home to a pet in need or find a new home for your beloved pet</p>
                <div class="cta-buttons">
                    <a href="/TAILTOTALE/backend/api/check_login.php?action=index" class="btn secondary-btn">Get Started</a>
                </div>
            </div>
        </section>

        <section id="available-pets" class="featured-section">
            <h2>Available Pets</h2>
            <div class="pet-grid">
                <?php
                // --- Database Connection (REPLACE WITH YOUR ACTUAL CREDENTIALS) ---
                $servername = "localhost"; // Replace with your database server name
                $username = "root"; // Replace with your database username
                $password = ""; // Replace with your database password
                $dbname = "tailtotale"; // Replace with your database name

                // Create connection
                $conn = new mysqli($servername, $username, $password, $dbname);

                // Check connection
                if ($conn->connect_error) {
                    // Log the error instead of exposing it directly in production
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
                    $sql = "SELECT id, name, type, breed, age, primary_image, reason, vaccination_status FROM pets";
                    if (!empty($adopted_pet_ids)) {
                        $placeholders = implode(',', array_fill(0, count($adopted_pet_ids), '?'));
                        $sql .= " WHERE id NOT IN ($placeholders)";
                    }
                    $sql .= " ORDER BY created_at DESC";

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
                                    echo '<div class="pet-card" data-pet-id="' . $pet_id . '">';
                                    echo '<div class="card-image-container">';
                                    echo '<img class="card-image-bg" src="' . $pet_image . '" alt="" aria-hidden="true" />';
                                    echo '<img class="card-image-main" src="' . $pet_image . '" alt="' . $pet_name . '" />';
                                    echo '</div>';
                                    echo '<div class="pet-info">';
                                    echo '<h3>' . $pet_name . '</h3>';
                                    echo '<p><strong>Type:</strong> ' . ucfirst($pet_type) . '</p>';
                                    echo '<p><strong>Breed:</strong> ' . ucwords($pet_breed) . '</p>';
                                    echo '<p><strong>Age:</strong> ' . $pet_age . '</p>';
                                    echo '<p><strong>Reason for Rehoming:</strong> ' . $pet_reason . '</p>';
                                    echo '<p><strong>Health/Vaccination Status:</strong> ' . $pet_health . '</p>';
                                    echo '<button class="btn primary-btn" onclick="handleAdoptMe(' . $pet_id . ')">Adopt Me</button>';
                               
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
                    $conn->close(); // Close DB connection
                }
                ?>
            </div>
            <div class="view-more">
                <?php if (isset($show_view_all) && $show_view_all): ?>
                <a href="/TAILTOTALE/backend/api/check_login.php?action=adopt.php" class="btn primary-btn">View All Pets <i class="fas fa-arrow-right"></i></a>
                <?php endif; ?>
            </div>
        </section>

        <section id="how-it-works" class="process-section">
            <h2>How It Works</h2>
            <div class="process-steps">
                <div class="step">
                    <i class="fas fa-search"></i>
                    <h3>Browse</h3>
                    <p>Search through our database of pets looking for homes</p>
                </div>
                <div class="step">
                    <i class="fas fa-heart"></i>
                    <h3>Connect</h3>
                    <p>Get in touch with pet owners or adoption centers</p>
                </div>
                <div class="step">
                    <i class="fas fa-home"></i>
                    <h3>Adopt</h3>
                    <p>Welcome your new furry friend into your family</p>
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

    <script src="../assets/js/script.js"></script>
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

        // Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const filterBtns = document.querySelectorAll('.filter-btn');

            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active class from all buttons
                    filterBtns.forEach(b => b.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');

                    // Here you would typically filter the pets based on the selected category
                    const filter = this.dataset.filter;
                    console.log('Filtering by:', filter);
                });
            });
        });

        function checkLoginRedirect(e) {
            <?php if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true): ?>
                e.preventDefault();
                window.location.href = '/TAILTOTALE/backend/api/signin.php';
                return false;
            <?php else: ?>
                return true;
            <?php endif; ?>
        }
    </script>

    <style>
        .pet-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            max-width: 100%;
            gap: 2rem;
        }
        .view-more {
            margin-top: 2.5rem;
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
    @media (max-width: 900px) {
        .hide-on-mobile { display: none !important; }
    }
    </style>
</body>

</html>