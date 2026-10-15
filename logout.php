<?php
require_once __DIR__ . '/includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Use the Log Out button to sign out.');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $cookie['path'],
        $cookie['domain'], $cookie['secure'], $cookie['httponly']);
}
session_destroy();
header('Location: ' . BASE_URL . '/index.php', true, 303);
exit;
