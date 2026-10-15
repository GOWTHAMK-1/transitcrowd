<?php
/**
 * CSRF Protection Middleware
 */

declare(strict_types=1);

namespace App\Middleware;

class CsrfMiddleware
{
    public static function initSession(): void
    {
        if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
            ]);
        }
    }

    public static function getToken(): string
    {
        self::initSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateToken(?string $token): bool
    {
        self::initSession();
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function verifyRequest(): bool
    {
        self::initSession();
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return true;
        }

        // Check header first (for AJAX/fetch)
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        // Check POST field
        if ($token === null && isset($_POST['csrf_token'])) {
            $token = (string)$_POST['csrf_token'];
        }

        // Check JSON body if applicable
        if ($token === null) {
            $input = json_decode((string)file_get_contents('php://input'), true);
            if (is_array($input) && isset($input['csrf_token'])) {
                $token = (string)$input['csrf_token'];
            }
        }

        return self::validateToken($token);
    }
}
