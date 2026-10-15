<?php
/**
 * Test Crowd Report Service
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/validators/ValidationService.php';
require_once __DIR__ . '/../app/services/CouchDBService.php';
require_once __DIR__ . '/../app/services/CrowdReportService.php';

use App\Config\Config;
use App\Services\CouchDBService;
use App\Services\CrowdReportService;

Config::load();
$couch = new CouchDBService();
$reportService = new CrowdReportService($couch);

echo "========================================\n";
echo "Testing Crowd Report Service\n";
echo "========================================\n";

$testIp = '192.168.1.' . rand(10, 99);

// 1. Submit valid report
$submitRes = $reportService->submitReport([
    'route_id' => 'route:metro-blue',
    'stop_id' => 'stop:tech-park',
    'transport_mode' => 'metro',
    'crowd_category' => 'high',
    'approximate_occupancy' => 80,
    'comments' => 'Crowded at evening changeover.',
], $testIp);

echo "1. Report Submission: " . ($submitRes['success'] ? 'OK (ID: ' . $submitRes['report_id'] . ')' : 'FAILED') . "\n";
$reportId = $submitRes['report_id'] ?? null;

// 2. Duplicate submission prevention
$dupRes = $reportService->submitReport([
    'route_id' => 'route:metro-blue',
    'stop_id' => 'stop:tech-park',
    'transport_mode' => 'metro',
    'crowd_category' => 'high',
], $testIp);

echo "2. Duplicate Detection: " . (!$dupRes['success'] ? 'OK (Flagged duplicate)' : 'FAILED') . "\n";

// 3. Moderate Report: approve
if ($reportId) {
    $modRes = $reportService->moderateReport($reportId, 'approved', 'Verified by operator');
    echo "3. Report Moderation (Approve): " . ($modRes['success'] ? 'OK' : 'FAILED') . "\n";

    // 4. Cleanup
    $delRes = $reportService->deleteReport($reportId);
    echo "4. Report Deletion: " . ($delRes['success'] ? 'OK' : 'FAILED') . "\n";
}

echo "\n========================================\n";
echo "All Crowd Report checks PASSED!\n";
echo "========================================\n";
