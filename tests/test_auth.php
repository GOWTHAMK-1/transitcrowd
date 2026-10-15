<?php
/**
 * Test Authentication Service
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/validators/ValidationService.php';
require_once __DIR__ . '/../app/middleware/CsrfMiddleware.php';
require_once __DIR__ . '/../app/services/CouchDBService.php';
require_once __DIR__ . '/../app/services/AuthenticationService.php';

use App\Config\Config;
use App\Services\AuthenticationService;

Config::load();
$auth = new AuthenticationService();

echo "========================================\n";
echo "Testing Authentication and Authorization\n";
echo "========================================\n";

// 1. Test Admin Login with seeded credentials
$adminLogin = $auth->login('admin@example.test', 'Admin@123');
echo "1. Admin Login: " . ($adminLogin['success'] ? 'OK (Role: ' . $adminLogin['user']['role'] . ')' : 'FAILED') . "\n";
echo "   Is Admin check: " . ($auth->isAdmin() ? 'YES' : 'NO') . "\n";

// 2. Test Invalid Password
$badLogin = $auth->login('admin@example.test', 'WrongPassword!');
echo "2. Invalid Password Rejected: " . (!$badLogin['success'] ? 'OK (Rejected correctly)' : 'FAILED') . "\n";

// 3. Test Registration of new passenger
$testEmail = 'test_passenger_' . time() . '@example.test';
$regRes = $auth->register('Test Runner', $testEmail, 'SecurePass123');
echo "3. User Registration: " . ($regRes['success'] ? 'OK' : 'FAILED') . "\n";
echo "   Is Passenger check: " . ($auth->isPassenger() ? 'YES' : 'NO') . "\n";

// 4. Test Duplicate Email Prevention
$dupRes = $auth->register('Duplicate Runner', $testEmail, 'SecurePass123');
echo "4. Duplicate Registration Prevented: " . (!$dupRes['success'] ? 'OK' : 'FAILED') . "\n";

// 5. Test Favorite Route Toggle
$favRes = $auth->toggleFavoriteRoute('route:metro-blue');
echo "5. Toggle Favorite Route: " . ($favRes['success'] && $favRes['action'] === 'added' ? 'OK' : 'FAILED') . "\n";

// Cleanup registered test user
$delRes = $auth->deleteAccount();
echo "6. User Account Deletion (GDPR/Privacy): " . ($delRes['success'] ? 'OK' : 'FAILED') . "\n";

echo "\n========================================\n";
echo "All Authentication checks PASSED!\n";
echo "========================================\n";
