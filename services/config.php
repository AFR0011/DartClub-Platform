<?php

$localConfigPath = __DIR__ . '/config.local.php';
if (is_file($localConfigPath)) {
    require_once $localConfigPath;
}

if (!function_exists('app_config_env')) {
    function app_config_env(string $name, string $default): string
    {
        $value = getenv($name);

        return $value === false ? $default : $value;
    }
}

if (!defined('APP_ENV')) {
    define('APP_ENV', strtolower(trim(app_config_env('APP_ENV', 'development'))));
}

if (!defined('APP_DB_HOST')) {
    define('APP_DB_HOST', app_config_env('APP_DB_HOST', 'localhost'));

    $portValue = getenv('APP_DB_PORT');
    define('APP_DB_PORT', $portValue === false ? 3306 : (int) $portValue);

    define('APP_DB_NAME', app_config_env('APP_DB_NAME', 'dart_club'));
    define('APP_DB_USER', app_config_env('APP_DB_USER', 'root'));
    define('APP_DB_PASS', app_config_env('APP_DB_PASS', ''));
    define('APP_DB_CHARSET', app_config_env('APP_DB_CHARSET', 'utf8mb4'));
}
