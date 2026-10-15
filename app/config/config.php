<?php
/**
 * Application Configuration Loader
 * Loads .env configuration if present, or uses safe defaults.
 */

declare(strict_types=1);

namespace App\Config;

class Config
{
    private static array $settings = [];
    private static bool $loaded = false;

    public static function load(?string $envPath = null): void
    {
        if (self::$loaded) {
            return;
        }

        $baseDir = dirname(__DIR__, 2);
        $envFile = $envPath ?? $baseDir . DIRECTORY_SEPARATOR . '.env';
        $envExample = $baseDir . DIRECTORY_SEPARATOR . '.env.example';

        // Load default values
        self::$settings = [
            'APP_NAME' => 'Public Transport Crowd Predictor',
            'APP_ENV' => 'development',
            'APP_DEBUG' => true,
            'APP_URL' => 'http://localhost:8000',
            'APP_SECRET' => '7f9b8c6e4d2a1059f8e7c6b5a4d3e2f1',
            'APP_TIMEZONE' => 'UTC',
            'COUCHDB_HOST' => '127.0.0.1',
            'COUCHDB_PORT' => 5984,
            'COUCHDB_USER' => 'admin',
            'COUCHDB_PASSWORD' => 'admin',
            'COUCHDB_PROTOCOL' => 'http',
            'COUCHDB_DB_ROUTES' => 'transport_routes',
            'COUCHDB_DB_STOPS' => 'transport_stops',
            'COUCHDB_DB_OBSERVATIONS' => 'transport_observations',
            'COUCHDB_DB_REPORTS' => 'transport_reports',
            'COUCHDB_DB_USERS' => 'transport_users',
            'COUCHDB_DB_PREDICTIONS' => 'transport_predictions',
            'CROWD_LOW_MAX' => 30,
            'CROWD_MODERATE_MAX' => 60,
            'CROWD_HIGH_MAX' => 80,
            'CROWD_VERY_HIGH_MAX' => 100,
            'PREDICTION_TIME_WINDOW_MINUTES' => 45,
            'PREDICTION_MIN_SAMPLES_RELIABLE' => 3,
            'PREDICTION_RECENCY_WEIGHT' => 1.5,
        ];

        // Parse file if exists
        $targetFile = file_exists($envFile) ? $envFile : (file_exists($envExample) ? $envExample : null);
        if ($targetFile !== null) {
            $lines = file($targetFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines !== false) {
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#')) {
                        continue;
                    }
                    if (str_contains($line, '=')) {
                        [$key, $value] = explode('=', $line, 2);
                        $key = trim($key);
                        $value = trim($value);
                        // Strip quotes if wrapped
                        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                            $value = substr($value, 1, -1);
                        }
                        if (strtolower($value) === 'true') {
                            $val = true;
                        } elseif (strtolower($value) === 'false') {
                            $val = false;
                        } elseif (is_numeric($value)) {
                            $val = str_contains($value, '.') ? (float)$value : (int)$value;
                        } else {
                            $val = $value;
                        }
                        self::$settings[$key] = $val;
                    }
                }
            }
        }

        date_default_timezone_set(self::$settings['APP_TIMEZONE'] ?? 'UTC');
        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            self::load();
        }
        return self::$settings[$key] ?? $default;
    }

    public static function all(): array
    {
        if (!self::$loaded) {
            self::load();
        }
        return self::$settings;
    }

    public static function getCouchDbConfig(): array
    {
        return [
            'host' => self::get('COUCHDB_HOST', '127.0.0.1'),
            'port' => (int)self::get('COUCHDB_PORT', 5984),
            'user' => self::get('COUCHDB_USER', 'admin'),
            'password' => self::get('COUCHDB_PASSWORD', 'admin'),
            'protocol' => self::get('COUCHDB_PROTOCOL', 'http'),
            'databases' => [
                'routes' => self::get('COUCHDB_DB_ROUTES', 'transport_routes'),
                'stops' => self::get('COUCHDB_DB_STOPS', 'transport_stops'),
                'observations' => self::get('COUCHDB_DB_OBSERVATIONS', 'transport_observations'),
                'reports' => self::get('COUCHDB_DB_REPORTS', 'transport_reports'),
                'users' => self::get('COUCHDB_DB_USERS', 'transport_users'),
                'predictions' => self::get('COUCHDB_DB_PREDICTIONS', 'transport_predictions'),
            ],
            'thresholds' => [
                'low_max' => (int)self::get('CROWD_LOW_MAX', 30),
                'moderate_max' => (int)self::get('CROWD_MODERATE_MAX', 60),
                'high_max' => (int)self::get('CROWD_HIGH_MAX', 80),
                'very_high_max' => (int)self::get('CROWD_VERY_HIGH_MAX', 100),
            ],
        ];
    }
}
