<?php
/**
 * PredictionController - Handles crowd predictions, horizons, and time comparison
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CouchDBService;
use App\Services\RouteService;
use App\Services\StopService;
use App\Services\CrowdPredictionService;
use DateTime;
use Exception;

class PredictionController
{
    private RouteService $routeService;
    private StopService $stopService;
    private CrowdPredictionService $predictionService;

    public function __construct()
    {
        $couch = new CouchDBService();
        $this->stopService = new StopService($couch);
        $this->routeService = new RouteService($couch, $this->stopService);
        $this->predictionService = new CrowdPredictionService($couch, $this->routeService, $this->stopService);
    }

    public function index(): void
    {
        $routes = $this->routeService->getAllRoutes(['active' => true]);
        $stops = $this->stopService->getAllStops();

        $selectedRouteId = $_GET['route_id'] ?? ($routes[0]['_id'] ?? '');
        $selectedStopId = $_GET['stop_id'] ?? '';
        $selectedTime = $_GET['time'] ?? date('H:i');

        if ($selectedRouteId !== '' && $selectedStopId === '') {
            $route = $this->routeService->getRouteById($selectedRouteId, true);
            $selectedStopId = $route['stops'][0]['_id'] ?? '';
        }

        $prediction = null;
        $multiHorizons = [];
        if ($selectedRouteId !== '' && $selectedStopId !== '') {
            try {
                $target = new DateTime($selectedTime);
            } catch (Exception) {
                $target = new DateTime();
            }
            $prediction = $this->predictionService->predict($selectedRouteId, $selectedStopId, $target, 30);
            $multiHorizons = $this->predictionService->getMultiHorizonPredictions($selectedRouteId, $selectedStopId);
        }

        $pageTitle = 'Crowd Level Predictions';
        require dirname(__DIR__, 2) . '/views/predictions/predict.php';
    }

    public function compare(): void
    {
        $routes = $this->routeService->getAllRoutes(['active' => true]);
        $stops = $this->stopService->getAllStops();

        $selectedRouteId = $_GET['route_id'] ?? ($routes[0]['_id'] ?? '');
        $selectedStopId = $_GET['stop_id'] ?? '';

        if ($selectedRouteId !== '' && $selectedStopId === '') {
            $route = $this->routeService->getRouteById($selectedRouteId, true);
            $selectedStopId = $route['stops'][0]['_id'] ?? '';
        }

        $customTimes = $_GET['times'] ?? ['08:00', '08:30', '09:00', '09:30', '10:00'];
        if (is_string($customTimes)) {
            $customTimes = array_map('trim', explode(',', $customTimes));
        }

        $comparison = null;
        if ($selectedRouteId !== '' && $selectedStopId !== '') {
            $comparison = $this->predictionService->compareDepartureTimes($selectedRouteId, $selectedStopId, $customTimes);
        }

        $pageTitle = 'Departure Time Crowd Comparison';
        require dirname(__DIR__, 2) . '/views/predictions/compare.php';
    }

    public function apiPredict(): void
    {
        header('Content-Type: application/json');
        $routeId = $_GET['route_id'] ?? '';
        $stopId = $_GET['stop_id'] ?? '';
        $horizon = (int)($_GET['horizon'] ?? 30);
        $timeStr = $_GET['time'] ?? null;

        if ($routeId === '' || $stopId === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'route_id and stop_id parameters are required.']);
            return;
        }

        $target = null;
        if ($timeStr !== null) {
            try {
                $target = new DateTime($timeStr);
            } catch (Exception) {
                $target = null;
            }
        }

        $result = $this->predictionService->predict($routeId, $stopId, $target, $horizon);
        echo json_encode(['success' => true, 'data' => $result]);
    }

    public function apiCompare(): void
    {
        header('Content-Type: application/json');
        $routeId = $_GET['route_id'] ?? '';
        $stopId = $_GET['stop_id'] ?? '';
        $times = $_GET['times'] ?? ['08:00', '08:30', '09:00'];

        if (is_string($times)) {
            $times = array_map('trim', explode(',', $times));
        }

        if ($routeId === '' || $stopId === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'route_id and stop_id are required.']);
            return;
        }

        $result = $this->predictionService->compareDepartureTimes($routeId, $stopId, $times);
        echo json_encode(['success' => true, 'data' => $result]);
    }
}
