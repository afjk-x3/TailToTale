<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Tail to Tale</title>
    <link rel="stylesheet" href="../../frontend/assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @media (max-width: 900px) {
            .hide-on-mobile { display: none !important; }
        }
    </style>
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
            </ul>
        </nav>
    </header>
    <main>
        <div class="signup-container">
            <h2>Create an Account</h2>
            <div id="message"></div>
            <form class="signup-form" id="registrationForm">
                <div class="form-group">
                    <label for="signup-name">Full Name</label>
                    <input type="text" id="signup-name" name="fullname" required>
                </div>
                <div class="form-group">
                    <label for="signup-address">Address</label>
                    <input type="text" id="signup-address" name="address" required>
                </div>
                <div class="form-group">
                    <label for="signup-contact">Contact Number</label>
                    <input type="tel" id="signup-contact" name="contact" required>
                </div>
                <div class="form-group">
                    <label for="signup-email">Email</label>
                    <input type="email" id="signup-email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="signup-password">Password</label>
                    <input type="password" id="signup-password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="signup-confirm-password">Confirm Password</label>
                    <input type="password" id="signup-confirm-password" name="confirm_password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="user-type">I am a:</label>
                    <select id="user-type" name="user_type" required>
                        <option value="adopter">Adopter</option>
                        <option value="rehomer">Rehomer</option>
                    </select>
                </div>
                <button type="submit" class="signup-btn" id="submitBtn">Sign Up</button>
            </form>
            <div class="login-link">
                <p>Already have an account? <a href="signin.php">Login</a></p>
            </div>
        </div>
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

    <script>
        document.getElementById('registrationForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const messageDiv = document.getElementById('message');
            const password = document.getElementById('signup-password').value;
            const confirmPassword = document.getElementById('signup-confirm-password').value;

            // Password validation
            if (password !== confirmPassword) {
                messageDiv.innerHTML = '<div class="message error">Passwords do not match.</div>';
                return;
            }

            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.textContent = 'Signing Up...';

            // Clear previous messages
            messageDiv.innerHTML = '';

            // Get form data
            const formData = new FormData(this);

            try {
                const response = await fetch('register.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.text();

                if (result.trim() === 'success') {
                    messageDiv.innerHTML =
                        '<div class="message success">Registration successful! Redirecting to login page...</div>';
                    this.reset(); // Clear form
                    setTimeout(() => {
                        window.location.href = 'signin.php';
                    }, 2000); // Redirect after 2 seconds
                    return;
                } else if (result.trim() === 'exists') {
                    messageDiv.innerHTML =
                        '<div class="message exists">Email already exists. Please use a different email.</div>';
                } else {
                    messageDiv.innerHTML =
                        '<div class="message error">Registration failed. Please try again.</div>';
                }

            } catch (error) {
                messageDiv.innerHTML = '<div class="message error">Network error. Please try again.</div>';
                console.error('Error:', error);
            }

            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.textContent = 'Sign Up';
        });
    </script>

    <div style="display: none;">
        <!-- PHP CODE STARTS HERE -->
        <?php
        // This would be in a separate file called register.php
        /*
        <?php
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        header('Content-Type: text/plain');
        $host = "localhost";
        $user = "root";
        $pass = "";
        $db = "tailtotale";
        $port = 3307;

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

        // Insert new user
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (fullname, address, contact, email, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $fullname, $address, $contact, $email, $hash);
        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "error";
        }
        $stmt->close();
        $conn->close();
        ?>
        */
        ?>
    </div>
</body>

</html>