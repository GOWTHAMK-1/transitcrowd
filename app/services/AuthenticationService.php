<?php
/**
 * AuthenticationService
 * Handles user authentication, registration, session management, and authorization.
 */

declare(strict_types=1);

namespace App\Services;

use App\Config\Config;
use App\Middleware\CsrfMiddleware;
use App\Validators\ValidationService;
use RuntimeException;

class AuthenticationService
{
    private CouchDBService $couch;
    private string $usersDb;

    public function __construct(?CouchDBService $couch = null)
    {
        $this->couch = $couch ?? new CouchDBService();
        $this->usersDb = $this->couch->getDatabaseName('users');
        CsrfMiddleware::initSession();
    }

    public function getUserByEmail(string $email): ?array
    {
        $email = ValidationService::sanitizeEmail($email);
        $docs = $this->couch->find($this->usersDb, [
            'selector' => [
                'type' => 'user',
                'email' => $email,
            ],
            'limit' => 1,
        ]);

        return !empty($docs) ? $docs[0] : null;
    }

    public function getUserById(string $userId): ?array
    {
        return $this->couch->getDocument($this->usersDb, $userId);
    }

    /**
     * Rate limiter for authentication attempts (per IP/session)
     */
    public function checkRateLimit(string $action = 'login', int $maxAttempts = 5, int $decaySeconds = 60): bool
    {
        $key = "rate_limit_{$action}";
        $now = time();

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'first_attempt' => $now];
        }

        // Reset if window expired
        if ($now - $_SESSION[$key]['first_attempt'] > $decaySeconds) {
            $_SESSION[$key] = ['count' => 0, 'first_attempt' => $now];
        }

        return $_SESSION[$key]['count'] < $maxAttempts;
    }

    public function incrementRateLimit(string $action = 'login'): void
    {
        $key = "rate_limit_{$action}";
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
        }
        $_SESSION[$key]['count']++;
    }

    public function resetRateLimit(string $action = 'login'): void
    {
        unset($_SESSION["rate_limit_{$action}"]);
    }

    /**
     * Register a new passenger account
     */
    public function register(string $name, string $email, string $password): array
    {
        $validator = new ValidationService();
        $validName = $validator->validateRequired('name', $name, 'Full Name');
        $validEmail = $validator->validateEmail('email', $email);
        $validPassword = $validator->validatePassword('password', $password, 6);

        if (!$validator->isValid()) {
            return [
                'success' => false,
                'errors' => $validator->getErrors(),
            ];
        }

        // Check if user already exists
        $existing = $this->getUserByEmail($validEmail);
        if ($existing !== null) {
            return [
                'success' => false,
                'errors' => ['email' => ['An account with this email address already exists.']],
            ];
        }

        $userId = 'user:' . bin2hex(random_bytes(8));
        $doc = [
            '_id' => $userId,
            'type' => 'user',
            'name' => $validName,
            'email' => $validEmail,
            'password_hash' => password_hash($validPassword, PASSWORD_BCRYPT),
            'role' => 'passenger',
            'favorite_routes' => [],
            'favorite_stops' => [],
            'recent_searches' => [],
            'created_at' => date('c'),
            'updated_at' => date('c'),
        ];

        $saveRes = $this->couch->saveDocument($this->usersDb, $doc, $userId);
        if (!$saveRes['ok']) {
            return [
                'success' => false,
                'errors' => ['general' => ['Failed to create user account. Please try again.']],
            ];
        }

        // Automatically log in newly registered user
        return $this->login($validEmail, $validPassword);
    }

    /**
     * Authenticate and log in user
     */
    public function login(string $email, string $password): array
    {
        if (!$this->checkRateLimit('login', 5, 60)) {
            return [
                'success' => false,
                'errors' => ['general' => ['Too many failed login attempts. Please wait 60 seconds before trying again.']],
            ];
        }

        $validator = new ValidationService();
        $validEmail = $validator->validateEmail('email', $email);
        $validPassword = $validator->validateRequired('password', $password, 'Password');

        if (!$validator->isValid()) {
            return [
                'success' => false,
                'errors' => $validator->getErrors(),
            ];
        }

        $user = $this->getUserByEmail($validEmail);
        if ($user === null || !password_verify($validPassword, $user['password_hash'] ?? '')) {
            $this->incrementRateLimit('login');
            return [
                'success' => false,
                'errors' => ['general' => ['Invalid email or password.']],
            ];
        }

        // Reset rate limit on successful login
        $this->resetRateLimit('login');

        // Prevent session fixation
        if (!headers_sent()) {
            session_regenerate_id(true);
        }

        $_SESSION['user'] = [
            'id' => $user['_id'],
            'name' => $user['name'] ?? 'User',
            'email' => $user['email'] ?? '',
            'role' => $user['role'] ?? 'passenger',
            'favorite_routes' => $user['favorite_routes'] ?? [],
            'favorite_stops' => $user['favorite_stops'] ?? [],
        ];

        return [
            'success' => true,
            'user' => $_SESSION['user'],
            'redirect' => ($user['role'] === 'admin') ? '/admin' : '/dashboard',
        ];
    }

    public function logout(): void
    {
        $_SESSION['user'] = null;
        unset($_SESSION['user']);
        if (!headers_sent()) {
            session_regenerate_id(true);
        }
    }

    public function isAuthenticated(): bool
    {
        return !empty($_SESSION['user']['id']);
    }

    public function getCurrentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public function isAdmin(): bool
    {
        return $this->isAuthenticated() && ($_SESSION['user']['role'] ?? '') === 'admin';
    }

    public function isPassenger(): bool
    {
        return $this->isAuthenticated() && ($_SESSION['user']['role'] ?? '') === 'passenger';
    }

    public function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            header('Location: /login?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
            exit;
        }
    }

    public function requireAdmin(): void
    {
        $this->requireAuth();
        if (!$this->isAdmin()) {
            http_response_code(403);
            require dirname(__DIR__, 2) . '/views/errors/403.php';
            exit;
        }
    }

    /**
     * Toggle favorite route for the logged-in user
     */
    public function toggleFavoriteRoute(string $routeId): array
    {
        if (!$this->isAuthenticated()) {
            return ['success' => false, 'message' => 'Authentication required'];
        }

        $userId = $_SESSION['user']['id'];
        $userDoc = $this->getUserById($userId);
        if ($userDoc === null) {
            return ['success' => false, 'message' => 'User record not found'];
        }

        $favorites = $userDoc['favorite_routes'] ?? [];
        $isFav = in_array($routeId, $favorites, true);

        if ($isFav) {
            $favorites = array_values(array_filter($favorites, fn($id) => $id !== $routeId));
            $action = 'removed';
        } else {
            $favorites[] = $routeId;
            $action = 'added';
        }

        $userDoc['favorite_routes'] = $favorites;
        $userDoc['updated_at'] = date('c');

        $updateRes = $this->couch->updateDocument($this->usersDb, $userDoc);
        if ($updateRes['ok']) {
            $_SESSION['user']['favorite_routes'] = $favorites;
            return ['success' => true, 'action' => $action, 'favorites' => $favorites];
        }

        return ['success' => false, 'message' => 'Failed to update preferences'];
    }

    /**
     * Toggle favorite stop for the logged-in user
     */
    public function toggleFavoriteStop(string $stopId): array
    {
        if (!$this->isAuthenticated()) {
            return ['success' => false, 'message' => 'Authentication required'];
        }

        $userId = $_SESSION['user']['id'];
        $userDoc = $this->getUserById($userId);
        if ($userDoc === null) {
            return ['success' => false, 'message' => 'User record not found'];
        }

        $favorites = $userDoc['favorite_stops'] ?? [];
        $isFav = in_array($stopId, $favorites, true);

        if ($isFav) {
            $favorites = array_values(array_filter($favorites, fn($id) => $id !== $stopId));
            $action = 'removed';
        } else {
            $favorites[] = $stopId;
            $action = 'added';
        }

        $userDoc['favorite_stops'] = $favorites;
        $userDoc['updated_at'] = date('c');

        $updateRes = $this->couch->updateDocument($this->usersDb, $userDoc);
        if ($updateRes['ok']) {
            $_SESSION['user']['favorite_stops'] = $favorites;
            return ['success' => true, 'action' => $action, 'favorites' => $favorites];
        }

        return ['success' => false, 'message' => 'Failed to update preferences'];
    }

    /**
     * Record a recent search query for the passenger
     */
    public function recordRecentSearch(string $query): void
    {
        if (!$this->isAuthenticated()) {
            return;
        }

        $userId = $_SESSION['user']['id'];
        $userDoc = $this->getUserById($userId);
        if ($userDoc === null) {
            return;
        }

        $searches = $userDoc['recent_searches'] ?? [];
        $query = trim($query);
        if ($query === '') {
            return;
        }

        // Add to front, remove duplicates, limit to 6
        $searches = array_values(array_filter($searches, fn($q) => strcasecmp($q, $query) !== 0));
        array_unshift($searches, $query);
        $searches = array_slice($searches, 0, 6);

        $userDoc['recent_searches'] = $searches;
        $this->couch->updateDocument($this->usersDb, $userDoc);
    }

    /**
     * Delete user account (Privacy / GDPR retention compliance)
     */
    public function deleteAccount(): array
    {
        if (!$this->isAuthenticated()) {
            return ['success' => false, 'message' => 'Not authenticated'];
        }

        $userId = $_SESSION['user']['id'];
        $delRes = $this->couch->deleteDocument($this->usersDb, $userId);
        if ($delRes['ok']) {
            $this->logout();
            return ['success' => true, 'message' => 'Account successfully removed'];
        }

        return ['success' => false, 'message' => 'Failed to delete account'];
    }
}
