<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rehome a Pet - Tail to Tale</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <?php
    if (isset($_SESSION['pet_posted_success']) && $_SESSION['pet_posted_success'] === true) {
        echo '<div class="success-message">PET LISTED SUCCESSFULLY</div>';
        unset($_SESSION['pet_posted_success']); // Clear the session variable
    }
    ?>
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
        <section class="hero-section" style="background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1500&q=80'); background-size: cover; background-position: center; color: white;">
            <div class="hero-content">
                <h1>Find a Loving Home for Your Pet</h1>
                <p>We understand that sometimes circumstances change. Let us help you find the perfect new home for your beloved pet.</p>
            </div>
        </section>

        <section class="rehome-form-section">
            <div class="form-container">
                <h2>List Your Pet for Rehoming</h2>
                <form id="rehome-form" class="rehome-form" action="/TAILTOTALE/backend/api/save_pet.php" method="post" enctype="multipart/form-data">
                    <div class="form-section">
                        <h3>Pet Information</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="pet-name">Pet's Name</label>
                                <input type="text" id="pet-name" name="pet-name" required>
                            </div>
                            <div class="form-group">
                                <label for="pet-type">Pet Type</label>
                                <select id="pet-type" name="pet-type" required>
                                    <option value="">Select Type</option>
                                    <option value="dog">Dog</option>
                                    <option value="cat">Cat</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="breed">Breed</label>
                                <select id="breed" name="breed" required>
                                    <option value="">Select Breed</option>
                                </select>
                                <input type="text" id="other-breed" name="other-breed" style="display: none;" placeholder="Enter breed">
                            </div>
                            <div class="form-group">
                                <label for="age">Age</label>
                                <input type="text" id="age" name="age" required>
                            </div>
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Health & Behavior</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="vaccination">Vaccination Status</label>
                                <select id="vaccination" name="vaccination" required>
                                    <option value="">Select Status</option>
                                    <option value="up-to-date">Up to Date</option>
                                    <option value="partial">Partial</option>
                                    <option value="none">None</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="spay-neuter">Spayed/Neutered</label>
                                <select id="spay-neuter" name="spay-neuter" required>
                                    <option value="">Select Status</option>
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                            <div class="form-group full-width">
                            <label for="reason">Reason for Rehoming</label>
                            <textarea id="reason" name="reason" rows="3" required></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Pet Photos</h3>
                        <div class="form-group">
                            <label for="pet-photos">Upload Photo</label>
                            <input type="file" id="pet-photos" name="pet-photos" accept="image/*" required>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn primary-btn">Submit Listing</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="rehoming-process">
            <h2>How Rehoming Works</h2>
            <div class="process-steps">
                <div class="step">
                    <i class="fas fa-file-alt"></i>
                    <h3>Create Listing</h3>
                    <p>Fill out our detailed form with your pet's information</p>
                </div>
                <div class="step">
                    <i class="fas fa-check-circle"></i>
                    <h3>Review</h3>
                    <p>Our team reviews your listing to ensure all information is complete</p>
                </div>
                <div class="step">
                    <i class="fas fa-users"></i>
                    <h3>Connect</h3>
                    <p>Potential adopters can contact you through our platform</p>
                </div>
                <div class="step">
                    <i class="fas fa-heart"></i>
                    <h3>New Home</h3>
                    <p>Find the perfect new family for your pet</p>
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
        // Function to close the success modal
        function closeSuccessModal() {
            document.getElementById('successModal').style.display = 'none';
        }

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

            function updateBreedOptions() {
                const petType = document.getElementById('pet-type').value;
                const breedSelect = document.getElementById('breed');
                const otherBreedInput = document.getElementById('other-breed');
                
                // Clear current options
                breedSelect.innerHTML = '<option value="">Select Breed</option>';
                
                // Hide or show the correct input based on pet type
                if (petType === 'dog') {
                    // Show dog breeds dropdown
                    dogBreeds.forEach(breed => {
                        const option = document.createElement('option');
                        option.value = breed.toLowerCase();
                        option.textContent = breed;
                        breedSelect.appendChild(option);
                    });
                    breedSelect.style.display = 'block';
                    breedSelect.required = true;
                    otherBreedInput.style.display = 'none';
                    otherBreedInput.required = false;
                } else if (petType === 'cat') {
                    // Show cat breeds dropdown
                    catBreeds.forEach(breed => {
                        const option = document.createElement('option');
                        option.value = breed.toLowerCase();
                        option.textContent = breed;
                        breedSelect.appendChild(option);
                    });
                    breedSelect.style.display = 'block';
                    breedSelect.required = true;
                    otherBreedInput.style.display = 'none';
                    otherBreedInput.required = false;
                } else if (petType === 'other') {
                    // Show text input for other pets
                    otherBreedInput.style.display = 'block';
                    otherBreedInput.required = true;
                    breedSelect.style.display = 'none';
                    breedSelect.required = false;
                } else {
                    // If no pet type is selected, show the default 'Select Breed' dropdown
                    breedSelect.style.display = 'block';
                    breedSelect.required = true;
                    otherBreedInput.style.display = 'none';
                    otherBreedInput.required = false;
                }
            }

            // Initialize breed options on page load
            updateBreedOptions();

            // Add event listener for pet type change
            document.getElementById('pet-type').addEventListener('change', updateBreedOptions);

            // Handle rehome form submission (placeholder)
            document.getElementById('rehome-form').addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent default form submission

                const form = e.target;
                const formData = new FormData(form);

                fetch(form.action, {
                    method: form.method,
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show the success modal
                        document.getElementById('successModal').style.display = 'block';
                        // Optionally, clear the form here if desired
                        form.reset();
                    } else {
                        // Handle errors (e.g., show an error message)
                        console.error('Error submitting form:', data.message);
                        alert('Error: ' + data.message); // Simple alert for now
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('An error occurred during submission.');
                });
            });

            // Get the modal and the close button
            const modal = document.getElementById('successModal');
            const closeButton = modal.querySelector('.close-button');

            // When the user clicks on <span> (x), close the modal
            closeButton.onclick = function() {
                closeSuccessModal();
            }

            // When the user clicks anywhere outside of the modal, close it
            window.onclick = function(event) {
                if (event.target == modal) {
                    closeSuccessModal();
                }
            }
        });
    </script>
</body>

</html>

<!-- Success Modal -->
<div id="successModal" class="modal">
  <div class="modal-content">
    <span class="close-button">&times;</span>
    <div class="success-icon">
      <i class="fas fa-check-circle"></i>
    </div>
    <h2>Success!</h2>
    <p>Your pet has been listed successfully.</p>
    <div class="modal-actions">
      <button class="btn primary-btn" onclick="closeSuccessModal()">Continue</button>
    </div>
  </div>
</div>