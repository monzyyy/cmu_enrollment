<?php

define('BACKEND_PATH', __DIR__);
define('ROOT_PATH', dirname(__DIR__));
define('FRONTEND_PATH', ROOT_PATH . '/frontend/');

require_once BACKEND_PATH . '/config/app.php';

error_reporting(E_ALL);
if (APP_ENV === 'local') {
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', BACKEND_PATH . '/logs/php-error.log');
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

# Helper


require_once BACKEND_PATH . '/config/database.php';