<?php
require dirname(__DIR__) . '/layout/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
            <a href="/admin" style="color: var(--text-muted); text-decoration: none;">← Admin Console</a>
        </div>
        <h1 style="font-size: 2rem; font-weight: 800; color: var(--primary-dark);">
            Manage Routes & Corridors
        </h1>
    </div>
</div>

<div class="card" style="padding: 0.75rem 1.25rem; margin-bottom: 2rem;">
    <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
        <span style="font-weight: 700; color: var(--primary-dark); font-size: 0.9rem;">ADMIN MODULES:</span>
        <a href="/admin" class="btn btn-sm btn-secondary">Health & Status</a>
        <a href="/admin/routes" class="btn btn-sm btn-primary">Manage Routes</a>
        <a href="/admin/stops" class="btn btn-sm btn-secondary">Manage Stops</a>
        <a href="/admin/reports" class="btn btn-sm btn-secondary">Report Moderation</a>
    </div>
</div>

<!-- Add New Route Form -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">+ Add New Transit Route</h3>
    </div>

    <form action="/admin/routes/create" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

        <div style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Route Number / Code *</label>
                <input type="text" name="route_number" placeholder="e.g. M-RED or 305" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label">Route Name *</label>
                <input type="text" name="route_name" placeholder="e.g. North Metro Express Corridor" class="form-control" required>
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
                <label class="form-label">Frequency (Minutes)</label>
                <input type="number" name="frequency_minutes" value="12" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">Operating Schedule</label>
                <input type="text" name="operating_hours" value="06:00 - 22:30" class="form-control">
            </div>

            <div class="form-group">
                <label class="form-label">Rated Vehicle Capacity</label>
                <input type="number" name="vehicle_capacity" value="80" class="form-control">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Corridor Description</label>
            <input type="text" name="description" placeholder="Short description of stops and connectivity..." class="form-control">
        </div>

        <div class="form-group">
            <label class="form-label">Associated Stop IDs (Comma-separated)</label>
            <input type="text" name="stop_ids" placeholder="stop:central-station, stop:tech-park" class="form-control">
            <small style="color: var(--text-muted);">Available stops: <?= implode(', ', array_column($stops, '_id')) ?></small>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="active" value="1" checked>
                <strong>Route is currently active and open for forecasting</strong>
            </label>
        </div>

        <button type="submit" class="btn btn-primary">
            Save Route to CouchDB
        </button>
    </form>
</div>

<!-- Routes Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Existing Routes (<?= count($routes) ?>)</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Route Name</th>
                    <th>Mode</th>
                    <th>Schedule</th>
                    <th>Headway</th>
                    <th>Capacity</th>
                    <th>Stops</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($routes as $route): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($route['route_number']) ?></strong></td>
                        <td>
                            <a href="/routes/<?= urlencode($route['_id']) ?>" style="color: var(--text-main); font-weight: 600;">
                                <?= htmlspecialchars($route['route_name']) ?>
                            </a>
                        </td>
                        <td><span class="badge badge-mode"><?= strtoupper($route['transport_mode']) ?></span></td>
                        <td><?= htmlspecialchars($route['operating_hours'] ?? 'Standard') ?></td>
                        <td><?= (int)($route['frequency_minutes'] ?? 15) ?>m</td>
                        <td><?= !empty($route['vehicle_capacity']) ? number_format($route['vehicle_capacity']) : '--' ?></td>
                        <td><?= count($route['stop_ids'] ?? []) ?></td>
                        <td>
                            <?php if ($route['active'] ?? true): ?>
                                <span class="badge badge-low">Active</span>
                            <?php else: ?>
                                <span class="badge badge-high">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form action="/admin/routes/delete" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete route <?= htmlspecialchars($route['route_number']) ?>?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="route_id" value="<?= htmlspecialchars($route['_id']) ?>">
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
