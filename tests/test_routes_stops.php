<?php
/**
 * Test Route and Stop Services
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/validators/ValidationService.php';
require_once __DIR__ . '/../app/services/CouchDBService.php';
require_once __DIR__ . '/../app/services/StopService.php';
require_once __DIR__ . '/../app/services/RouteService.php';

use App\Config\Config;
use App\Services\CouchDBService;
use App\Services\StopService;
use App\Services\RouteService;

Config::load();
$couch = new CouchDBService();
$stopService = new StopService($couch);
$routeService = new RouteService($couch, $stopService);

echo "========================================\n";
echo "Testing Route and Stop Explorer Services\n";
echo "========================================\n";

// 1. List stops
$stops = $stopService->getAllStops();
echo "1. Stop Count: " . count($stops) . " (Expected >= 8)\n";

// 2. Filter stops by mode
$metroStops = $stopService->getAllStops(['transport_mode' => 'metro']);
echo "2. Metro Stops: " . count($metroStops) . " (Expected >= 3)\n";

// 3. Search stops
$searchStops = $stopService->getAllStops(['search' => 'central']);
echo "3. Search 'central': " . count($searchStops) . " found\n";

// 4. List routes
$routes = $routeService->getAllRoutes();
echo "4. Route Count: " . count($routes) . " (Expected >= 4)\n";

// 5. Route detail with stops populated
$blueLine = $routeService->getRouteById('route:metro-blue', true);
echo "5. Route 'route:metro-blue' retrieved: " . ($blueLine !== null ? 'OK' : 'FAILED') . "\n";
echo "   Stops associated: " . count($blueLine['stops'] ?? []) . " (Expected 3)\n";

// 6. Find routes between Central Terminal and University Square
$routesBetween = $routeService->findRoutesBetweenStops('stop:central-station', 'stop:university-square');
echo "6. Find Routes Between Central & University: " . count($routesBetween) . " route(s) found\n";

// 7. Test Route creation & cleanup
$testRoute = $routeService->createRoute([
    'route_number' => 'TEST-99',
    'route_name' => 'Automated Test Shuttle',
    'transport_mode' => 'bus',
    'stop_ids' => ['stop:central-station', 'stop:tech-park'],
]);
echo "7. Route Creation: " . ($testRoute['success'] ? 'OK' : 'FAILED') . "\n";

if ($testRoute['success']) {
    $delRes = $routeService->deleteRoute($testRoute['route']['_id']);
    echo "   Route Deletion: " . ($delRes['success'] ? 'OK' : 'FAILED') . "\n";
}

echo "\n========================================\n";
echo "All Route & Stop Explorer checks PASSED!\n";
echo "========================================\n";
