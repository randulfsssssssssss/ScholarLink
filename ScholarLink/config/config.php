<?php

declare(strict_types=1);

if (!defined('SCHOLARLINK_ROOT')) {
    define('SCHOLARLINK_ROOT', dirname(__DIR__));
}

// Load environment variables if available
$envFile = SCHOLARLINK_ROOT . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        $parts = explode('=', $line, 2);
        $key = trim($parts[0]);
        $val = trim($parts[1]);
        $val = trim($val, '"\'');
        if (!isset($_ENV[$key]) || !getenv($key)) {
            putenv("$key=$val");
            $_ENV[$key] = $val;
        }
    }
}

return [
    'app' => [
        'name'        => 'ScholarLink',
        'environment' => getenv('APP_ENV') ?: 'development',
        'debug'       => (getenv('APP_DEBUG') ?: 'true') === 'true',
        'base_url'    => rtrim(getenv('APP_URL') ?: 'http://localhost:8080', '/'),
    ],

    'database' => [
        'host'     => getenv('DB_HOST') ?: 'localhost',
        'port'     => (int)(getenv('DB_PORT') ?: 3306),
        'name'     => getenv('DB_NAME') ?: 'scholarlink',
        'user'     => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASS') ?: '',
        'charset'  => 'utf8mb4',
    ],

    'session' => [
        'name'           => 'scholarlink_session',
        'cookie_httponly' => true,
        'cookie_secure'  => (getenv('SESSION_SECURE') ?: 'false') === 'true',
        'cookie_samesite' => 'Lax',
        'timeout'        => (int)(getenv('SESSION_TIMEOUT') ?: 1800),
    ],

    'upload' => [
        'dir'         => SCHOLARLINK_ROOT . '/public/uploads',
        'max_size'    => (int)(getenv('UPLOAD_MAX_SIZE') ?: 5242880),
        'allowed_types' => [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
        ],
        'public_url'  => '/uploads',
    ],
];
