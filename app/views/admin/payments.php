<?php require_once ROOT_PATH . '/app/views/admin/_helpers.php'; ?>
<?php
$summary = $summary ?? [];
$monthlyRevenue = (float)($summary['monthly_revenue'] ?? 0);
$previousMonthRevenue = (float)($summary['previous_month_revenue'] ?? 0);
$monthlyDelta = $previousMonthRevenue > 0
    ? (($monthlyRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100
    : 0;
$monthlyDeltaClass = $monthlyDelta >= 0 ? 'up' : 'down';
$monthlyDeltaPrefix = $monthlyDelta >= 0 ? '↑ +' : '↓ ';
?>

<div class="module-header">
    <div>
        <h1>Payments</h1>
        <p>Transaction history & revenue</p>
    </div>
</div>

<section class="stats-grid two">
    <a class="stat-card c-green" href="#transactions">
        <div class="stat-label">Total Revenue</div>
        <div class="stat-value"><?= money($summary['total_revenue'] ?? 0) ?></div>
        <div class="stat-delta up"><?= number_format((int)($summary['success_count'] ?? 0)) ?> successful payments</div>
    </a>
    <a class="stat-card c-amber" href="#transactions">
        <div class="stat-label">This Month</div>
        <div class="stat-value"><?= money($summary['monthly_revenue'] ?? 0) ?></div>
        <div class="stat-delta <?= $monthlyDeltaClass ?>"><?= $monthlyDeltaPrefix ?><?= number_format(abs($monthlyDelta), 1) ?>% vs last month</div>
    </a>
</section>

<form class="filters module-filters" method="get">
    <input type="hidden" name="module" value="admin"><input type="hidden" name="page" value="payments">
    <select class="filter-input" name="status">
        <option value="">All Status</option>
        <?php foreach (['success', 'pending', 'failed', 'refunded'] as $status): ?>
            <option value="<?= $status ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
        <?php endforeach; ?>
    </select>
    <select class="filter-input" name="method">
        <option value="">All Methods</option>
        <?php foreach (['UPI', 'Card', 'Wallet', 'NetBanking', 'Paypal'] as $method): ?>
            <option value="<?= $method ?>" <?= $filters['method'] === $method ? 'selected' : '' ?>><?= $method ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn-ghost" type="submit">Filter</button>
</form>

<section class="panel payment-table-panel" id="transactions">
    <div class="panel-head"><span class="text-success">▭</span><span class="panel-title">Transaction History</span></div>
    <div class="table-responsive payment-table-scroll">
        <table class="data-table">
            <thead><tr><th>Txn ID</th><th>User</th><th>Plan</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr data-search-row>
                        <td><?= h($payment['txn_id']) ?></td>
                        <td><strong><?= h($payment['user']) ?></strong></td>
                        <td><span class="pill <?= strtolower($payment['plan']) === 'premium' ? 'pill-red' : 'pill-blue' ?>"><?= h($payment['plan']) ?></span></td>
                        <td><strong><?= money($payment['amount'], $payment['currency'] === 'INR' ? '₹' : '$') ?></strong></td>
                        <td><?= h($payment['method']) ?></td>
                        <td><span class="pill <?= status_class($payment['status']) ?>"><?= h(ucfirst($payment['status'])) ?></span></td>
                        <td><?= $payment['paid_at'] ? date('M j, Y', strtotime($payment['paid_at'])) : '-' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
