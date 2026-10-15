<?php
require dirname(__DIR__) . '/layout/header.php';
?>

<div style="margin-bottom: 2rem;">
    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.4rem;">
        <span class="badge badge-mode">Crowdsourcing</span>
        <span style="font-size: 0.82rem; color: var(--text-muted);">Collaborative Transit Intelligence</span>
    </div>
    <h1 style="font-size: 2.2rem; font-weight: 800; color: var(--primary-navy); margin-bottom: 0.4rem;">
        Submit Voluntary Crowd Report
    </h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">
        Help fellow passengers travel comfortably. Share your real-world transit crowd observation.
    </p>
</div>

<div style="display: grid; grid-template-columns: 3fr 2fr; gap: 2rem; align-items: flex-start;">
    <!-- Report Form -->
    <div class="card" style="box-shadow: var(--shadow-md);">
        <div class="card-header">
            <h3 class="card-title">Observation Details</h3>
            <span class="badge badge-low">Verified Storage</span>
        </div>

        <form action="/reports/submit" method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                <div class="form-group">
                    <label class="form-label">Corridor / Line *</label>
                    <select name="route_id" id="routeSelect" class="form-select" required>
                        <option value="">-- Choose Route --</option>
                        <?php foreach ($routes as $r): ?>
                            <option value="<?= htmlspecialchars($r['_id']) ?>" data-stops='<?= json_encode($r['stop_ids'] ?? []) ?>' <?= (($selectedRoute ?? '') === $r['_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['route_number']) ?> - <?= htmlspecialchars($r['route_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Station / Stop *</label>
                    <select name="stop_id" id="stopSelect" class="form-select" required>
                        <option value="">-- Choose Stop --</option>
                        <?php foreach ($stops as $s): ?>
                            <option value="<?= htmlspecialchars($s['_id']) ?>" <?= (($selectedStop ?? '') === $s['_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['stop_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                <div class="form-group">
                    <label class="form-label">Transport Mode *</label>
                    <select name="transport_mode" class="form-select" required>
                        <option value="bus">🚌 Bus</option>
                        <option value="metro">🚇 Metro</option>
                        <option value="train">🚆 Suburban Train</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Observation Timestamp</label>
                    <input type="datetime-local" name="observed_at" value="<?= date('Y-m-d\TH:i') ?>" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Observed Crowd Level *</label>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 0.85rem;">
                    <label style="border: 2px solid var(--crowd-low-border); background: var(--crowd-low-bg); border-radius: var(--radius); padding: 0.85rem; cursor: pointer; text-align: center; transition: all 0.2s;">
                        <input type="radio" name="crowd_category" value="low" required>
                        <div style="font-weight: 800; color: var(--crowd-low); margin-top: 0.35rem;">Low</div>
                        <div style="font-size: 0.72rem; color: #065f46; margin-top: 0.15rem;">Many seats free (0-30%)</div>
                    </label>

                    <label style="border: 2px solid var(--crowd-moderate-border); background: var(--crowd-moderate-bg); border-radius: var(--radius); padding: 0.85rem; cursor: pointer; text-align: center; transition: all 0.2s;">
                        <input type="radio" name="crowd_category" value="moderate">
                        <div style="font-weight: 800; color: var(--crowd-moderate); margin-top: 0.35rem;">Moderate</div>
                        <div style="font-size: 0.72rem; color: #78350f; margin-top: 0.15rem;">Some seats (31-60%)</div>
                    </label>

                    <label style="border: 2px solid var(--crowd-high-border); background: var(--crowd-high-bg); border-radius: var(--radius); padding: 0.85rem; cursor: pointer; text-align: center; transition: all 0.2s;">
                        <input type="radio" name="crowd_category" value="high">
                        <div style="font-weight: 800; color: var(--crowd-high); margin-top: 0.35rem;">High</div>
                        <div style="font-size: 0.72rem; color: #7f1d1d; margin-top: 0.15rem;">Standing only (61-80%)</div>
                    </label>

                    <label style="border: 2px solid var(--crowd-very-high-border); background: var(--crowd-very-high-bg); border-radius: var(--radius); padding: 0.85rem; cursor: pointer; text-align: center; transition: all 0.2s;">
                        <input type="radio" name="crowd_category" value="very_high">
                        <div style="font-weight: 800; color: var(--crowd-very-high); margin-top: 0.35rem;">Very High</div>
                        <div style="font-size: 0.72rem; color: #881337; margin-top: 0.15rem;">Packed (81-100%)</div>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                    <label class="form-label" style="margin-bottom: 0;">Approximate Occupancy Utilization</label>
                    <span id="rangeVal" style="font-weight: 800; font-size: 1.1rem; color: var(--primary-light);">50%</span>
                </div>
                <input type="range" name="approximate_occupancy" min="0" max="100" value="50" step="5" class="form-control" style="cursor: pointer;" oninput="updateRangeLabel(this.value)">
                <div style="display: flex; justify-content: space-between; font-size: 0.72rem; color: var(--text-muted); font-weight: 600; margin-top: 0.2rem;">
                    <span>0% Empty</span>
                    <span>50% Half Full</span>
                    <span>100% Packed</span>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Passenger Comments & Context (Optional)</label>
                <textarea name="comments" class="form-control" rows="3" placeholder="e.g. Platform congested due to light rain, smooth boarding..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                Submit Crowd Observation
            </button>
        </form>

        <div class="transparency-box">
            🛡️ <strong>Privacy Protection Guarantee:</strong> Precise GPS coordinates are never collected. Only corridor identifier, station, observation time, and crowd category are stored in CouchDB. Network IP addresses are irreversibly hashed for duplicate and spam protection.
        </div>
    </div>

    <!-- Recent Submissions Feed -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Live Passenger Feed</h3>
            <span class="badge badge-mode">Real-time</span>
        </div>

        <?php if (empty($recentReports)): ?>
            <p style="color: var(--text-muted); font-size: 0.9rem; text-align: center; padding: 2rem;">
                No crowd reports submitted yet. Be the first to share an observation!
            </p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach (array_slice($recentReports, 0, 7) as $rep): ?>
                    <div style="border-bottom: 1px solid var(--border-subtle); padding-bottom: 0.85rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                            <strong><?= htmlspecialchars($rep['route_id']) ?></strong>
                            <span class="badge badge-<?= htmlspecialchars($rep['crowd_category'] ?? 'moderate') ?>">
                                <?= ucfirst(str_replace('_', ' ', $rep['crowd_category'] ?? 'moderate')) ?>
                            </span>
                        </div>
                        <div style="font-size: 0.85rem; color: #475569;">
                            Calling at: <strong><?= htmlspecialchars($rep['stop_id']) ?></strong>
                        </div>
                        <?php if (!empty($rep['comments'])): ?>
                            <div style="font-size: 0.82rem; font-style: italic; color: var(--text-muted); margin-top: 0.3rem;">
                                "<?= htmlspecialchars($rep['comments']) ?>"
                            </div>
                        <?php endif; ?>
                        <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 0.35rem;">
                            <?= date('M j, g:i A', strtotime($rep['reported_at'] ?? 'now')) ?> • Status: <?= ucfirst($rep['status'] ?? 'pending') ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function updateRangeLabel(val) {
    const label = document.getElementById('rangeVal');
    label.innerText = val + '%';
    if (val <= 30) {
        label.style.color = 'var(--crowd-low)';
    } else if (val <= 60) {
        label.style.color = 'var(--crowd-moderate)';
    } else if (val <= 80) {
        label.style.color = 'var(--crowd-high)';
    } else {
        label.style.color = 'var(--crowd-very-high)';
    }
}
</script>

<?php
require dirname(__DIR__) . '/layout/footer.php';
?>
