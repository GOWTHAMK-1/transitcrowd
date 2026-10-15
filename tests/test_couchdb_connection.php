<?php
/**
 * Test CouchDB Connectivity and Basic Operations
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/services/CouchDBService.php';

use App\Config\Config;
use App\Services\CouchDBService;

Config::load();
$couch = new CouchDBService();

echo "========================================\n";
echo "Testing Apache CouchDB Connectivity\n";
echo "========================================\n";

$connTest = $couch->testConnection();
echo "Status Code: " . $connTest['status_code'] . "\n";
echo "Success: " . ($connTest['success'] ? 'YES' : 'NO') . "\n";
echo "Message: " . $connTest['message'] . "\n";
if ($connTest['success']) {
    echo "CouchDB Version: " . $connTest['version'] . "\n";
} else {
    echo "FAILED: Cannot connect to CouchDB.\n";
    exit(1);
}

// Test temporary database creation, doc insert, retrieval, deletion
$testDb = 'transport_test_' . time();
echo "\nTesting Database Operations on temporary DB: {$testDb}...\n";

$createDbRes = $couch->createDb($testDb);
echo "1. createDb: " . ($createDbRes['ok'] ? 'OK' : 'FAILED') . "\n";

$doc = [
    '_id' => 'test:doc1',
    'type' => 'test',
    'title' => 'CouchDB Connectivity Verification',
    'timestamp' => date('c'),
];

$saveDocRes = $couch->saveDocument($testDb, $doc);
echo "2. saveDocument: " . ($saveDocRes['ok'] ? 'OK' : 'FAILED') . " (rev: " . ($saveDocRes['body']['rev'] ?? 'none') . ")\n";

$retrieved = $couch->getDocument($testDb, 'test:doc1');
echo "3. getDocument: " . ($retrieved !== null && $retrieved['title'] === $doc['title'] ? 'OK' : 'FAILED') . "\n";

// Test Mango query
$queryRes = $couch->find($testDb, [
    'selector' => ['type' => 'test'],
]);
echo "4. find (Mango Query): " . (count($queryRes) === 1 ? 'OK (Found 1 doc)' : 'FAILED') . "\n";

// Cleanup test db
$deleteDbRes = $couch->deleteDb($testDb);
echo "5. deleteDb (cleanup): " . ($deleteDbRes['ok'] ? 'OK' : 'FAILED') . "\n";

echo "\n========================================\n";
echo "All CouchDB connectivity checks PASSED!\n";
echo "========================================\n";
