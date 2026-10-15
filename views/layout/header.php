<?php
/**
 * Shared Application Header Layout
 */

use App\Middleware\CsrfMiddleware;
use App\Config\Config;

$appName = Config::get('APP_NAME', 'Public Transport Crowd Predictor');
$currentUser = $_SESSION['user'] ?? null;
$isAdmin = ($currentUser['role'] ?? '') === 'admin';
$csrfToken = CsrfMiddleware::getToken();
$currentUri = $_SERVER['REQUEST_URI'] ?? '/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Predict and monitor public transport crowd levels at stops, stations, and routes using Apache CouchDB and explainable analytics.">
    <title><?= htmlspecialchars($pageTitle ?? $appName) ?> - <?= htmlspecialchars($appName) ?></title>
    
    <!-- CSS Assets -->
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <!-- Leaflet Map CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
</head>
<body>
    <header class="app-header">
        <div class="container header-container">
            <a href="/" class="brand-logo" title="TransitCrowd Home">
                <div class="logo-badge">🚍</div>
                <div class="brand-text">
                    <strong>TransitCrowd</strong>
                    <span class="brand-sub">Crowd Predictor</span>
                </div>
            </a>

            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation menu">
                <span></span><span></span><span></span>
            </button>

            <nav class="nav-menu" id="navMenu">
                <a href="/routes" class="nav-link <?= str_starts_with($currentUri, '/routes') ? 'active' : '' ?>">Routes & Stops</a>
                <a href="/predictions" class="nav-link <?= ($currentUri === '/predictions') ? 'active' : '' ?>">Predictions</a>
                <a href="/predictions/compare" class="nav-link <?= str_starts_with($currentUri, '/predictions/compare') ? 'active' : '' ?>">Compare Times</a>
                <a href="/reports/submit" class="nav-link <?= str_starts_with($currentUri, '/reports/submit') ? 'active' : '' ?>">Report Crowd</a>
                <a href="/analytics" class="nav-link <?= str_starts_with($currentUri, '/analytics') ? 'active' : '' ?>">Analytics</a>

                <div class="nav-auth">
                    <?php if ($currentUser): ?>
                        <div class="user-dropdown">
                            <button class="btn btn-outline btn-sm user-btn" id="userMenuBtn">
                                👤 <?= htmlspecialchars($currentUser['name']) ?> (<?= ucfirst($currentUser['role']) ?>) ▾
                            </button>
                            <div class="dropdown-content" id="userMenuDropdown">
                                <?php if ($isAdmin): ?>
                                    <a href="/admin" class="dropdown-item">⚙️ Admin Console</a>
                                    <a href="/admin/routes" class="dropdown-item">Manage Routes</a>
                                    <a href="/admin/stops" class="dropdown-item">Manage Stops</a>
                                    <a href="/admin/reports" class="dropdown-item">Review Reports</a>
                                <?php else: ?>
                                    <a href="/dashboard" class="dropdown-item">📊 My Dashboard</a>
                                <?php endif; ?>
                                <hr class="dropdown-divider">
                                <a href="/logout" class="dropdown-item" style="color: var(--crowd-high);">Sign Out</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="/login" class="btn btn-outline btn-sm">Sign In</a>
                        <a href="/register" class="btn btn-primary btn-sm">Register</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <?php if (!empty($_SESSION['flash_success'])): ?>
                <div class="alert alert-success">
                    <span style="font-size: 1.25rem;">✓</span>
                    <div><?= htmlspecialchars($_SESSION['flash_success']) ?></div>
                    <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                </div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['flash_error'])): ?>
                <div class="alert alert-danger">
                    <span style="font-size: 1.25rem;">⚠️</span>
                    <div><?= htmlspecialchars($_SESSION['flash_error']) ?></div>
                    <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['flash_errors'])): ?>
                <div class="alert alert-danger">
                    <span style="font-size: 1.25rem;">⚠️</span>
                    <div>
                        <strong>Please resolve the following issues:</strong>
                        <ul style="margin-top: 0.35rem; padding-left: 1.25rem;">
                            <?php foreach ($_SESSION['flash_errors'] as $errGroup): ?>
                                <?php foreach ((array)$errGroup as $err): ?>
                                    <li><?= htmlspecialchars($err) ?></li>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                </div>
                <?php unset($_SESSION['flash_errors']); ?>
            <?php endif; ?>
        </div>
