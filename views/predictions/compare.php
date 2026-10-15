<?php
require dirname(__DIR__) . '/layout/header.php';
?>

<div style="margin-bottom: 2rem;">
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
        <span class="badge badge-mode">Trip Planner</span>
        <span style="font-size: 0.82rem; color: var(--text-muted);">Smart Departure Recommendation</span>
    </div>
    <h1 style="font-size: 2.2rem; font-weight: 800; color: var(--primary-navy); margin-bottom: 0.4rem;">
        Departure Time Crowd Comparison
    </h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">
        Compare anticipated crowd conditions across different departure windows to choose the most comfortable travel time.
    </p>
</div>

<!-- Selection Form -->
<div class="card" style="box-shadow: var(--shadow-md);">
    <form action="/predictions/compare" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)) 160px; gap: 1.25rem; align-items: flex-end;">
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

        <button type="submit" class="btn btn-primary btn-lg" style="height: 48px; width: 100%;">
            Compare Windows
        </button>
    </form>
</div>

<?php if ($comparison !== null): ?>
    <!-- Recommendation Banner -->
    <?php if ($comparison['recommended_time']): ?>
        <div class="card" style="background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%); border: 2px solid var(--crowd-low-border); box-shadow: 0 10px 25px var(--crowd-low-glow); margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 52px; height: 52px; border-radius: 50%; background: #10b981; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);">
                        💡
                    </div>
                    <div>
                        <div style="font-size: 0.82rem; font-weight: 700; color: #047857; text-transform: uppercase; letter-spacing: 0.6px;">
                            OPTIMAL DEPARTURE WINDOW
                        </div>
                        <h2 style="font-size: 1.8rem; font-weight: 800; color: #065f46; letter-spacing: -0.02em;">
                            <?= htmlspecialchars($comparison['recommended_time']) ?>
                        </h2>
                        <div style="font-size: 0.92rem; color: #047857; margin-top: 0.2rem;">
                            Predicted lowest crowd density (<strong><?= ucfirst(str_replace('_', ' ', $comparison['recommended_category'] ?? '')) ?></strong>) among evaluated departure times.
                        </div>
                    </div>
                </div>

                <span class="badge badge-low" style="font-size: 0.95rem; padding: 0.5rem 1.1rem;">
                    ★ Best Choice
                </span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Visual Comparison Cards -->
    <div class="comparison-grid">
        <?php foreach ($comparison['options'] as $opt): ?>
            <?php
            $isRec = ($opt['time_full'] === ($comparison['recommended_time'] ?? ''));
            $cat = $opt['crowd_category'];
            $occ = $opt['occupancy_percent'];
            ?>
            <div class="compare-slot <?= $isRec ? 'recommended' : '' ?>">
                <div class="compare-time"><?= htmlspecialchars($opt['time_full']) ?></div>
                
                <?php if ($cat): ?>
                    <div style="margin: 0.6rem 0;">
                        <span class="badge badge-<?= htmlspecialchars($cat) ?>">
                            <span class="pulse-dot pulse-<?= htmlspecialchars($cat) ?>"></span>
                            <?= ucfirst(str_replace('_', ' ', $cat)) ?>
                        </span>
                    </div>

                    <?php if ($occ !== null): ?>
                        <div style="font-size: 1.6rem; font-weight: 800; color: var(--primary-navy); margin: 0.5rem 0;">
                            <?= (int)$occ ?>%
                        </div>
                        <div class="occupancy-meter" style="height: 12px;">
                            <div class="occupancy-fill fill-<?= htmlspecialchars($cat) ?>" style="width: <?= (int)$occ ?>%;"></div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="margin: 0.75rem 0;">
                        <span class="badge badge-mode" style="color: #64748b;">No Records</span>
                    </div>
                    <div style="font-size: 0.8rem; color: var(--text-muted); padding: 0.5rem 0;">
                        Insufficient historical observations
                    </div>
                <?php endif; ?>

                <?php if ($isRec): ?>
                    <div style="font-size: 0.8rem; font-weight: 700; color: var(--crowd-low); margin-top: 0.75rem; background: rgba(16, 185, 129, 0.15); padding: 0.3rem 0.6rem; border-radius: var(--radius-full); display: inline-block;">
                        ★ Least Crowded
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Notice Box -->
    <div class="transparency-box" style="margin-top: 2rem;">
        <strong>Notice & Disclaimer:</strong> <?= htmlspecialchars($comparison['disclaimer']) ?>
    </div>
<?php endif; ?>

<?php
require dirname(__DIR__) . '/layout/footer.php';
?>
