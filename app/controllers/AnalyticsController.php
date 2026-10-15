<?php
/**
 * AnalyticsController - Crowd trends, hourly charts, and historical analytics
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Services\CouchDBService;
use App\Services\AnalyticsService;

class AnalyticsController
{
    private AnalyticsService $analytics;

    public function __construct()
    {
        $couch = new CouchDBService();
        $this->analytics = new AnalyticsService($couch);
    }

    public function index(): void
    {
        $metrics = $this->analytics->getOverviewMetrics();
        $hourly = $this->analytics->getHourlyCrowdPattern();
        $weekdayWeekend = $this->analytics->getWeekdayVsWeekendTrends();
        $categories = $this->analytics->getCategoryDistribution();
        $routeSummaries = $this->analytics->getRouteCrowdSummaries();

        $pageTitle = 'Crowd Analytics & Historical Trends';
        require dirname(__DIR__, 2) . '/views/analytics/index.php';
    }

    public function apiHourly(): void
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $this->analytics->getHourlyCrowdPattern()]);
    }

    public function apiWeekdayWeekend(): void
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $this->analytics->getWeekdayVsWeekendTrends()]);
    }

    public function apiCategories(): void
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $this->analytics->getCategoryDistribution()]);
    }
}
