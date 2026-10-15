<?php
require dirname(__DIR__) . '/layout/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
            <span class="badge badge-low">Passenger Portal</span>
            <span style="font-size: 0.85rem; color: var(--text-muted);"><?= htmlspecialchars($currentUser['email'] ?? '') ?></span>
        </div>
        <h1 style="font-size: 2.2rem; font-weight: 800; color: var(--primary-navy);">
            My Commute Dashboard
        </h1>
    </div>

    <div>
        <a href="/reports/submit" class="btn btn-primary">+ Submit Crowd Report</a>
    </div>
</div>

<!-- Favorite Services with Real-Time Predictions -->
<div class="card" style="box-shadow: var(--shadow-md);">
    <div class="card-header">
        <div>
            <h3 class="card-title">★ My Monitored Corridors & Real-Time Status</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.15rem;">
                Current crowd estimates for your saved daily transit services:
            </p>
        </div>
        <a href="/routes" class="btn btn-sm btn-outline" style="color: var(--primary); border-color: var(--primary-light);">Explore More Lines</a>
    </div>

    <?php if (empty($favoriteRoutes)): ?>
        <div style="text-align: center; padding: 3rem 1.5rem; color: var(--text-muted);">
            <div style="font-size: 3rem; margin-bottom: 0.75rem;">🚍</div>
            <h4 style="font-size: 1.25rem; font-weight: 700; color: var(--primary-navy); margin-bottom: 0.5rem;">No Favorite Corridors Saved Yet</h4>
            <p style="font-size: 0.95rem; margin-bottom: 1.5rem; max-width: 460px; margin-left: auto; margin-right: auto;">
                Save your regular commute routes to view live crowd forecasts and departure suggestions right here.
            </p>
            <a href="/routes" class="btn btn-primary">Browse Network Lines</a>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.35rem;">
            <?php foreach ($favoriteRoutes as $route): ?>
                <?php $pred = $route['current_prediction'] ?? null; ?>
                <div style="border: 1.5px solid var(--card-border); border-radius: var(--radius); padding: 1.35rem; background: #ffffff; box-shadow: var(--shadow-xs); display: flex; flex-direction: column; justify-content: space-between;">
                    <div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.6rem;">
                            <span class="badge badge-mode">
                                <?= match ($route['transport_mode']) {
                                    'metro' => '🚇 METRO',
                                    'train' => '🚆 RAIL',
                                    default => '🚌 BUS'
                                } ?>
                            </span>
                            <strong style="color: var(--primary-light); font-size: 1.15rem; font-family: monospace;">
                                <?= htmlspecialchars($route['route_number']) ?>
                            </strong>
                        </div>

                        <h4 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.75rem;">
                            <a href="/routes/<?= urlencode($route['_id']) ?>" style="color: var(--text-primary); text-decoration: none;">
                                <?= htmlspecialchars($route['route_name']) ?>
                            </a>
                        </h4>

                        <div style="margin: 0.85rem 0; padding: 0.85rem 1rem; background: #f8fafc; border: 1px solid var(--card-border); border-radius: var(--radius-sm);">
                            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; margin-bottom: 0.4rem;">
                                <span style="font-weight: 600; color: var(--text-secondary);">Current Status:</span>
                                <?php if ($pred && $pred['crowd_category']): ?>
                                    <span class="badge badge-<?= htmlspecialchars($pred['crowd_category']) ?>">
                                        <span class="pulse-dot pulse-<?= htmlspecialchars($pred['crowd_category']) ?>"></span>
                                        <?= ucfirst(str_replace('_', ' ', $pred['crowd_category'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-mode" style="color: #64748b;">Sparse Data</span>
                                <?php endif; ?>
                            </div>
                            <?php if ($pred && $pred['estimated_occupancy_percent'] !== null): ?>
                                <div class="occupancy-meter" style="height: 10px; margin: 0.35rem 0;">
                                    <div class="occupancy-fill fill-<?= htmlspecialchars($pred['crowd_category']) ?>" style="width: <?= (int)$pred['estimated_occupancy_percent'] ?>%;"></div>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted); text-align: right; font-weight: 600;">
                                    ~<?= (int)$pred['estimated_occupancy_percent'] ?>% capacity utilization
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div style="display: flex; gap: 0.6rem; border-top: 1px solid var(--border-subtle); padding-top: 0.85rem;">
                        <a href="/routes/<?= urlencode($route['_id']) ?>" class="btn btn-sm btn-secondary" style="flex: 1;">Line Details</a>
                        <a href="/predictions?route_id=<?= urlencode($route['_id']) ?>" class="btn btn-sm btn-primary" style="flex: 1;">Forecast</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Suggested Alternative Travel Windows -->
<?php if (!empty($alternativeSuggestions['options'])): ?>
<div class="card" style="background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%); border: 2px solid var(--crowd-low-border); box-shadow: 0 10px 25px var(--crowd-low-glow);">
    <div class="card-header" style="border-bottom-color: #d1fae5;">
        <div>
            <h3 class="card-title" style="color: #065f46;">⏱ Suggested Departure Windows for Your Line</h3>
            <p style="font-size: 0.85rem; color: #047857; margin-top: 0.15rem;">
                Upcoming departures evaluated for optimal comfort:
            </p>
        </div>
        <span class="badge badge-low">Smart Scheduler</span>
    </div>

    <div class="comparison-grid">
        <?php foreach ($alternativeSuggestions['options'] as $opt): ?>
            <?php
            $isRec = ($opt['time_full'] === ($alternativeSuggestions['recommended_time'] ?? ''));
            $cat = $opt['crowd_category'];
            $occ = $opt['occupancy_percent'];
            ?>
            <div class="compare-slot <?= $isRec ? 'recommended' : '' ?>">
                <div class="compare-time"><?= htmlspecialchars($opt['time_full']) ?></div>
                <?php if ($cat): ?>
                    <span class="badge badge-<?= htmlspecialchars($cat) ?>"><?= ucfirst(str_replace('_', ' ', $cat)) ?></span>
                    <?php if ($occ !== null): ?>
                        <div style="font-size: 1.3rem; font-weight: 800; color: var(--primary-navy); margin-top: 0.4rem;"><?= (int)$occ ?>%</div>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="badge badge-mode">Sparse Data</span>
                <?php endif; ?>
                <?php if ($isRec): ?>
                    <div style="font-size: 0.78rem; font-weight: 700; color: var(--crowd-low); margin-top: 0.5rem;">★ Recommended</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Saved Stations & Privacy Controls -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Favorite Stops -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">★ Saved Stations (<?= count($favoriteStops) ?>)</h3>
        </div>
        <?php if (empty($favoriteStops)): ?>
            <p style="color: var(--text-muted); font-size: 0.9rem; text-align: center; padding: 2rem;">
                No saved stations yet. Explore stations to bookmark frequent boarding points.
            </p>
        <?php else: ?>
            <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.85rem;">
                <?php foreach ($favoriteStops as $s): ?>
                    <li style="display: flex; justify-content: space-between; align-items: center; padding: 0.65rem 0; border-bottom: 1px solid var(--border-subtle);">
                        <div>
                            <strong>
                                <a href="/stops/<?= urlencode($s['_id']) ?>" style="color: var(--text-primary); text-decoration: none;">
                                    <?= htmlspecialchars($s['stop_name']) ?>
                                </a>
                            </strong>
                            <div style="font-size: 0.78rem; color: var(--text-muted);"><?= (int)$s['routes_count'] ?> active services</div>
                        </div>
                        <a href="/stops/<?= urlencode($s['_id']) ?>" class="btn btn-sm btn-outline" style="color: var(--primary); border-color: var(--primary-light);">
                            View
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Account & Privacy Controls -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Privacy & Stored Preferences</h3>
        </div>

        <?php if (!empty($recentSearches)): ?>
            <div style="margin-bottom: 1.5rem;">
                <div style="font-size: 0.82rem; font-weight: 700; color: var(--text-muted); margin-bottom: 0.6rem; text-transform: uppercase;">
                    Recent Searches:
                </div>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <?php foreach ($recentSearches as $q): ?>
                        <a href="/routes?q=<?= urlencode($q) ?>" class="badge badge-mode" style="text-decoration: none; padding: 0.4rem 0.75rem;">
                            🔍 <?= htmlspecialchars($q) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div style="padding-top: 1rem; border-top: 1px solid var(--border-subtle);">
            <div style="font-size: 0.88rem; font-weight: 700; color: var(--primary-navy); margin-bottom: 0.4rem;">
                Account & Data Rights
            </div>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1.25rem; line-height: 1.5;">
                In compliance with open-source privacy standards, you can permanently remove your account and all associated preferences at any time.
            </p>
            <form action="/account/delete" method="POST" onsubmit="return confirm('Permanently remove your account and all stored favorites?');">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <button type="submit" class="btn btn-danger btn-sm">
                    Delete Account & Preferences
                </button>
            </form>
        </div>
    </div>
</div>

<?php
require dirname(__DIR__) . '/layout/footer.php';
?>
