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

    function app_is_service_request(): bool
    {
        $scriptPath = $_SERVER['SCRIPT_FILENAME'] ?? '';
        if ($scriptPath === '') {
            return false;
        }

        $resolvedScript = realpath($scriptPath) ?: $scriptPath;
        $servicesDir = realpath(__DIR__);
        if ($servicesDir === false) {
            return false;
        }

        return str_starts_with($resolvedScript, $servicesDir . DIRECTORY_SEPARATOR);
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

    function app_service_exception_payload(Throwable $exception): array
    {
        return [
            'success' => false,
            'message' => $exception->getMessage() !== '' ? $exception->getMessage() : 'Unexpected server error.',
        ];
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

    function app_public_path(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        $normalized = trim($path);
        if ($normalized === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $normalized) === 1) {
            return $normalized;
        }

        $normalized = str_replace('\\', '/', $normalized);
        while (str_starts_with($normalized, '../')) {
            $normalized = substr($normalized, 3);
        }
        while (str_starts_with($normalized, './')) {
            $normalized = substr($normalized, 2);
        }

        $normalized = '/' . ltrim($normalized, '/');
        $normalized = preg_replace('#/+#', '/', $normalized) ?: $normalized;

        return $normalized;
    }

    if (app_is_service_request()) {
        ini_set('display_errors', '0');

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }

            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(static function (Throwable $exception): void {
            app_json_response(app_service_exception_payload($exception), 500);
        });
    }
}
