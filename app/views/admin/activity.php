<?php require_once ROOT_PATH . '/app/views/admin/_helpers.php'; ?>

<div class="module-header">
    <div>
        <h1>Activity Logs</h1>
        <p>Complete audit trail</p>
    </div>
    <form method="post" action="<?= admin_url('activity', ['action' => 'clear']) ?>" data-confirm="Clear all activity logs?">
        <button class="btn btn-ghost" type="submit">⌫ Clear Logs</button>
    </form>
</div>

<?php if ($flash): ?><div class="alert <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div><?php endif; ?>

<form class="filters module-filters" method="get">
    <input type="hidden" name="module" value="admin"><input type="hidden" name="page" value="activity">
    <input class="filter-input flex-grow-1" name="search" value="<?= h($filters['search']) ?>" placeholder="  Search logs...">
    <select class="filter-input" name="action">
        <option value="">All Actions</option>
        <?php foreach (['Login', 'Settings Updated', 'Video Deleted', 'Category Added', 'User Suspended', 'New video uploaded'] as $action): ?>
            <option value="<?= h($action) ?>" <?= $filters['action'] === $action ? 'selected' : '' ?>><?= h($action) ?></option>
        <?php endforeach; ?>
    </select>
    <input class="filter-input" type="date" name="date" value="<?= h($filters['date']) ?>">
    <button class="btn btn-ghost" type="submit">Search</button>
</form>

<section class="panel activity-table-panel">
    <div class="table-responsive activity-table-scroll">
        <table class="data-table">
            <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Module</th><th>Details</th><th>IP</th></tr></thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr data-search-row>
                        <td><?= date('M j H:i', strtotime($log['logged_at'])) ?></td>
                        <td><strong><?= h($log['actor']) ?></strong></td>
                        <td><strong><?= h($log['action']) ?></strong></td>
                        <td><span class="pill pill-gray"><?= h($log['module']) ?></span></td>
                        <td><?= h($log['details']) ?></td>
                        <td><?= h($log['ip_address']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
