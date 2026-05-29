<?php
require_once ROOT_PATH . '/app/views/admin/_helpers.php';

function vs_num(float|int|null $value): string
{
    $value = (float)($value ?? 0);
    if ($value >= 1000000) {
        return rtrim(rtrim(number_format($value / 1000000, 1), '0'), '.') . 'M';
    }
    if ($value >= 1000) {
        return rtrim(rtrim(number_format($value / 1000, 1), '0'), '.') . 'K';
    }
    return number_format($value);
}

function vs_money(float|int|null $value): string
{
    return '$' . number_format((float)($value ?? 0));
}

function vs_status_class(string $status): string
{
    return match ($status) {
        'published', 'active', 'success' => 'pill-green',
        'processing', 'pending' => 'pill-amber',
        'draft' => 'pill-blue',
        default => 'pill-red',
    };
}

function vs_time_ago(?string $date): string
{
    if (!$date) {
        return 'Just now';
    }

    $seconds = max(1, time() - strtotime($date));
    if ($seconds < 3600) {
        return floor($seconds / 60) . ' minutes ago';
    }
    if ($seconds < 86400) {
        return floor($seconds / 3600) . ' hours ago';
    }
    return floor($seconds / 86400) . ' days ago';
}

$stats = $stats ?? [];
$revenueChart = $revenueChart ?? [];
$subscriptions = $subscriptions ?? [];
$recentVideos = $recentVideos ?? [];
$topContent = $topContent ?? [];
$activity = $activity ?? [];
$maxRevenue = max(array_map(fn ($row) => (float)$row['revenue'], $revenueChart ?: [['revenue' => 1]]));
$totalSubscribers = array_sum(array_map(fn ($row) => (int)$row['subscriber_count'], $subscriptions));
$donut = [];
$colors = ['Premium' => '#e50914', 'Basic' => '#2563eb', 'Free' => '#8a8a8a'];
$cursor = 0;
foreach ($subscriptions as $row) {
    $pct = (float)$row['pct'];
    $donut[] = ($colors[$row['plan_name']] ?? '#f59e0b') . ' ' . $cursor . '% ' . ($cursor + $pct) . '%';
    $cursor += $pct;
}
$donutStyle = $donut ? implode(', ', $donut) : '#2d2d2d 0% 100%';
?>

<?php if (!empty($dbError)): ?>
    <div class="panel mb-4">
        <div class="panel-body">
            <strong>Database is not connected yet.</strong>
            <span class="text-muted">Import schema.sql, check config/database.php, then refresh.</span>
        </div>
    </div>
<?php endif; ?>

<section class="stats-grid">
    <a class="stat-card c-red dashboard-search-item" href="<?= admin_url('videos') ?>">
        <div class="stat-icon red">▶</div>
        <div class="stat-label">Total Videos</div>
        <div class="stat-value" data-live-stat="total_videos"><?= number_format((int)($stats['total_videos'] ?? 0)) ?></div>
        <div class="stat-delta up">↑ +<?= number_format((int)($stats['videos_this_week'] ?? 0)) ?> this week</div>
    </a>
    <a class="stat-card c-green dashboard-search-item" href="<?= admin_url('users') ?>">
        <div class="stat-icon green">☷</div>
        <div class="stat-label">Active Users</div>
        <div class="stat-value" data-live-stat="active_users"><?= number_format((int)($stats['active_users'] ?? 0)) ?></div>
        <div class="stat-delta up">↑ +<?= number_format((int)($stats['users_this_month'] ?? 0)) ?> this month</div>
    </a>
    <a class="stat-card c-amber dashboard-search-item" href="<?= admin_url('payments') ?>">
        <div class="stat-icon amber">$</div>
        <div class="stat-label">Monthly Revenue</div>
        <div class="stat-value" data-live-stat="monthly_revenue"><?= vs_money($stats['monthly_revenue'] ?? 0) ?></div>
        <div class="stat-delta up" data-live-stat="revenue_growth_pct">↑ <?= number_format((float)($stats['revenue_growth_pct'] ?? 0), 1) ?>% vs last month</div>
    </a>
    <a class="stat-card c-blue dashboard-search-item" href="<?= admin_url('videos') ?>">
        <div class="stat-icon blue">◎</div>
        <div class="stat-label">Total Views</div>
        <div class="stat-value" data-live-stat="total_views"><?= vs_num($stats['total_views'] ?? 0) ?></div>
        <div class="stat-delta up">↑ <?= number_format((float)($stats['views_growth_pct'] ?? 0), 1) ?>% this week</div>
    </a>
</section>

<section class="grid-3">
    <article class="panel dashboard-search-item">
        <div class="panel-head">
            <span class="text-danger">▮</span>
            <span class="panel-title">Revenue Overview</span>
            <span class="panel-sub">Last 7 months</span>
        </div>
        <div class="panel-body">
            <canvas id="revenueChart" height="160"></canvas>
        </div>
    </article>

    <article class="panel dashboard-search-item">
        <div class="panel-head">
            <span class="text-warning">◒</span>
            <span class="panel-title">Subscribers</span>
        </div>
        <div class="panel-body">
            <div style="width: 200px; margin: 0 auto;">
             <canvas id="subscriberChart" height="180"></canvas>
           </div>
            <div class="sec-divider"></div>
            <div class="metric-line"><span>Churn Rate</span><strong class="text-danger"><?= number_format((float)($stats['churn_rate'] ?? 0), 1) ?>%</strong></div>
            <div class="metric-line"><span>New This Month</span><strong class="text-success">+<?= number_format((int)($stats['users_this_month'] ?? 0)) ?></strong></div>
        </div>
    </article>
</section>

<section class="grid-2 dashboard-lists-grid">
    <article class="panel dashboard-search-item dashboard-recent-panel">
        <div class="panel-head">
            <span class="text-danger">▶</span>
            <span class="panel-title">Recent Uploads</span>
            <a class="panel-link" href="<?= BASE_URL ?>?module=admin&page=videos">View all →</a>
        </div>
        <div class="table-responsive dashboard-recent-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Views</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentVideos as $video): ?>
                        <tr>
                            <td>
                                <div class="media-title">
                                    <span class="thumb thumb-image">
                                        <?php if (!empty($video['thumbnail'])): ?>
                                            <img src="<?= h(app_media_url($video['thumbnail'])) ?>" alt="">
                                        <?php else: ?>
                                            &#9654;
                                        <?php endif; ?>
                                    </span>
                                    <a class="table-link" href="<?= admin_url('videos', ['search' => $video['title']]) ?>"><strong><?= htmlspecialchars($video['title']) ?></strong></a>
                                </div>
                            </td>
                            <td><span class="pill pill-gray"><?= htmlspecialchars($video['category']) ?></span></td>
                            <td><?= vs_num($video['views']) ?></td>
                            <td><span class="pill <?= vs_status_class($video['status']) ?>"><?= htmlspecialchars(ucfirst($video['status'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="panel dashboard-search-item dashboard-top-panel">
            <div class="panel-head">
                <span class="text-warning">♛</span>
                <span class="panel-title">Top Content</span>
            </div>
            <div class="panel-body dashboard-top-scroll">
                <div class="top-list">
                    <?php foreach ($topContent as $index => $item): ?>
                        <div class="top-item">
                            <div class="top-rank r<?= min($index + 1, 3) ?>"><?= $index + 1 ?></div>
                            <div class="top-info">
                                <a class="top-name" href="<?= admin_url('videos', ['search' => $item['title']]) ?>"><?= htmlspecialchars($item['title']) ?></a>
                                <div class="top-cat"><?= htmlspecialchars($item['cat']) ?></div>
                            </div>
                            <div>
                                <div class="top-views"><?= vs_num($item['views']) ?></div>
                                <div class="top-bar"><span class="top-bar-fill" style="width: <?= (int)($item['pct'] ?? 0) ?>%"></span></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
    </article>
</section>

<section class="dashboard-activity-row">
        <article class="panel dashboard-search-item dashboard-activity-panel">
            <div class="panel-head">
                <span class="text-primary">ϟ</span>
                <span class="panel-title">Recent Activity</span>
            </div>
            <div class="panel-body dashboard-activity-scroll">
                <div class="activity-list">
                    <?php foreach ($activity as $entry): ?>
                        <div class="activity-item">
                            <span class="act-icon <?= htmlspecialchars($entry['icon_color'] ?? 'blue') ?>">↑</span>
                            <div class="act-body">
                                <div class="act-text"><strong><?= htmlspecialchars($entry['action']) ?></strong> — <?= htmlspecialchars($entry['details'] ?? $entry['module']) ?></div>
                                <div class="act-time"><?= vs_time_ago($entry['logged_at']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </article>
</section>

<script>
window.addEventListener('load', function() {
    const revenueLabels = <?= json_encode(array_column($revenueChart, 'month_label')) ?>;
    const revenueData   = <?= json_encode(array_column($revenueChart, 'revenue')) ?>;
    const subLabels     = <?= json_encode(array_column($subscriptions, 'plan_name')) ?>;
    const subData       = <?= json_encode(array_column($subscriptions, 'subscriber_count')) ?>;
    const subColorMap   = <?= json_encode($colors) ?>;
    const subColors     = subLabels.map(l => subColorMap[l] || '#8a8a8a');

    window.VSCharts = window.VSCharts || {};
    window.VSCharts.revenue = new Chart(document.getElementById('revenueChart'), {
        type: 'bar',
        data: {
            labels: revenueLabels,
            datasets: [{
                label: 'Revenue',
                data: revenueData,
                backgroundColor: function(context) {
            const chart = context.chart;
             const {ctx, chartArea} = chart;
             if (!chartArea) return '#e50914';
            const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
             gradient.addColorStop(0, '#e50914');
            gradient.addColorStop(1, 'rgba(229,9,20,0.1)');
             return gradient;
},
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    window.VSCharts.subscribers = new Chart(document.getElementById('subscriberChart'), {
        type: 'doughnut',
        data: {
            labels: subLabels,
            datasets: [{
                data: subData,
                backgroundColor: subColors,
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            cutout: '70%',
            plugins: { legend: { position: 'bottom' } }
        }
    });
});
</script>
