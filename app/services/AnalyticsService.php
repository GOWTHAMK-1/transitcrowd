<?php
/**
 * AnalyticsService - Aggregates historical transport observations,
 * crowd reports, and trends for charts and analytics.
 */

declare(strict_types=1);

namespace App\Services;

use App\Config\Config;
use DateTime;
use Exception;

class AnalyticsService
{
    private CouchDBService $couch;
    private string $obsDb;
    private string $reportsDb;
    private string $routesDb;
    private string $stopsDb;

    public function __construct(?CouchDBService $couch = null)
    {
        $this->couch = $couch ?? new CouchDBService();
        $this->obsDb = $this->couch->getDatabaseName('observations');
        $this->reportsDb = $this->couch->getDatabaseName('reports');
        $this->routesDb = $this->couch->getDatabaseName('routes');
        $this->stopsDb = $this->couch->getDatabaseName('stops');
    }

    /**
     * Get hourly crowd profile (0-23 hours) based on actual historical observations
     */
    public function getHourlyCrowdPattern(): array
    {
        $docs = $this->couch->find($this->obsDb, [
            'selector' => ['type' => 'observation'],
            'limit' => 1000,
        ]);

        $hours = array_fill(0, 24, ['count' => 0, 'sum' => 0]);

        foreach ($docs as $doc) {
            if (empty($doc['observed_at'])) {
                continue;
            }
            try {
                $d = new DateTime($doc['observed_at']);
                $h = (int)$d->format('H');
                $occ = $doc['occupancy_percent'] ?? null;
                if ($occ !== null) {
                    $hours[$h]['count']++;
                    $hours[$h]['sum'] += (float)$occ;
                }
            } catch (Exception) {
                continue;
            }
        }

        $labels = [];
        $averages = [];
        $sampleCounts = [];

        for ($i = 6; $i <= 23; $i++) {
            $labels[] = sprintf('%02d:00', $i);
            $avg = $hours[$i]['count'] > 0 ? round($hours[$i]['sum'] / $hours[$i]['count']) : 0;
            $averages[] = (int)$avg;
            $sampleCounts[] = $hours[$i]['count'];
        }

        return [
            'labels' => $labels,
            'averages' => $averages,
            'sample_counts' => $sampleCounts,
        ];
    }

    /**
     * Compare weekday vs weekend crowd trends
     */
    public function getWeekdayVsWeekendTrends(): array
    {
        $docs = $this->couch->find($this->obsDb, [
            'selector' => ['type' => 'observation'],
            'limit' => 1000,
        ]);

        $weekdayHours = array_fill(0, 24, ['count' => 0, 'sum' => 0]);
        $weekendHours = array_fill(0, 24, ['count' => 0, 'sum' => 0]);

        foreach ($docs as $doc) {
            if (empty($doc['observed_at'])) {
                continue;
            }
            try {
                $d = new DateTime($doc['observed_at']);
                $h = (int)$d->format('H');
                $dow = (int)$d->format('N');
                $isWeekend = ($dow >= 6);
                $occ = $doc['occupancy_percent'] ?? null;

                if ($occ !== null) {
                    if ($isWeekend) {
                        $weekendHours[$h]['count']++;
                        $weekendHours[$h]['sum'] += (float)$occ;
                    } else {
                        $weekdayHours[$h]['count']++;
                        $weekdayHours[$h]['sum'] += (float)$occ;
                    }
                }
            } catch (Exception) {
                continue;
            }
        }

        $labels = [];
        $weekdayAvgs = [];
        $weekendAvgs = [];

        for ($i = 6; $i <= 23; $i++) {
            $labels[] = sprintf('%02d:00', $i);
            $weekdayAvgs[] = $weekdayHours[$i]['count'] > 0 ? (int)round($weekdayHours[$i]['sum'] / $weekdayHours[$i]['count']) : 0;
            $weekendAvgs[] = $weekendHours[$i]['count'] > 0 ? (int)round($weekendHours[$i]['sum'] / $weekendHours[$i]['count']) : 0;
        }

        return [
            'labels' => $labels,
            'weekday' => $weekdayAvgs,
            'weekend' => $weekendAvgs,
        ];
    }

    /**
     * Distribution of crowd categories across all observations
     */
    public function getCategoryDistribution(): array
    {
        $docs = $this->couch->find($this->obsDb, [
            'selector' => ['type' => 'observation'],
            'limit' => 1000,
        ]);

        $counts = [
            'low' => 0,
            'moderate' => 0,
            'high' => 0,
            'very_high' => 0,
        ];

        foreach ($docs as $doc) {
            $cat = strtolower((string)($doc['crowd_category'] ?? ''));
            if (isset($counts[$cat])) {
                $counts[$cat]++;
            }
        }

        $total = array_sum($counts);
        $percentages = [];
        foreach ($counts as $k => $c) {
            $percentages[$k] = $total > 0 ? round(($c / $total) * 100, 1) : 0;
        }

        return [
            'counts' => $counts,
            'percentages' => $percentages,
            'total' => $total,
        ];
    }

    /**
     * Route-wise average crowd level and observation count
     */
    public function getRouteCrowdSummaries(): array
    {
        $routes = $this->couch->find($this->routesDb, ['selector' => ['type' => 'route']]);
        $observations = $this->couch->find($this->obsDb, ['selector' => ['type' => 'observation'], 'limit' => 1000]);

        $routeStats = [];
        foreach ($routes as $r) {
            $routeStats[$r['_id']] = [
                'route_id' => $r['_id'],
                'route_number' => $r['route_number'] ?? '',
                'route_name' => $r['route_name'] ?? '',
                'transport_mode' => $r['transport_mode'] ?? 'bus',
                'count' => 0,
                'sum' => 0,
            ];
        }

        foreach ($observations as $obs) {
            $rid = $obs['route_id'] ?? null;
            $occ = $obs['occupancy_percent'] ?? null;
            if ($rid && isset($routeStats[$rid]) && $occ !== null) {
                $routeStats[$rid]['count']++;
                $routeStats[$rid]['sum'] += (float)$occ;
            }
        }

        $results = [];
        foreach ($routeStats as $rid => $stat) {
            $avg = $stat['count'] > 0 ? (int)round($stat['sum'] / $stat['count']) : null;
            $results[] = [
                'route_id' => $rid,
                'route_number' => $stat['route_number'],
                'route_name' => $stat['route_name'],
                'transport_mode' => $stat['transport_mode'],
                'average_occupancy' => $avg,
                'observations_count' => $stat['count'],
                'has_sufficient_data' => ($stat['count'] >= 5),
            ];
        }

        return $results;
    }

    /**
     * System-wide overview metrics for dashboard
     */
    public function getOverviewMetrics(): array
    {
        $routes = $this->couch->find($this->routesDb, ['selector' => ['type' => 'route']]);
        $stops = $this->couch->find($this->stopsDb, ['selector' => ['type' => 'stop']]);
        $obs = $this->couch->find($this->obsDb, ['selector' => ['type' => 'observation'], 'limit' => 1000]);
        $pendingReports = $this->couch->find($this->reportsDb, [
            'selector' => ['type' => 'crowd_report', 'status' => 'pending'],
        ]);
        $allReports = $this->couch->find($this->reportsDb, [
            'selector' => ['type' => 'crowd_report'],
        ]);

        return [
            'total_routes' => count($routes),
            'total_stops' => count($stops),
            'total_observations' => count($obs),
            'pending_reports' => count($pendingReports),
            'total_reports' => count($allReports),
        ];
    }
}
