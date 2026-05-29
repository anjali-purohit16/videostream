<?php require_once ROOT_PATH . '/app/views/admin/_helpers.php'; ?>

<div class="module-header">
    <div>
        <h1>Reports</h1>
        <p>Flagged content & user complaints</p>
    </div>
</div>

<?php if ($flash): ?><div class="alert <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div><?php endif; ?>

<section class="panel report-table-panel">
    <div class="table-responsive report-table-scroll">
        <table class="data-table">
            <thead><tr><th>Report ID</th><th>Type</th><th>Reported By</th><th>Content</th><th>Reason</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody data-live-report-rows>
                <?php foreach ($reports as $report): ?>
                    <tr data-search-row>
                        <td><?= h($report['report_code']) ?></td>
                        <td><span class="pill pill-gray"><?= h($report['type']) ?></span></td>
                        <td><?= h($report['reporter']) ?></td>
                        <td><strong><?= h($report['content_ref']) ?></strong></td>
                        <td><?= h($report['reason']) ?></td>
                        <td><?= date('M j', strtotime($report['created_at'])) ?></td>
                        <td><span class="pill <?= status_class($report['status']) ?>"><?= h(ucfirst($report['status'])) ?></span></td>
                        <td class="action-cell">
                            <form method="post" action="<?= admin_url('reports', ['action' => 'resolve']) ?>">
                                <input type="hidden" name="id" value="<?= (int)$report['id'] ?>">
                                <button class="mini-btn" type="submit">Review</button>
                            </form>
                            <form method="post" action="<?= admin_url('reports', ['action' => 'dismiss']) ?>">
                                <input type="hidden" name="id" value="<?= (int)$report['id'] ?>">
                                <button class="mini-btn" type="submit">Dismiss</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
