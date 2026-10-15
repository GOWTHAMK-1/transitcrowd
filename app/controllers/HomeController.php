<?php
/**
 * HomeController - Landing page and introductory information
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CouchDBService;
use App\Services\RouteService;
use App\Services\StopService;
use App\Services\AnalyticsService;
use App\Services\CrowdReportService;

class HomeController
{
    private RouteService $routeService;
    private StopService $stopService;
    private AnalyticsService $analytics;
    private CrowdReportService $reportService;

    public function __construct()
    {
        $couch = new CouchDBService();
        $this->stopService = new StopService($couch);
        $this->routeService = new RouteService($couch, $this->stopService);
        $this->analytics = new AnalyticsService($couch);
        $this->reportService = new CrowdReportService($couch);
    }

    public function index(): void
    {
        $routes = $this->routeService->getAllRoutes(['active' => true]);
        $stops = $this->stopService->getAllStops();
        $metrics = $this->analytics->getOverviewMetrics();
        $recentReports = $this->reportService->getReports(['status' => 'approved'], 4);

        $pageTitle = 'Travel Smarter. Avoid the Crowds.';
        require dirname(__DIR__, 2) . '/views/home/index.php';
    }
}
