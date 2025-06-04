<?php
session_start();

// Include database connection or connection details here
// Example (replace with your actual details):
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tailtotale";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$pet = null;
$pet_id = null;

// Get pet ID from URL
if (isset($_GET['id'])) {
    $pet_id = intval($_GET['id']);
    
    // Fetch pet data from database
    $sql = "SELECT * FROM pets WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $pet_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $pet = $result->fetch_assoc();
    } else {
        $error_message = "Pet not found.";
    }
    
    $stmt->close();
} else {
    $error_message = "No pet ID provided.";
}

// After fetching $pet
$is_adopted = false;
if ($pet_id) {
    $adopted_sql = "SELECT 1 FROM applications WHERE pet_id = ? AND (status = 'confirmed' OR status = 'approved') LIMIT 1";
    $adopted_stmt = $conn->prepare($adopted_sql);
    $adopted_stmt->bind_param("i", $pet_id);
    $adopted_stmt->execute();
    $adopted_stmt->store_result();
    if ($adopted_stmt->num_rows > 0) {
        $is_adopted = true;
    }
    $adopted_stmt->close();
}

// Handle Save Changes (Update) - This is a placeholder. Implement your update logic here.
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_changes']) && $pet_id) {
    // Sanitize and validate input data
    $name = htmlspecialchars($_POST['pet-name']);
    $type = htmlspecialchars($_POST['pet-type']);
    $breed = htmlspecialchars($_POST['breed']);
    $age = htmlspecialchars($_POST['age']);
    $gender = htmlspecialchars($_POST['gender']);
    $vaccination = htmlspecialchars($_POST['vaccination']);
    $spay_neuter = htmlspecialchars($_POST['spay-neuter']);
    $reason = htmlspecialchars($_POST['reason']);
    
    // Handle file upload for new primary image
    $new_image_uploaded = false;
    $image_url = $pet['primary_image']; // Keep existing image if no new one is uploaded

    if (isset($_FILES['pet-photo']) && $_FILES['pet-photo']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../../uploads/pets/'; // Adjust this path to your upload directory
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileTmpPath = $_FILES['pet-photo']['tmp_name'];
        $fileName = uniqid() . '_' . basename($_FILES['pet-photo']['name']); // Generate unique filename
        $destPath = $uploadDir . $fileName;

        // Move the uploaded file
        if (move_uploaded_file($fileTmpPath, $destPath)) {
            // Update the image URL to the new file path relative to the web root
            $image_url = '/TAILTOTALE/uploads/pets/' . $fileName; // Adjust this path to your web-accessible image directory
            $new_image_uploaded = true;
            
            // Optional: Delete the old primary image file from the server
            // Add logic here to delete $pet['primary_image'] file if it exists and is not the default/placeholder image
        } else {
            $error_message = "Error uploading new image.";
        }
    }

    // Update pet information in the database
    // IMPORTANT: Implement your SQL UPDATE query here using prepared statements
    // Example:
    $update_sql = "UPDATE pets SET name = ?, type = ?, breed = ?, age = ?, gender = ?, reason = ?, primary_image = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sssssssi", $name, $type, $breed, $age, $gender, $reason, $image_url, $pet_id);

    if ($update_stmt->execute()) {
        $_SESSION['success_message'] = "Pet information updated successfully!";
        header("Location: index-rehomer.php"); // Redirect back to the rehoming list
        exit();
    } else {
        $error_message = "Error updating pet information: " . $conn->error;
    }
    $update_stmt->close();
    
    // Placeholder for success/error message if update logic is not implemented yet
     $_SESSION['info_message'] = "Save Changes logic is a placeholder. Database not updated.";
     header("Location: index-rehomer.php"); // Redirect even if not saved
     exit();
}

// Handle Delete Pet - This is a placeholder. Implement your delete logic here.
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_pet']) && $pet_id) {
    // Add a confirmation prompt using JavaScript in the frontend before submitting this form

    // Delete pet from database
    // IMPORTANT: Implement your SQL DELETE query here using prepared statements
    // Example:
    
    $delete_sql = "DELETE FROM pets WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $pet_id);

    if ($delete_stmt->execute()) {
         // Optional: Delete the associated image files from the server
         // Add logic here to delete files like $pet['primary_image']

        $_SESSION['success_message'] = "Pet deleted successfully!";
        header("Location: mylistings.php"); // Redirect back to the rehoming list
        exit();
    } else {
        $error_message = "Error deleting pet: " . $conn->error;
    }
    $delete_stmt->close();
    
    
    // Placeholder for success/error message if delete logic is not implemented yet
     $_SESSION['info_message'] = "Delete Pet logic is a placeholder. Pet not deleted.";
     header("Location: index-rehomer.php"); // Redirect even if not deleted
     exit();
}

$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pet ? 'Edit ' . $pet['name'] : 'Pet Not Found'; ?> - Tail to Tale</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                    <li><a href="messages.php" class="nav-link"><i class="fas fa-envelope"></i> Messages</a></li>
                    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'rehomer'): ?>
                        <li><a href="mylistings.php" class="nav-link"><i class="fas fa-list"></i> My Listings</a></li>
                    <?php else: ?>
                        <li><a href="adoption-history.php" class="nav-link"><i class="fas fa-history"></i> Adoption History</a></li>
                    <?php endif; ?>
                    <li><a href="profile.php" class="nav-link"><i class="fas fa-user"></i> My Profile</a></li>
                <?php else: ?>
                    <li><a href="/TAILTOTALE/backend/api/signin.php" class="nav-link login-btn"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                    <li><a href="/TAILTOTALE/backend/api/register.php" class="nav-link register-btn"><i class="fas fa-user-plus"></i> Register</a>
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
            <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'rehomer'): ?>
                <a href="mylistings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'mylistings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> My Listings
                </a>
            <?php endif; ?>
            <a href="about.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'about.php' ? 'active' : ''; ?>">
                <i class="fas fa-info-circle"></i> About Us
            </a>
        </nav>
    </aside>
    <main>
        <section class="rehome-form-section">
            <div class="form-container">
                <h2><?php echo $pet ? 'Edit Pet: ' . htmlspecialchars($pet['name']) : 'Pet Not Found'; ?></h2>
                
                <?php if (isset($error_message)): ?>
                    <div class="message error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <?php if ($pet): ?>
                    <?php if ($is_adopted): ?>
                        <div class="message error">This pet has been adopted and can no longer be edited.</div>
                    <?php else: ?>
                        <form id="edit-pet-form" class="rehome-form" action="edit-pet.php?id=<?php echo $pet_id; ?>" method="post" enctype="multipart/form-data">
                            
                                <h3>Pet Information</h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="pet-name">Pet's Name</label>
                                        <input type="text" id="pet-name" name="pet-name" value="<?php echo htmlspecialchars($pet['name']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="pet-type">Pet Type</label>
                                        <select id="pet-type" name="pet-type" required>
                                            <option value="">Select Type</option>
                                            <option value="dog" <?php echo ($pet['type'] ?? '') === 'dog' ? 'selected' : ''; ?>>Dog</option>
                                            <option value="cat" <?php echo ($pet['type'] ?? '') === 'cat' ? 'selected' : ''; ?>>Cat</option>
                                            <option value="other" <?php echo ($pet['type'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="breed">Breed</label>
                                         <!-- This breed select/input needs dynamic options based on pet type, similar to the rehome form -->
                                        <select id="breed" name="breed" required>
                                            <option value="">Select Breed</option>
                                        </select>
                                         <input type="text" id="other-breed" name="other-breed" style="display: none;" placeholder="Enter breed">
                                    </div>
                                    <div class="form-group">
                                        <label for="age">Age</label>
                                        <input type="text" id="age" name="age" value="<?php echo htmlspecialchars($pet['age']); ?>" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="gender">Gender</label>
                                        <select id="gender" name="gender" required>
                                            <option value="">Select Gender</option>
                                            <option value="male" <?php echo ($pet['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="female" <?php echo ($pet['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                    </div>
                                </div>
                            

                            
                                <h3>Health & Behavior</h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="vaccination">Vaccination Status</label>
                                        <select id="vaccination" name="vaccination" required>
                                            <option value="">Select Status</option>
                                            <option value="up-to-date" <?php echo (($pet['vaccination'] ?? '') === 'up-to-date') ? 'selected' : ''; ?>>Up to Date</option>
                                            <option value="partial" <?php echo (($pet['vaccination'] ?? '') === 'partial') ? 'selected' : ''; ?>>Partial</option>
                                            <option value="none" <?php echo (($pet['vaccination'] ?? '') === 'none') ? 'selected' : ''; ?>>None</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="spay-neuter">Spayed/Neutered</label>
                                        <select id="spay-neuter" name="spay-neuter" required>
                                            <option value="">Select Status</option>
                                            <option value="yes" <?php echo (($pet['spay_neuter'] ?? '') === 'yes') ? 'selected' : ''; ?>>Yes</option>
                                            <option value="no" <?php echo (($pet['spay_neuter'] ?? '') === 'no') ? 'selected' : ''; ?>>No</option>
                                        </select>
                                    </div>
                                    <div class="form-group full-width">
                                    <label for="reason">Reason for Rehoming</label>
                                    <textarea id="reason" name="reason" rows="3" required><?php echo htmlspecialchars($pet['reason']); ?></textarea>
                                    </div>
                                </div>
                            

                            
                                <h3>Pet Photos</h3>
                                <div class="form-group">
                                    <label>Current Primary Photo:</label>
                                    <?php if (!empty($pet['primary_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($pet['primary_image']); ?>" alt="Current Pet Photo" style="max-width: 200px; margin-top: 10px; display: block;">
                                    <?php else: ?>
                                        <p>No current photo available.</p>
                                    <?php endif; ?>
                                </div>
                                 <div class="form-group">
                                    <label for="pet-photo">Upload New Primary Photo (Optional):</label>
                                    <input type="file" id="pet-photo" name="pet-photo" accept="image/*">
                                </div>
                            

                            <div class="form-actions" style="display: flex; justify-content: center; gap: 20px;">
                                <button type="submit" name="save_changes" class="btn primary-btn">Save Changes</button>
                                <!-- Delete button - Use a separate form or JavaScript for confirmation -->
                                <button type="button" class="btn secondary-btn" onclick="confirmDelete()">Delete Pet</button>
                            </div>
                        </form>

                        <!-- Separate form for deletion, triggered by JavaScript -->
                        <form id="delete-pet-form" action="edit-pet.php?id=<?php echo $pet_id; ?>" method="post" style="display: none;">
                            <input type="hidden" name="delete_pet" value="1">
                        </form>

                        <script>
                            function confirmDelete() {
                                if (confirm("Are you sure you want to delete this pet listing? This action cannot be undone.")) {
                                    document.getElementById('delete-pet-form').submit();
                                }
                            }
                            
                            // Optional: Add dynamic breed dropdown based on pet type, similar to rehome.php
                            // You would need to include or replicate the JavaScript logic from rehome.php here
                        </script>

                    <?php endif; ?>

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
            <div class="footer-section">
                <h3>Newsletter</h3>
                <p>Subscribe to our newsletter for pet care tips and success stories.</p>
                <form class="newsletter-form">
                    <input type="email" placeholder="Enter your email">
                    <button type="submit" class="btn primary-btn">Subscribe</button>
                </form>
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
    <style>
    @media (max-width: 600px) {
      .form-actions {
        flex-direction: column !important;
        gap: 10px !important;
        align-items: stretch !important;
      }
      .form-actions .btn {
        width: 100% !important;
        min-width: 0 !important;
        box-sizing: border-box;
      }
    }
    </style>
    <script src="../assets/js/script.js"></script>
    <script>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Dog breeds list
            const dogBreeds = [
                "Labrador Retriever", "German Shepherd", "Golden Retriever", "French Bulldog", 
                "Bulldog", "Poodle", "Beagle", "Rottweiler", "Dachshund", "Yorkshire Terrier",
                "Boxer", "Chihuahua", "Great Dane", "Doberman", "Shih Tzu", "Siberian Husky",
                "Pomeranian", "Cavalier King Charles Spaniel", "Shetland Sheepdog", "Bernese Mountain Dog",
                "Border Collie", "Cocker Spaniel", "Australian Shepherd", "Maltese", "Newfoundland",
                "Saint Bernard", "Bichon Frise", "Boston Terrier", "Pug", "Chow Chow"
            ];

            // Cat breeds list
            const catBreeds = [
                "Persian", "Maine Coon", "Siamese", "Ragdoll", "Bengal", "Abyssinian",
                "Sphynx", "British Shorthair", "American Shorthair", "Russian Blue",
                "Exotic Shorthair", "Norwegian Forest Cat", "Scottish Fold", "Birman",
                "Himalayan", "Devon Rex", "Cornish Rex", "Tonkinese", "Burmese", "Oriental"
            ];

            const petTypeSelect = document.getElementById('pet-type');
            const breedSelect = document.getElementById('breed');
            const otherBreedInput = document.getElementById('other-breed');
            const existingBreed = "<?php echo htmlspecialchars($pet['breed'] ?? ''); ?>"; // Get existing breed
            const existingType = "<?php echo htmlspecialchars($pet['type'] ?? ''); ?>"; // Get existing type

            function updateBreedOptions(selectedType) {
                // Clear current options
                breedSelect.innerHTML = '<option value="">Select Breed</option>';
                otherBreedInput.value = ''; // Clear other breed input
                
                let breeds = [];
                if (selectedType === 'dog') {
                    breeds = dogBreeds;
                    breedSelect.style.display = 'block';
                    breedSelect.required = true;
                    otherBreedInput.style.display = 'none';
                    otherBreedInput.required = false;
                } else if (selectedType === 'cat') {
                    breeds = catBreeds;
                    breedSelect.style.display = 'block';
                    breedSelect.required = true;
                    otherBreedInput.style.display = 'none';
                    otherBreedInput.required = false;
                } else if (selectedType === 'other') {
                     // Show text input for other pets
                    otherBreedInput.style.display = 'block';
                    otherBreedInput.required = true;
                    breedSelect.style.display = 'none';
                    breedSelect.required = false;
                    // If the existing type was 'other', pre-fill the other breed input
                    if (existingType === 'other') {
                         otherBreedInput.value = existingBreed;
                    }
                } else {
                    // If no pet type is selected, show the default 'Select Breed' dropdown
                    breedSelect.style.display = 'block';
                    breedSelect.required = true;
                    otherBreedInput.style.display = 'none';
                    otherBreedInput.required = false;
                }
                
                // Populate dropdown with breeds and pre-select if it matches existing breed
                if (selectedType === 'dog' || selectedType === 'cat') {
                    breeds.forEach(breed => {
                        const option = document.createElement('option');
                        option.value = breed.toLowerCase();
                        option.textContent = breed;
                        breedSelect.appendChild(option);
                    });
                    
                    // Pre-select the existing breed if it exists in the populated dropdown
                    if (existingBreed) {
                         const optionToSelect = breedSelect.querySelector(`option[value="${existingBreed.toLowerCase()}"]`);
                         if (optionToSelect) {
                             optionToSelect.selected = true;
                         } else if (existingType === selectedType) {
                              // If the existing breed is not in the list for the current type,
                              // and the saved type matches the current selected type, it means it was likely
                              // entered as 'Other' originally for this type, so switch to 'Other'
                              petTypeSelect.value = 'other';
                              updateBreedOptions('other');
                              otherBreedInput.value = existingBreed; // Ensure value is set
                         }
                    }
                }
            }

            // Initialize breed options on page load and pre-select existing breed
            // Use the existing type for the initial population
            updateBreedOptions(existingType);

            // Add event listener for pet type change
            petTypeSelect.addEventListener('change', function() {
                // When the pet type changes, reset breed value and update options
                // Don't use existingBreed here, only when initializing
                updateBreedOptions(this.value);
            });
        });
    </script>
</body>
</html> 