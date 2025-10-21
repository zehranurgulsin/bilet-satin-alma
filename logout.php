<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

if (session_status() === PHP_SESSION_ACTIVE) {
    session_regenerate_id(true); 
    $_SESSION = [];
}

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        [
            'expires'  => time() - 42000,
            'path'     => $params['path'] ?? '/',
            'domain'   => $params['domain'] ?? '',
            'secure'   => $params['secure'] ?? false,
            'httponly' => $params['httponly'] ?? true,
            'samesite' => $params['samesite'] ?? 'Lax',
        ]
    );
}

session_destroy();


header('Location: index.php');
exit;
