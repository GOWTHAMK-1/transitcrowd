<?php
/**
 * Master Comprehensive Test Suite
 * Tests all components and acceptance criteria of Public Transport Crowd Predictor.
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/validators/ValidationService.php';
require_once __DIR__ . '/../app/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../app/services/CouchDBService.php';
require_once __DIR__ . '/../app/services/StopService.php';
require_once __DIR__ . '/../app/services/RouteService.php';
require_once __DIR__ . '/../app/services/CrowdReportService.php';
require_once __DIR__ . '/../app/services/CrowdPredictionService.php';
require_once __DIR__ . '/../app/services/AuthenticationService.php';
require_once __DIR__ . '/../app/services/AnalyticsService.php';
require_once __DIR__ . '/../app/services/AdminService.php';

use App\Config\Config;
use App\Services\CouchDBService;
use App\Services\StopService;
use App\Services\RouteService;
use App\Services\CrowdReportService;
use App\Services\CrowdPredictionService;
use App\Services\AuthenticationService;
use App\Services\AnalyticsService;
use App\Services\AdminService;

Config::load();

$passedTests = 0;
$totalTests = 0;

function assertTest(string $description, bool $condition, string $detail = ''): void {
    global $passedTests, $totalTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo " [PASS] {$description}\n";
    } else {
        echo " [FAIL] {$description} (" . ($detail ?: 'Assertion failed') . ")\n";
    }
}

echo "====================================================================\n";
echo "   Public Transport Crowd Predictor - Master Automated Test Suite   \n";
echo "====================================================================\n\n";

$couch = new CouchDBService();
$stopService = new StopService($couch);
$routeService = new RouteService($couch, $stopService);
$reportService = new CrowdReportService($couch);
$predService = new CrowdPredictionService($couch, $routeService, $stopService);
$authService = new AuthenticationService($couch);
$analyticsService = new AnalyticsService($couch);
$adminService = new AdminService($couch, $analyticsService);

// 1. CouchDB Connectivity
$conn = $couch->testConnection();
assertTest('1. CouchDB Connectivity & Version Check', $conn['success'] === true && !empty($conn['version']));

// 2. Database existence
$requiredDbs = ['routes', 'stops', 'observations', 'reports', 'users', 'predictions'];
$allExist = true;
foreach ($requiredDbs as $key) {
    if (!$couch->dbExists($couch->getDatabaseName($key))) {
        $allExist = false;
        break;
    }
}
assertTest('2. All 6 Required Application Databases Exist in CouchDB', $allExist);

// 3. User Authentication - Admin
$adminLogin = $authService->login('admin@example.test', 'Admin@123');
assertTest('3. Admin Authentication with Bcrypt Verification', $adminLogin['success'] === true && $adminLogin['user']['role'] === 'admin');

// 4. User Authentication - Password Rejection
$badLogin = $authService->login('admin@example.test', 'WrongPassword!');
assertTest('4. Invalid Password Rejected Securely', $badLogin['success'] === false);

// 5. Passenger Registration
$uniqueEmail = 'student_' . time() . '_' . rand(100, 999) . '@example.test';
$regRes = $authService->register('Test Student', $uniqueEmail, 'CollegePass@2026');
assertTest('5. Passenger Registration with Unique Email', $regRes['success'] === true && $regRes['user']['role'] === 'passenger');

// 6. Duplicate Registration Prevention
$dupReg = $authService->register('Test Student 2', $uniqueEmail, 'CollegePass@2026');
assertTest('6. Duplicate Email Registration Prevented', $dupReg['success'] === false);

// 7. Route Retrieval & Stop Population
$metroBlue = $routeService->getRouteById('route:metro-blue', true);
assertTest('7. Route Retrieval with Ordered Stop Sequence', $metroBlue !== null && count($metroBlue['stops']) >= 3);

// 8. Stop Search & Mode Filtering
$busStops = $stopService->getAllStops(['transport_mode' => 'bus']);
assertTest('8. Stop Filtering by Transport Mode', count($busStops) >= 2);

// 9. Finding Routes Between Stations
$between = $routeService->findRoutesBetweenStops('stop:central-station', 'stop:university-square');
assertTest('9. Origin-Destination Route Discovery', count($between) >= 1);

// 10. Crowd Report Submission & Rate Limiting
$testIp = '10.0.0.' . rand(10, 99);
$reportSubmit = $reportService->submitReport([
    'route_id' => 'route:metro-blue',
    'stop_id' => 'stop:tech-park',
    'transport_mode' => 'metro',
    'crowd_category' => 'moderate',
    'approximate_occupancy' => 50,
    'comments' => 'Automated test observation',
], $testIp);
assertTest('10. Voluntary Crowd Report Submission', $reportSubmit['success'] === true);

// 11. Duplicate Report Prevention
$dupReport = $reportService->submitReport([
    'route_id' => 'route:metro-blue',
    'stop_id' => 'stop:tech-park',
    'transport_mode' => 'metro',
    'crowd_category' => 'moderate',
], $testIp);
assertTest('11. Duplicate / Spam Report Prevention within 10-Minute Window', $dupReport['success'] === false);

// 12. Explainable Prediction Calculation
$pred = $predService->predict('route:metro-blue', 'stop:central-station', new DateTime('08:45'), 30);
assertTest('12. Explainable Prediction with Non-Empty Reasoning', !empty($pred['explanation']) && $pred['data_quality'] !== 'insufficient');

// 13. Insufficient Data Handling (Strict Prompt Rule)
$insufficientPred = $predService->predict('route:empty-sample', 'stop:empty-sample', null, 30);
assertTest(
    '13. Insufficient Data Handling Returns NULL Occupancy & "insufficient" Quality',
    $insufficientPred['data_quality'] === 'insufficient' &&
    $insufficientPred['estimated_occupancy_percent'] === null &&
    $insufficientPred['crowd_category'] === null
);

// 14. Departure Time Comparison
$compare = $predService->compareDepartureTimes('route:metro-blue', 'stop:central-station', ['08:00', '12:00', '18:30']);
assertTest('14. Departure Time Comparison & Optimal Window Recommendation', !empty($compare['options']) && !empty($compare['recommended_time']));

// 15. Analytics Aggregation from CouchDB
$analyticsOverview = $analyticsService->getOverviewMetrics();
assertTest('15. Analytics Dashboard Aggregates Actual CouchDB Records', $analyticsOverview['total_observations'] > 500);

// 16. Hourly Trend Calculation
$hourly = $analyticsService->getHourlyCrowdPattern();
assertTest('16. Hourly Crowd Trend Profile Computed (6:00 - 23:00)', count($hourly['labels']) === 18 && count($hourly['averages']) === 18);

// 17. Weekday vs Weekend Shift
$dowTrends = $analyticsService->getWeekdayVsWeekendTrends();
assertTest('17. Weekday Commute vs Weekend Leisure Patterns Computed', count($dowTrends['weekday']) === 18 && count($dowTrends['weekend']) === 18);

// 18. System Health and NoSQL Disk Statistics
$health = $adminService->getSystemHealth();
assertTest('18. System Health and CouchDB Storage Metrics Inspected', $health['couchdb']['status'] === 'online' && !empty($health['summary']['total_disk_size']));

// Clean up registered student user
$authService->deleteAccount();
// Clean up submitted report
if (!empty($reportSubmit['report_id'])) {
    $reportService->deleteReport($reportSubmit['report_id']);
}

echo "\n====================================================================\n";
echo " Test Results: {$passedTests} / {$totalTests} Passed (" . round(($passedTests / $totalTests) * 100) . "%)\n";
echo " Status: " . ($passedTests === $totalTests ? "ALL ACCEPTANCE CRITERIA MET" : "SOME TESTS FAILED") . "\n";
echo "====================================================================\n";

exit($passedTests === $totalTests ? 0 : 1);
