<?php
/**
 * CrowdPredictionService
 * Explainable, rule-based & statistical crowd estimation and prediction engine.
 * Blends historical CouchDB observations, time-slot averages, peak-hour multipliers,
 * and recent validated crowd reports.
 */

declare(strict_types=1);

namespace App\Services;

use App\Config\Config;
use DateTime;
use DateTimeZone;
use Exception;

class CrowdPredictionService
{
    private CouchDBService $couch;
    private RouteService $routeService;
    private StopService $stopService;
    private string $obsDb;
    private string $reportsDb;
    private string $predDb;

    // Category thresholds
    private int $lowMax;
    private int $moderateMax;
    private int $highMax;
    private int $minSamplesReliable;

    public function __construct(
        ?CouchDBService $couch = null,
        ?RouteService $routeService = null,
        ?StopService $stopService = null
    ) {
        $this->couch = $couch ?? new CouchDBService();
        $this->stopService = $stopService ?? new StopService($this->couch);
        $this->routeService = $routeService ?? new RouteService($this->couch, $this->stopService);

        $this->obsDb = $this->couch->getDatabaseName('observations');
        $this->reportsDb = $this->couch->getDatabaseName('reports');
        $this->predDb = $this->couch->getDatabaseName('predictions');

        $this->lowMax = (int)Config::get('CROWD_LOW_MAX', 30);
        $this->moderateMax = (int)Config::get('CROWD_MODERATE_MAX', 60);
        $this->highMax = (int)Config::get('CROWD_HIGH_MAX', 80);
        $this->minSamplesReliable = (int)Config::get('PREDICTION_MIN_SAMPLES_RELIABLE', 3);
    }

    /**
     * Map numerical occupancy percentage to standard qualitative crowd category
     */
    public function occupancyToCategory(?float $occupancy): ?string
    {
        if ($occupancy === null) {
            return null;
        }
        $val = round($occupancy);
        if ($val <= $this->lowMax) {
            return 'low';
        }
        if ($val <= $this->moderateMax) {
            return 'moderate';
        }
        if ($val <= $this->highMax) {
            return 'high';
        }
        return 'very_high';
    }

    /**
     * Map category string to baseline median occupancy percentage
     */
    public function categoryToOccupancy(string $category): int
    {
        return match (strtolower($category)) {
            'low' => 20,
            'moderate' => 45,
            'high' => 70,
            'very_high' => 90,
            default => 50,
        };
    }

    /**
     * Predict crowd level for a specific route and stop at a given target time (or horizon)
     */
    public function predict(
        string $routeId,
        string $stopId,
        ?DateTime $targetTime = null,
        int $horizonMinutes = 30
    ): array {
        if ($targetTime === null) {
            $targetTime = new DateTime("+{$horizonMinutes} minutes");
        }

        $route = $this->routeService->getRouteById($routeId, false);
        $stop = $this->stopService->getStopById($stopId);

        if ($route === null || $stop === null) {
            return [
                'route_id' => $routeId,
                'stop_id' => $stopId,
                'prediction_time' => $targetTime->format('c'),
                'horizon_minutes' => $horizonMinutes,
                'crowd_category' => null,
                'estimated_occupancy_percent' => null,
                'method' => 'insufficient_metadata',
                'sample_count' => 0,
                'data_quality' => 'insufficient',
                'data_source' => 'none',
                'explanation' => 'Selected route or stop was not found in the database.',
            ];
        }

        // Normalize targetTime to UTC for absolute consistency
        $utcTz = new DateTimeZone('UTC');
        $targetUtc = clone $targetTime;
        $targetUtc->setTimezone($utcTz);

        $targetHourMin = $targetUtc->format('H:i');
        $targetMinuteOfDay = ((int)$targetUtc->format('H') * 60) + (int)$targetUtc->format('i');
        $targetDayOfWeek = (int)$targetUtc->format('N'); // 1 = Mon, 7 = Sun
        $isTargetWeekend = ($targetDayOfWeek >= 6);

        // 1. Fetch historical observations for this route and stop
        $observations = $this->couch->find($this->obsDb, [
            'selector' => [
                'type' => 'observation',
                'route_id' => $routeId,
                'stop_id' => $stopId,
            ],
            'limit' => 200,
        ]);

        // Filter observations matching the time window (+/- 45 mins) and day type (weekday vs weekend)
        $windowMinutes = (int)Config::get('PREDICTION_TIME_WINDOW_MINUTES', 45);
        $matchingObs = [];

        foreach ($observations as $obs) {
            if (empty($obs['observed_at'])) {
                continue;
            }

            try {
                $obsDate = new DateTime($obs['observed_at']);
                $obsDate->setTimezone($utcTz);
            } catch (Exception) {
                continue;
            }

            $obsDayOfWeek = (int)$obsDate->format('N');
            $isObsWeekend = ($obsDayOfWeek >= 6);

            // Match day type (weekend vs weekday)
            if ($isObsWeekend !== $isTargetWeekend) {
                continue;
            }

            // Match time window in UTC
            $obsMinuteOfDay = ((int)$obsDate->format('H') * 60) + (int)$obsDate->format('i');
            $diff = abs($targetMinuteOfDay - $obsMinuteOfDay);
            if ($diff > 720) {
                $diff = 1440 - $diff; // wrap around midnight
            }

            if ($diff <= $windowMinutes) {
                $occupancy = $obs['occupancy_percent'] ?? null;
                if ($occupancy === null && !empty($obs['crowd_category'])) {
                    $occupancy = $this->categoryToOccupancy($obs['crowd_category']);
                }
                if ($occupancy !== null) {
                    $matchingObs[] = [
                        'occupancy' => (float)$occupancy,
                        'observed_at' => $obs['observed_at'],
                        'weight' => 1.0 - ($diff / ($windowMinutes * 1.5)), // closer time gets higher weight
                    ];
                }
            }
        }

        $sampleCount = count($matchingObs);

        // 2. Fetch recent validated reports (last 60 minutes) to blend live passenger feedback
        $oneHourAgo = date('c', strtotime('-60 minutes'));
        $recentReports = $this->couch->find($this->reportsDb, [
            'selector' => [
                'type' => 'crowd_report',
                'route_id' => $routeId,
                'stop_id' => $stopId,
                'reported_at' => ['$gte' => $oneHourAgo],
                'status' => ['$in' => ['approved', 'pending']],
            ],
            'limit' => 20,
        ]);

        $recentReportCount = count($recentReports);
        $recentReportWeights = [];
        foreach ($recentReports as $rep) {
            $occ = $rep['approximate_occupancy'] ?? $this->categoryToOccupancy($rep['crowd_category'] ?? 'moderate');
            $recentReportWeights[] = (float)$occ;
        }

        // 3. Handle insufficient data case
        if ($sampleCount < 2 && $recentReportCount === 0) {
            return [
                'route_id' => $routeId,
                'stop_id' => $stopId,
                'route_name' => $route['route_name'] ?? '',
                'stop_name' => $stop['stop_name'] ?? '',
                'transport_mode' => $route['transport_mode'] ?? 'bus',
                'prediction_time' => $targetTime->format('c'),
                'horizon_minutes' => $horizonMinutes,
                'crowd_category' => null,
                'estimated_occupancy_percent' => null,
                'method' => 'historical_time_slot_average',
                'sample_count' => $sampleCount,
                'data_quality' => 'insufficient',
                'data_source' => 'historical observations',
                'explanation' => 'Not enough data to make a reliable prediction for this time window. Submit a crowd report or check another time slot.',
            ];
        }

        // 4. Calculate weighted historical average
        $historicalAvg = null;
        if ($sampleCount > 0) {
            $totalWeight = 0.0;
            $weightedSum = 0.0;
            foreach ($matchingObs as $item) {
                $weightedSum += ($item['occupancy'] * $item['weight']);
                $totalWeight += $item['weight'];
            }
            $historicalAvg = $totalWeight > 0 ? ($weightedSum / $totalWeight) : null;
        }

        // 5. Apply peak period multiplier if applicable
        $peakMultiplierApplied = 1.0;
        $isPeak = false;
        if (!empty($route['peak_hours']) && is_array($route['peak_hours'])) {
            foreach ($route['peak_hours'] as $peak) {
                if ($targetHourMin >= $peak['start'] && $targetHourMin <= $peak['end']) {
                    $isPeak = true;
                    // Apply peak multiplier if historical average didn't already reflect full peak
                    $mult = (float)($peak['multiplier'] ?? 1.2);
                    if ($historicalAvg !== null && $historicalAvg < 70) {
                        $peakMultiplierApplied = min(1.35, $mult);
                        $historicalAvg = min(100, $historicalAvg * $peakMultiplierApplied);
                    }
                    break;
                }
            }
        }

        // 6. Blend recent validated passenger reports if available (recency weighting)
        $finalOccupancy = $historicalAvg;
        $method = 'historical_time_slot_average';

        if ($recentReportCount > 0) {
            $avgRecent = array_sum($recentReportWeights) / count($recentReportWeights);
            if ($finalOccupancy !== null) {
                // Blend: 60% historical average, 40% recent passenger observation
                $finalOccupancy = ($finalOccupancy * 0.60) + ($avgRecent * 0.40);
                $method = 'hybrid_historical_and_recent_reports';
            } else {
                $finalOccupancy = $avgRecent;
                $method = 'recent_passenger_reports_average';
            }
        }

        $finalOccupancy = round(max(5, min(100, $finalOccupancy)));
        $crowdCat = $this->occupancyToCategory($finalOccupancy);

        // Determine data quality label
        $totalEvidence = $sampleCount + ($recentReportCount * 2);
        if ($totalEvidence >= 6) {
            $dataQuality = 'reliable';
        } elseif ($totalEvidence >= 3) {
            $dataQuality = 'moderate';
        } else {
            $dataQuality = 'low';
        }

        // Explanation text for transparency
        $explanationParts = [];
        if ($sampleCount > 0) {
            $dayType = $isTargetWeekend ? 'weekend' : 'weekday';
            $explanationParts[] = "Calculated from {$sampleCount} historical observations for this {$dayType} time window";
        }
        if ($isPeak) {
            $explanationParts[] = "factored in scheduled peak-hour commuter flow";
        }
        if ($recentReportCount > 0) {
            $explanationParts[] = "blended with {$recentReportCount} recent passenger report(s)";
        }
        $explanation = implode(', ', $explanationParts) . '.';

        $result = [
            'route_id' => $routeId,
            'stop_id' => $stopId,
            'route_name' => $route['route_name'] ?? '',
            'stop_name' => $stop['stop_name'] ?? '',
            'transport_mode' => $route['transport_mode'] ?? 'bus',
            'prediction_time' => $targetTime->format('c'),
            'horizon_minutes' => $horizonMinutes,
            'crowd_category' => $crowdCat,
            'estimated_occupancy_percent' => (int)$finalOccupancy,
            'method' => $method,
            'sample_count' => $sampleCount + $recentReportCount,
            'data_quality' => $dataQuality,
            'data_source' => ($recentReportCount > 0) ? 'historical observations & passenger reports' : 'historical observations',
            'explanation' => ucfirst($explanation),
            'vehicle_capacity' => $route['vehicle_capacity'] ?? null,
            'is_peak' => $isPeak,
        ];

        // Audit/cache prediction record in predictions database
        $predId = 'pred:' . bin2hex(random_bytes(6));
        $cacheDoc = array_merge($result, [
            '_id' => $predId,
            'type' => 'prediction',
            'created_at' => date('c'),
        ]);
        $this->couch->saveDocument($this->predDb, $cacheDoc, $predId);

        return $result;
    }

    /**
     * Get crowd predictions across multiple standard horizons (15, 30, 60, 120 minutes)
     */
    public function getMultiHorizonPredictions(string $routeId, string $stopId): array
    {
        $horizons = [15, 30, 60, 120];
        $results = [];

        foreach ($horizons as $mins) {
            $target = new DateTime("+{$mins} minutes");
            $results[$mins] = $this->predict($routeId, $stopId, $target, $mins);
        }

        return $results;
    }

    /**
     * Compare predicted crowd levels across custom departure times
     * e.g., 08:00 AM, 08:30 AM, 09:00 AM, 09:30 AM
     */
    public function compareDepartureTimes(string $routeId, string $stopId, array $times): array
    {
        $comparisons = [];

        foreach ($times as $timeStr) {
            try {
                $target = new DateTime($timeStr);
            } catch (Exception) {
                continue;
            }

            $pred = $this->predict($routeId, $stopId, $target, 0);
            $comparisons[] = [
                'time' => $target->format('H:i'),
                'time_full' => $target->format('g:i A'),
                'crowd_category' => $pred['crowd_category'],
                'occupancy_percent' => $pred['estimated_occupancy_percent'],
                'data_quality' => $pred['data_quality'],
                'explanation' => $pred['explanation'],
            ];
        }

        // Recommend least crowded option
        $validOptions = array_filter($comparisons, fn($c) => $c['occupancy_percent'] !== null);
        $bestOption = null;
        if (!empty($validOptions)) {
            usort($validOptions, fn($a, $b) => $a['occupancy_percent'] <=> $b['occupancy_percent']);
            $bestOption = $validOptions[0];
        }

        return [
            'route_id' => $routeId,
            'stop_id' => $stopId,
            'options' => $comparisons,
            'recommended_time' => $bestOption['time_full'] ?? null,
            'recommended_category' => $bestOption['crowd_category'] ?? null,
            'disclaimer' => 'Predictions are statistical estimates based on historical patterns and cannot guarantee seat availability.',
        ];
    }
}
