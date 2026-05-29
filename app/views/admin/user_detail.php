<?php require_once ROOT_PATH . '/app/views/admin/_helpers.php'; ?>
<?php
$user = $details['user'];
$subscription = $details['subscription'];
$history = $details['history'];
$wishlist = $details['wishlist'];
$payments = $details['payments'];
?>

<div class="module-header">
    <div>
        <h1>User Details</h1>
        <p>Complete profile for <?= h($user['name']) ?></p>
    </div>
    <a class="btn btn-ghost" href="<?= admin_url('users') ?>">← Back to Users</a>
</div>

<?php if ($flash): ?><div class="alert <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div><?php endif; ?>

<!-- Profile + Subscription Row -->
<div class="detail-grid-2">
    <!-- Profile Card -->
    <section class="panel user-detail-card">
        <div class="ud-avatar-row">
            <div class="ud-avatar"><?= h(initials($user['name'])) ?></div>
            <div>
                <h2><?= h($user['name']) ?></h2>
                <span class="pill <?= status_class($user['status']) ?>"><?= h(ucfirst($user['status'])) ?></span>
            </div>
        </div>
        <div class="ud-fields">
            <div class="ud-field"><span class="ud-label">Email</span><span><?= h($user['email']) ?></span></div>
            <div class="ud-field"><span class="ud-label">Plan</span><span class="pill <?= strtolower($user['plan']) === 'premium' ? 'pill-red' : (strtolower($user['plan']) === 'basic' ? 'pill-blue' : 'pill-gray') ?>"><?= h($user['plan']) ?></span></div>
            <div class="ud-field"><span class="ud-label">Joined</span><span><?= date('M j, Y H:i', strtotime($user['joined_at'])) ?></span></div>
            <div class="ud-field"><span class="ud-label">Last Seen</span><span><?= $user['last_seen'] ? ago($user['last_seen']) : 'Never' ?></span></div>
            <div class="ud-field"><span class="ud-label">User ID</span><span>#<?= (int)$user['id'] ?></span></div>
            <div class="ud-field ud-field-full"><span class="ud-label">Password</span><span class="ud-password">●●●●●●●●●●●● <small style="opacity:.5">(hashed, not visible)</small></span></div>
        </div>
        <div class="ud-actions">
            <form method="post" action="<?= admin_url('users', ['action' => $user['status'] === 'active' ? 'suspend' : 'activate']) ?>">
                <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                <button class="btn btn-ghost btn-sm" type="submit"><?= $user['status'] === 'active' ? 'Suspend User' : 'Activate User' ?></button>
            </form>
        </div>
    </section>

    <!-- Subscription Card -->
    <section class="panel user-detail-card">
        <h3 class="ud-section-title">Subscription Details</h3>
        <?php if ($subscription): ?>
            <div class="ud-fields">
                <div class="ud-field"><span class="ud-label">Plan</span><span class="pill <?= strtolower($subscription['plan_name']) === 'premium' ? 'pill-red' : 'pill-blue' ?>"><?= h($subscription['plan_name']) ?></span></div>
                <div class="ud-field"><span class="ud-label">Price</span><span><?= h($subscription['currency']) ?> <?= number_format((float)$subscription['price'], 2) ?>/mo</span></div>
                <div class="ud-field"><span class="ud-label">Status</span><span class="pill <?= $subscription['status'] === 'active' ? 'pill-green' : 'pill-gray' ?>"><?= h(ucfirst($subscription['status'])) ?></span></div>
                <div class="ud-field"><span class="ud-label">Started</span><span><?= date('M j, Y', strtotime($subscription['starts_at'])) ?></span></div>
                <div class="ud-field"><span class="ud-label">Expires</span><span><?= date('M j, Y', strtotime($subscription['expires_at'])) ?></span></div>
                <div class="ud-field"><span class="ud-label">Duration</span><span><?= (int)$subscription['duration_days'] ?> days</span></div>
                <div class="ud-field ud-field-full">
                    <?php
                    $daysLeft = (int)ceil((strtotime($subscription['expires_at']) - time()) / 86400);
                    $pct = max(0, min(100, ($daysLeft / max(1, (int)$subscription['duration_days'])) * 100));
                    ?>
                    <span class="ud-label">Time Left</span>
                    <div class="ud-progress-bar"><div class="ud-progress-fill" style="width:<?= $pct ?>%"></div></div>
                    <small><?= max(0, $daysLeft) ?> days remaining</small>
                </div>
            </div>
        <?php else: ?>
            <div class="ud-empty">No active subscription.</div>
        <?php endif; ?>

        <h3 class="ud-section-title" style="margin-top:1.5rem">Payment History</h3>
        <?php if ($payments): ?>
            <div class="ud-mini-table">
                <?php foreach ($payments as $pay): ?>
                    <div class="ud-mini-row">
                        <span class="ud-txn"><?= h($pay['txn_id']) ?></span>
                        <span><?= h($pay['plan_name']) ?></span>
                        <span><?= h($pay['currency']) ?> <?= number_format((float)$pay['amount'], 2) ?></span>
                        <span class="pill <?= $pay['status'] === 'success' ? 'pill-green' : ($pay['status'] === 'pending' ? 'pill-amber' : 'pill-red') ?>"><?= h(ucfirst($pay['status'])) ?></span>
                        <span class="ud-date"><?= $pay['paid_at'] ? date('M j, Y', strtotime($pay['paid_at'])) : '—' ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="ud-empty">No payment records.</div>
        <?php endif; ?>
    </section>
</div>

<!-- Watch History + Wishlist Row -->
<div class="detail-grid-2">
    <section class="panel">
        <h3 class="ud-section-title">Watch History</h3>
        <?php if ($history): ?>
            <div class="ud-video-list">
                <?php foreach ($history as $item): ?>
                    <div class="ud-video-row">
                        <div class="ud-thumb-placeholder">▶</div>
                        <div class="ud-video-info">
                            <strong><?= h($item['title']) ?></strong>
                            <span><?= VideoModel::formatDuration((int)$item['duration_sec']) ?> · <?= ago($item['watched_at']) ?></span>
                        </div>
                        <?php if ($item['rating']): ?>
                            <span class="stars"><?= str_repeat('★', (int)$item['rating']) ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="ud-empty">No watch history yet.</div>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h3 class="ud-section-title">Wishlist / Favourites</h3>
        <?php if ($wishlist): ?>
            <div class="ud-video-list">
                <?php foreach ($wishlist as $item): ?>
                    <div class="ud-video-row">
                        <div class="ud-thumb-placeholder">♥</div>
                        <div class="ud-video-info">
                            <strong><?= h($item['title']) ?></strong>
                            <span><?= h($item['category']) ?> · <?= VideoModel::formatDuration((int)$item['duration_sec']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="ud-empty">No wishlist items yet.</div>
        <?php endif; ?>
    </section>
</div>
