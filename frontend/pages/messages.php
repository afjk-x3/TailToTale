<?php session_start();
// Count notifications for adopters
$total_adopter_notifications = 0;
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'adopter') {
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

// Count notifications for rehomers
$total_new_applications = 0;
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'rehomer') {
    $conn_notify = new mysqli('localhost', 'root', '', 'tailtotale');
    if (!$conn_notify->connect_error) {
        $user_id = $_SESSION['user_id'];
        $sql_notify = "SELECT COUNT(*) as total_new FROM applications WHERE pet_id IN (SELECT id FROM pets WHERE user_id = ?) AND seen = 0";
        $stmt_notify = $conn_notify->prepare($sql_notify);
        $stmt_notify->bind_param("i", $user_id);
        $stmt_notify->execute();
        $result_notify = $stmt_notify->get_result();
        if ($result_notify->num_rows > 0) {
            $total_new_applications = $result_notify->fetch_assoc()['total_new'];
        }
        $stmt_notify->close();
        $conn_notify->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Tail to Tale</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Copy sidebar and main-content styles from profile.php */
        body {
            margin: 0;
            /* font-family: Arial, sans-serif; */
            background: #f7f7f7;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .dashboard-container {
            display: flex;
            flex: 1;
            position: relative;
            height: calc(100vh - 80px); /* Adjust for header height */
            min-height: 0; /* Allow flex children to shrink */
        }
        .sidebar {
            width: 250px;
            background: #fff;
            box-shadow: 2px 0 8px rgba(0, 0, 0, 0.05);
            padding: 0.6rem 1rem 1.5rem 1rem;
            display: flex;
            flex-direction: column;
            gap: 2rem;
            position: sticky;
            top: 100px; /* Adjusted from 80px */
            height: calc(100vh - 80px);
            z-index: 99;
        }
        .sidebar .menu {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
            margin-top: 1.5rem;
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
            background: #43b05c;
            color: #fff;
        }
        .sidebar .menu a.active i,
        .sidebar .menu a:hover i,
        .sidebar .menu button:hover i {
            color: #fff;
        }
        .main-content {
            flex: 1;
            padding: 1.5rem 2.5rem 2.5rem 2.5rem;
            background: #f7f7f7;
            min-height: 0; /* Allow shrinking */
            height: 100%;
        }
        .messages-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            display: flex;
            gap: 2rem;
            flex: 1 1 0;
            min-height: 0;
            max-height: 700px; /* Adjust as needed, a bit more than .chat-messages */
            height: 700px;     /* Fixed height for the chat container */
        }
        .conversations-list {
            width: 300px;
            border-right: 1px solid #eee;
            background: #f9f9f9;
            overflow-y: auto;
            border-radius: 12px 0 0 12px;
            display: flex;
            flex-direction: column;
            min-height: 0;
            height: 100%;
            max-height: 100%;
        }
        /* Updated Search Container - Remove background and make it seamless */
    .search-container {
        margin-top: 10px;
        padding: 20px 20px 15px 20px; /* Slightly adjusted padding */
        border-bottom: 1px solid #eee;
        background: transparent; /* Remove background color */
    }

    /* Enhanced Search Input to match website design */
    .search-input {
        width: 100%;
        padding: 12px 16px 12px 45px; /* Add left padding for icon */
        border: 2px solid #e0e0e0; /* Slightly thicker border */
        border-radius: 25px; /* More rounded to match modern design */
        outline: none;
        font-size: 0.95em;
        background: #fff;
        transition: all 0.3s ease; /* Smooth transition */
        font-family: inherit; /* Match website font */
        color: #333;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); /* Subtle shadow */
        position: relative;
    }

    /* Add search icon inside the input */
    .search-container {
        position: relative;
    }

    .search-container::before {
        content: '\f002'; /* Font Awesome search icon */
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        left: 35px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
        font-size: 0.9em;
        z-index: 1;
        pointer-events: none;
    }

    /* Focus state for search input */
    .search-input:focus {
        border-color: #4caf50; /* Match your green theme */
        background: #fff;
        box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1); /* Green glow effect */
        transform: translateY(-1px); /* Subtle lift effect */
    }

    /* Placeholder styling */
    .search-input::placeholder {
        color: #aaa;
        font-style: italic;
    }

    /* Search Results Container */
    .search-results {
        max-height: 200px;
        overflow-y: auto;
        border-bottom: 1px solid #eee;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); /* Add shadow for depth */
    }

    .search-results .conversation-item {
        background: #fff;
        transition: all 0.2s ease;
    }

    .search-results .conversation-item:hover {
        background: #f8f9fa; /* Lighter hover state */
        transform: translateX(2px); /* Subtle slide effect */
    }

    /* Section Header in Search Results */
    .search-results .section-header {
        background: #f5f5f5;
        color: #4caf50; /* Match your theme color */
        font-weight: 600;
        font-size: 0.85em;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Custom scrollbar for search results */
    .search-results::-webkit-scrollbar {
        width: 6px;
    }

    .search-results::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .search-results::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }

    .search-results::-webkit-scrollbar-thumb:hover {
        background: #4caf50;
    }

    /* No results message styling */
    .search-results p {
        padding: 15px 20px;
        color: #666;
        margin: 0;
        font-style: italic;
        text-align: center;
        background: #f9f9f9;
    }
        .section-header {
            padding: 10px 20px;
            margin: 0;
            border-bottom: 1px solid #eee;
            background: #f9f9f9;
            font-size: 0.9em;
            color: #666;
            font-weight: 600;
        }
        .conversation-item {
            padding: 18px 20px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.3s;
            display: flex;
            align-items: center;
        }
        .conversation-item:hover, .conversation-item.active {
            background-color: #e3f2fd;
        }
        .conversation-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            margin-right: 14px;
            object-fit: cover;
        }
        .conversation-info {
            flex: 1;
        }
        .conversation-name {
            font-weight: 600;
            color: #333;
        }
        .conversation-preview {
            font-size: 0.95em;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px;
            display: block;
        }
        .conversation-time {
            font-size: 0.85em;
            color: #aaa;
            margin-left: 8px;
            white-space: nowrap;
            float: right;
        }
        .chat-window {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 0;
            height: 100%;
        }
        .chat-header {
            padding: 18px 24px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            background: #f9f9f9;
            min-height: 60px;
            height: 70px;
            box-sizing: border-box;
        }
        .chat-header .conversation-avatar {
            width: 44px;
            height: 44px;
            margin-right: 14px;
        }
        .chat-header .conversation-name {
            font-size: 1.15em;
        }
        .chat-header .no-conversation {
            color: #888;
            font-style: italic;
        }
        .chat-messages {
            flex: 1 1 auto;
            max-height: none;
            min-height: 0;
            overflow-y: auto;
            padding: 30px 24px;
            background: #f5f6fa;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: flex-start;
        }
        .chat-messages.has-content {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: flex-start;
        }
        .no-conversation-selected {
            text-align: center;
            padding: 2rem;
            background: #e8f5e9;
            border-radius: 12px;
            color: #2e7d32;
            font-size: 1.1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            width: 100%;
        }
        .message {
            margin-bottom: 18px;
            max-width: 70%;
            width: fit-content;
            align-self: flex-start;
        }
        .message.sent {
            align-self: flex-end;
            text-align: right;
        }
        .message.received{
            align-self: flex-start;
            text-align: left;
        }
        .message-content {
            padding: 12px 18px;
            border-radius: 18px;
            background: #e3f2fd;
            display: inline-block;
            font-size: 1.05em;
            word-wrap: break-word;
        }
        .message.sent .message-content {
            background: #4caf50;
            color: white;
        }
        .message.received .message-content{
            background: #E3F2FD;
            color: #333;
        }
        .message-time {
            font-size: smaller;
            color: #888;
            margin-top: 5px;
        }
        .chat-input {
            padding: 18px 24px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
            background: #fff;
            /* min-height: 60px;
            height: 60px;
            box-sizing: border-box; */
        }
        .chat-input input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 20px;
            outline: none;
            font-size: 1em;
        }
        .chat-input input:disabled {
            background-color: #f5f5f5;
            cursor: not-allowed;
        }
        .chat-input button {
            padding: 12px 24px;
            border: none;
            border-radius: 20px;
            background: #4caf50;
            color: white;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s;
        }
        .chat-input button:hover:not(:disabled) {
            background: #388e3c;
        }
        .chat-input button:disabled {
            background: #ccc;
            cursor: not-allowed;
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

        .conversation-info {
            position: relative; /* To position the badge */
            flex: 1;
        }

        .conversation-unread-badge {
            position: absolute;
            top: 0;
            right: 0;
            background-color: #e74c3c; /* Red color */
            color: white;
            font-size: 0.7rem;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 10px;
            z-index: 1;
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
            .messages-card {
                flex-direction: column;
                align-items: flex-start;
                height: auto;
                min-height: 0;
                max-height: none;
                padding: 0.5rem;
            }
            .conversations-list {
                width: 100%;
                max-width: 100vw;
                border-radius: 12px 12px 0 0;
                border-right: none;
                min-height: 0;
                height: 288px;
                max-height: 288px;
                display: block;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
                flex: none;
            }
            .conversation-item {
                height: 72px;
                min-height: 72px;
                max-height: 72px;
                box-sizing: border-box;
            }
            .chat-window {
                width: 100%;
                min-width: 0;
                min-height: 0;
                height: auto;
                border-radius: 0 0 12px 12px;
                display: none;
            }
            .messages-card.mobile-chat-active .conversations-list {
                display: none !important;
            }
            .messages-card.mobile-chat-active .chat-window {
                display: flex !important;
                flex-direction: column; /* Ensure chat window content stacks */
            }
            /* Explicitly show back button when chat is active on mobile */
            .messages-card.mobile-chat-active .chat-header .back-btn {
                display: inline-flex !important;
            }
            .chat-messages { /* Adjust height for ~4 messages on mobile */
                 height: 240px; 
                 max-height: 240px;
            }
        }
        @media (max-width: 600px) {
            .messages-card {
                padding: 0.2rem;
            }
            /* Removed chat-messages height from here */
        }
        nav.navbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-sizing: border-box;
            width: 100%;
            min-width: 0;
        }
        .logo {
            min-width: 0;
            overflow: hidden;
            flex-shrink: 1;
        }
        .logo h1 {
            font-size: 2rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin: 0;
        }
        .hamburger {
            flex-shrink: 0;
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
                display: block;
            }
            .sidebar.open {
                left: 0;
            }
            .sidebar:not(.open) {
                left: -270px;
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
        /* Explicitly show back button when chat is active on mobile */
        .messages-card.mobile-chat-active .chat-header .back-btn {
            display: inline-flex !important;
        }
        .back-button-container {
            margin: 1rem 0 1rem 10px; /* Add some margin for spacing and left alignment */
            text-align: left;
            display: none; /* Hidden by default on mobile */
        }

        /* Show the back button container only when chat is active on mobile */
        .messages-card.mobile-chat-active + .back-button-container {
            display: block; /* Show the container when chat is active */
        }

        .back-button-container .back-btn {
             display: inline-flex; /* Button should be visible when container is block */
             align-items: center;
             background: none;
             border: none;
             color: #388e3c; /* Match website green */
             font-size: 1.1rem; /* Adjust size */
             cursor: pointer;
             padding: 0;
             /* Remove the back button style from chat-header */
        }

        .messages-card {
            flex-direction: column;
            /* ... existing styles ... */
        }

        /* Ensure flex direction is row on desktop */
        @media (min-width: 901px) {
             .messages-card {
                 flex-direction: row;
                 display: flex; /* Ensure it's a flex container */
                 height: 700px; /* Keep fixed height for desktop */
                 max-height: 700px;
             }
             .conversations-list {
                  width: 300px; /* Fixed width for the list */
                  flex-shrink: 0; /* Prevent shrinking */
                  height: 100%; /* Take full height of parent */
             }
             .chat-window {
                 display: flex; /* Ensure chat window is a flex container on desktop */
                 flex-direction: column;
                 flex: 1; /* Take remaining space */
                 height: 100%; /* Take full height of parent */
             }
             .chat-header .back-btn {
                  display: none; /* Hide the back button in the header on desktop */
             }
             .back-button-container {
                 display: none; /* Hide the external back button container on desktop */
             }
        }

        /* Styles for No Conversations Message */
        .no-conversations-message {
            text-align: center;
            padding: 2rem;
            background: #e8f5e9;
            border-radius: 12px;
            color: #2e7d32;
            font-size: 1.1rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .no-conversations-message i,
        .no-conversation-selected i {
            font-size: 2em;
            color: #4CAF50;
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="logo">
                <a href="/TAILTOTALE/frontend/pages/<?php echo isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'rehomer' ? 'index-rehomer.php' : 'index-adopter.php'; ?>" style="text-decoration: none; color: inherit;">
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
                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'rehomer'): ?>
                    <a href="mylistings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'mylistings.php' ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i> My Listings
                        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'rehomer' && $total_new_applications > 0): ?>
                            <span class="notification-badge"><?php echo $total_new_applications; ?></span>
                        <?php endif; ?>
                    </a>
                <?php elseif (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'adopter'): ?>
                    <a href="adoption-history.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'adoption-history.php' ? 'active' : ''; ?>">
                        <i class="fas fa-history"></i> Adoption History
                        <?php if ($total_adopter_notifications > 0): ?>
                            <span class="notification-badge"><?php echo $total_adopter_notifications; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
                <a href="messages.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'messages.php' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i> Messages
                    <span class="notification-badge" id="unread-messages-badge" style="display: none;"></span>
                </a>
                <button onclick="window.location.href='/TAILTOTALE/backend/api/logout.php'"><i class="fas fa-sign-out-alt"></i> Log Out</button>
            </nav>
        </aside>
        <!-- Main Content -->
        <main class="main-content">
            <div class="dashboard-header" style="margin-bottom: 1.5rem;">
                <div class="welcome" style="font-size: 2rem; font-weight: bold; color: #2e7d32; margin-bottom: 0; letter-spacing: 0.5px;">
                    <i class="fas fa-envelope" style="margin-right:10px;"></i>Messages
                </div>
            </div>

            <!-- Back Button Container (Moved Outside messages-card) -->
            <div class="back-button-container" id="messagesBackButtonContainer">
                <button class="back-btn" id="backToListBtn" style="display:none;"><i class="fas fa-arrow-left"></i> Back to List</button>
            </div>

            <div class="messages-card">
                <!-- Conversation List -->
                <div class="conversations-list">
                    <div class="search-container">
                        <input type="text" class="search-input" placeholder="Search users..." id="userSearch">
                    </div>
                    <div class="search-results" id="searchResults" style="display: none;"></div>
                    <div class="conversations-content" id="conversationsContent"></div>
                </div>
                <!-- Chat Window -->
                <div class="chat-window">
                    <div class="chat-header">
                        <div class="no-conversation">Select a conversation to start messaging</div>
                    </div>
                    <div class="chat-messages">
                        <div class="no-conversation-selected">
                            <i class="fas fa-comments" style="font-size: 3em; color: #ddd; margin-bottom: 1rem;"></i>
                            <p>Select a conversation from the left to start messaging</p>
                        </div>
                    </div>
                    <div class="chat-input">
                        <input type="text" placeholder="Select a conversation to start typing..." disabled>
                        <button disabled><i class="fas fa-paper-plane"></i> Send</button>
                    </div>
                </div>
            </div>
        </main>
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
        function logout() {
            fetch('/TAILTOTALE/backend/api/logout.php')
                .then(() => {
                    window.location.href = '/TAILTOTALE/frontend/pages/index.php';
                });
        }

        let currentConversationId = null; // To keep track of the currently active conversation
        const chatMessagesDiv = document.querySelector('.chat-messages');
        const chatInput = document.querySelector('.chat-input input');
        const sendMessageButton = document.querySelector('.chat-input button');
        const conversationsContentDiv = document.getElementById('conversationsContent');
        const searchResultsDiv = document.getElementById('searchResults');
        const userSearchInput = document.getElementById('userSearch');
        const chatHeaderDiv = document.querySelector('.chat-header');

        let searchTimeout;
        let allUsers = []; // Store all users for search
        let chatOpenedSuccessfully = false;

        // Function to search users
        function searchUsers(query) {
            if (!query.trim()) {
                searchResultsDiv.style.display = 'none';
                return;
            }

            const filteredUsers = allUsers.filter(user => 
                user.username.toLowerCase().includes(query.toLowerCase())
            );

            if (filteredUsers.length === 0) {
                searchResultsDiv.innerHTML = '<p style="padding: 10px 20px; color: #888; margin: 0;">No users found</p>';
                searchResultsDiv.style.display = 'block';
                return;
            }

            searchResultsDiv.innerHTML = '';
            const searchHeader = document.createElement('div');
            searchHeader.className = 'section-header';
            searchHeader.textContent = 'Search Results';
            searchResultsDiv.appendChild(searchHeader);

            filteredUsers.forEach(user => {
                const userItem = document.createElement('div');
                userItem.className = 'conversation-item';
                userItem.setAttribute('data-user-id', user.id);
                userItem.innerHTML = `
                    ${user.profile_picture ? 
                        `<img src="${user.profile_picture}" alt="Avatar" class="conversation-avatar">` : 
                        `<div class="conversation-avatar" style="background: #ddd; display: flex; align-items: center; justify-content: center;"><i class="fas fa-user" style="color: #888;"></i></div>`
                    }
                    <div class="conversation-info">
                        <div class="conversation-name">${user.username}</div>
                        <div class="conversation-preview">Click to start conversation</div>
                    </div>
                `;
                userItem.addEventListener('click', () => {
                    selectUserToMessage(user.id, user.username, user.profile_picture);
                    userSearchInput.value = '';
                    searchResultsDiv.style.display = 'none';
                });
                searchResultsDiv.appendChild(userItem);
            });

            searchResultsDiv.style.display = 'block';
        }

        // Search input event listener
        userSearchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchUsers(e.target.value);
            }, 300); // Debounce search
        });

        // Hide search results when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-container') && !e.target.closest('.search-results')) {
                searchResultsDiv.style.display = 'none';
            }
        });

        // Function to fetch all users for search
        async function fetchAllUsers() {
            try {
                const response = await fetch('/TAILTOTALE/backend/api/get_users_to_message.php');
                const data = await response.json();

                if (data.success) {
                    allUsers = data.users;
                } else {
                    console.error('Error fetching users:', data.error);
                }
            } catch (error) {
                console.error('Error fetching users:', error);
            }
        }

        // Function to fetch and display conversations
        async function fetchAndDisplayConversations() {
            try {
                const response = await fetch('/TAILTOTALE/backend/api/get_conversations.php');
                const data = await response.json();

                if (data.success) {
                    conversationsContentDiv.innerHTML = ''; // Clear current list

                    // Filter conversations to only show those with actual messages
                    const activeConversations = data.conversations.filter(conversation => 
                        conversation.lastMessageText && conversation.lastMessageText.trim() !== ''
                    );

                    // Only show the "Your Conversations" section if there are active conversations
                    if (activeConversations.length > 0) {
                        const conversationsHeader = document.createElement('div');
                        conversationsHeader.className = 'section-header';
                        conversationsHeader.textContent = 'Your Conversations';
                        conversationsContentDiv.appendChild(conversationsHeader);

                        activeConversations.forEach(conversation => {
                            const conversationItem = document.createElement('div');
                            conversationItem.className = 'conversation-item';
                            conversationItem.dataset.conversationId = conversation.conversationId;
                            conversationItem.innerHTML = `
                                ${conversation.otherProfilePicture ? 
                                    `<img src="${conversation.otherProfilePicture}" alt="Avatar" class="conversation-avatar" onerror="this.onerror=null;this.src='/TAILTOTALE/frontend/assets/images/profile_pics/default.png';">` : 
                                    `<img src="/TAILTOTALE/frontend/assets/images/profile_pics/default.png" alt="Avatar" class="conversation-avatar">`
                                }
                                <div class="conversation-info">
                                    <div class="conversation-name">${conversation.otherUsername}</div>
                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <span class="conversation-preview">${conversation.lastMessageText}</span>
                                        <span class="conversation-time">${conversation.lastMessageTime ? new Date(conversation.lastMessageTime).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : ''}</span>
                                    </div>
                                    ${conversation.unreadCount > 0 ? `<span class="conversation-unread-badge">${conversation.unreadCount}</span>` : ''}
                                </div>
                            `;
                            conversationItem.addEventListener('click', () => selectConversation(conversation.conversationId, conversation.otherUsername, conversation.otherProfilePicture));
                            conversationsContentDiv.appendChild(conversationItem);
                        });
                    }

                    // If no active conversations, show a helpful message
                    if (activeConversations.length === 0) {
                        const noConversations = document.createElement('div');
                        noConversations.className = 'no-conversations-message';
                        noConversations.innerHTML = `
                           <i class="fas fa-comments"></i>
                           <p>No conversations yet</p>
                           <p>Use the search bar above to find and message other users</p>
                       `;
                        conversationsContentDiv.appendChild(noConversations);
                    }
                } else {
                    console.error('Error fetching conversations:', data.error);
                }
            } catch (error) {
                console.error('Error fetching conversations:', error);
            }
        }

        // Function to handle selecting an existing conversation
        async function selectConversation(conversationId, otherUsername, otherProfilePicture) {
             // Remove active class from all items
            document.querySelectorAll('.conversation-item').forEach(item => item.classList.remove('active'));

            // Add active class to the selected conversation item
            const selectedItem = document.querySelector(`.conversation-item[data-conversation-id="${conversationId}"]`);
            if(selectedItem) selectedItem.classList.add('active');

            currentConversationId = conversationId;
            updateChatHeader(otherUsername, otherProfilePicture);
            fetchAndDisplayMessages(conversationId);

            // Mark messages in this conversation as read
            markMessagesAsRead(conversationId);

            // Enable message input
            chatInput.disabled = false;
            chatInput.placeholder = "Type your message...";
            sendMessageButton.disabled = false;
        }

         // Function to handle selecting a user to start a new conversation
        async function selectUserToMessage(userId, username, profilePicture) {
            chatOpenedSuccessfully = false;
            try {
                const response = await fetch('/TAILTOTALE/backend/api/create_conversation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ otherUserId: userId })
                });

                const data = await response.json();

                if (data.success) {
                    chatOpenedSuccessfully = true;
                    selectConversation(data.conversationId, username, profilePicture);
                    setTimeout(() => {
                        fetchAndDisplayConversations();
                    }, 100);
                } else {
                    alert(data.error || 'Failed to start conversation. Please try again.');
                }
            } catch (error) {
                if (!chatOpenedSuccessfully) {
                    alert('Failed to start conversation. Please try again.');
                }
            }
        }

        // Function to update chat header
        function updateChatHeader(username, profilePicture) {
            chatHeaderDiv.innerHTML = `
                <div style="display: flex; align-items: center; width: 100%;">
                    <div style="display: flex; align-items: center;">
                        ${profilePicture ? 
                            `<img src="${profilePicture}" class="conversation-avatar" alt="${username}" onerror="this.onerror=null;this.src='/TAILTOTALE/frontend/assets/images/profile_pics/default.png';">` : 
                            `<img src="/TAILTOTALE/frontend/assets/images/profile_pics/default.png" class="conversation-avatar" alt="${username}">`
                        }
                        <div class="conversation-name" style="margin-left: 8px; font-size: 1.15em;">${username}</div>
                    </div>
                </div>
            `;
        }

        // Function to fetch and display messages for a conversation
        async function fetchAndDisplayMessages(conversationId) {
            try {
                const response = await fetch(`/TAILTOTALE/backend/api/get_messages.php?conversationId=${conversationId}`);
                const data = await response.json();

                if (data.success) {
                    chatMessagesDiv.innerHTML = ''; // Clear current messages
                    chatMessagesDiv.classList.add('has-content'); // Remove centering styles
                    
                    if (data.messages.length === 0) {
                         const noMessages = document.createElement('p');
                         noMessages.textContent = 'Start the conversation by sending a message!';
                         noMessages.style.textAlign = 'center';
                         noMessages.style.color = '#888';
                         noMessages.style.marginTop = '2rem';
                         chatMessagesDiv.appendChild(noMessages);
                    } else {
                         data.messages.forEach(message => {
                            const messageElement = document.createElement('div');
                            messageElement.className = `message ${message.sender_id == <?php echo $_SESSION['user_id'] ?? 'null'; ?> ? 'sent' : 'received'}`;
                            messageElement.innerHTML = `
                                <div class="message-content">${message.message_text}</div>
                                <div class="message-time">${new Date(message.sent_at).toLocaleString()}</div>
                            `;
                            chatMessagesDiv.appendChild(messageElement);
                        });
                    }
                    // Scroll to the latest message
                    chatMessagesDiv.scrollTop = chatMessagesDiv.scrollHeight;
                } else {
                    console.error('Error fetching messages:', data.error);
                }
            } catch (error) {
                console.error('Error fetching messages:', error);
            }
        }

        // Function to send a message
        async function sendMessage() {
            const messageText = chatInput.value.trim();
            if (!messageText || !currentConversationId) {
                return; // Don't send empty messages or if no conversation is selected
            }

            try {
                const response = await fetch('/TAILTOTALE/backend/api/send_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ conversationId: currentConversationId, messageText: messageText })
                });

                const data = await response.json();

                if (data.success) {
                    // Message sent successfully, clear input and refresh messages
                    chatInput.value = '';
                    // We can either refetch all messages or optimistically add the sent message
                    // For simplicity, let's refetch for now. In a real-time app, you'd use websockets or polling.
                    fetchAndDisplayMessages(currentConversationId);
                     // Also refresh conversations list to update last message preview and time
                    setTimeout(() => {
                        fetchAndDisplayConversations();
                    }, 100);
                } else {
                    console.error('Error sending message:', data.error);
                    alert('Failed to send message. Please try again.');
                }
            } catch (error) {
                console.error('Error sending message:', error);
                alert('Failed to send message. Please try again.');
            }
        }

        // Event listener for sending message on button click
        sendMessageButton.addEventListener('click', sendMessage);

        // Event listener for sending message on pressing Enter in input field
        chatInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault(); // Prevent default form submission
                sendMessage();
            }
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

        // Function to mark messages in a conversation as read
        async function markMessagesAsRead(conversationId) {
            try {
                const response = await fetch('/TAILTOTALE/backend/api/mark_messages_as_read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ conversationId: conversationId })
                });

                const data = await response.json();

                if (data.success) {
                    // Refresh the unread count after marking messages as read
                    fetchUnreadMessageCount();
                } else {
                    console.error('Error marking messages as read:', data.error);
                }
            } catch (error) {
                console.error('Error marking messages as read:', error);
            }
        }

        // Initial load of conversations and users when the page is ready
        document.addEventListener('DOMContentLoaded', () => {
            fetchAndDisplayConversations();
            fetchAllUsers(); // Load users for search functionality
            fetchUnreadMessageCount(); // Fetch initial unread count

            // Check for rehomer_id or adopter_id in URL and auto-open conversation
            const urlParams = new URLSearchParams(window.location.search);
            const rehomerId = urlParams.get('rehomer_id');
            const adopterId = urlParams.get('adopter_id');
            let targetUserId = rehomerId || adopterId;
            if (targetUserId) {
                // Wait for users to be loaded before trying to open conversation
                const checkAndOpenConversation = async () => {
                    if (allUsers.length > 0) {
                        const user = allUsers.find(user => user.id === parseInt(targetUserId));
                        if (user) {
                            selectUserToMessage(user.id, user.username, user.profile_picture);
                        }
                    } else {
                        setTimeout(checkAndOpenConversation, 100);
                    }
                };
                checkAndOpenConversation();
            }

            // Poll for new messages and unread count periodically (e.g., every 30 seconds)
            setInterval(() => {
                if (!currentConversationId) { // Only fetch conversations and count if not in a chat
                    fetchAndDisplayConversations();
                    fetchUnreadMessageCount();
                }
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

        // Mobile chat toggle logic
        function isMobileView() {
            return window.innerWidth <= 900;
        }
        function showChatWindowMobile() {
            document.querySelector('.messages-card').classList.add('mobile-chat-active');
        }
        function showListMobile() {
            document.querySelector('.messages-card').classList.remove('mobile-chat-active');
        }
        // Patch selectConversation to show chat window on mobile
        const origSelectConversation = selectConversation;
        selectConversation = function(...args) {
            origSelectConversation.apply(this, args);
            if (isMobileView()) showChatWindowMobile();
        };
        // Also patch selectUserToMessage
        const origSelectUserToMessage = selectUserToMessage;
        selectUserToMessage = function(...args) {
            origSelectUserToMessage.apply(this, args);
            if (isMobileView()) showChatWindowMobile();
        };
        // On resize, if going back to desktop, always show both
        window.addEventListener('resize', function() {
            if (!isMobileView()) {
                document.querySelector('.messages-card').classList.remove('mobile-chat-active');
                document.getElementById('backToListBtn').style.display = 'none';
            }
        });

        // Add event listener to make the header clickable on mobile
        chatHeaderDiv.addEventListener('click', function() {
            // Check if we are on mobile and the chat window is active
            if (isMobileView() && document.querySelector('.messages-card').classList.contains('mobile-chat-active')) {
                showListMobile(); // Go back to the conversation list
            }
        });
    </script>
</body>
</html>
