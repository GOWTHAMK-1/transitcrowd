<?php
/**
 * DashboardController - Passenger personalized travel dashboard
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CouchDBService;
use App\Services\RouteService;
use App\Services\StopService;
use App\Services\CrowdPredictionService;
use App\Services\CrowdReportService;
use App\Services\AuthenticationService;

class DashboardController
{
    private RouteService $routeService;
    private StopService $stopService;
    private CrowdPredictionService $predictionService;
    private CrowdReportService $reportService;
    private AuthenticationService $auth;

    public function __construct()
    {
        $couch = new CouchDBService();
        $this->stopService = new StopService($couch);
        $this->routeService = new RouteService($couch, $this->stopService);
        $this->predictionService = new CrowdPredictionService($couch, $this->routeService, $this->stopService);
        $this->reportService = new CrowdReportService($couch);
        $this->auth = new AuthenticationService($couch);
    }

    public function index(): void
    {
        $currentUser = $this->auth->getCurrentUser();
        $userId = $currentUser['id'] ?? null;
        $userDoc = $userId ? $this->auth->getUserById($userId) : null;

        $favoriteRouteIds = $userDoc['favorite_routes'] ?? ($currentUser['favorite_routes'] ?? []);
        $favoriteStopIds = $userDoc['favorite_stops'] ?? ($currentUser['favorite_stops'] ?? []);
        $recentSearches = $userDoc['recent_searches'] ?? [];

        // Load favorite routes
        $favoriteRoutes = [];
        foreach ($favoriteRouteIds as $rid) {
            $r = $this->routeService->getRouteById($rid, true);
            if ($r !== null) {
                // Get quick prediction for first stop
                $firstStopId = $r['stop_ids'][0] ?? null;
                $quickPred = $firstStopId ? $this->predictionService->predict($r['_id'], $firstStopId, null, 15) : null;
                $r['current_prediction'] = $quickPred;
                $favoriteRoutes[] = $r;
            }
        }

        // Load favorite stops
        $favoriteStops = [];
        foreach ($favoriteStopIds as $sid) {
            $s = $this->stopService->getStopById($sid);
            if ($s !== null) {
                $routesServing = $this->routeService->getRoutesByStopId($sid);
                $s['routes_count'] = count($routesServing);
                $favoriteStops[] = $s;
            }
        }

        // Suggested alternatives if favorite route is crowded
        $alternativeSuggestions = [];
        if (!empty($favoriteRoutes)) {
            $primaryRoute = $favoriteRoutes[0];
            $firstStopId = $primaryRoute['stop_ids'][0] ?? null;
            if ($firstStopId) {
                $comp = $this->predictionService->compareDepartureTimes($primaryRoute['_id'], $firstStopId, [
                    date('H:i', strtotime('+15 minutes')),
                    date('H:i', strtotime('+45 minutes')),
                    date('H:i', strtotime('+75 minutes')),
                ]);
                $alternativeSuggestions = $comp;
            }
        }

        $recentReports = $this->reportService->getReports([], 6);

        $pageTitle = 'Passenger Dashboard';
        require dirname(__DIR__, 2) . '/views/dashboard/passenger.php';
    }
}
