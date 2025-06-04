<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Tail to Tale</title>
    <link rel="stylesheet" href="../../frontend/assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            margin: 0;
        }

        main {
            flex: 1;
        }

        .signin-container {
            max-width: 400px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .signin-container h2 {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .signin-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 600;
            color: #333;
        }

        .form-group input {
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            width: 100%;
            box-sizing: border-box;
        }

        .signin-btn {
            background-color: #4CAF50;
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background-color 0.3s ease;
            width: 100%;
        }

        .signin-btn:hover {
            background-color: #45a049;
        }

        .signin-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .signup-link {
            text-align: center;
            margin-top: 1rem;
        }

        .signup-link a {
            color: #4CAF50;
            text-decoration: none;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            text-align: center;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        footer {
            background-color: #333;
            color: white;
            padding: 2rem 0;
            margin-top: 2rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .footer-section {
            margin-bottom: 1.5rem;
        }

        .footer-section h3 {
            color: #4CAF50;
            margin-bottom: 0.5rem;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 1rem;
            border-top: 1px solid #444;
        }

        .footer-bottom p {
            margin: 0;
            color: #888;
        }

        @media (max-width: 900px) {
            .hide-on-mobile { display: none !important; }
        }
    </style>
</head>

<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="../../frontend/pages/index.php" style="text-decoration: none; color: inherit;">
                    <h1>Tail to Tale</h1>
                </a>
            </div>
            <ul class="nav-links">
                <li><a href="../../frontend/pages/about.php" class="nav-link"><i class="fas fa-info-circle"></i> About Us</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="signin-container">
            <h2>Login</h2>
            <form id="loginForm" class="signin-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div id="login-message"></div>
                <button type="submit" class="signin-btn">Login</button>
            </form>
            <div class="signup-link">
                <p>Don't have an account? <a href="signup.php">Sign up</a></p>
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
                    <li><a href="../../frontend/pages/about.php">About Us</a></li>
                    <li><a href="../../frontend/pages/pet-care-tips.php">Pet Care Tips</a></li>
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
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const messageDiv = document.getElementById('login-message');
            const submitBtn = event.target.querySelector('button[type="submit"]');

            if (!email || !password) {
                messageDiv.innerHTML = '<div class="message error">Please fill in all fields.</div>';
                return;
            }

            // Disable submit button and show loading state
            submitBtn.disabled = true;
            submitBtn.textContent = 'Logging in...';

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'login.php', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.textContent = 'Login';

                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        if (response.user_type === 'adopter') {
                            window.location.href = '../../frontend/pages/index-adopter.php';
                        } else if (response.user_type === 'rehomer') {
                            window.location.href = '../../frontend/pages/index-rehomer.php';
                        } else {
                            // Handle other user types or default success
                             window.location.href = '../../frontend/pages/index.php';
                        }
                    } else {
                         // Display the error message from the backend
                        messageDiv.innerHTML = '<div class="message error">' + (response.error || 'Login failed.') + '</div>';
                    }
                } catch (e) {
                    // Handle cases where the response is not valid JSON (e.g., PHP errors)
                    console.error('Error parsing JSON response:', e);
                    console.error('Response text:', xhr.responseText);
                    messageDiv.innerHTML = '<div class="message error">An unexpected error occurred. Please try again.</div>';
                }
            };

             xhr.onerror = function() {
                // Handle network errors
                submitBtn.disabled = false;
                submitBtn.textContent = 'Login';
                messageDiv.innerHTML = '<div class="message error">Network error. Please try again.</div>';
            };

            xhr.send('email=' + encodeURIComponent(email) + '&password=' + encodeURIComponent(password));
        });
    </script>
</body>

</html>