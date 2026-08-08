<?php

require_once __DIR__ . '/app_bootstrap.php';

app_start_session();
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $params['path'] ?: '/',
        'domain' => $params['domain'] ?? '',
        'secure' => (bool) ($params['secure'] ?? false),
        'httponly' => (bool) ($params['httponly'] ?? true),
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}

$sessionDestroyed = session_destroy();

app_json_response(
    $sessionDestroyed
        ? ['success' => true]
        : ['success' => false, 'message' => 'Session destruction failed.'],
    $sessionDestroyed ? 200 : 500
);
