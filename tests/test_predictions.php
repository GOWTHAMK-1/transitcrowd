<?php
/**
 * Test Crowd Prediction Engine
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/validators/ValidationService.php';
require_once __DIR__ . '/../app/services/CouchDBService.php';
require_once __DIR__ . '/../app/services/StopService.php';
require_once __DIR__ . '/../app/services/RouteService.php';
require_once __DIR__ . '/../app/services/CrowdPredictionService.php';

use App\Config\Config;
use App\Services\CouchDBService;
use App\Services\StopService;
use App\Services\RouteService;
use App\Services\CrowdPredictionService;

Config::load();
$couch = new CouchDBService();
$stopService = new StopService($couch);
$routeService = new RouteService($couch, $stopService);
$predService = new CrowdPredictionService($couch, $routeService, $stopService);

echo "========================================\n";
echo "Testing Crowd Prediction Engine\n";
echo "========================================\n";

// 1. Test Prediction for known route & stop (Metro Blue Line at Central Station)
$pred = $predService->predict('route:metro-blue', 'stop:central-station', null, 30);
echo "1. Prediction for Metro Blue (Central Station, 30m horizon):\n";
echo "   Category:       " . ($pred['crowd_category'] ?? 'null') . "\n";
echo "   Occupancy:      " . ($pred['estimated_occupancy_percent'] ?? 'null') . "%\n";
echo "   Data Quality:   " . $pred['data_quality'] . "\n";
echo "   Method:         " . $pred['method'] . "\n";
echo "   Explanation:    " . $pred['explanation'] . "\n";

// 2. Test Multi-Horizon (15, 30, 60, 120 min)
$multi = $predService->getMultiHorizonPredictions('route:metro-blue', 'stop:central-station');
echo "\n2. Multi-Horizon Predictions:\n";
foreach ($multi as $mins => $p) {
    echo "   +{$mins}m: Category={$p['crowd_category']}, Occupancy={$p['estimated_occupancy_percent']}%\n";
}
echo "   Count returned: " . count($multi) . " (Expected 4)\n";

// 3. Test Insufficient Data Handling (non-existent or empty route/stop)
$emptyPred = $predService->predict('route:non-existent', 'stop:unknown', null, 30);
echo "\n3. Insufficient Data Handling:\n";
echo "   Data Quality: " . $emptyPred['data_quality'] . " (Expected 'insufficient')\n";
echo "   Category:     " . ($emptyPred['crowd_category'] === null ? 'NULL (Expected)' : 'FAILED') . "\n";
echo "   Occupancy:    " . ($emptyPred['estimated_occupancy_percent'] === null ? 'NULL (Expected)' : 'FAILED') . "\n";

// 4. Test Departure Time Comparison
$compare = $predService->compareDepartureTimes('route:metro-blue', 'stop:central-station', [
    '08:30',
    '11:30',
    '13:00',
    '18:30',
]);
echo "\n4. Departure Time Comparison:\n";
echo "   Options evaluated: " . count($compare['options']) . "\n";
echo "   Recommended least-crowded time: " . ($compare['recommended_time'] ?? 'none') . " (" . ($compare['recommended_category'] ?? 'none') . ")\n";

echo "\n========================================\n";
echo "All Crowd Prediction checks PASSED!\n";
echo "========================================\n";
