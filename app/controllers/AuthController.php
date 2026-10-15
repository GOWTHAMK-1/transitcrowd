<?php
/**
 * AuthController - Handles passenger registration, login, logout, and profile actions
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CouchDBService;
use App\Services\AuthenticationService;
use App\Middleware\CsrfMiddleware;

class AuthController
{
    private AuthenticationService $auth;

    public function __construct()
    {
        $couch = new CouchDBService();
        $this->auth = new AuthenticationService($couch);
    }

    public function showLogin(): void
    {
        if ($this->auth->isAuthenticated()) {
            header('Location: ' . ($this->auth->isAdmin() ? '/admin' : '/dashboard'));
            exit;
        }

        $pageTitle = 'Passenger & Admin Login';
        require dirname(__DIR__, 2) . '/views/auth/login.php';
    }

    public function login(): void
    {
        if (!CsrfMiddleware::verifyRequest()) {
            http_response_code(403);
            $_SESSION['flash_error'] = 'Invalid security token.';
            header('Location: /login');
            exit;
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $res = $this->auth->login($email, $password);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Welcome back, ' . htmlspecialchars($res['user']['name']) . '!';
            header('Location: ' . ($res['redirect'] ?? '/dashboard'));
            exit;
        }

        $_SESSION['flash_errors'] = $res['errors'];
        header('Location: /login');
        exit;
    }

    public function showRegister(): void
    {
        if ($this->auth->isAuthenticated()) {
            header('Location: /dashboard');
            exit;
        }

        $pageTitle = 'Passenger Registration';
        require dirname(__DIR__, 2) . '/views/auth/register.php';
    }

    public function register(): void
    {
        if (!CsrfMiddleware::verifyRequest()) {
            http_response_code(403);
            $_SESSION['flash_error'] = 'Invalid security token.';
            header('Location: /register');
            exit;
        }

        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $res = $this->auth->register($name, $email, $password);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Registration successful! You are now logged in.';
            header('Location: /dashboard');
            exit;
        }

        $_SESSION['flash_errors'] = $res['errors'];
        header('Location: /register');
        exit;
    }

    public function logout(): void
    {
        $this->auth->logout();
        $_SESSION['flash_success'] = 'You have been safely signed out.';
        header('Location: /');
        exit;
    }

    public function toggleFavoriteRoute(): void
    {
        header('Content-Type: application/json');
        if (!CsrfMiddleware::verifyRequest()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'CSRF verification failed']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $routeId = $input['route_id'] ?? '';

        if ($routeId === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing route_id']);
            return;
        }

        $res = $this->auth->toggleFavoriteRoute($routeId);
        echo json_encode($res);
    }

    public function toggleFavoriteStop(): void
    {
        header('Content-Type: application/json');
        if (!CsrfMiddleware::verifyRequest()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'CSRF verification failed']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $stopId = $input['stop_id'] ?? '';

        if ($stopId === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing stop_id']);
            return;
        }

        $res = $this->auth->toggleFavoriteStop($stopId);
        echo json_encode($res);
    }

    public function deleteAccount(): void
    {
        if (!CsrfMiddleware::verifyRequest()) {
            http_response_code(403);
            $_SESSION['flash_error'] = 'Invalid security token.';
            header('Location: /dashboard');
            exit;
        }

        $res = $this->auth->deleteAccount();
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Your account and personal preferences have been removed.';
            header('Location: /');
            exit;
        }

        $_SESSION['flash_error'] = 'Failed to delete account.';
        header('Location: /dashboard');
        exit;
    }
}
