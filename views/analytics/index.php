<?php
require dirname(__DIR__) . '/layout/header.php';
?>

<div style="margin-bottom: 2rem;">
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
        <span class="badge badge-mode">Data Intelligence</span>
        <span style="font-size: 0.82rem; color: var(--text-muted);">Apache CouchDB Analytics</span>
    </div>
    <h1 style="font-size: 2.2rem; font-weight: 800; color: var(--primary-navy); margin-bottom: 0.4rem;">
        Historical Crowd Analytics & Trends
    </h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">
        Empirical transit utilization profiles calculated directly from Apache CouchDB historical observations and community reports.
    </p>
</div>

<!-- Key Analytics Counters -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-icon" style="color: #2563eb; background: #eff6ff;">📈</div>
        <div>
            <div class="metric-num"><?= (int)$metrics['total_observations'] ?></div>
            <div class="metric-label">Analyzed Observations</div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon" style="color: #059669; background: #ecfdf5;">🟢</div>
        <div>
            <div class="metric-num"><?= (int)($categories['percentages']['low'] ?? 0) ?>%</div>
            <div class="metric-label">Low Crowd Periods</div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon" style="color: #d97706; background: #fffbeb;">🟡</div>
        <div>
            <div class="metric-num"><?= (int)($categories['percentages']['moderate'] ?? 0) ?>%</div>
            <div class="metric-label">Moderate Occupancy</div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon" style="color: #dc2626; background: #fef2f2;">🔴</div>
        <div>
            <div class="metric-num"><?= (int)(($categories['percentages']['high'] ?? 0) + ($categories['percentages']['very_high'] ?? 0)) ?>%</div>
            <div class="metric-label">High / Peak Commute</div>
        </div>
    </div>
</div>

<!-- Charts Row 1: Hourly Trends & Category Distribution -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Hourly Profile -->
    <div class="card" style="box-shadow: var(--shadow-md);">
        <div class="card-header">
            <div>
                <h3 class="card-title">Hourly Crowd Profile (Average Occupancy %)</h3>
                <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.15rem;">Time slot curve from 06:00 to 23:00</p>
            </div>
            <span class="badge badge-mode">Line Analysis</span>
        </div>
        <div style="height: 320px; position: relative;">
            <canvas id="hourlyCrowdChart"></canvas>
        </div>
    </div>

    <!-- Category Doughnut -->
    <div class="card" style="box-shadow: var(--shadow-md);">
        <div class="card-header">
            <div>
                <h3 class="card-title">Crowd Distribution</h3>
                <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.15rem;">Classification breakdown</p>
            </div>
            <span class="badge badge-mode">Categories</span>
        </div>
        <div style="height: 320px; position: relative;">
            <canvas id="categoryPieChart"></canvas>
        </div>
    </div>
</div>

<!-- Charts Row 2: Weekday vs Weekend Comparison -->
<div class="card" style="box-shadow: var(--shadow-md); margin-bottom: 2rem;">
    <div class="card-header">
        <div>
            <h3 class="card-title">Weekday Commute vs Weekend Leisure Patterns</h3>
            <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.15rem;">
                Comparing morning and evening commuter peaks against weekend leisure travel flows:
            </p>
        </div>
        <span class="badge badge-low">Peak Shift Model</span>
    </div>
    <div style="height: 340px; position: relative;">
        <canvas id="weekdayWeekendChart"></canvas>
    </div>
</div>

<!-- Route-wise Summary Table -->
<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title">Corridor Utilization & Data Sufficiency</h3>
            <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.15rem;">
                Sample counts and average crowd metrics partitioned by corridor:
            </p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Line / Corridor</th>
                    <th>Transport Mode</th>
                    <th>Sample Observations</th>
                    <th>Average Occupancy</th>
                    <th>Data Sufficiency</th>
                    <th>Forecast Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($routeSummaries as $rs): ?>
                    <tr>
                        <td>
                            <strong>
                                <a href="/routes/<?= urlencode($rs['route_id']) ?>" style="color: var(--text-primary); text-decoration: none;">
                                    <?= htmlspecialchars($rs['route_number']) ?> - <?= htmlspecialchars($rs['route_name']) ?>
                                </a>
                            </strong>
                        </td>
                        <td>
                            <span class="badge badge-mode"><?= strtoupper($rs['transport_mode']) ?></span>
                        </td>
                        <td><strong><?= (int)$rs['observations_count'] ?></strong> records</td>
                        <td>
                            <?php if ($rs['average_occupancy'] !== null): ?>
                                <strong style="font-size: 1.05rem;"><?= (int)$rs['average_occupancy'] ?>%</strong>
                            <?php else: ?>
                                <span class="text-muted">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($rs['has_sufficient_data']): ?>
                                <span class="badge badge-low">✓ Sufficient</span>
                            <?php else: ?>
                                <span class="badge badge-high">Sparse Data</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/predictions?route_id=<?= urlencode($rs['route_id']) ?>" class="btn btn-sm btn-outline" style="color: var(--primary); border-color: var(--primary-light);">
                                Predict Corridor →
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
