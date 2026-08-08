<?php

require_once __DIR__ . '/config.php';

if (!defined('APP_BOOTSTRAPPED')) {
    define('APP_BOOTSTRAPPED', true);

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    function app_is_production(): bool
    {
        return APP_ENV === 'production';
    }

    function app_is_https(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        return isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443;
    }

    function app_start_session(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            session_name('dart_club_session');
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => app_is_production() || app_is_https(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    function app_db_connect(): mysqli
    {
        static $connection = null;

        if ($connection instanceof mysqli) {
            return $connection;
        }

        if (app_is_production() && trim(APP_DB_PASS) === '') {
            throw new RuntimeException('Database credentials are not configured for production.');
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
            header('X-Content-Type-Options: nosniff');
        }

        echo json_encode($payload);
        exit();
    }

    function app_safe_error_message(Throwable $exception, string $fallback = 'Unexpected server error.'): string
    {
        if (app_is_production()) {
            return $fallback;
        }

        return $exception->getMessage() !== '' ? $exception->getMessage() : $fallback;
    }

    function app_service_exception_payload(Throwable $exception): array
    {
        return [
            'success' => false,
            'message' => app_safe_error_message($exception),
        ];
    }

    function app_request_host(): string
    {
        return strtolower(trim((string) ($_SERVER['HTTP_HOST'] ?? '')));
    }

    function app_origin_host(string $origin): string
    {
        $host = strtolower((string) parse_url($origin, PHP_URL_HOST));
        if ($host === '') {
            return '';
        }

        $port = parse_url($origin, PHP_URL_PORT);

        return $port ? $host . ':' . (int) $port : $host;
    }

    function app_enforce_same_origin_mutation(): void
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        $origin = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
        if ($origin === '') {
            if (app_is_production()) {
                app_json_response([
                    'success' => false,
                    'message' => 'Request origin could not be verified.',
                ], 403);
            }
            return;
        }

        $requestHost = app_request_host();
        $originHost = app_origin_host($origin);
        if ($requestHost === '' || $originHost === '' || !hash_equals($requestHost, $originHost)) {
            app_json_response([
                'success' => false,
                'message' => 'Cross-origin state-changing requests are not allowed.',
            ], 403);
        }
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

    function app_existing_public_path(?string $path): ?string
    {
        $publicPath = app_public_path($path);
        if ($publicPath === null) {
            return null;
        }

        if (preg_match('#^https?://#i', $publicPath) === 1) {
            return $publicPath;
        }

        $absolutePath = dirname(__DIR__) . $publicPath;

        return is_file($absolutePath) ? $publicPath : null;
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

        app_enforce_same_origin_mutation();
    }
}
