<?php
session_start();

// Detect if this is an AJAX/fetch request
$isAjax = (
    isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) || (
    isset($_SERVER['HTTP_SEC_FETCH_MODE']) &&
    strtolower($_SERVER['HTTP_SEC_FETCH_MODE']) === 'cors'
);

// If AJAX/fetch, return plain text
if ($isAjax) {
    header('Content-Type: text/plain');
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        echo "loggedin";
    } else {
        echo "notloggedin";
    }
    exit;
}

// Otherwise, do normal redirect for browser navigation
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    // User is logged in, redirect based on action
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'adopt':
                header('Location: /TAILTOTALE/frontend/pages/adopt.php');
                break;
            case 'rehome':
                header('Location: /TAILTOTALE/frontend/pages/rehome.php');
                break;
            default:
                header('Location: /TAILTOTALE/frontend/pages/index.php');
        }
    } else {
        header('Location: /TAILTOTALE/frontend/pages/index.php');
    }
    exit;
} else {
    // User is not logged in, redirect to signin page
    // Store the intended destination in session
    if (isset($_GET['action'])) {
        $_SESSION['redirect_after_login'] = $_GET['action'];
    }
    header('Location: /TAILTOTALE/backend/api/signin.php');
    exit;
}

if (isset($_SESSION['redirect_after_login'])) {
    $redirect = $_SESSION['redirect_after_login'];
    unset($_SESSION['redirect_after_login']);
    echo $redirect; // JS will use this to redirect
} else {
    echo "success";
}
