<?php
require dirname(__DIR__) . '/layout/header.php';
$isFav = in_array($stop['_id'], $currentUser['favorite_stops'] ?? [], true);
?>

<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
            <a href="/routes" style="color: var(--text-muted); text-decoration: none;">← All Stops</a>
            <span style="color: var(--text-muted);">/</span>
            <span class="badge badge-mode"><?= strtoupper($stop['transport_mode'] ?? 'bus') ?></span>
            <code><?= htmlspecialchars($stop['stop_code'] ?? '') ?></code>
        </div>
        <h1 style="font-size: 2.2rem; font-weight: 800; color: var(--primary-dark);">
            <?= htmlspecialchars($stop['stop_name']) ?>
        </h1>
        <p style="color: var(--text-muted);"><?= htmlspecialchars($stop['zone'] ?? 'Standard Network Zone') ?></p>
    </div>

    <div style="display: flex; gap: 0.75rem; align-items: center;">
        <?php if ($currentUser): ?>
            <button class="btn btn-outline btn-fav-stop <?= $isFav ? 'active' : '' ?>" data-stop-id="<?= htmlspecialchars($stop['_id']) ?>" style="color: var(--primary-dark); border-color: var(--border-color);">
                <?= $isFav ? '★ Favorited' : '☆ Add to Favorites' ?>
            </button>
        <?php endif; ?>
        <a href="/reports/submit?stop_id=<?= urlencode($stop['_id']) ?>" class="btn btn-primary">
            + Report Crowd Here
        </a>
    </div>
</div>

<div class="metrics-grid" style="margin-bottom: 2rem;">
    <div class="metric-card">
        <div class="metric-icon">🚏</div>
        <div>
            <div style="font-size: 1.1rem; font-weight: 700; color: var(--primary-dark);"><?= count($routes) ?> Transit Lines</div>
            <div class="metric-label">Services Calling Here</div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon">♿</div>
        <div>
            <div style="font-size: 0.95rem; font-weight: 600; color: var(--primary-dark);">
                <?= !empty($stop['facilities']) ? htmlspecialchars(implode(', ', $stop['facilities'])) : 'Basic shelter' ?>
            </div>
            <div class="metric-label">Passenger Facilities</div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon">📍</div>
        <div>
            <div style="font-size: 0.95rem; font-weight: 600; color: var(--primary-dark);">
                <?= ($stop['latitude'] && $stop['longitude']) ? number_format($stop['latitude'], 4) . ', ' . number_format($stop['longitude'], 4) : 'Unmapped' ?>
            </div>
            <div class="metric-label">Geographic Coordinates</div>
        </div>
    </div>
</div>

<!-- Services calling at this stop -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Services Calling At This Stop (Current Crowd Conditions)</h3>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Line / Service</th>
                    <th>Transport Mode</th>
                    <th>Frequency</th>
                    <th>Current Expected Crowd</th>
                    <th>Estimated Occupancy</th>
                    <th>Confidence</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($routes)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            No active routes are currently registered for this stop.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($routes as $route): ?>
                        <?php $pred = $routePredictions[$route['_id']] ?? null; ?>
                        <tr>
                            <td>
                                <strong>
                                    <a href="/routes/<?= urlencode($route['_id']) ?>" style="color: var(--text-main); text-decoration: none;">
                                        <?= htmlspecialchars($route['route_number']) ?>: <?= htmlspecialchars($route['route_name']) ?>
                                    </a>
                                </strong>
                            </td>
                            <td><span class="badge badge-mode"><?= strtoupper($route['transport_mode'] ?? 'bus') ?></span></td>
                            <td>Every <?= (int)($route['frequency_minutes'] ?? 15) ?> mins</td>
                            <td>
                                <?php if ($pred && $pred['crowd_category']): ?>
                                    <span class="badge badge-<?= htmlspecialchars($pred['crowd_category']) ?>">
                                        <?= ucfirst(str_replace('_', ' ', $pred['crowd_category'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-mode" style="color: #64748b;">Insufficient Data</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($pred && $pred['estimated_occupancy_percent'] !== null): ?>
                                    <strong><?= (int)$pred['estimated_occupancy_percent'] ?>%</strong>
                                <?php else: ?>
                                    <span class="text-muted">--</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-size: 0.8rem; color: #64748b;"><?= ucfirst($pred['data_quality'] ?? 'unknown') ?></span>
                            </td>
                            <td>
                                <a href="/predictions?route_id=<?= urlencode($route['_id']) ?>&stop_id=<?= urlencode($stop['_id']) ?>" class="btn btn-sm btn-outline" style="color: var(--primary-light); border-color: var(--primary-light);">
                                    Forecast Line
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require dirname(__DIR__) . '/layout/footer.php';
?>
