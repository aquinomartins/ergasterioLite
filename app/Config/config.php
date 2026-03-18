<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$envPath = $root . '/.env';

if (is_file($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim(trim($value), "\"'");

        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
        putenv($name . '=' . $value);
    }
}

$get = static function (string $key, mixed $default = null): mixed {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return $value;
};

return [
    'app' => [
        'name' => $get('APP_NAME', 'Ergastério Lite'),
        'url' => $get('APP_URL', 'http://localhost:8000'),
        'env' => $get('APP_ENV', 'production'),
        'debug' => filter_var($get('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
    ],
    'database' => [
        'host' => $get('DB_HOST', '127.0.0.1'),
        'port' => (int) $get('DB_PORT', 3306),
        'name' => $get('DB_NAME', 'ergasterio_lite'),
        'user' => $get('DB_USER', 'root'),
        'pass' => $get('DB_PASS', ''),
        'charset' => $get('DB_CHARSET', 'utf8mb4'),
    ],
    'session' => [
        'name' => $get('SESSION_NAME', 'ergasterio_lite'),
        'lifetime' => 7200,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax',
    ],
];
