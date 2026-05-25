<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(($title ?? 'Admin') . ' | ' . APP_NAME) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Bebas+Neue&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/dashboard.css?v=<?= @filemtime(ROOT_PATH . '/public/assets/css/dashboard.css') ?: time() ?>" rel="stylesheet">
</head>
<body data-base-url="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>">
    <div class="shell">
        <?php require ROOT_PATH . '/app/views/admin/partials/sidebar.php'; ?>
        <div class="main">
            <header class="topbar">
                <button class="topbar-toggle" type="button" data-sidebar-toggle aria-label="Toggle sidebar">☰</button>
                <!-- topbar-title REMOVED — title already shown in each module's <h1> -->
                <div class="live-badge"><span class="live-dot"></span> Live</div>
                <div class="topbar-spacer"></div>
                <div class="topbar-search-wrap">
                    <label class="topbar-search" aria-label="Search dashboard">
                        <i class="bi bi-search search-icon"></i>
                        <input type="search" id="adminGlobalSearch" data-dashboard-search placeholder="Search videos, categories, users, plans...">
                        <kbd class="search-kbd">⌘K</kbd>
                    </label>
                    <div class="search-dropdown" id="searchDropdown">
                        <div class="search-drop-inner" id="searchDropInner"></div>
                    </div>
                </div>
                <div class="topbar-actions">
                    <div class="topbar-menu">
                        <button class="tb-btn" type="button" data-menu-toggle="notifications" aria-label="Notifications">
                            <i class="bi bi-bell"></i>
                            <span class="topbar-count-badge <?= (($notificationCount ?? 0) > 0) ? '' : 'is-hidden' ?>" data-topbar-count="notifications"><?= (int)($notificationCount ?? 0) ?></span>
                        </button>
                        <div class="topbar-dropdown" data-menu="notifications" data-live-menu="notifications">
                            <div class="dropdown-head">
                                <strong>Notifications</strong>
                                <span class="dropdown-actions">
                                    <form method="post" action="<?= BASE_URL ?>admin/notifications/read"><button type="submit">Mark read</button></form>
                                    <form method="post" action="<?= BASE_URL ?>admin/notifications/clear" data-confirm="Clear all notifications?"><button type="submit">Clear</button></form>
                                </span>
                            </div>
                            <?php foreach (($notifications ?? []) as $item): ?>
                                <a class="dropdown-item" href="<?= htmlspecialchars($item['link_url'] ?: '#') ?>">
                                    <strong><?= htmlspecialchars($item['title']) ?></strong>
                                    <span><?= htmlspecialchars($item['body']) ?></span>
                                </a>
                            <?php endforeach; ?>
                            <?php if (empty($notifications)): ?><div class="dropdown-empty">No notifications</div><?php endif; ?>
                        </div>
                    </div>
                    <div class="topbar-menu">
                        <button class="tb-btn tb-btn-text" type="button" data-menu-toggle="messages" aria-label="Messages">
                            MSG
                            <span class="topbar-count-badge <?= (($messageCount ?? 0) > 0) ? '' : 'is-hidden' ?>" data-topbar-count="messages"><?= (int)($messageCount ?? 0) ?></span>
                        </button>
                        <div class="topbar-dropdown" data-menu="messages" data-live-menu="messages">
                            <div class="dropdown-head">
                                <strong>Messages</strong>
                                <span class="dropdown-actions">
                                    <form method="post" action="<?= BASE_URL ?>admin/messages/read"><button type="submit">Mark read</button></form>
                                    <form method="post" action="<?= BASE_URL ?>admin/messages/clear" data-confirm="Clear all messages?"><button type="submit">Clear</button></form>
                                </span>
                            </div>
                            <?php foreach (($messages ?? []) as $item): ?>
                               <a class="dropdown-item" href="<?= BASE_URL ?>admin/messages/index/<?= (int)$item['id'] ?>">
                                    <strong><?= htmlspecialchars($item['sender_name']) ?></strong>
                                    <span><?= htmlspecialchars($item['subject']) ?></span>
                                </a>
                            <?php endforeach; ?>
                            <?php if (empty($messages)): ?><div class="dropdown-empty">No messages</div><?php endif; ?>
                        </div>
                    </div>
                    <a class="tb-avatar" href="<?= BASE_URL ?>logout" title="Logout"><?= htmlspecialchars(strtoupper(substr($adminName ?? 'AD', 0, 2))) ?></a>
                </div>
            </header>
            <main class="content">
                <?= $content ?>
            </main>
        </div>
    </div>
    <!-- Custom Modal Backdrop (scoped to .main only, not full screen) -->
    <div class="admin-modal-backdrop" id="adminModalBackdrop"></div>
    <div class="admin-confirm" id="adminConfirm" aria-hidden="true">
        <div class="admin-confirm-card" role="dialog" aria-modal="true" aria-labelledby="adminConfirmTitle">
            <div class="admin-confirm-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="admin-confirm-copy">
                <h2 id="adminConfirmTitle">Confirm action</h2>
                <p data-confirm-message>Are you sure?</p>
            </div>
            <div class="admin-confirm-actions">
                <button class="mini-btn" type="button" data-confirm-cancel>Cancel</button>
                <button class="btn btn-primary" type="button" data-confirm-ok>Confirm</button>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/app.js?v=<?= @filemtime(ROOT_PATH . '/public/assets/js/app.js') ?: time() ?>"></script>
</body>
</html>
