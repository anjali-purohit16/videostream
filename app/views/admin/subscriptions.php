<?php require_once ROOT_PATH . '/app/views/admin/_helpers.php'; ?>
<?php
    $maxCost = 0;
    foreach (($distribution ?: []) as $row) {
        $maxCost = max($maxCost, (float)$row['premium_cost'], (float)$row['basic_cost']);
    }
    if ($maxCost <= 0) { $maxCost = 1; }

    // Round the chart ceiling up to a "nice" number so Y-axis labels look clean.
    $magnitude = pow(10, max(0, floor(log10($maxCost))));
    $chartMax  = ceil($maxCost / $magnitude) * $magnitude;
    if ($chartMax <= 0) { $chartMax = $maxCost; }

    $tickCount = 4; // 0, 25%, 50%, 75%, 100%
    $ticks = [];
    for ($i = $tickCount; $i >= 0; $i--) {
        $ticks[] = $chartMax * ($i / $tickCount);
    }
?>

<div class="module-header">
    <div>
        <h1>Subscriptions</h1>
        <p>Plan management & renewals</p>
    </div>
</div>

<section class="grid-2">
    <article class="panel subscription-chart-panel">
        <div class="panel-head"><span class="text-primary">▥</span><span class="panel-title">Plan Distribution</span></div>
        <div class="panel-body">
            <div class="group-chart has-y-axis">
                <div class="chart-legend"><span class="legend-dot red"></span> Premium <span class="legend-dot gray"></span> Basic</div>

                <div class="y-axis">
                    <?php foreach ($ticks as $tickVal): ?>
                        <span class="y-tick"><?= money($tickVal, '$') ?></span>
                    <?php endforeach; ?>
                </div>

                <div class="chart-bars">
                    <?php foreach ($distribution as $row): ?>
                        <div class="group-bar">
                            <span class="bar red"
                                  style="height: <?= max(2, ((float)$row['premium_cost'] / $chartMax) * 100) ?>%"
                                  title="Premium: <?= money($row['premium_cost'], '$') ?>"></span>
                            <span class="bar gray"
                                  style="height: <?= max(2, ((float)$row['basic_cost'] / $chartMax) * 100) ?>%"
                                  title="Basic: <?= money($row['basic_cost'], '$') ?>"></span>
                            <strong><?= h($row['month_label']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </article>

    <article class="panel subscription-renewal-panel">
        <div class="panel-head"><span class="text-success">↻</span><span class="panel-title">Upcoming Renewals</span></div>
        <div class="panel-body">
            <div class="activity-list subscription-renewal-scroll">
                <?php foreach ($renewals as $renewal): ?>
                    <?php
                        $expired = (int)$renewal['days_left'] < 0;
                        if ($expired) {
                            $dotClass = 'amber';
                        } elseif ($renewal['plan'] === 'Premium') {
                            $dotClass = 'red';
                        } else {
                            $dotClass = 'gray';
                        }
                    ?>
                    <div class="activity-item" data-search-row>
                        <span class="act-icon <?= $dotClass ?>">◎</span>
                        <div class="act-body">
                            <div class="act-text"><strong><?= h($renewal['name']) ?></strong> — <?= h($renewal['plan']) ?> Plan <?= $expired ? 'expired ' . abs((int)$renewal['days_left']) . ' days ago' : 'renews in ' . (int)$renewal['days_left'] . ' days' ?></div>
                            <div class="act-time"><?= money($renewal['price'], $renewal['currency'] === 'INR' ? '₹' : '$') ?>/mo</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </article>
</section>
