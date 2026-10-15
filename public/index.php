<?php
/**
 * Public Transport Crowd Predictor - Front Controller & Application Router
 */

declare(strict_types=1);

// Autoload all classes cleanly
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Config\Config;
use App\Middleware\CsrfMiddleware;
use App\Controllers\HomeController;
use App\Controllers\RouteController;
use App\Controllers\PredictionController;
use App\Controllers\ReportController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\AnalyticsController;
use App\Controllers\AdminController;

Config::load();
CsrfMiddleware::initSession();

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$rawMethod = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$method = ($rawMethod === 'HEAD') ? 'GET' : $rawMethod;

// Route matching
try {
    // 1. Static and Public Views
    if ($uri === '/' && $method === 'GET') {
        (new HomeController())->index();
        exit;
    }

    if ($uri === '/routes' && $method === 'GET') {
        (new RouteController())->index();
        exit;
    }

    if (preg_match('#^/routes/([^/]+)$#', $uri, $matches) && $method === 'GET') {
        (new RouteController())->detail(urldecode($matches[1]));
        exit;
    }

    if (preg_match('#^/stops/([^/]+)$#', $uri, $matches) && $method === 'GET') {
        (new RouteController())->stopDetail(urldecode($matches[1]));
        exit;
    }

    if ($uri === '/predictions' && $method === 'GET') {
        (new PredictionController())->index();
        exit;
    }

    if ($uri === '/predictions/compare' && $method === 'GET') {
        (new PredictionController())->compare();
        exit;
    }

    if ($uri === '/reports/submit' && $method === 'GET') {
        (new ReportController())->index();
        exit;
    }

    if ($uri === '/reports/submit' && $method === 'POST') {
        (new ReportController())->submit();
        exit;
    }

    if ($uri === '/dashboard' && $method === 'GET') {
        (new DashboardController())->index();
        exit;
    }

    if ($uri === '/analytics' && $method === 'GET') {
        (new AnalyticsController())->index();
        exit;
    }

    // 2. Authentication
    if ($uri === '/login' && $method === 'GET') {
        (new AuthController())->showLogin();
        exit;
    }

    if ($uri === '/login' && $method === 'POST') {
        (new AuthController())->login();
        exit;
    }

    if ($uri === '/register' && $method === 'GET') {
        (new AuthController())->showRegister();
        exit;
    }

    if ($uri === '/register' && $method === 'POST') {
        (new AuthController())->register();
        exit;
    }

    if ($uri === '/logout') {
        (new AuthController())->logout();
        exit;
    }

    if ($uri === '/account/delete' && $method === 'POST') {
        (new AuthController())->deleteAccount();
        exit;
    }

    // 3. Admin Routes
    if ($uri === '/admin' && $method === 'GET') {
        (new AdminController())->index();
        exit;
    }

    if ($uri === '/admin/routes' && $method === 'GET') {
        (new AdminController())->routes();
        exit;
    }

    if ($uri === '/admin/routes/create' && $method === 'POST') {
        (new AdminController())->createRoute();
        exit;
    }

    if ($uri === '/admin/routes/delete' && $method === 'POST') {
        (new AdminController())->deleteRoute($_POST['route_id'] ?? '');
        exit;
    }

    if ($uri === '/admin/stops' && $method === 'GET') {
        (new AdminController())->stops();
        exit;
    }

    if ($uri === '/admin/stops/create' && $method === 'POST') {
        (new AdminController())->createStop();
        exit;
    }

    if ($uri === '/admin/stops/delete' && $method === 'POST') {
        (new AdminController())->deleteStop($_POST['stop_id'] ?? '');
        exit;
    }

    if ($uri === '/admin/reports' && $method === 'GET') {
        (new AdminController())->reports();
        exit;
    }

    if ($uri === '/admin/reports/moderate' && $method === 'POST') {
        (new AdminController())->moderateReport();
        exit;
    }

    // 4. REST JSON API Endpoints
    if ($uri === '/api/routes' && $method === 'GET') {
        (new RouteController())->apiListRoutes();
        exit;
    }

    if ($uri === '/api/stops' && $method === 'GET') {
        (new RouteController())->apiListStops();
        exit;
    }

    if ($uri === '/api/predict' && $method === 'GET') {
        (new PredictionController())->apiPredict();
        exit;
    }

    if ($uri === '/api/predict/compare' && $method === 'GET') {
        (new PredictionController())->apiCompare();
        exit;
    }

    if ($uri === '/api/reports' && $method === 'GET') {
        header('Content-Type: application/json');
        $couch = new \App\Services\CouchDBService();
        $repService = new \App\Services\CrowdReportService($couch);
        echo json_encode(['success' => true, 'data' => $repService->getReports([], 20)]);
        exit;
    }

    if ($uri === '/api/reports/submit' && $method === 'POST') {
        (new ReportController())->submit();
        exit;
    }

    if ($uri === '/api/favorites/route' && $method === 'POST') {
        (new AuthController())->toggleFavoriteRoute();
        exit;
    }

    if ($uri === '/api/favorites/stop' && $method === 'POST') {
        (new AuthController())->toggleFavoriteStop();
        exit;
    }

    if ($uri === '/api/analytics/hourly' && $method === 'GET') {
        (new AnalyticsController())->apiHourly();
        exit;
    }

    if ($uri === '/api/analytics/weekday-weekend' && $method === 'GET') {
        (new AnalyticsController())->apiWeekdayWeekend();
        exit;
    }

    if ($uri === '/api/analytics/categories' && $method === 'GET') {
        (new AnalyticsController())->apiCategories();
        exit;
    }

    // 5. 404 Fallback
    http_response_code(404);
    $pageTitle = 'Page Not Found';
    require dirname(__DIR__) . '/views/errors/404.php';

} catch (Throwable $e) {
    http_response_code(500);
    if (Config::get('APP_DEBUG', false)) {
        echo '<h1>Internal Application Error (Debug Mode)</h1>';
        echo '<p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        echo '<h1>500 Internal Server Error</h1>';
        echo '<p>An unexpected error occurred. Please contact the administrator.</p>';
    }
}
