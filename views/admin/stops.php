<?php
require dirname(__DIR__) . '/layout/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
            <a href="/admin" style="color: var(--text-muted); text-decoration: none;">← Admin Console</a>
        </div>
        <h1 style="font-size: 2rem; font-weight: 800; color: var(--primary-dark);">
            Manage Stops & Stations
        </h1>
    </div>
</div>

<div class="card" style="padding: 0.75rem 1.25rem; margin-bottom: 2rem;">
    <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
        <span style="font-weight: 700; color: var(--primary-dark); font-size: 0.9rem;">ADMIN MODULES:</span>
        <a href="/admin" class="btn btn-sm btn-secondary">Health & Status</a>
        <a href="/admin/routes" class="btn btn-sm btn-secondary">Manage Routes</a>
        <a href="/admin/stops" class="btn btn-sm btn-primary">Manage Stops</a>
        <a href="/admin/reports" class="btn btn-sm btn-secondary">Report Moderation</a>
    </div>
</div>

<!-- Add New Stop Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">+ Add New Stop / Station</h3>
    </div>

    <form action="/admin/stops/create" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

        <div style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Stop Code</label>
                <input type="text" name="stop_code" placeholder="ST-101" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label">Station / Stop Name *</label>
                <input type="text" name="stop_name" placeholder="e.g. West Coast Terminal" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label">Transport Mode *</label>
                <select name="transport_mode" class="form-select" required>
                    <option value="bus">Bus</option>
                    <option value="metro">Metro</option>
                    <option value="train">Suburban Train</option>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Zone / Area</label>
                <input type="text" name="zone" value="Zone 1 - Downtown" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">Latitude</label>
                <input type="number" step="any" name="latitude" placeholder="e.g. 12.9716" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">Longitude</label>
                <input type="number" step="any" name="longitude" placeholder="e.g. 77.5946" class="form-control">
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            Save Stop to CouchDB
        </button>
    </form>
</div>

<!-- Stops Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Existing Stops & Stations (<?= count($stops) ?>)</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Stop Name</th>
                    <th>Mode</th>
                    <th>Zone</th>
                    <th>Coordinates</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stops as $stop): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($stop['stop_code'] ?? 'ST') ?></code></td>
                        <td>
                            <a href="/stops/<?= urlencode($stop['_id']) ?>" style="color: var(--text-main); font-weight: 600;">
                                <?= htmlspecialchars($stop['stop_name']) ?>
                            </a>
                        </td>
                        <td><span class="badge badge-mode"><?= strtoupper($stop['transport_mode']) ?></span></td>
                        <td><?= htmlspecialchars($stop['zone'] ?? 'Standard Zone') ?></td>
                        <td>
                            <?php if ($stop['latitude'] && $stop['longitude']): ?>
                                <?= number_format($stop['latitude'], 4) ?>, <?= number_format($stop['longitude'], 4) ?>
                            <?php else: ?>
                                <span class="text-muted">Unmapped</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form action="/admin/stops/delete" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete stop <?= htmlspecialchars($stop['stop_name']) ?>?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="stop_id" value="<?= htmlspecialchars($stop['_id']) ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
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
