<?php
require dirname(__DIR__) . '/layout/header.php';
$isFav = in_array($route['_id'], $currentUser['favorite_routes'] ?? [], true);
?>

<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 2rem; flex-wrap: wrap; gap: 1.25rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.6rem;">
            <a href="/routes" class="btn btn-secondary btn-sm" style="font-weight: 600;">← All Routes</a>
            <span class="badge badge-mode">
                <?= match ($route['transport_mode'] ?? 'bus') {
                    'metro' => '🚇 METRO CORRIDOR',
                    'train' => '🚆 SUBURBAN RAIL',
                    default => '🚌 CITY BUS'
                } ?>
            </span>
        </div>
        <h1 style="font-size: 2.4rem; font-weight: 800; color: var(--primary-navy); letter-spacing: -0.02em;">
            <?= htmlspecialchars($route['route_name']) ?>
            <span style="color: var(--primary-light); font-family: monospace;">(<?= htmlspecialchars($route['route_number']) ?>)</span>
        </h1>
        <p style="color: var(--text-muted); font-size: 1.05rem; max-width: 820px; line-height: 1.5; margin-top: 0.35rem;">
            <?= htmlspecialchars($route['description'] ?? '') ?>
        </p>
    </div>

    <div style="display: flex; gap: 0.75rem; align-items: center;">
        <?php if ($currentUser): ?>
            <button class="btn btn-secondary btn-fav-route <?= $isFav ? 'active' : '' ?>" data-route-id="<?= htmlspecialchars($route['_id']) ?>" style="border-color: var(--card-border);">
                <?= $isFav ? '★ Favorited' : '☆ Add to Favorites' ?>
            </button>
        <?php endif; ?>
        <a href="/predictions?route_id=<?= urlencode($route['_id']) ?>" class="btn btn-primary">
            🔮 Forecast Full Line
        </a>
    </div>
</div>

<!-- Key Performance Indicators -->
<div class="metrics-grid" style="margin-bottom: 2rem;">
    <div class="metric-card">
        <div class="metric-icon" style="color: #2563eb; background: #eff6ff;">🕒</div>
        <div>
            <div style="font-size: 1.15rem; font-weight: 700; color: var(--primary-navy);"><?= htmlspecialchars($route['operating_hours'] ?? 'Standard') ?></div>
            <div class="metric-label">Operating Schedule</div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon" style="color: #059669; background: #ecfdf5;">⏱</div>
        <div>
            <div style="font-size: 1.15rem; font-weight: 700; color: var(--primary-navy);">Every <?= (int)($route['frequency_minutes'] ?? 15) ?> mins</div>
            <div class="metric-label">Service Headway</div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon" style="color: #7c3aed; background: #f5f3ff;">👥</div>
        <div>
            <div style="font-size: 1.15rem; font-weight: 700; color: var(--primary-navy);"><?= !empty($route['vehicle_capacity']) ? number_format($route['vehicle_capacity']) : 'Standard' ?></div>
            <div class="metric-label">Rated Vehicle Capacity</div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon" style="color: #ea580c; background: #fff7ed;">🚏</div>
        <div>
            <div style="font-size: 1.15rem; font-weight: 700; color: var(--primary-navy);"><?= count($route['stops']) ?> Stations</div>
            <div class="metric-label">Corridor Stops</div>
        </div>
    </div>
</div>

<!-- Interactive Map Container -->
<div class="card" style="padding: 1.25rem; margin-bottom: 2.5rem;">
    <div class="card-header" style="margin-bottom: 1rem;">
        <div>
            <h3 class="card-title">Interactive Corridor Alignment Map</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.15rem;">
                OpenStreetMap tiles with station pin overlays.
            </p>
        </div>
        <span class="badge badge-mode">OpenStreetMap Layer</span>
    </div>
    <div id="routeMap"></div>
</div>

<!-- Stop Sequence with Crowd Forecasts -->
<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title">Stop Sequence & Real-Time Crowd Forecasts</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.15rem;">
                Station-by-station expected density for the next 15-minute window:
            </p>
        </div>
        <span class="badge badge-low">
            <span class="pulse-dot pulse-low"></span> Live Estimates
        </span>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">Seq</th>
                    <th>Station Name</th>
                    <th>Zone</th>
                    <th>Key Facilities</th>
                    <th>Current Crowd</th>
                    <th>Occupancy %</th>
                    <th>Confidence</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($route['stops'] as $idx => $stop): ?>
                    <?php $pred = $stopPredictions[$stop['_id']] ?? null; ?>
                    <tr>
                        <td>
                            <span style="width: 28px; height: 28px; border-radius: 50%; background: #f1f5f9; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; color: var(--primary-navy);">
                                <?= $idx + 1 ?>
                            </span>
                        </td>
                        <td>
                            <strong>
                                <a href="/stops/<?= urlencode($stop['_id']) ?>" style="color: var(--text-primary); text-decoration: none; font-size: 1rem;">
                                    <?= htmlspecialchars($stop['stop_name']) ?>
                                </a>
                            </strong>
                            <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.15rem;">
                                Code: <code><?= htmlspecialchars($stop['stop_code'] ?? 'ST') ?></code>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($stop['zone'] ?? 'Standard Zone') ?></td>
                        <td>
                            <?php if (!empty($stop['facilities'])): ?>
                                <span style="font-size: 0.82rem; color: #475569;"><?= htmlspecialchars(implode(', ', array_slice($stop['facilities'], 0, 2))) ?></span>
                            <?php else: ?>
                                <span class="text-muted" style="font-size: 0.82rem;">Standard platform</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($pred && $pred['crowd_category']): ?>
                                <span class="badge badge-<?= htmlspecialchars($pred['crowd_category']) ?>">
                                    <span class="pulse-dot pulse-<?= htmlspecialchars($pred['crowd_category']) ?>"></span>
                                    <?= ucfirst(str_replace('_', ' ', $pred['crowd_category'])) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge badge-mode" style="color: #64748b;">Sparse Data</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($pred && $pred['estimated_occupancy_percent'] !== null): ?>
                                <strong style="font-size: 1.05rem;"><?= (int)$pred['estimated_occupancy_percent'] ?>%</strong>
                            <?php else: ?>
                                <span class="text-muted">--</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="font-size: 0.82rem; font-weight: 600; color: <?= ($pred['data_quality'] ?? '') === 'reliable' ? 'var(--crowd-low)' : 'var(--text-muted)' ?>;">
                                <?= ucfirst($pred['data_quality'] ?? 'unknown') ?>
                            </span>
                        </td>
                        <td>
                            <a href="/predictions?route_id=<?= urlencode($route['_id']) ?>&stop_id=<?= urlencode($stop['_id']) ?>" class="btn btn-sm btn-outline" style="color: var(--primary); border-color: var(--primary-light);">
                                Forecast →
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const stopsData = <?= json_encode($route['stops']) ?>;
    initRouteMap('routeMap', stopsData);
});
</script>

<?php
require dirname(__DIR__) . '/layout/footer.php';
?>
