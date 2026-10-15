<?php
require dirname(__DIR__) . '/layout/header.php';
?>

<div style="margin-bottom: 2rem;">
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
        <span class="badge badge-mode">AI / Analytics Engine</span>
        <span style="font-size: 0.82rem; color: var(--text-muted);">Explainable Modeling</span>
    </div>
    <h1 style="font-size: 2.2rem; font-weight: 800; color: var(--primary-navy); margin-bottom: 0.4rem;">
        Transit Crowd Level Predictions
    </h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">
        Empirical, explainable forecasts calculated from Apache CouchDB historical observations and validated passenger reports.
    </p>
</div>

<!-- Selection Form -->
<div class="card" style="box-shadow: var(--shadow-md);">
    <form action="/predictions" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)) 160px; gap: 1.25rem; align-items: flex-end;">
        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Corridor / Line</label>
            <select name="route_id" id="routeSelect" class="form-select">
                <?php foreach ($routes as $r): ?>
                    <option value="<?= htmlspecialchars($r['_id']) ?>" data-stops='<?= json_encode($r['stop_ids'] ?? []) ?>' <?= ($selectedRouteId === $r['_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r['route_number']) ?> - <?= htmlspecialchars($r['route_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Station / Stop</label>
            <select name="stop_id" id="stopSelect" class="form-select">
                <?php foreach ($stops as $s): ?>
                    <option value="<?= htmlspecialchars($s['_id']) ?>" <?= ($selectedStopId === $s['_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['stop_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">Departure / Target Time</label>
            <input type="time" name="time" value="<?= htmlspecialchars($selectedTime) ?>" class="form-control">
        </div>

        <button type="submit" class="btn btn-primary btn-lg" style="height: 48px; width: 100%;">
            Calculate
        </button>
    </form>
</div>

<?php if ($prediction !== null): ?>
    <?php
    $cat = $prediction['crowd_category'];
    $occ = $prediction['estimated_occupancy_percent'];
    $quality = $prediction['data_quality'];
    ?>
    <!-- Primary Prediction Display Card -->
    <div class="card prediction-card pred-<?= htmlspecialchars($cat ?? 'unknown') ?>" style="padding: 2rem;">
        <div class="card-header" style="border-bottom: 1px solid rgba(0,0,0,0.06); padding-bottom: 1.25rem; margin-bottom: 1.75rem;">
            <div>
                <span class="badge badge-mode"><?= strtoupper($prediction['transport_mode'] ?? 'bus') ?></span>
                <span style="font-size: 0.88rem; color: var(--text-muted); margin-left: 0.75rem;">
                    Forecast Target: <strong><?= date('g:i A, l', strtotime($prediction['prediction_time'])) ?></strong>
                </span>
            </div>
            <div>
                <?php if ($cat): ?>
                    <span class="badge badge-<?= htmlspecialchars($cat) ?>" style="font-size: 0.95rem; padding: 0.45rem 1rem;">
                        <span class="pulse-dot pulse-<?= htmlspecialchars($cat) ?>"></span>
                        <?= ucfirst(str_replace('_', ' ', $cat)) ?> Crowd Level
                    </span>
                <?php else: ?>
                    <span class="badge badge-mode" style="color: #64748b;">Insufficient Historical Data</span>
                <?php endif; ?>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 3fr 2fr; gap: 2.5rem; align-items: center; margin-bottom: 1.75rem;">
            <div>
                <h2 style="font-size: 2rem; font-weight: 800; color: var(--primary-navy); margin-bottom: 0.35rem; letter-spacing: -0.02em;">
                    <?= htmlspecialchars($prediction['route_name']) ?>
                </h2>
                <div style="font-size: 1.1rem; color: var(--text-secondary); margin-bottom: 1.5rem;">
                    Platform / Stop: <strong><?= htmlspecialchars($prediction['stop_name']) ?></strong>
                </div>

                <?php if ($occ !== null): ?>
                    <div style="background: #f8fafc; border: 1px solid var(--card-border); border-radius: var(--radius); padding: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 0.5rem;">
                            <span style="font-weight: 700; font-size: 0.95rem; color: var(--text-primary);">
                                Estimated Vehicle Capacity Utilization
                            </span>
                            <span style="font-size: 2.2rem; font-weight: 800; line-height: 1; color: <?= match ($cat) {
                                'low' => 'var(--crowd-low)',
                                'moderate' => 'var(--crowd-moderate)',
                                'high' => 'var(--crowd-high)',
                                default => 'var(--crowd-very-high)'
                            } ?>;">
                                <?= (int)$occ ?>%
                            </span>
                        </div>
                        <div class="occupancy-meter" style="height: 18px;">
                            <div class="occupancy-fill fill-<?= htmlspecialchars($cat ?? 'moderate') ?>" style="width: <?= (int)$occ ?>%;"></div>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.78rem; color: var(--text-muted); font-weight: 600; margin-top: 0.35rem;">
                            <span>0% Empty</span>
                            <span>30% Low</span>
                            <span>60% Moderate</span>
                            <span>80% High</span>
                            <span>100% Full</span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info" style="margin-bottom: 0;">
                        <div>
                            <strong>Sparse Data Window:</strong> <?= htmlspecialchars($prediction['explanation']) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Metadata Breakdown -->
            <div style="background: #f8fafc; border: 1px solid var(--card-border); border-radius: var(--radius); padding: 1.5rem;">
                <h4 style="font-size: 0.85rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.6px; font-weight: 700; margin-bottom: 1rem;">
                    Prediction Evidence & Parameters
                </h4>
                <div style="font-size: 0.9rem; display: flex; flex-direction: column; gap: 0.65rem;">
                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.4rem;">
                        <span style="color: var(--text-muted);">Data Quality:</span>
                        <strong style="color: <?= $quality === 'reliable' ? 'var(--crowd-low)' : ($quality === 'moderate' ? 'var(--crowd-moderate)' : '#64748b') ?>;">
                            <?= ucfirst($quality) ?>
                        </strong>
                    </div>

                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.4rem;">
                        <span style="color: var(--text-muted);">Data Source:</span>
                        <strong><?= htmlspecialchars($prediction['data_source']) ?></strong>
                    </div>

                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.4rem;">
                        <span style="color: var(--text-muted);">Estimation Model:</span>
                        <code><?= htmlspecialchars($prediction['method']) ?></code>
                    </div>

                    <div style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.4rem;">
                        <span style="color: var(--text-muted);">Sample Records:</span>
                        <strong><?= (int)$prediction['sample_count'] ?> records</strong>
                    </div>

                    <?php if (!empty($prediction['vehicle_capacity'])): ?>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: var(--text-muted);">Rated Capacity:</span>
                            <strong><?= number_format($prediction['vehicle_capacity']) ?> passengers</strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="transparency-box">
            <strong>ℹ️ Explainable Transparency Summary:</strong> <?= htmlspecialchars($prediction['explanation']) ?>
            <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 0.45rem;">
                * Crowd capacity utilization percentages represent statistical estimates computed from Apache CouchDB historical records and validated passenger feedback.
            </div>
        </div>
    </div>

    <!-- Multi-Horizon Breakdown Cards -->
    <?php if (!empty($multiHorizons)): ?>
        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Upcoming Forecast Horizons (Next 2 Hours)</h3>
                    <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.2rem;">
                        Projected crowd trends at 15, 30, 60, and 120-minute intervals:
                    </p>
                </div>
                <a href="/predictions/compare?route_id=<?= urlencode($selectedRouteId) ?>&stop_id=<?= urlencode($selectedStopId) ?>" class="btn btn-sm btn-outline" style="color: var(--primary); border-color: var(--primary-light);">
                    Compare Custom Departure Times →
                </a>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem;">
                <?php foreach ($multiHorizons as $horizonMins => $hPred): ?>
                    <?php
                    $hCat = $hPred['crowd_category'];
                    $hOcc = $hPred['estimated_occupancy_percent'];
                    ?>
                    <div style="background: #ffffff; border: 1.5px solid var(--card-border); border-radius: var(--radius); padding: 1.25rem; box-shadow: var(--shadow-xs);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <strong style="color: var(--primary-navy); font-size: 1.05rem;">+<?= $horizonMins ?> mins</strong>
                            <span style="font-size: 0.82rem; color: var(--text-muted);"><?= date('g:i A', strtotime($hPred['prediction_time'])) ?></span>
                        </div>

                        <div style="margin: 0.75rem 0;">
                            <?php if ($hCat): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                                    <span class="badge badge-<?= htmlspecialchars($hCat) ?>"><?= ucfirst(str_replace('_', ' ', $hCat)) ?></span>
                                    <strong style="font-size: 1.15rem; color: var(--primary-navy);"><?= (int)$hOcc ?>%</strong>
                                </div>
                                <div class="occupancy-meter" style="height: 10px; margin: 0.25rem 0;">
                                    <div class="occupancy-fill fill-<?= htmlspecialchars($hCat) ?>" style="width: <?= (int)$hOcc ?>%;"></div>
                                </div>
                            <?php else: ?>
                                <span class="badge badge-mode">Insufficient Data</span>
                            <?php endif; ?>
                        </div>

                        <div style="font-size: 0.78rem; color: #475569; line-height: 1.4; border-top: 1px solid var(--border-subtle); padding-top: 0.6rem;">
                            <?= htmlspecialchars($hPred['explanation']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php
require dirname(__DIR__) . '/layout/footer.php';
?>
