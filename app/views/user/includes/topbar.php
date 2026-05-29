<!-- TOPBAR -->
<header class="u-topbar">
  <button class="u-topbar-toggle" id="uSidebarToggle" type="button" aria-label="Toggle sidebar"><?= u_icon('bi-list') ?></button>
  <div class="u-topbar-title">
    <?= [
      'home'         => 'Home',
      'movies'       => 'Browse All',
      'trending'     => 'Trending',
      'categories'   => 'Categories',
      'watchlist'    => 'Watchlist',
      'history'      => 'Watch History',
      'profile'      => 'My Profile',
      'subscription' => 'Subscription',
    ][$activePage] ?? 'Home' ?>
  </div>

  <label class="u-search" aria-label="Search videos">
    <span class="u-search-icon"><?= u_icon('bi-search') ?></span>
    <input type="search" id="uSearchInput" placeholder="Search by title, category, year, plan…">
  </label>

  <div class="u-topbar-actions">
    <!-- Notification bell -->
    <div class="u-topbar-menu">
      <button class="u-tb-btn" type="button" id="uNotifToggle" aria-label="Notifications">
        <?= u_icon('bi-bell') ?>
        <?php if (!empty($notifications)): ?><span class="u-notif-dot"></span><?php endif; ?>
      </button>
      <div class="u-topbar-dropdown" id="uNotifDropdown" data-feed-target="notifications">
        <div class="u-dropdown-head">
          <strong>Notifications</strong>
          <?php if (!empty($notifications)): ?>
          <form method="post" action="<?= BASE_URL ?>?action=clear_notifications">
            <button class="u-dropdown-clear" type="submit">Clear</button>
          </form>
          <?php endif; ?>
        </div>
        <?php foreach ($notifications as $note): ?>
        <a class="u-dropdown-item" href="<?= h($note['link_url'] ?? '#') ?>">
          <span class="u-dropdown-icon"><?= u_icon($note['icon'] ?? 'bi-info-circle') ?></span>
          <span>
            <strong><?= h($note['title'] ?? '') ?></strong>
            <small><?= h($note['body'] ?? '') ?></small>
          </span>
        </a>
        <?php endforeach; ?>
        <?php if (empty($notifications)): ?>
        <div class="u-dropdown-empty">No notifications yet.</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Avatar -->
    <a class="u-topbar-profile" title="<?= h($userName) ?>" href="<?= u_page_url('profile') ?>">
      <span class="u-avatar-btn">
        <?= h($userInitials) ?>
      </span>
      <span class="u-profile-mini">
        <strong><?= h($userName) ?></strong>
        <small><?= h($userProfile['plan'] ?? 'Free Plan') ?></small>
      </span>
    </a>
  </div>
</header>
