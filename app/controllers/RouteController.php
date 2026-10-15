<?php
/**
 * RouteController - Handles route and stop explorer, route details, and map data
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CouchDBService;
use App\Services\RouteService;
use App\Services\StopService;
use App\Services\CrowdPredictionService;
use App\Services\AuthenticationService;

class RouteController
{
    private RouteService $routeService;
    private StopService $stopService;
    private CrowdPredictionService $predictionService;
    private AuthenticationService $auth;

    public function __construct()
    {
        $couch = new CouchDBService();
        $this->stopService = new StopService($couch);
        $this->routeService = new RouteService($couch, $this->stopService);
        $this->predictionService = new CrowdPredictionService($couch, $this->routeService, $this->stopService);
        $this->auth = new AuthenticationService($couch);
    }

    public function index(): void
    {
        $search = $_GET['q'] ?? '';
        $mode = $_GET['mode'] ?? '';

        if ($search !== '') {
            $this->auth->recordRecentSearch($search);
        }

        $filters = ['active' => true];
        if ($mode !== '') {
            $filters['transport_mode'] = $mode;
        }
        if ($search !== '') {
            $filters['search'] = $search;
        }

        $routes = $this->routeService->getAllRoutes($filters);
        $stops = $this->stopService->getAllStops();

        // Check if origin/destination search was submitted
        $originId = $_GET['origin'] ?? '';
        $destId = $_GET['destination'] ?? '';
        $matchingRoutes = [];
        if ($originId !== '' && $destId !== '') {
            $matchingRoutes = $this->routeService->findRoutesBetweenStops($originId, $destId);
        }

        $pageTitle = 'Route & Stop Explorer';
        require dirname(__DIR__, 2) . '/views/routes/explorer.php';
    }

    public function detail(string $routeId): void
    {
        $route = $this->routeService->getRouteById($routeId, true);
        if ($route === null) {
            http_response_code(404);
            require dirname(__DIR__, 2) . '/views/errors/404.php';
            return;
        }

        // Get current prediction for each stop on this route
        $stopPredictions = [];
        foreach ($route['stops'] as $stop) {
            $stopPredictions[$stop['_id']] = $this->predictionService->predict($route['_id'], $stop['_id'], null, 15);
        }

        $pageTitle = $route['route_name'] . ' (' . $route['route_number'] . ')';
        require dirname(__DIR__, 2) . '/views/routes/detail.php';
    }

    public function stopDetail(string $stopId): void
    {
        $stop = $this->stopService->getStopById($stopId);
        if ($stop === null) {
            http_response_code(404);
            require dirname(__DIR__, 2) . '/views/errors/404.php';
            return;
        }

        $routes = $this->routeService->getRoutesByStopId($stopId);

        // Get predictions for each route serving this stop
        $routePredictions = [];
        foreach ($routes as $r) {
            $routePredictions[$r['_id']] = $this->predictionService->predict($r['_id'], $stop['_id'], null, 20);
        }

        $pageTitle = $stop['stop_name'] . ' - Stop Details';
        require dirname(__DIR__, 2) . '/views/stops/detail.php';
    }

    public function apiListRoutes(): void
    {
        header('Content-Type: application/json');
        $routes = $this->routeService->getAllRoutes(['active' => true]);
        echo json_encode(['success' => true, 'data' => $routes]);
    }

    public function apiListStops(): void
    {
        header('Content-Type: application/json');
        $stops = $this->stopService->getAllStops();
        echo json_encode(['success' => true, 'data' => $stops]);
    }
}
