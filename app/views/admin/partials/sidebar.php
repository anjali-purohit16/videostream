<?php
$navCounts = $navCounts ?? [];
$groups = [
    'Main' => [
        ['dashboard', 'Dashboard', '▦', null],
        ['videos', 'Videos', '▶', null],
        ['categories', 'Categories', '▣', null],
        ['users', 'Users', '☷', null],
    ],
    'Finance' => [
        ['payments', 'Payments', '$', null],
        ['subscriptions', 'Subscriptions', '◆', null],
    ],
    'Content' => [
        ['reviews', 'Reviews', '*', $navCounts['reviews'] ?? 0],
        ['reports', 'Reports', '⚑', $navCounts['reports'] ?? 0],
    ],
    'System' => [
        ['messages', 'Messages', 'MSG', $navCounts['messages'] ?? 0],
        ['activity', 'Activity Logs', '~', null],
        ['settings', 'Settings', '⚙', null],
    ],
];
?>
<aside class="sidebar" data-sidebar>
    <!-- <a class="sb-logo" href="<?= BASE_URL ?>admin">
        <span class="sb-logo-icon">▶</span>
        <span class="sb-logo-text"><?= strtoupper(APP_NAME) ?></span>
    </a> -->
    <a class="sb-logo" href="<?= BASE_URL ?>admin">
         <img src="<?= BASE_URL ?>assets/images/logo1.png" alt="<?= htmlspecialchars(APP_NAME) ?> Logo" style="width:100%;height:auto;max-width:200px;object-fit:contain;display:block;margin:0 auto;">
    </a>
    <nav class="sb-nav">
        <?php foreach ($groups as $group => $items): ?>
            <div class="sb-section-label"><?= htmlspecialchars($group) ?></div>
            <?php foreach ($items as [$key, $label, $icon, $count]): ?>
                <a class="nav-item <?= ($section ?? '') === $key ? 'active' : '' ?>"
                   href="<?= BASE_URL ?>admin/<?= urlencode($key) ?>">
                    <span><?= $icon ?></span>
                    <span class="nav-label"><?= htmlspecialchars($label) ?></span>
                    <?php if ($count || $key === 'messages'): ?>
                        <span class="nav-badge <?= $count ? '' : 'is-hidden' ?>" data-nav-count="<?= htmlspecialchars($key) ?>"><?= (int)$count ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>
    <div class="sb-footer">
        <a class="nav-item logout-link" href="<?= BASE_URL ?>logout">
            <span>⇤</span>
            <span class="nav-label">Logout</span>
        </a>
    </div>
</aside>
