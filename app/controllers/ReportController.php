<?php
/**
 * ReportController - Handles crowd report submission and listings
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CouchDBService;
use App\Services\RouteService;
use App\Services\StopService;
use App\Services\CrowdReportService;
use App\Services\AuthenticationService;
use App\Middleware\CsrfMiddleware;

class ReportController
{
    private RouteService $routeService;
    private StopService $stopService;
    private CrowdReportService $reportService;
    private AuthenticationService $auth;

    public function __construct()
    {
        $couch = new CouchDBService();
        $this->stopService = new StopService($couch);
        $this->routeService = new RouteService($couch, $this->stopService);
        $this->reportService = new CrowdReportService($couch);
        $this->auth = new AuthenticationService($couch);
    }

    public function index(): void
    {
        $routes = $this->routeService->getAllRoutes(['active' => true]);
        $stops = $this->stopService->getAllStops();
        $recentReports = $this->reportService->getReports([], 30);

        $selectedRoute = $_GET['route_id'] ?? '';
        $selectedStop = $_GET['stop_id'] ?? '';

        $pageTitle = 'Submit Crowd Report';
        require dirname(__DIR__, 2) . '/views/reports/submit.php';
    }

    public function submit(): void
    {
        if (!CsrfMiddleware::verifyRequest()) {
            http_response_code(403);
            if ($this->isJsonRequest()) {
                echo json_encode(['success' => false, 'errors' => ['general' => ['CSRF token expired or invalid. Please refresh and try again.']]]);
            } else {
                $_SESSION['flash_error'] = 'Invalid request security token.';
                header('Location: /reports/submit');
            }
            return;
        }

        $input = $this->isJsonRequest()
            ? (json_decode(file_get_contents('php://input'), true) ?? [])
            : $_POST;

        $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $user = $this->auth->getCurrentUser();

        $result = $this->reportService->submitReport($input, $clientIp, $user);

        if ($this->isJsonRequest()) {
            header('Content-Type: application/json');
            echo json_encode($result);
            return;
        }

        if ($result['success']) {
            $_SESSION['flash_success'] = $result['message'];
        } else {
            $_SESSION['flash_errors'] = $result['errors'];
        }

        header('Location: /reports/submit');
    }

    private function isJsonRequest(): bool
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        return str_contains($contentType, 'application/json');
    }
}
