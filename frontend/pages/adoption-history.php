<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['user_type'] !== 'adopter') {
    header('Location: /TAILTOTALE/backend/api/signin.php');
    exit;
}
// Count total unseen approved/rejected applications for the logged-in adopter
$total_adopter_notifications = 0;
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'adopter') {
    $conn_notify = new mysqli('localhost', 'root', '', 'tailtotale');
    if (!$conn_notify->connect_error) {
        $user_id = $_SESSION['user_id'];
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
// Replace the rejection check logic at the top with this:
$recent_status = false;
$status_message = '';
$status_type = '';

$conn = new mysqli('localhost', 'root', '', 'tailtotale');
if ($conn->connect_error) {
    // handle error
} else {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT a.status, p.name AS pet_name, a.application_date
            FROM applications a
            JOIN pets p ON a.pet_id = p.id
            WHERE a.adopter_id = ?
            ORDER BY a.application_date DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($status, $pet_name, $application_date);
    if ($stmt->fetch()) {
        $recent_status = true;
        $status_type = $status;
        if ($status === 'rejected') {
            $status_message = "Your application for <strong>" . htmlspecialchars($pet_name) . "</strong> was rejected.";
        } elseif ($status === 'confirmed') {
            $status_message = "Your application for <strong>" . htmlspecialchars($pet_name) . "</strong> was approved!";

        } else {
            $status_message = "Your application for <strong>" . htmlspecialchars($pet_name) . "</strong> is pending review.";
        }
    }
    $stmt->close();
    // Fetch all adoption applications for this user
    $sql2 = "SELECT a.*, p.name AS pet_name, p.primary_image FROM applications a
            JOIN pets p ON a.pet_id = p.id
            WHERE a.adopter_id = ?
            ORDER BY a.application_date DESC";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("i", $user_id);
    $stmt2->execute();
    $result = $stmt2->get_result();
}

// Mark adopter notifications as seen when visiting the adoption history page
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'adopter') {
    $conn_update = new mysqli('localhost', 'root', '', 'tailtotale');
    if (!$conn_update->connect_error) {
        $user_id = $_SESSION['user_id'];
        // Ensure the 'adopter_seen' column exists in your 'applications' table for this query to work.
        // If not, you will need to add it via a database migration or manual SQL command.
        $sql_update_seen = "UPDATE applications SET adopter_seen = 1 WHERE adopter_id = ? AND (status = 'approved' OR status = 'rejected' OR status = 'confirmed')";
        $stmt_update_seen = $conn_update->prepare($sql_update_seen);
        if ($stmt_update_seen) {
            $stmt_update_seen->bind_param("i", $user_id);
            $stmt_update_seen->execute();
            $stmt_update_seen->close();
        } else {
            error_log("Error preparing update seen query: " . $conn_update->error, 0);
        }
        $conn_update->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Adoption History - Tail to Tale</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { 
            background: #f7f7f7; 
            margin: 0; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .dashboard-container {
            display: flex;
            flex: 1;
            position: relative;
            margin-top: 80px;
        }
        .sidebar {
            width: 250px;
            background: #fff;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.05);
            padding: 0.5rem 1rem 1.5rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 2rem;
            position: sticky;
            top: 80px;
            height: calc(100vh - 80px);
            z-index: 99;
        }
        .sidebar .menu {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }
        .sidebar .menu a,
        .sidebar .menu button {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            background: none;
            border: none;
            text-align: left;
            font-size: 1.1rem;
            padding: 1rem 1.2rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #222;
            text-decoration: none;
            font-weight: 500;
        }
        .sidebar .menu a i,
        .sidebar .menu button i {
            font-size: 1.25rem;
            width: 26px;
            color: #4CAF50;
        }
        
        .sidebar .menu a.active,
        .sidebar .menu a:hover,
        .sidebar .menu button:hover {
            background: #4CAF50;
            color: #fff;
        }
        .sidebar .menu a.active i,
        .sidebar .menu a:hover i,
        .sidebar .menu button:hover i {
            color: #fff;
        }
        .main-content {
            flex: 1;
            padding: 2.5rem;
            background: #f7f7f7;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .dashboard-header {
            width: 100%;
            max-width: 900px;
            margin-bottom: 1.5rem;
        }
        .welcome {
            font-size: 2rem;
            font-weight: bold;
            color: #2e7d32;
            margin-bottom: 1.5rem;
            letter-spacing: 0.5px;
        }
        .history-section {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            padding: 2rem;
            width: 100%;
            max-width: 900px;
        }
        .alert-rejection,
        .alert-confirmed,
        .alert-pending,
        .alert-approved {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            line-height: 1.5;
        }
        .alert-rejection {
            background: #ffebee;
            color: #c62828;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            font-weight: 500;
        }
        .alert-confirmed {
            background: #e8f5e9;
            color: #388e3c;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            font-weight: 500;
        }
        .alert-pending {
            background: #fff8e1;
            color: #f9a825;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            font-weight: 500;
        }
        .alert-approved {
            background: #e3fcec;
            color: #2e7d32;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            font-weight: 500;
        }
        .history-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            padding: 1.5rem 2rem;
        }
        .history-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .history-card img {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
        }
        .history-card h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.3rem;
            color: #2e7d32;
        }
        .history-card .status-badge, .history-card span {
            display: inline-block;
            padding: 3px 14px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            margin-right: 10px;
        }
        .history-card .status-confirmed, .history-card .status-badge[style*='color:#388e3c'] {
            background: #e8f5e9;
            color: #388e3c;
        }
        .history-card .status-rejected, .history-card .status-badge[style*='color:#c62828'] {
            background: #ffebee;
            color: #c62828;
        }
        .history-card .status-pending, .history-card .status-badge[style*='color:#f9a825'] {
            background: #fff8e1;
            color: #f9a825;
        }
        .history-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            justify-content: center;
            margin-top: 1.2rem;
        }
        .filter-btn {
            background: #f5f5f5;
            border: none;
            border-radius: 8px;
            padding: 0.5rem 1.2rem;
            font-size: 1rem;
            color: #388e3c;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s, color 0.2s;
            flex: 1 1 0;
            min-width: 90px;
            max-width: 150px;
            text-align: center;
        }
        .filter-btn.active, .filter-btn:hover {
            background: #43b05c;
            color: #fff;
        }
        @media (max-width: 600px) {
            .history-filters {
                gap: 0.5rem;
                margin-bottom: 1rem;
                margin-top: 0.7rem;
            }
            .filter-btn {
                font-size: 0.97rem;
                padding: 0.5rem 0.5rem;
                min-width: 0;
                max-width: 100%;
            }
        }
        @media (max-width: 768px) {
            .dashboard-container { flex-direction: column; }
            .sidebar { width: 100%; height: auto; position: static; padding: 1rem; }
            .sidebar .menu { flex-direction: row; overflow-x: auto; padding-bottom: 0.5rem; }
            .main-content { padding: 1.5rem; }
            .history-section { padding: 1rem; }
            .history-card { flex-direction: column; align-items: stretch; padding: 1.2rem; }
            .history-card img { margin-bottom: 1rem; }
        }
        /* New Applications Notification Badge in Sidebar */
        .sidebar .menu a .notification-badge {
            display: inline-block;
            background-color: #e74c3c; /* Red color */
            color: white;
            font-size: 0.7rem;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
            vertical-align: top; /* Align badge to the top */
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
        @media (max-width: 900px) {
            .hamburger {
                display: flex;
            }
            .sidebar {
                position: fixed;
                left: -270px;
                top: 0;
                height: 100vh;
                width: 250px;
                background: #fff;
                box-shadow: 2px 0 8px rgba(0,0,0,0.12);
                transition: left 0.3s;
                z-index: 150;
                flex-direction: column;
                padding-top: 100px;
                align-items: flex-start;
                justify-content: flex-start;
                padding-left: 1rem;
                padding-right: 1rem;
            }
            .sidebar.open {
                left: 0;
            }
            .sidebar .menu {
                flex-direction: column;
                gap: 1.2rem;
                align-items: flex-start;
                justify-content: flex-start;
                width: 100%;
                margin-top: .5rem;
            }
            .dashboard-container {
                flex-direction: column;
            }
            .main-content {
                padding: 4.5rem 0.5rem 2rem 0.5rem;
            }
            body.sidebar-open {
                overflow: hidden;
            }
            .sidebar-overlay {
                display: block;
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(0,0,0,0.2);
                z-index: 100;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="/TAILTOTALE/frontend/pages/index-adopter.php" style="text-decoration: none; color: inherit;">
                    <h1>Tail to Tale</h1>
                </a>
            </div>
            <button class="hamburger" id="hamburgerBtn" aria-label="Open navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </nav>
    </header>
    <div class="dashboard-container">
        <aside class="sidebar" id="sidebarNav">
            <nav class="menu">
                <a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i> My Profile
                </a>
                <a href="adoption-history.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'adoption-history.php' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i> Adoption History
                    <?php if ($total_adopter_notifications > 0): ?>
                        <span class="notification-badge"><?php echo $total_adopter_notifications; ?></span>
                    <?php endif; ?>
                </a>
                <a href="messages.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i> Messages
                    <span class="notification-badge" id="unread-messages-badge"></span>
                </a>
                <button onclick="window.location.href='/TAILTOTALE/backend/api/logout.php'"><i class="fas fa-sign-out-alt"></i> Log Out</button>
            </nav>
        </aside>
        <div class="main-content">
            <div class="dashboard-header">
                <div class="welcome"><i class="fas fa-history" style="margin-right:10px;"></i>My Adoption History</div>
            </div>
            <div class="history-section">
                <?php
                if ($recent_status) {
                    $alert_class = 'alert-rejection';
                    if ($status_type === 'confirmed') $alert_class = 'alert-confirmed';
                    elseif ($status_type === 'pending') $alert_class = 'alert-pending';
                    elseif ($status_type === 'approved') $alert_class = 'alert-approved';
                    echo '<div class="' . $alert_class . '">';
                    echo '<i class="fas fa-exclamation-circle" style="margin-right:8px;"></i> ' . $status_message;
                    echo '</div>';
                }
                ?>
                <div class="history-filters">
                    <button class="filter-btn active" data-status="all">All</button>
                    <button class="filter-btn" data-status="pending">Pending</button>
                    <button class="filter-btn" data-status="rejected">Rejected</button>
                    <button class="filter-btn" data-status="confirmed">Approved</button>
                </div>
                <?php if (isset($result) && $result->num_rows === 0): ?>
                    <p>You have not submitted any adoption applications yet.</p>
                <?php elseif (isset($result)): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="history-card" data-status="<?php echo htmlspecialchars($row['status']); ?>">
                            <?php if (!empty($row['primary_image'])): ?>
                                <img src="<?php echo htmlspecialchars($row['primary_image']); ?>" alt="Pet Image">
                            <?php endif; ?>
                            <div style="flex:1">
                                <h3><?php echo htmlspecialchars($row['pet_name']); ?></h3>
                                <div style="margin-bottom:0.7rem;">
                                    <?php if ($row['status'] == 'confirmed'): ?>
                                        <span class="status-badge status-confirmed">Approved</span>
                                    <?php elseif ($row['status'] == 'rejected'): ?>
                                        <span class="status-badge status-rejected">Rejected</span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">Pending</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($row['status'] == 'confirmed'): ?>
                                    <p style="color:#388e3c;margin:0 0 0.5rem 0;">Congratulations! Your adoption was approved.</p>
                                <?php elseif ($row['status'] == 'rejected'): ?>
                                    <p style="color:#c62828;margin:0 0 0.5rem 0;">Sorry, your application was rejected.</p>
                                <?php else: ?>
                                    <p style="color:#f9a825;margin:0 0 0.5rem 0;">Your application is pending review.</p>
                                <?php endif; ?>
                                <div style="color:#444;font-size:1.05rem;margin-bottom:0.3rem;"><strong>Message:</strong> <?php echo nl2br(htmlspecialchars($row['message'])); ?></div>
                                <div style="color:#888;font-size:0.97rem;"><strong>Date:</strong> <?php echo htmlspecialchars($row['application_date']); ?></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
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
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const status = this.getAttribute('data-status');
            document.querySelectorAll('.history-card').forEach(card => {
                if (status === 'all' || card.getAttribute('data-status') === status) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Function to fetch and display unread message count
    async function fetchUnreadMessageCount() {
        try {
            const response = await fetch('/TAILTOTALE/backend/api/get_unread_message_count.php');
            const data = await response.json();

            const unreadBadge = document.getElementById('unread-messages-badge');
            if (data.success && data.unread_count > 0) {
                unreadBadge.textContent = data.unread_count;
                unreadBadge.style.display = 'inline-block';
            } else {
                unreadBadge.style.display = 'none';
            }
        } catch (error) {
            console.error('Error fetching unread message count:', error);
            const unreadBadge = document.getElementById('unread-messages-badge');
            unreadBadge.style.display = 'none'; // Hide badge on error
        }
    }

    // Fetch initial unread count on page load
    document.addEventListener('DOMContentLoaded', () => {
        fetchUnreadMessageCount();
        
        // Poll for new messages and unread count periodically (e.g., every 30 seconds)
        setInterval(() => {
            fetchUnreadMessageCount();
        }, 30000); // Poll every 30 seconds
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
</body>
</html>
<?php if (isset($stmt2)) $stmt2->close(); if (isset($conn)) $conn->close(); ?> 