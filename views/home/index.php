<?php
require dirname(__DIR__) . '/layout/header.php';
?>

<!-- Elevated Hero Section -->
<section class="hero-section">
    <div class="container hero-content">
        <div class="hero-pill">
            <span class="pulse-dot pulse-low"></span> Live Multi-Corridor Analytics Active
        </div>
        <h1 class="hero-title">
            Travel Smarter. <br><span class="highlight">Avoid the Crowds.</span>
        </h1>
        <p class="hero-subtitle">
            Get explainable capacity forecasts and crowd trends for buses, metros, and commuter rails.
            Powered by Apache CouchDB and statistical predictive modeling.
        </p>

        <!-- Search Bar with Quick Action -->
        <form action="/routes" method="GET" class="hero-search-box">
            <span style="font-size: 1.25rem; display: flex; align-items: center; padding-left: 0.75rem;">🔍</span>
            <input type="text" name="q" class="hero-search-input" placeholder="Search line number (e.g. M-BLU, 101), station, or terminal..." aria-label="Search routes and stations" required>
            <button type="submit" class="btn btn-primary btn-lg" style="padding: 0 1.75rem;">
                Explore Lines
            </button>
        </form>

        <div style="display: flex; justify-content: center; gap: 0.75rem; flex-wrap: wrap;">
            <a href="/predictions" class="btn btn-outline">🔮 Check Forecasts</a>
            <a href="/predictions/compare" class="btn btn-outline">⏱ Compare Departure Times</a>
            <a href="/reports/submit" class="btn btn-outline">📢 Report Current Crowd</a>
        </div>

        <div class="hero-microstats" aria-label="Live transit summary">
            <div class="hero-stat">
                <strong>24/7</strong>
                <span>Monitoring</span>
            </div>
            <div class="hero-stat">
                <strong>4 min</strong>
                <span>Avg. wait</span>
            </div>
            <div class="hero-stat">
                <strong>93%</strong>
                <span>Forecast confidence</span>
            </div>
        </div>
    </div>
</section>

<!-- Overview Metrics Counters -->
<section class="metrics-grid">
    <div class="metric-card">
        <div class="metric-icon" style="color: #2563eb; background: #eff6ff;">🗺️</div>
        <div>
            <div class="metric-num"><?= (int)($metrics['total_routes'] ?? 0) ?></div>
            <div class="metric-label">Transit Lines Monitored</div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon" style="color: #059669; background: #ecfdf5;">🚏</div>
        <div>
            <div class="metric-num"><?= (int)($metrics['total_stops'] ?? 0) ?></div>
            <div class="metric-label">Network Stations & Stops</div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon" style="color: #d97706; background: #fffbeb;">📊</div>
        <div>
            <div class="metric-num"><?= (int)($metrics['total_observations'] ?? 0) ?></div>
            <div class="metric-label">Historical Calibration Records</div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon" style="color: #7c3aed; background: #f5f3ff;">👥</div>
        <div>
            <div class="metric-num"><?= (int)($metrics['total_reports'] ?? 0) ?></div>
            <div class="metric-label">Community Crowd Submissions</div>
        </div>
    </div>
</section>

<section class="creative-showcase">
    <div class="showcase-card showcase-feature primary">
        <div class="showcase-icon">🚉</div>
        <div>
            <span>Live route flow</span>
            <strong>See where crowding is building in real time.</strong>
        </div>
    </div>
    <div class="showcase-card showcase-feature">
        <div class="showcase-icon">🧭</div>
        <div>
            <span>Smarter departure</span>
            <strong>Compare the least crowded window before you travel.</strong>
        </div>
    </div>
    <div class="showcase-card showcase-feature accent">
        <div class="showcase-icon">📈</div>
        <div>
            <span>Explainable insights</span>
            <strong>Use historical patterns and transparent forecasts.</strong>
        </div>
    </div>
</section>

<!-- Crowd Categories Visual Guide -->
<section class="card" style="margin-bottom: 3rem; background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);">
    <div class="card-header" style="border-bottom: none; margin-bottom: 0.5rem; padding-bottom: 0;">
        <div>
            <h3 class="card-title">Understanding Crowd Categories</h3>
            <p style="font-size: 0.88rem; color: var(--text-muted); margin-top: 0.2rem;">
                Standardized capacity utilization classifications across all lines:
            </p>
        </div>
        <span class="badge badge-mode">Standard Model</span>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-top: 1rem;">
        <div style="background: var(--crowd-low-bg); border: 1.5px solid var(--crowd-low-border); border-radius: var(--radius); padding: 1.1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                <span class="badge badge-low">Low Crowd</span>
                <strong style="color: var(--crowd-low); font-size: 1.1rem;">0–30%</strong>
            </div>
            <div class="occupancy-meter" style="height: 8px;">
                <div class="occupancy-fill fill-low" style="width: 25%;"></div>
            </div>
            <div style="font-size: 0.82rem; color: #065f46; font-weight: 500;">
                Abundant seating available. Relaxed boarding.
            </div>
        </div>

        <div style="background: var(--crowd-moderate-bg); border: 1.5px solid var(--crowd-moderate-border); border-radius: var(--radius); padding: 1.1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                <span class="badge badge-moderate">Moderate</span>
                <strong style="color: var(--crowd-moderate); font-size: 1.1rem;">31–60%</strong>
            </div>
            <div class="occupancy-meter" style="height: 8px;">
                <div class="occupancy-fill fill-moderate" style="width: 50%;"></div>
            </div>
            <div style="font-size: 0.82rem; color: #78350f; font-weight: 500;">
                Few seats left; light standing room. Standard commute.
            </div>
        </div>

        <div style="background: var(--crowd-high-bg); border: 1.5px solid var(--crowd-high-border); border-radius: var(--radius); padding: 1.1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                <span class="badge badge-high">High Crowd</span>
                <strong style="color: var(--crowd-high); font-size: 1.1rem;">61–80%</strong>
            </div>
            <div class="occupancy-meter" style="height: 8px;">
                <div class="occupancy-fill fill-high" style="width: 75%;"></div>
            </div>
            <div style="font-size: 0.82rem; color: #7f1d1d; font-weight: 500;">
                Standing room only. High station platform density.
            </div>
        </div>

        <div style="background: var(--crowd-very-high-bg); border: 1.5px solid var(--crowd-very-high-border); border-radius: var(--radius); padding: 1.1rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                <span class="badge badge-very-high">Very High</span>
                <strong style="color: var(--crowd-very-high); font-size: 1.1rem;">81–100%</strong>
            </div>
            <div class="occupancy-meter" style="height: 8px;">
                <div class="occupancy-fill fill-very_high" style="width: 95%;"></div>
            </div>
            <div style="font-size: 0.82rem; color: #881337; font-weight: 500;">
                Crush load / severe crowding. Consider shifting departure.
            </div>
        </div>
    </div>
</section>

<!-- Featured Transit Corridors -->
<section style="margin-bottom: 3.5rem;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="font-size: 1.6rem; font-weight: 800; color: var(--primary-navy);">Featured Corridors</h2>
            <p style="color: var(--text-muted); font-size: 0.92rem;">Monitored mass transit lines with active prediction models.</p>
        </div>
        <a href="/routes" class="btn btn-outline btn-sm" style="color: var(--primary); border-color: var(--primary-light);">
            Explore All Corridors →
        </a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.35rem;">
        <?php foreach (array_slice($routes, 0, 4) as $route): ?>
            <div class="card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
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

                    <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.5rem;">
                        <a href="/routes/<?= urlencode($route['_id']) ?>" style="text-decoration: none; color: var(--text-primary);">
                            <?= htmlspecialchars($route['route_name']) ?>
                        </a>
                    </h3>

                    <p style="color: var(--text-secondary); font-size: 0.88rem; margin-bottom: 1.1rem; line-height: 1.45;">
                        <?= htmlspecialchars($route['description'] ?? 'Connecting key regional nodes.') ?>
                    </p>
                </div>

                <div style="border-top: 1px solid var(--border-subtle); padding-top: 0.85rem; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.82rem; color: var(--text-muted); font-weight: 600;">
                        ⏱ Every <?= (int)($route['frequency_minutes'] ?? 15) ?> mins
                    </span>
                    <a href="/routes/<?= urlencode($route['_id']) ?>" class="btn btn-secondary btn-sm">
                        View Stops & Map →
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- How It Works Section -->
<section style="margin-bottom: 3.5rem;">
    <div style="text-align: center; max-width: 650px; margin: 0 auto 2.5rem auto;">
        <h2 style="font-size: 1.8rem; font-weight: 800; color: var(--primary-navy);">How It Works</h2>
        <p style="color: var(--text-muted); font-size: 0.95rem;">
            A privacy-conscious, rule-based approach powered by Apache CouchDB NoSQL.
        </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
        <div class="card" style="text-align: center; padding: 2rem 1.5rem;">
            <div style="font-size: 2.5rem; margin-bottom: 1rem;">1️⃣</div>
            <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.5rem;">Historical Calibration</h3>
            <p style="font-size: 0.88rem; color: var(--text-muted);">
                Historical observations partitioned by day-of-week and time slots stored in CouchDB.
            </p>
        </div>

        <div class="card" style="text-align: center; padding: 2rem 1.5rem;">
            <div style="font-size: 2.5rem; margin-bottom: 1rem;">2️⃣</div>
            <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.5rem;">Explainable Forecasting</h3>
            <p style="font-size: 0.88rem; color: var(--text-muted);">
                Statistical distance-weighted averages combined with commuter peak factors.
            </p>
        </div>

        <div class="card" style="text-align: center; padding: 2rem 1.5rem;">
            <div style="font-size: 2.5rem; margin-bottom: 1rem;">3️⃣</div>
            <h3 style="font-size: 1.15rem; font-weight: 700; margin-bottom: 0.5rem;">Live Community Feedback</h3>
            <p style="font-size: 0.88rem; color: var(--text-muted);">
                Voluntary passenger reports blended with historical averages for responsive estimates.
            </p>
        </div>
    </div>
</section>

<!-- Recent Community Reports Feed -->
<?php if (!empty($recentReports)): ?>
<section style="margin-bottom: 2rem;">
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Recent Validated Community Observations</h3>
                <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.15rem;">
                    Voluntary passenger feedback validated by operators.
                </p>
            </div>
            <a href="/reports/submit" class="btn btn-sm btn-primary">+ Submit Report</a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Corridor</th>
                        <th>Station</th>
                        <th>Reported Crowd</th>
                        <th>Occupancy</th>
                        <th>Observed At</th>
                        <th>Passenger Context</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentReports as $rep): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($rep['route_id']) ?></strong></td>
                            <td><?= htmlspecialchars($rep['stop_id']) ?></td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($rep['crowd_category'] ?? 'moderate') ?>">
                                    <?= ucfirst(str_replace('_', ' ', $rep['crowd_category'] ?? 'moderate')) ?>
                                </span>
                            </td>
                            <td>
                                <?= isset($rep['approximate_occupancy']) ? (int)$rep['approximate_occupancy'] . '%' : '<span class="text-muted">N/A</span>' ?>
                            </td>
                            <td style="font-size: 0.82rem; color: var(--text-muted);">
                                <?= date('M j, g:i A', strtotime($rep['reported_at'] ?? 'now')) ?>
                            </td>
                            <td style="font-size: 0.85rem; font-style: italic; color: #475569;">
                                "<?= htmlspecialchars($rep['comments'] ?? 'No comments') ?>"
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
require dirname(__DIR__) . '/layout/footer.php';
?>
