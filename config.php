<?php

if (!function_exists('loadEnv')) {
    function loadEnv($path)
    {
        if (!file_exists($path)) {
            throw new Exception(".env file not found at: $path");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) continue;
            if (!str_contains($line, '=')) continue; // skip malformed lines

            [$name, $value] = array_map('trim', explode('=', $line, 2));
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }

    // Load .env only once
    loadEnv(__DIR__ . '/.env');

    // Define constants only if not defined
    defined('DB_HOST') or define('DB_HOST', getenv('DB_HOST'));
    defined('DB_PORT') or define('DB_PORT', getenv('DB_PORT'));
    defined('DB_NAME') or define('DB_NAME', getenv('DB_NAME'));
    defined('DB_USER') or define('DB_USER', getenv('DB_USER'));
    defined('DB_PASS') or define('DB_PASS', getenv('DB_PASS'));
    defined('APP_ENV') or define('APP_ENV', getenv('APP_ENV') ?: 'production');
    defined('APP_DEBUG') or define('APP_DEBUG', filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN));
}

// Return config array for DB usage
return [
    'db' => [
        'host' => DB_HOST,
        'port' => DB_PORT,
        'name' => DB_NAME,
        'user' => DB_USER,
        'pass' => DB_PASS,
    ],
    'app' => [
        'env' => APP_ENV,
        'debug' => APP_DEBUG,
    ],
];
