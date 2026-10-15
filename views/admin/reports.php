<?php
require dirname(__DIR__) . '/layout/header.php';
$currStatus = $_GET['status'] ?? 'pending';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
            <a href="/admin" style="color: var(--text-muted); text-decoration: none;">← Admin Console</a>
        </div>
        <h1 style="font-size: 2rem; font-weight: 800; color: var(--primary-dark);">
            Crowd Report Moderation Queue
        </h1>
    </div>
</div>

<div class="card" style="padding: 0.75rem 1.25rem; margin-bottom: 2rem;">
    <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
        <span style="font-weight: 700; color: var(--primary-dark); font-size: 0.9rem;">ADMIN MODULES:</span>
        <a href="/admin" class="btn btn-sm btn-secondary">Health & Status</a>
        <a href="/admin/routes" class="btn btn-sm btn-secondary">Manage Routes</a>
        <a href="/admin/stops" class="btn btn-sm btn-secondary">Manage Stops</a>
        <a href="/admin/reports" class="btn btn-sm btn-primary">Report Moderation</a>
    </div>
</div>

<!-- Filter Tabs -->
<div class="card" style="padding: 0.75rem 1.25rem;">
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
        <span style="font-weight: 600; font-size: 0.9rem; margin-right: 0.5rem;">FILTER QUEUE:</span>
        <a href="/admin/reports?status=pending" class="btn btn-sm <?= ($currStatus === 'pending') ? 'btn-primary' : 'btn-secondary' ?>">
            ⏳ Pending Moderation
        </a>
        <a href="/admin/reports?status=approved" class="btn btn-sm <?= ($currStatus === 'approved') ? 'btn-primary' : 'btn-secondary' ?>">
            ✓ Approved Observations
        </a>
        <a href="/admin/reports?status=rejected" class="btn btn-sm <?= ($currStatus === 'rejected') ? 'btn-primary' : 'btn-secondary' ?>">
            ✗ Rejected Reports
        </a>
        <a href="/admin/reports?status=all" class="btn btn-sm <?= ($currStatus === 'all') ? 'btn-primary' : 'btn-secondary' ?>">
            All Records
        </a>
    </div>
</div>

<!-- Reports Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Submitted Crowd Observations (<?= count($reports) ?>)</h3>
    </div>

    <?php if (empty($reports)): ?>
        <p style="text-align: center; color: var(--text-muted); padding: 2rem;">
            No reports found in the '<?= htmlspecialchars($currStatus) ?>' queue.
        </p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Report ID</th>
                        <th>Corridor</th>
                        <th>Stop</th>
                        <th>Mode</th>
                        <th>Crowd</th>
                        <th>Occ %</th>
                        <th>Submitted At</th>
                        <th>Comments</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $rep): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($rep['_id']) ?></code></td>
                            <td><strong><?= htmlspecialchars($rep['route_id']) ?></strong></td>
                            <td><?= htmlspecialchars($rep['stop_id']) ?></td>
                            <td><span class="badge badge-mode"><?= strtoupper($rep['transport_mode'] ?? 'bus') ?></span></td>
                            <td>
                                <span class="badge badge-<?= htmlspecialchars($rep['crowd_category'] ?? 'moderate') ?>">
                                    <?= ucfirst(str_replace('_', ' ', $rep['crowd_category'] ?? 'moderate')) ?>
                                </span>
                            </td>
                            <td><?= isset($rep['approximate_occupancy']) ? (int)$rep['approximate_occupancy'] . '%' : '--' ?></td>
                            <td style="font-size: 0.8rem; color: var(--text-muted);"><?= date('M j, g:i A', strtotime($rep['reported_at'] ?? 'now')) ?></td>
                            <td style="font-size: 0.8rem; max-width: 200px;"><?= htmlspecialchars($rep['comments'] ?? 'No comments') ?></td>
                            <td>
                                <?php if (($rep['status'] ?? '') === 'approved'): ?>
                                    <span class="badge badge-low">Approved</span>
                                <?php elseif (($rep['status'] ?? '') === 'rejected'): ?>
                                    <span class="badge badge-high">Rejected</span>
                                <?php else: ?>
                                    <span class="badge badge-moderate">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form action="/admin/reports/moderate" method="POST" style="display: flex; gap: 0.25rem;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="report_id" value="<?= htmlspecialchars($rep['_id']) ?>">
                                    
                                    <?php if (($rep['status'] ?? '') !== 'approved'): ?>
                                        <button type="submit" name="action" value="approved" class="btn btn-sm btn-success" title="Approve and promote to historical observation">✓</button>
                                    <?php endif; ?>

                                    <?php if (($rep['status'] ?? '') !== 'rejected'): ?>
                                        <button type="submit" name="action" value="rejected" class="btn btn-sm btn-secondary" title="Mark report as rejected">✗</button>
                                    <?php endif; ?>

                                    <button type="submit" name="action" value="delete" class="btn btn-sm btn-danger" onclick="return confirm('Delete this report record permanently?');" title="Delete record">🗑</button>
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
