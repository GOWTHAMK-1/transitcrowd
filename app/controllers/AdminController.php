<?php
/**
 * AdminController - Administrator dashboard, route/stop management, report moderation
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CouchDBService;
use App\Services\AdminService;
use App\Services\RouteService;
use App\Services\StopService;
use App\Services\CrowdReportService;
use App\Services\AuthenticationService;
use App\Middleware\CsrfMiddleware;

class AdminController
{
    private CouchDBService $couch;
    private AdminService $adminService;
    private RouteService $routeService;
    private StopService $stopService;
    private CrowdReportService $reportService;
    private AuthenticationService $auth;

    public function __construct()
    {
        $this->couch = new CouchDBService();
        $this->stopService = new StopService($this->couch);
        $this->routeService = new RouteService($this->couch, $this->stopService);
        $this->reportService = new CrowdReportService($this->couch);
        $this->adminService = new AdminService($this->couch);
        $this->auth = new AuthenticationService($this->couch);

        // Enforce strict administrative authorization
        $this->auth->requireAdmin();
    }

    public function index(): void
    {
        $health = $this->adminService->getSystemHealth();
        $routes = $this->routeService->getAllRoutes();
        $stops = $this->stopService->getAllStops();
        $pendingReports = $this->reportService->getReports(['status' => 'pending'], 20);

        $pageTitle = 'Administrator Control Panel';
        require dirname(__DIR__, 2) . '/views/admin/dashboard.php';
    }

    public function routes(): void
    {
        $routes = $this->routeService->getAllRoutes();
        $stops = $this->stopService->getAllStops();

        $pageTitle = 'Manage Routes & Services';
        require dirname(__DIR__, 2) . '/views/admin/routes.php';
    }

    public function createRoute(): void
    {
        if (!CsrfMiddleware::verifyRequest()) {
            http_response_code(403);
            $_SESSION['flash_error'] = 'Invalid CSRF token.';
            header('Location: /admin/routes');
            exit;
        }

        $stopIds = $_POST['stop_ids'] ?? [];
        if (is_string($stopIds)) {
            $stopIds = array_filter(array_map('trim', explode(',', $stopIds)));
        }

        $res = $this->routeService->createRoute([
            'route_number' => $_POST['route_number'] ?? '',
            'route_name' => $_POST['route_name'] ?? '',
            'transport_mode' => $_POST['transport_mode'] ?? 'bus',
            'description' => $_POST['description'] ?? '',
            'frequency_minutes' => $_POST['frequency_minutes'] ?? 15,
            'operating_hours' => $_POST['operating_hours'] ?? '06:00 - 22:00',
            'vehicle_capacity' => $_POST['vehicle_capacity'] ?? null,
            'stop_ids' => $stopIds,
            'active' => isset($_POST['active']),
        ]);

        if ($res['success']) {
            $_SESSION['flash_success'] = 'Route successfully created.';
        } else {
            $_SESSION['flash_errors'] = $res['errors'];
        }

        header('Location: /admin/routes');
        exit;
    }

    public function updateRoute(string $routeId): void
    {
        if (!CsrfMiddleware::verifyRequest()) {
            http_response_code(403);
            $_SESSION['flash_error'] = 'Invalid CSRF token.';
            header('Location: /admin/routes');
            exit;
        }

        $stopIds = $_POST['stop_ids'] ?? [];
        if (is_string($stopIds)) {
            $stopIds = array_filter(array_map('trim', explode(',', $stopIds)));
        }

        $res = $this->routeService->updateRoute($routeId, [
            'route_number' => $_POST['route_number'] ?? '',
            'route_name' => $_POST['route_name'] ?? '',
            'transport_mode' => $_POST['transport_mode'] ?? 'bus',
            'description' => $_POST['description'] ?? '',
            'frequency_minutes' => (int)($_POST['frequency_minutes'] ?? 15),
            'operating_hours' => $_POST['operating_hours'] ?? '',
            'vehicle_capacity' => !empty($_POST['vehicle_capacity']) ? (int)$_POST['vehicle_capacity'] : null,
            'stop_ids' => $stopIds,
            'active' => isset($_POST['active']),
        ]);

        if ($res['success']) {
            $_SESSION['flash_success'] = 'Route updated successfully.';
        } else {
            $_SESSION['flash_errors'] = $res['errors'];
        }

        header('Location: /admin/routes');
        exit;
    }

    public function deleteRoute(string $routeId): void
    {
        if (!CsrfMiddleware::verifyRequest()) {
            http_response_code(403);
            $_SESSION['flash_error'] = 'Invalid security token.';
            header('Location: /admin/routes');
            exit;
        }

        $res = $this->routeService->deleteRoute($routeId);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Route deleted.';
        } else {
            $_SESSION['flash_error'] = 'Failed to delete route.';
        }

        header('Location: /admin/routes');
        exit;
    }

    public function stops(): void
    {
        $stops = $this->stopService->getAllStops();
        $pageTitle = 'Manage Stops & Stations';
        require dirname(__DIR__, 2) . '/views/admin/stops.php';
    }

    public function createStop(): void
    {
        if (!CsrfMiddleware::verifyRequest()) {
            http_response_code(403);
            $_SESSION['flash_error'] = 'Invalid CSRF token.';
            header('Location: /admin/stops');
            exit;
        }

        $res = $this->stopService->createStop([
            'stop_code' => $_POST['stop_code'] ?? '',
            'stop_name' => $_POST['stop_name'] ?? '',
            'transport_mode' => $_POST['transport_mode'] ?? 'bus',
            'zone' => $_POST['zone'] ?? '',
            'latitude' => !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null,
            'longitude' => !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null,
        ]);

        if ($res['success']) {
            $_SESSION['flash_success'] = 'Stop created successfully.';
        } else {
            $_SESSION['flash_errors'] = $res['errors'];
        }

        header('Location: /admin/stops');
        exit;
    }

    public function deleteStop(string $stopId): void
    {
        if (!CsrfMiddleware::verifyRequest()) {
            http_response_code(403);
            $_SESSION['flash_error'] = 'Invalid security token.';
            header('Location: /admin/stops');
            exit;
        }

        $res = $this->stopService->deleteStop($stopId);
        if ($res['success']) {
            $_SESSION['flash_success'] = 'Stop deleted.';
        } else {
            $_SESSION['flash_error'] = 'Failed to delete stop.';
        }

        header('Location: /admin/stops');
        exit;
    }

    public function reports(): void
    {
        $status = $_GET['status'] ?? 'pending';
        $reports = $this->reportService->getReports($status !== 'all' ? ['status' => $status] : [], 50);

        $pageTitle = 'Crowd Report Moderation';
        require dirname(__DIR__, 2) . '/views/admin/reports.php';
    }

    public function moderateReport(): void
    {
        if (!CsrfMiddleware::verifyRequest()) {
            http_response_code(403);
            $_SESSION['flash_error'] = 'Invalid security token.';
            header('Location: /admin/reports');
            exit;
        }

        $reportId = $_POST['report_id'] ?? '';
        $action = $_POST['action'] ?? '';
        $notes = $_POST['admin_notes'] ?? '';

        if ($action === 'delete') {
            $this->reportService->deleteReport($reportId);
            $_SESSION['flash_success'] = 'Report deleted.';
        } elseif (in_array($action, ['approved', 'rejected'], true)) {
            $this->reportService->moderateReport($reportId, $action, $notes);
            $_SESSION['flash_success'] = "Report {$action} successfully.";
        }

        header('Location: /admin/reports');
        exit;
    }
}
