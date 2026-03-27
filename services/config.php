<?php

if (!defined('APP_DB_HOST')) {
    define('APP_DB_HOST', getenv('APP_DB_HOST') ?: 'localhost');
    define('APP_DB_PORT', (int) (getenv('APP_DB_PORT') ?: 3306));
    define('APP_DB_NAME', getenv('APP_DB_NAME') ?: 'dart_club');
    define('APP_DB_USER', getenv('APP_DB_USER') ?: 'dartadmin');
    define('APP_DB_PASS', getenv('APP_DB_PASS') ?: '1234');
    define('APP_DB_CHARSET', getenv('APP_DB_CHARSET') ?: 'utf8mb4');
}
