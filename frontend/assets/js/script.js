// Function to show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i>
            <p>${message}</p>
        </div>
        <button class="close-notification">&times;</button>
    `;
    
    document.body.appendChild(notification);
    
    // Add show class after a small delay for animation
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 5000);
    
    // Close button functionality
    notification.querySelector('.close-notification').addEventListener('click', () => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    });
}

// Function to close modal
function closeModal() {
    const modal = document.querySelector('.modal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = 'auto';
    }
}

// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
            behavior: 'smooth'
        });
    });
});

// Function to handle rehoming form submission
function handleRehomingSubmission(event) {
    event.preventDefault();

    const formData = new FormData(event.target);

    // Use fetch API to send data to a PHP script
    fetch('save_pet.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showNotification('Pet listing submitted successfully!', 'success');

            // Reset the form
            event.target.reset();

            // Redirect to adopt.php after a short delay to show the message
            setTimeout(() => {
                window.location.href = 'adopt.php';
            }, 2000); // Redirect after 2 seconds

        } else {
            // Show error message
            showNotification('Error submitting pet listing: ' + (data.message || 'Please try again.'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again later.', 'error');
    });
}

// Profile dropdown toggle
const profileBtn = document.getElementById('profileBtn');
const profileDropdown = document.querySelector('.profile-dropdown');
if (profileDropdown) {
    document.addEventListener('click', function(event) {
        if (profileDropdown.contains(event.target)) {
            profileDropdown.classList.toggle('open');
        } else {
            profileDropdown.classList.remove('open');
        }
    });
}

// Function to handle logout
function logout() {
    fetch('/TAILTOTALE/backend/api/logout.php')
        .then(() => {
            window.location.href = '/TAILTOTALE/frontend/pages/index.php';
        });
}

// Function to handle adopt me button click
function handleAdoptMe(petId) {
    // Check if user is logged in
    fetch('/TAILTOTALE/backend/api/check_login.php')
        .then(response => response.text())
        .then(status => {
            if (status === 'loggedin') {
                // User is logged in, proceed with adoption
                window.location.href = `/TAILTOTALE/frontend/pages/adopt.php?id=${petId}`;
            } else {
                // User is not logged in, redirect to login page
                window.location.href = '/TAILTOTALE/backend/api/signin.php';
            }
        });
} 