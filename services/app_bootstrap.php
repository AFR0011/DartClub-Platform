<?php

require_once __DIR__ . '/config.php';

if (!defined('APP_BOOTSTRAPPED')) {
    define('APP_BOOTSTRAPPED', true);

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    function app_start_session(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    function app_db_connect(): mysqli
    {
        static $connection = null;

        if ($connection instanceof mysqli) {
            return $connection;
        }

        $connection = new mysqli(APP_DB_HOST, APP_DB_USER, APP_DB_PASS, APP_DB_NAME, APP_DB_PORT);
        $connection->set_charset(APP_DB_CHARSET);

        return $connection;
    }

    function app_read_json_input(): array
    {
        $decoded = json_decode(file_get_contents('php://input'), true);

        return is_array($decoded) ? $decoded : [];
    }

    function app_json_response(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);

        if (!headers_sent()) {
            header('Content-Type: application/json');
        }

        echo json_encode($payload);
        exit();
    }

    function app_redirect(string $path): void
    {
        header('Location: ' . $path);
        exit();
    }

    function app_value_or_null($value)
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) && trim($value) === '') {
            return null;
        }

        return $value;
    }

    function app_parse_date(string $value, string $fieldName): DateTime
    {
        try {
            return new DateTime($value);
        } catch (Exception $exception) {
            throw new InvalidArgumentException($fieldName . ' is invalid.');
        }
    }
}
