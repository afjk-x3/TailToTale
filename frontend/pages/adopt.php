<?php session_start(); ?>
<?php
// Count total unseen approved/rejected applications for the logged-in adopter
$total_adopter_notifications = 0;
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'adopter') {
    $conn_notify = new mysqli('localhost', 'root', '', 'tailtotale');
    if (!$conn_notify->connect_error) {
        $user_id = $_SESSION['user_id'];
        // Ensure the 'adopter_seen' column exists in your 'applications' table for this query to work.
        // If not, you will need to add it via a database migration or manual SQL command.
        $sql_notify = "SELECT COUNT(*) as total_new FROM applications WHERE adopter_id = ? AND (status = 'approved' OR status = 'rejected') AND adopter_seen = 0";
        $stmt_notify = $conn_notify->prepare($sql_notify);
        if ($stmt_notify) {
            $stmt_notify->bind_param("i", $user_id);
            $stmt_notify->execute();
            $result_notify = $stmt_notify->get_result();
            if ($result_notify && $result_notify->num_rows > 0) {
                $total_adopter_notifications = $result_notify->fetch_assoc()['total_new'];
            }
            $stmt_notify->close();
        } else {
             error_log("Error preparing notification query: " . $conn_notify->error, 0);
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
    <title>Adopt a Pet - Tail to Tale</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <?php
    if (isset($_SESSION['pet_posted_success']) && $_SESSION['pet_posted_success'] === true) {
        echo '<div class="success-message" style="background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; margin-bottom: 20px; text-align: center; font-weight: bold;">Pet Posted successfully!</div>';
        unset($_SESSION['pet_posted_success']); // Clear the session variable
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
                        <ul class="nav-links">
                    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                        <li><a href="messages.php" class="nav-link"><i class="fas fa-envelope"></i> Messages</a></li>
                        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'rehomer'): ?>
                            <li><a href="mylistings.php" class="nav-link"><i class="fas fa-list"></i> My Listings</a></li>
                        <?php else: ?>
                            <li><a href="adoption-history.php" class="nav-link"><i class="fas fa-history"></i> Adoption History</a></li>
                        <?php endif; ?>
                        <li><a href="profile.php" class="nav-link"><i class="fas fa-user"></i> My Profile</a></li>
                    <?php else: ?>
                        <li><a href="../../backend/api/signin.php" class="nav-link login-btn"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                        <li><a href="../../backend/api/signup.php" class="nav-link register-btn"><i class="fas fa-user-plus"></i> Register</a></li>
                    <?php endif; ?>
            </ul>
                <?php else: ?>
                    <li><a href="../../backend/api/signin.php" class="nav-link login-btn"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="../../backend/api/register.php" class="nav-link register-btn"><i class="fas fa-user-plus"></i> Register</a>
                    </li>
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
                </a>
                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'adopter'): ?>
                    <a href="adoption-history.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'adoption-history.php' ? 'active' : ''; ?>">
                        <i class="fas fa-history"></i> Adoption History
                    </a>
                <?php endif; ?>
                <a href="about.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>">
                    <i class="fas fa-info-circle"></i> About Us
                </a>
            </nav>
        </aside>
    <main>
        <section class="adoption-hero">
            <div class="hero-content">
                <h1>Find Your Perfect Companion</h1>
                <p>Browse through our selection of loving pets waiting for their forever homes</p>
            </div>
        </section>

        <section class="search-section">
            <div class="search-container">
                <h2>Search for Your Perfect Match</h2>
                <form id="search-form" class="search-form" method="get">
                    <div class="form-group">
                        <label for="pet-type">Pet Type</label>
                        <select id="pet-type" name="pet-type" required>
                            <option value="">Select Pet</option>
                            <option value="dog" <?php if(isset($_GET['pet-type']) && $_GET['pet-type']=='dog') echo 'selected'; ?>>Dog</option>
                            <option value="cat" <?php if(isset($_GET['pet-type']) && $_GET['pet-type']=='cat') echo 'selected'; ?>>Cat</option>
                            <option value="other" <?php if(isset($_GET['pet-type']) && $_GET['pet-type']=='other') echo 'selected'; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="breed">Breed</label>
                        <select id="breed" name="breed">
                            <option value="">Any Breed</option>
                        </select>
                        <input type="text" id="other-breed" name="other-breed" style="display: none;" placeholder="Enter breed" value="<?php echo isset($_GET['other-breed']) ? htmlspecialchars($_GET['other-breed']) : ''; ?>">
                    </div>
                    <button type="submit" class="btn primary-btn">Search</button>
                </form>
            </div>
        </section>

        <section class="pets-section">
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
                    // --- Fetching Pet Data (Ensure table name and columns match your database) ---
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

                    $sql = "SELECT p.id, p.name, p.type, p.breed, p.age, p.primary_image, p.reason, p.vaccination_status, u.fullname as rehomer_name, u.id as rehomer_id FROM pets p JOIN users u ON p.user_id = u.id";
                    $where_clauses = [];
                    $params = [];
                    $param_types = '';

                    // Add filter to exclude adopted pets
                    if (!empty($adopted_pet_ids)) {
                        $placeholders = implode(',', array_fill(0, count($adopted_pet_ids), '?'));
                        $where_clauses[] = "p.id NOT IN ($placeholders)";
                         // Add adopted pet IDs to params for binding
                         $params = array_merge($params, $adopted_pet_ids);
                         $param_types .= str_repeat('i', count($adopted_pet_ids));
                    }

                    // Check for search parameters and add WHERE clauses
                    if (isset($_GET['pet-type']) && !empty($_GET['pet-type'])) {
                        $where_clauses[] = "p.type = ?";
                        $params[] = $_GET['pet-type'];
                        $param_types .= 's';
                    }

                    // Only filter by breed if a specific breed is selected (not 'Any Breed')
                    if (isset($_GET['breed']) && !empty($_GET['breed'])) {
                        // If pet type is 'other', use the other-breed input
                        if (isset($_GET['pet-type']) && $_GET['pet-type'] === 'other' && isset($_GET['other-breed']) && !empty($_GET['other-breed'])) {
                             $where_clauses[] = "p.breed LIKE ?";
                             $params[] = '%' . $_GET['other-breed'] . '%'; // Use LIKE for partial matching on 'other' breed
                             $param_types .= 's';
                        } else if (isset($_GET['pet-type']) && $_GET['pet-type'] !== 'other') { // Apply breed filter only if a specific type (dog or cat) is selected
                             $where_clauses[] = "p.breed = ?";
                             $params[] = $_GET['breed'];
                             $param_types .= 's';
                        }
                    }

                    // Add WHERE clauses to the SQL query
                    if (count($where_clauses) > 0) {
                        $sql .= " WHERE " . implode(" AND ", $where_clauses);
                    }

                    $sql .= " ORDER BY p.created_at DESC";

                    $stmt = $conn->prepare($sql);

                    if ($stmt === false) {
                        // Log the error instead of exposing it directly
                        error_log("Database prepare failed: " . $conn->error, 0);
                        echo '<p>Error preparing database query.</p>';
                    } else {
                        // Bind parameters if there are any
                        if (count($params) > 0) {
                            $stmt->bind_param($param_types, ...$params);
                        }

                        if ($stmt->execute()) {
                            $result = $stmt->get_result();

                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    // Display each pet card using data from the database
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
                        } else {
                            // Log the error
                            error_log("Database execute failed: " . $stmt->error, 0);
                            echo '<p>Error executing database query.</p>';
                        }

                        $stmt->close();
                    }

                    $conn->close(); // Close DB connection
                }
                ?>
            </div>
        </section>

        <section class="adoption-process">
            <h2>Adoption Process</h2>
            <div class="process-steps">
                <div class="step">
                    <i class="fas fa-search"></i>
                    <h3>Find Your Match</h3>
                    <p>Browse through our available pets and find your perfect companion</p>
                </div>
                <div class="step">
                    <i class="fas fa-file-alt"></i>
                    <h3>Submit Application</h3>
                    <p>Fill out our adoption application form</p>
                </div>
                <div class="step">
                    <i class="fas fa-handshake"></i>
                    <h3>Meet & Greet</h3>
                    <p>Schedule a meeting with your potential new family member</p>
                </div>
                <div class="step">
                    <i class="fas fa-home"></i>
                    <h3>Welcome Home</h3>
                    <p>Complete the adoption process and take your new friend home</p>
                </div>
            </div>
        </section>
    </main>

    <!-- Adoption Application Modal -->
    <div id="adoptModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeAdoptModal()">&times;</span>
            <h2>Adoption Application for <span id="modalPetName"></span></h2>
            <form id="adoptForm">
                <input type="hidden" id="modalPetId" name="pet_id">
                <div class="form-group">
                    <label for="applicantName">Your Name</label>
                    <?php
                        $loggedInName = isset($_SESSION['fullname']) ? htmlspecialchars($_SESSION['fullname']) : '';
                        $isLoggedIn = !empty($loggedInName);
                        $loggedInEmail = isset($_SESSION['email']) ? $_SESSION['email'] : '';
                    ?>
                    <input type="text" id="applicantName" name="applicantName" value="<?php echo $loggedInName; ?>" <?php echo $isLoggedIn ? 'readonly' : ''; ?> required>
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
        // Dog and cat breeds
        const dogBreeds = [
            "Labrador Retriever", "German Shepherd", "Golden Retriever", "French Bulldog", 
            "Bulldog", "Poodle", "Beagle", "Rottweiler", "Dachshund", "Yorkshire Terrier",
            "Boxer", "Chihuahua", "Great Dane", "Doberman", "Shih Tzu", "Siberian Husky",
            "Pomeranian", "Cavalier King Charles Spaniel", "Shetland Sheepdog", "Bernese Mountain Dog",
            "Border Collie", "Cocker Spaniel", "Australian Shepherd", "Maltese", "Newfoundland",
            "Saint Bernard", "Bichon Frise", "Boston Terrier", "Pug", "Chow Chow"
        ];
        const catBreeds = [
            "Persian", "Maine Coon", "Siamese", "Ragdoll", "Bengal", "Abyssinian",
            "Sphynx", "British Shorthair", "American Shorthair", "Russian Blue",
            "Exotic Shorthair", "Norwegian Forest Cat", "Scottish Fold", "Birman",
            "Himalayan", "Devon Rex", "Cornish Rex", "Tonkinese", "Burmese", "Oriental"
        ];

        function updateBreedOptions() {
            const petType = document.getElementById('pet-type').value;
            const breedSelect = document.getElementById('breed');
            const otherBreedInput = document.getElementById('other-breed');
            const selectedBreed = '<?php echo isset($_GET['breed']) ? addslashes($_GET['breed']) : ''; ?>';
            
            // Clear current options
            breedSelect.innerHTML = '<option value="">Any Breed</option>';
            
            if (petType === 'dog') {
                dogBreeds.forEach(breed => {
                    const option = document.createElement('option');
                    option.value = breed.toLowerCase();
                    option.textContent = breed;
                    if (selectedBreed && selectedBreed.toLowerCase() === breed.toLowerCase()) option.selected = true;
                    breedSelect.appendChild(option);
                });
                breedSelect.style.display = 'block';
                otherBreedInput.style.display = 'none';
            } else if (petType === 'cat') {
                catBreeds.forEach(breed => {
                    const option = document.createElement('option');
                    option.value = breed.toLowerCase();
                    option.textContent = breed;
                    if (selectedBreed && selectedBreed.toLowerCase() === breed.toLowerCase()) option.selected = true;
                    breedSelect.appendChild(option);
                });
                breedSelect.style.display = 'block';
                otherBreedInput.style.display = 'none';
            } else if (petType === 'other') {
                breedSelect.style.display = 'none';
                otherBreedInput.style.display = 'block';
            } else {
                breedSelect.style.display = 'block';
                otherBreedInput.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateBreedOptions();
            document.getElementById('pet-type').addEventListener('change', updateBreedOptions);
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

        .adopted-btn {
            background: #e0e0e0;
            color: #888;
            border: 1px solid #ccc;
            cursor: not-allowed;
            font-weight: 600;
        }

        .pet-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            max-width: 100%;
            gap: 2rem;
        }
        @media (max-width: 1200px) {
            .pet-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        @media (max-width: 800px) {
            .pet-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 500px) {
            .pet-grid {
                grid-template-columns: 1fr;
            }
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
</body>

</html>