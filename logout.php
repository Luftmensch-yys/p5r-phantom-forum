<?php
declare(strict_types=1);

include 'auth.php';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
}

setcookie('current_username', '', time() - 3600, '/');
session_destroy();

redirect('login.php');
?>
