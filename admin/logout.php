<?php
session_start();

// Destroy all admin session data and redirect to login page [web:135]
$_SESSION = [];
session_unset();
session_destroy();

// Optional: clear session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

header('Location: login.php');
exit;
