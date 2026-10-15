<?php
require dirname(__DIR__) . '/layout/header.php';
?>

<div class="page-header explorer-header">
    <div>
        <p class="eyebrow">Transit Network</p>
        <h1>Route & Stop Explorer</h1>
    </div>
    <p class="header-copy">
        Browse transport networks, inspect stations along corridors, and check estimated crowd conditions.
    </p>
</div>

<!-- Origin - Destination Trip Planner Box -->
<div class="card route-planner">
    <div class="card-header planner-header">
        <h3 class="card-title planner-title">📍 Find Routes Between Stops</h3>
    </div>
    <form action="/routes" method="GET" class="planner-form">
        <div class="form-group planner-field">
            <label class="form-label">Origin Station</label>
            <select name="origin" class="form-select">
                <option value="">-- Choose Origin Stop --</option>
                <?php foreach ($stops as $s): ?>
                    <option value="<?= htmlspecialchars($s['_id']) ?>" <?= (($originId ?? '') === $s['_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['stop_name']) ?> (<?= strtoupper($s['transport_mode']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group planner-field">
            <label class="form-label">Destination Station</label>
            <select name="destination" class="form-select">
                <option value="">-- Choose Destination Stop --</option>
                <?php foreach ($stops as $s): ?>
                    <option value="<?= htmlspecialchars($s['_id']) ?>" <?= (($destId ?? '') === $s['_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['stop_name']) ?> (<?= strtoupper($s['transport_mode']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary planner-button">
            Find Route
        </button>
    </form>

    <?php if (!empty($matchingRoutes)): ?>
        <div class="matching-routes-wrap">
            <h4>
                Direct Routes Found (<?= count($matchingRoutes) ?>):
            </h4>
            <div class="matching-routes-list">
                <?php foreach ($matchingRoutes as $mr): ?>
                    <div class="match-card">
                        <strong><?= htmlspecialchars($mr['route_number']) ?>: <?= htmlspecialchars($mr['route_name']) ?></strong>
                        <div>
                            <?= (int)$mr['stops_between_count'] ?> stops away • Freq: <?= (int)$mr['frequency_minutes'] ?> mins
                        </div>
                        <a href="/routes/<?= urlencode($mr['_id']) ?>" class="btn btn-sm btn-outline match-link">
                            View Line Details →
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php elseif (($originId ?? '') !== '' && ($destId ?? '') !== ''): ?>
        <div class="alert alert-info" style="margin-top: 1rem; margin-bottom: 0;">
            No single direct route currently connects these two selected stops in this direction.
        </div>
    <?php endif; ?>
</div>

<!-- Search & Filter Controls -->
<div class="card explorer-toolbar">
    <form action="/routes" method="GET" class="toolbar-form">
        <div class="toolbar-search">
            <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" class="form-control" placeholder="Search route number, name, or keywords...">
        </div>
        <div class="toolbar-select">
            <select name="mode" class="form-select">
                <option value="">All Modes</option>
                <option value="bus" <?= (($_GET['mode'] ?? '') === 'bus') ? 'selected' : '' ?>>Bus Corridors</option>
                <option value="metro" <?= (($_GET['mode'] ?? '') === 'metro') ? 'selected' : '' ?>>Metro Lines</option>
                <option value="train" <?= (($_GET['mode'] ?? '') === 'train') ? 'selected' : '' ?>>Suburban Rail</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Filter</button>
        <?php if (!empty($_GET['q']) || !empty($_GET['mode'])): ?>
            <a href="/routes" class="btn btn-secondary">Clear Filters</a>
        <?php endif; ?>
    </form>
</div>

<!-- Route Grid -->
<div class="route-grid">
    <?php if (empty($routes)): ?>
        <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
            <p style="font-size: 1.1rem; color: var(--text-muted); margin-bottom: 1rem;">No routes found matching your criteria.</p>
            <a href="/routes" class="btn btn-primary">Reset Filters</a>
        </div>
    <?php else: ?>
        <?php foreach ($routes as $route): ?>
            <article class="card route-card">
                <div>
                    <div class="route-card-head">
                        <span class="badge badge-mode"><?= strtoupper($route['transport_mode'] ?? 'bus') ?></span>
                        <strong><?= htmlspecialchars($route['route_number']) ?></strong>
                    </div>

                    <h3>
                        <a href="/routes/<?= urlencode($route['_id']) ?>">
                            <?= htmlspecialchars($route['route_name']) ?>
                        </a>
                    </h3>

                    <p>
                        <?= htmlspecialchars($route['description'] ?? 'Standard transit corridor.') ?>
                    </p>

                    <div class="route-meta">
                        <div>🕒 <strong>Hours:</strong> <?= htmlspecialchars($route['operating_hours'] ?? '06:00 - 22:00') ?></div>
                        <div>⏱ <strong>Frequency:</strong> Every <?= (int)($route['frequency_minutes'] ?? 15) ?> minutes</div>
                        <div>🚏 <strong>Stops:</strong> <?= count($route['stop_ids'] ?? []) ?> designated stops</div>
                        <div>👥 <strong>Capacity:</strong> <?= !empty($route['vehicle_capacity']) ? number_format($route['vehicle_capacity']) . ' passengers/vehicle' : 'Standard' ?></div>
                    </div>
                </div>

                <div class="route-card-actions">
                    <a href="/routes/<?= urlencode($route['_id']) ?>" class="btn btn-primary btn-sm" style="flex: 1;">
                        View Line & Map →
                    </a>
                    <a href="/predictions?route_id=<?= urlencode($route['_id']) ?>" class="btn btn-secondary btn-sm">
                        Forecast
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- All Stations & Stops Section -->
<div class="card network-table-card">
    <div class="card-header">
        <h3 class="card-title">All Network Stops & Stations (<?= count($stops) ?>)</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Stop Code</th>
                    <th>Station Name</th>
                    <th>Mode</th>
                    <th>Zone</th>
                    <th>Coordinates</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stops as $stop): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($stop['stop_code'] ?? 'ST-000') ?></code></td>
                        <td>
                            <strong>
                                <a href="/stops/<?= urlencode($stop['_id']) ?>" style="color: var(--text-main); text-decoration: none;">
                                    <?= htmlspecialchars($stop['stop_name']) ?>
                                </a>
                            </strong>
                        </td>
                        <td><span class="badge badge-mode"><?= strtoupper($stop['transport_mode'] ?? 'bus') ?></span></td>
                        <td><?= htmlspecialchars($stop['zone'] ?? 'Standard Zone') ?></td>
                        <td>
                            <?php if ($stop['latitude'] && $stop['longitude']): ?>
                                <span style="font-size: 0.85rem; color: var(--text-muted);">
                                    <?= number_format($stop['latitude'], 4) ?>, <?= number_format($stop['longitude'], 4) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted" style="font-size: 0.85rem;">Not mapped</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/stops/<?= urlencode($stop['_id']) ?>" class="btn btn-sm btn-outline" style="color: var(--primary-light); border-color: var(--primary-light);">
                                View Details
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require dirname(__DIR__) . '/layout/footer.php';
?>
