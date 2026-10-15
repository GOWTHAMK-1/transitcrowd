<?php
require dirname(__DIR__) . '/layout/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
            <span class="badge badge-high" style="font-size: 0.75rem;">ADMIN CONTROL ACTIVE</span>
            <span style="font-size: 0.85rem; color: var(--text-muted);">Operator: <?= htmlspecialchars($currentUser['email'] ?? 'admin@example.test') ?></span>
        </div>
        <h1 style="font-size: 2.2rem; font-weight: 800; color: var(--primary-navy);">
            Administrator Control Center
        </h1>
    </div>

    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
        <a href="<?= htmlspecialchars($health['couchdb']['fauxton_url']) ?>" target="_blank" rel="noopener" class="btn btn-secondary">
            🖥 Open Fauxton NoSQL UI ↗
        </a>
        <a href="/admin/reports" class="btn btn-primary">
            Review Reports (<?= count($pendingReports) ?>)
        </a>
    </div>
</div>

<!-- Admin Navigation Sub-bar -->
<div class="card" style="padding: 0.85rem 1.25rem; margin-bottom: 2rem;">
    <div style="display: flex; gap: 0.65rem; flex-wrap: wrap; align-items: center;">
        <span style="font-weight: 700; color: var(--primary-navy); font-size: 0.85rem; margin-right: 0.5rem; text-transform: uppercase;">ADMIN MODULES:</span>
        <a href="/admin" class="btn btn-sm btn-primary">Health & Status</a>
        <a href="/admin/routes" class="btn btn-sm btn-secondary">Manage Routes</a>
        <a href="/admin/stops" class="btn btn-sm btn-secondary">Manage Stops</a>
        <a href="/admin/reports" class="btn btn-sm btn-secondary">Report Moderation (<?= count($pendingReports) ?>)</a>
    </div>
</div>

<!-- System & CouchDB Health Cards -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="metric-icon" style="color: #059669; background: #ecfdf5;">🗄️</div>
        <div>
            <div style="font-size: 1.35rem; font-weight: 800; color: <?= $health['couchdb']['status'] === 'online' ? 'var(--crowd-low)' : 'var(--crowd-high)' ?>;">
                <?= strtoupper($health['couchdb']['status']) ?>
            </div>
            <div class="metric-label">CouchDB v<?= htmlspecialchars($health['couchdb']['version']) ?></div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon" style="color: #2563eb; background: #eff6ff;">📄</div>
        <div>
            <div class="metric-num"><?= number_format($health['summary']['total_documents']) ?></div>
            <div class="metric-label">Stored CouchDB Documents</div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon" style="color: #7c3aed; background: #f5f3ff;">💾</div>
        <div>
            <div class="metric-num"><?= htmlspecialchars($health['summary']['total_disk_size']) ?></div>
            <div class="metric-label">Total NoSQL Disk Footprint</div>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-icon" style="color: #0284c7; background: #f0f9ff;">🐘</div>
        <div>
            <div style="font-size: 1.25rem; font-weight: 800; color: var(--primary-navy);">PHP <?= PHP_VERSION ?></div>
            <div class="metric-label">cURL: <?= $health['php']['curl_enabled'] ? 'Enabled' : 'Disabled' ?></div>
        </div>
    </div>
</div>

<!-- CouchDB Database Breakdown Table -->
<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title">CouchDB Database Registry & Document Counts</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.15rem;">Active NoSQL databases configured in .env:</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Database Key</th>
                    <th>CouchDB Database Name</th>
                    <th>Document Count</th>
                    <th>Disk Footprint</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($health['databases'] as $key => $db): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($key) ?></code></td>
                        <td><strong><?= htmlspecialchars($db['name']) ?></strong></td>
                        <td><?= number_format($db['doc_count']) ?> documents</td>
                        <td><?= htmlspecialchars($db['disk_size_formatted']) ?></td>
                        <td>
                            <?php if ($db['exists']): ?>
                                <span class="badge badge-low">
                                    <span class="pulse-dot pulse-low"></span> Online
                                </span>
                            <?php else: ?>
                                <span class="badge badge-high">Missing</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pending Reports Awaiting Moderation -->
<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title">Pending Crowd Reports Awaiting Review</h3>
            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.15rem;">Approve reports to promote them into historical observation models.</p>
        </div>
        <a href="/admin/reports" class="btn btn-sm btn-primary">Go to Moderation Queue →</a>
    </div>

    <?php if (empty($pendingReports)): ?>
        <p style="text-align: center; color: var(--text-muted); padding: 2rem;">
            No reports currently awaiting review. Queue is clear!
        </p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Report ID</th>
                        <th>Corridor</th>
                        <th>Station</th>
                        <th>Reported Crowd</th>
                        <th>Occupancy</th>
                        <th>Submitted At</th>
                        <th>Quick Moderation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingReports as $rep): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($rep['_id']) ?></code></td>
                            <td><strong><?= htmlspecialchars($rep['route_id']) ?></strong></td>
                            <td><?= htmlspecialchars($rep['stop_id']) ?></td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($rep['crowd_category'] ?? 'moderate') ?>">
                                    <?= ucfirst(str_replace('_', ' ', $rep['crowd_category'] ?? 'moderate')) ?>
                                </span>
                            </td>
                            <td><?= isset($rep['approximate_occupancy']) ? (int)$rep['approximate_occupancy'] . '%' : '--' ?></td>
                            <td style="font-size: 0.82rem; color: var(--text-muted);"><?= date('M j, g:i A', strtotime($rep['reported_at'] ?? 'now')) ?></td>
                            <td>
                                <form action="/admin/reports/moderate" method="POST" style="display: inline-flex; gap: 0.35rem;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="report_id" value="<?= htmlspecialchars($rep['_id']) ?>">
                                    <button type="submit" name="action" value="approved" class="btn btn-sm btn-success">✓ Approve</button>
                                    <button type="submit" name="action" value="rejected" class="btn btn-sm btn-secondary">✗ Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
require dirname(__DIR__) . '/layout/footer.php';
?>
