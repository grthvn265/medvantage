<?php

declare(strict_types=1);

/**
 * Lightweight .env loader for MedVantage.
 *
 * Usage:
 * require_once __DIR__ . '/components/env.php';
 * $dbHost = env('DB_HOST', 'localhost');
 * $config = app_config();
 */

if (!function_exists('load_dotenv')) {
    /**
     * Parse key=value lines from a .env file and return them as an array.
     */
    function load_dotenv(?string $path = null): array
    {
        static $cache = [];

        $envPath = $path ?? __DIR__ . '/../.env';
        if (isset($cache[$envPath])) {
            return $cache[$envPath];
        }

        $values = [];

        if (!is_file($envPath) || !is_readable($envPath)) {
            $cache[$envPath] = $values;
            return $cache[$envPath];
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            $cache[$envPath] = $values;
            return $cache[$envPath];
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (str_starts_with($trimmed, 'export ')) {
                $trimmed = trim(substr($trimmed, 7));
            }

            $parts = explode('=', $trimmed, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);

            if ($key === '') {
                continue;
            }

            $first = substr($value, 0, 1);
            $last = substr($value, -1);
            if (($first === '"' && $last === '"') || ($first === '\'' && $last === '\'')) {
                $value = substr($value, 1, -1);
            } else {
                $commentPos = strpos($value, ' #');
                if ($commentPos !== false) {
                    $value = rtrim(substr($value, 0, $commentPos));
                }
            }

            $values[$key] = $value;

            if (getenv($key) === false) {
                putenv($key . '=' . $value);
            }

            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
            }

            if (!array_key_exists($key, $_SERVER)) {
                $_SERVER[$key] = $value;
            }
        }

        $cache[$envPath] = $values;
        return $cache[$envPath];
    }
}

if (!function_exists('env')) {
    /**
     * Read a value from loaded .env values or process environment.
     */
    function env(string $key, mixed $default = null): mixed
    {
        $values = load_dotenv();

        if (array_key_exists($key, $values)) {
            return normalize_env_value($values[$key]);
        }

        $value = getenv($key);
        if ($value !== false) {
            return normalize_env_value($value);
        }

        return $default;
    }
}

if (!function_exists('normalize_env_value')) {
    /**
     * Convert common scalar string values to native PHP types.
     */
    function normalize_env_value(string $value): mixed
    {
        $value = trim($value);
        $lower = strtolower($value);

        return match ($lower) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }
}

if (!function_exists('app_is_production')) {
    function app_is_production(): bool
    {
        return strtolower((string) env('APP_ENV', 'production')) === 'production';
    }
}

if (!function_exists('app_config')) {
    /**
     * Build normalized app config from environment values.
     */
    function app_config(): array
    {
        return [
            'app' => [
                'env' => (string) env('APP_ENV', 'production'),
                'url' => (string) env('APP_URL', ''),
                'domain' => (string) env('APP_DOMAIN', ''),
                'force_https' => (bool) env('APP_FORCE_HTTPS', false),
            ],
            'db' => [
                'host' => (string) env('DB_HOST', 'localhost'),
                'name' => (string) env('DB_NAME', ''),
                'user' => (string) env('DB_USER', ''),
                'pass' => (string) env('DB_PASS', ''),
                'charset' => (string) env('DB_CHARSET', 'utf8mb4'),
            ],
        ];
    }
}

// Prime cache immediately for early consumers.
load_dotenv();
