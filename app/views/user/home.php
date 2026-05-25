<?php require_once ROOT_PATH . '/app/views/admin/_helpers.php'; ?>
<?php
$userId      = (int)($_SESSION['user_id'] ?? 0);
$userName    = $_SESSION['user_name'] ?? 'Guest';
$userInitials = strtoupper(substr(explode(' ', trim($userName))[0], 0, 1) . substr(explode(' ', trim($userName))[1] ?? '', 0, 1));
$activePage  = $_GET['upage'] ?? 'home';
$greeting    = date('H') < 12 ? 'Morning' : (date('H') < 18 ? 'Afternoon' : 'Evening');

$featured          = $featured          ?? [];
$trending          = $trending          ?? [];
$continueWatching  = $continueWatching  ?? [];
$categories        = $categories        ?? [];
$wishlistItems     = $wishlistItems     ?? [];
$historyItems      = $historyItems      ?? [];
$userProfile       = $userProfile       ?? [];
$subscription      = $subscription      ?? null;
$publishedCount    = $publishedCount    ?? 0;
$wishlistCount     = $wishlistCount     ?? 0;
$historyCount      = $historyCount      ?? 0;
$categoryVideos    = $categoryVideos    ?? [];
$notifications     = $notifications     ?? [];
$trendingSort      = $trendingSort      ?? 'desc';
$plans             = $plans             ?? [];
$selectedCatId     = (int)($_GET['cat'] ?? 0);
/*
 * Determine userPlanLevel:
 * 1. If user has an active, non-expired subscription  → use subscription plan name
 * 2. Otherwise                                        → treat as 'free'
 * Normalize to exactly 'free' | 'basic' | 'premium'
 */
function u_normalize_plan(string $name): string {
    $n = strtolower(trim($name));
    if (str_contains($n, 'premium')) return 'premium';
    if (str_contains($n, 'basic'))   return 'basic';
    return 'free';
}
$uPlanRank = ['free' => 0, 'basic' => 1, 'premium' => 2];
$userPlanLevel = u_normalize_plan($userProfile['plan'] ?? 'free');
if (!empty($subscription) && strtolower($subscription['sub_status'] ?? '') === 'active') {
    $subPlanLevel = u_normalize_plan($subscription['plan_name'] ?? 'free');
    if (($uPlanRank[$subPlanLevel] ?? 0) > ($uPlanRank[$userPlanLevel] ?? 0)) {
        $userPlanLevel = $subPlanLevel;
    }
}

function u_icon(string $name): string
{
    return '<i class="bi ' . h($name) . '" aria-hidden="true"></i>';
}

function u_media_url(?string $path): string
{
    return app_media_url($path);
}

function u_video_payload(array $item, string $idKey = 'id'): string
{
    global $userPlanLevel;
    $rank = ['free' => 0, 'basic' => 1, 'premium' => 2];
    $accessLevel = strtolower($item['access_level'] ?? 'free');
    $canWatch = ($rank[$userPlanLevel] ?? 0) >= ($rank[$accessLevel] ?? 0);
    $filePath = u_media_url($item['file_path'] ?? '');
    $thumbUrl = u_media_url($item['thumbnail'] ?? '');
    return h(json_encode([
        'id' => (int)($item[$idKey] ?? $item['id'] ?? 0),
        'title' => $item['title'] ?? '',
        'filePath' => $filePath,
        'thumbUrl' => $thumbUrl,
        'desc' => $item['description'] ?? '',
        'category' => $item['category'] ?? '',
        'accessLevel' => $accessLevel,
        'canWatch' => $canWatch,
        'durationSec' => (int)($item['duration_sec'] ?? 0),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function u_access_label(array $item): string
{
    return ucfirst(strtolower($item['access_level'] ?? 'free'));
}

function u_access_class(array $item): string
{
    return 'badge-access-' . strtolower($item['access_level'] ?? 'free');
}

/* Returns HD badge HTML if video is HD quality */
function u_hd_badge(array $item): string
{
    $q = strtolower($item['quality'] ?? $item['resolution'] ?? '');
    $isHd = ($q && (str_contains($q, 'hd') || str_contains($q, '1080') || str_contains($q, '4k') || str_contains($q, '720')));
    if (!$isHd) return '';
    return '<div class="u-card-badge u-badge-hd">HD</div>';
}

/* Returns plan badge HTML (access level) — right side */
function u_plan_badge(array $item): string
{
    $level = strtolower($item['access_level'] ?? 'free');
    $label = ucfirst($level);
    return '<div class="u-card-badge u-card-badge-right u-badge-plan badge-plan-' . h($level) . '">' . h($label) . '</div>';
}

/* Returns plan badge HTML — LEFT side (for trending cards where rank is on right) */
function u_plan_badge_left(array $item): string
{
    $level = strtolower($item['access_level'] ?? 'free');
    $label = $level === 'free' ? 'Free' : ucfirst($level);
    $cls   = 'badge-plan-' . h($level);
    return '<div class="u-card-badge u-badge-plan u-badge-plan-left ' . $cls . '">' . h($label) . '</div>';
}
?>

<div class="u-shell">

  <!-- ======================================================
       SIDEBAR
  ====================================================== -->
  <aside class="u-sidebar" id="uSidebar">
    <a class="u-logo" href="<?= BASE_URL ?>">
      <!-- <div class="u-logo-icon"><?= u_icon('bi-play-fill') ?></div>
      <span class="u-logo-text"><?= h(APP_NAME) ?></span> -->
      <img    src="<?= BASE_URL ?>assets/images/logo1.png" alt="<?= h(APP_NAME) ?>" style="width:180px; height:60px;">
    </a>

    <nav class="u-nav">
      <!-- <div class="u-nav-label">Browse</div> -->
      <a class="u-nav-item <?= $activePage === 'home' ? 'active' : '' ?>"
         href="<?= BASE_URL ?>?upage=home">
        <span class="u-nav-icon"><?= u_icon('bi-house-door') ?></span>
        <span class="u-nav-label-text">Home</span>
      </a>
      <a class="u-nav-item <?= $activePage === 'movies' ? 'active' : '' ?>"
         href="<?= BASE_URL ?>?upage=movies">
        <span class="u-nav-icon"><?= u_icon('bi-collection-play') ?></span>
        <span class="u-nav-label-text">Browse All</span>
      </a>
      <a class="u-nav-item <?= $activePage === 'trending' ? 'active' : '' ?>"
         href="<?= BASE_URL ?>?upage=trending">
        <span class="u-nav-icon"><?= u_icon('bi-graph-up-arrow') ?></span>
        <span class="u-nav-label-text">Trending</span>
      </a>
      <a class="u-nav-item <?= $activePage === 'categories' ? 'active' : '' ?>"
         href="<?= BASE_URL ?>?upage=categories">
        <span class="u-nav-icon"><?= u_icon('bi-grid') ?></span>
        <span class="u-nav-label-text">Categories</span>
      </a>

      <div class="u-nav-label">My Library</div>
      <a class="u-nav-item <?= $activePage === 'watchlist' ? 'active' : '' ?>"
         href="<?= BASE_URL ?>?upage=watchlist">
        <span class="u-nav-icon"><?= u_icon('bi-bookmark-check') ?></span>
        <span class="u-nav-label-text">Watchlist</span>
      </a>
      <a class="u-nav-item <?= $activePage === 'history' ? 'active' : '' ?>"
         href="<?= BASE_URL ?>?upage=history">
        <span class="u-nav-icon"><?= u_icon('bi-clock-history') ?></span>
        <span class="u-nav-label-text">Watch History</span>
      </a>

      <div class="u-nav-label">Account</div>
      <a class="u-nav-item <?= $activePage === 'profile' ? 'active' : '' ?>"
         href="<?= BASE_URL ?>?upage=profile">
        <span class="u-nav-icon"><?= u_icon('bi-person-circle') ?></span>
        <span class="u-nav-label-text">Profile</span>
      </a>
      <a class="u-nav-item <?= $activePage === 'subscription' ? 'active' : '' ?>"
         href="<?= BASE_URL ?>?upage=subscription">
        <span class="u-nav-icon"><?= u_icon('bi-credit-card') ?></span>
        <span class="u-nav-label-text">Subscription</span>
      </a>
    </nav>

    <div class="u-sidebar-footer">
      <a class="u-nav-item" href="<?= BASE_URL ?>logout" style="color:#e50914;">
        <span class="u-nav-icon"><?= u_icon('bi-box-arrow-right') ?></span>
        <span class="u-nav-label-text">Sign Out</span>
      </a>
    </div>
  </aside>

  <!-- ======================================================
       MAIN
  ====================================================== -->
  <div class="u-main">

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
        <a class="u-topbar-profile" title="<?= h($userName) ?>" href="<?= BASE_URL ?>?upage=profile">
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

    <!-- PAGE CONTENT -->
    <div class="u-content" id="uPageContent">

      <!-- ================================================
           HOME PAGE
      ================================================ -->
      <?php if ($activePage === 'home'): ?>

     
     
      <!-- Featured -->
      <div class="u-section-header">
        <span class="u-section-title"><?= u_icon('bi-stars') ?> Featured For You</span>
        <a class="u-section-more" href="<?= BASE_URL ?>?upage=movies">Browse all <?= u_icon('bi-arrow-right') ?></a>
      </div>

      <div class="u-content-row u-searchable" data-feed-target="featured">
        <?php foreach (array_slice($featured, 0, 5) as $item): ?>
        <?php $thumbUrl = u_media_url($item['thumbnail'] ?? ''); ?>
        <div class="u-video-card js-open-video" data-title="<?= h(strtolower($item['title'])) ?>" data-category="<?= h(strtolower($item['category'] ?? '')) ?>" data-desc="<?= h(strtolower(substr($item['description'] ?? '', 0, 200))) ?>" data-year="<?= date('Y', strtotime($item['created_at'] ?? 'now')) ?>" data-plan="<?= h(strtolower($item['access_level'] ?? 'free')) ?>" data-views="<?= (int)($item['views'] ?? 0) ?>" data-duration="<?= (int)($item['duration_sec'] ?? 0) ?>" data-video="<?= u_video_payload($item) ?>">
          <?= u_hd_badge($item) ?>
          <?= u_plan_badge($item) ?>
          <div class="u-card-thumb">
            <?php if ($thumbUrl): ?><img src="<?= h($thumbUrl) ?>" alt=""><?php else: ?><?= u_icon('bi-play-fill') ?><?php endif; ?>
            <div class="u-card-play-overlay"><div class="u-card-play-btn"><?= u_icon('bi-play-fill') ?></div></div>
          </div>
          <div class="u-card-body">
            <div class="u-card-title"><?= h($item['title']) ?></div>
            <div class="u-card-meta"><?= h($item['category'] ?? '') ?> &middot; <?= VideoModel::formatDuration((int)($item['duration_sec'] ?? 0)) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($featured)): ?>
        <div class="u-empty"><span class="u-empty-icon"><?= u_icon('bi-collection-play') ?></span>No videos available yet.</div>
        <?php endif; ?>
      </div>

      <!-- Trending -->
      <div class="u-section-header">
        <span class="u-section-title"><?= u_icon('bi-graph-up-arrow') ?> Trending Now</span>
        <a class="u-section-more" href="<?= BASE_URL ?>?upage=trending">See all <?= u_icon('bi-arrow-right') ?></a>
      </div>
      <div class="u-content-row u-searchable" data-feed-target="trending">
        <?php foreach (array_slice($trending, 0, 5) as $i => $item): ?>
        <?php $thumbUrl = u_media_url($item['thumbnail'] ?? ''); ?>
        <div class="u-video-card js-open-video" data-title="<?= h(strtolower($item['title'])) ?>" data-category="<?= h(strtolower($item['category'] ?? '')) ?>" data-desc="<?= h(strtolower(substr($item['description'] ?? '', 0, 200))) ?>" data-year="<?= date('Y', strtotime($item['created_at'] ?? 'now')) ?>" data-plan="<?= h(strtolower($item['access_level'] ?? 'free')) ?>" data-views="<?= (int)($item['views'] ?? 0) ?>" data-duration="<?= (int)($item['duration_sec'] ?? 0) ?>" data-video="<?= u_video_payload($item) ?>">
          <?= u_plan_badge_left($item) ?>
          <div class="u-card-badge u-card-rank-badge">#<?= $i+1 ?></div>
          <div class="u-card-thumb">
            <?php if ($thumbUrl): ?><img src="<?= h($thumbUrl) ?>" alt=""><?php else: ?><?= u_icon('bi-play-fill') ?><?php endif; ?>
            <div class="u-card-play-overlay"><div class="u-card-play-btn"><?= u_icon('bi-play-fill') ?></div></div>
          </div>
          <div class="u-card-body">
            <div class="u-card-title"><?= h($item['title']) ?></div>
            <div class="u-card-meta"><?= num_short($item['views'] ?? 0) ?> views</div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>


        <!-- Stat row -->

       <div class="u-stats-row">
        <a href="<?= BASE_URL ?>?upage=movies" class="u-stat-card c-red" style="text-decoration:none;cursor:pointer;" title="Browse all videos">
          <div class="u-stat-icon red"><?= u_icon('bi-collection-play') ?></div>
          <div class="u-stat-value"><?= number_format((int)$publishedCount) ?></div>
          <div class="u-stat-label">Videos Available</div>
          <div class="u-stat-sub">Ready to stream <?= u_icon('bi-arrow-right') ?></div>
        </a>
        <a href="<?= BASE_URL ?>?upage=history" class="u-stat-card c-amber" style="text-decoration:none;cursor:pointer;" title="Your watch history">
          <div class="u-stat-icon amber"><?= u_icon('bi-clock-history') ?></div>
          <div class="u-stat-value" data-history-count><?= number_format((int)$historyCount) ?></div>
          <div class="u-stat-label">Watched</div>
          <div class="u-stat-sub">In your history <?= u_icon('bi-arrow-right') ?></div>
        </a>
        <a href="<?= BASE_URL ?>?upage=watchlist" class="u-stat-card c-green" style="text-decoration:none;cursor:pointer;" title="Your watchlist">
          <div class="u-stat-icon green"><?= u_icon('bi-bookmark-check') ?></div>
          <div class="u-stat-value" data-watchlist-count><?= number_format((int)$wishlistCount) ?></div>
          <div class="u-stat-label">Watchlist</div>
          <div class="u-stat-sub">Saved to watch <?= u_icon('bi-arrow-right') ?></div>
        </a>
        <a href="<?= BASE_URL ?>?upage=categories" class="u-stat-card c-blue" style="text-decoration:none;cursor:pointer;" title="Browse by category">
          <div class="u-stat-icon blue"><?= u_icon('bi-grid') ?></div>
          <div class="u-stat-value"><?= number_format(count($categories)) ?></div>
          <div class="u-stat-label">Categories</div>
          <div class="u-stat-sub">Browse by genre <?= u_icon('bi-arrow-right') ?></div>
        </a>
      </div>


 <!-- Continue watching + Activity -->
      <div class="u-grid-2" style="margin-bottom:24px;">
        <div class="u-panel">
          <div class="u-panel-head">
            <span class="u-panel-title"><?= u_icon('bi-clock-history') ?> Continue Watching</span>
            <a class="u-panel-link" href="<?= BASE_URL ?>?upage=history">View all <?= u_icon('bi-arrow-right') ?></a>
          </div>
          <div class="u-panel-body">
            <div class="u-continue-list">
              <?php foreach (array_slice($continueWatching, 0, 4) as $item): ?>
              <div class="u-continue-item js-open-video" data-video="<?= u_video_payload($item, 'video_id') ?>">
                <div class="u-continue-thumb">
                  <?php if (!empty($item['thumbnail'])): ?><img src="<?= h(u_media_url($item['thumbnail'])) ?>" alt=""><?php else: ?><?= u_icon('bi-play-fill') ?><?php endif; ?>
                </div>
                <div class="u-continue-info">
                  <div class="u-continue-title"><?= h($item['title']) ?></div>
                  <div class="u-continue-meta"><?= h($item['category'] ?? 'Movie') ?></div>
                  <div class="u-progress-bar"><div class="u-progress-fill" style="width:<?= (int)($item['progress_percent'] ?? 0) ?>%"></div></div>
                </div>
                <div class="u-continue-play"><?= u_icon('bi-play-fill') ?></div>
              </div>
              <?php endforeach; ?>
              <?php if (empty($continueWatching)): ?>
              <div class="u-empty"><span class="u-empty-icon"><?= u_icon('bi-tv') ?></span>Start watching a video and it will appear here.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="u-panel">
          <div class="u-panel-head"><span class="u-panel-title"><?= u_icon('bi-activity') ?> Recent Activity</span></div>
          <div class="u-panel-body">
            <div class="u-activity-list">
              <?php if (!empty($continueWatching[0])): ?>
              <div class="u-activity-item">
                <div class="u-activity-dot" style="background:var(--red)"></div>
                <div><div class="u-activity-text">Watching <strong><?= h($continueWatching[0]['title']) ?></strong></div><div class="u-activity-time"><?= ago($continueWatching[0]['watched_at'] ?? null) ?></div></div>
              </div>
              <?php endif; ?>
              <?php if (!empty($wishlistItems[0])): ?>
              <div class="u-activity-item">
                <div class="u-activity-dot" style="background:var(--amber)"></div>
                <div><div class="u-activity-text">Added <strong><?= h($wishlistItems[0]['title']) ?></strong> to Watchlist</div><div class="u-activity-time"><?= ago($wishlistItems[0]['created_at'] ?? null) ?></div></div>
              </div>
              <?php endif; ?>
              <div class="u-activity-item">
                <div class="u-activity-dot" style="background:var(--green)"></div>
                <div><div class="u-activity-text">Joined <strong><?= h(APP_NAME) ?></strong></div><div class="u-activity-time">Member since <?= date('M Y', strtotime($userProfile['joined_at'] ?? 'now')) ?></div></div>
              </div>
              <div class="u-activity-item">
                <div class="u-activity-dot"></div>
                <div><div class="u-activity-text">Plan: <strong><?= h($userProfile['plan'] ?? 'Free') ?></strong></div><div class="u-activity-time">Current subscription</div></div>
              </div>
            </div>
          </div>
        </div>
      </div>



      <!-- ================================================
           RECENT BLOGS
      ================================================ -->
      <div class="u-section-header">
        <span class="u-section-title"><?= u_icon('bi-journal-text') ?> Recent Blogs</span>
        <a class="u-section-more"  href="http://myserver.com/PHP/vs950/blog/home/" target="_blank" rel="noopener">
          View all <?= u_icon('bi-arrow-right') ?>
        </a>
      </div>

      <div class="u-blog-row" id="uBlogRow">
        <?php
        $blog_posts = [];
        $blog_error = '';
        try {
          $blog_feed = @file_get_contents(rtrim(BASE_URL, '/') . 'blog/wp-json/wp/v2/posts?per_page=3');
          if ($blog_feed) {
            $blog_data = json_decode($blog_feed, true);
            $blog_posts = $blog_data['posts'] ?? $blog_data ?? [];
          }
        } catch (\Throwable $e) {
          $blog_error = 'Could not load blogs.';
        }
        ?>
        <?php if (!empty($blog_posts)): ?>
          <?php foreach (array_slice($blog_posts, 0, 3) as $post): ?>
          <a class="u-blog-card"
             href="<?= h($post['link'] ?? $post['url'] ?? '#') ?>"
             target="_blank" rel="noopener">
            <?php if (!empty($post['thumbnail'] ?? $post['image'] ?? '')): ?>
            <div class="u-blog-thumb">
              <img src="<?= h($post['thumbnail'] ?? $post['image']) ?>" alt="<?= h($post['title'] ?? '') ?>">
            </div>
            <?php else: ?>
            <div class="u-blog-thumb u-blog-thumb-placeholder">
              <?= u_icon('bi-journal-richtext') ?>
            </div>
            <?php endif; ?>
            <div class="u-blog-body">
              <?php if (!empty($post['category'] ?? $post['tags'][0] ?? '')): ?>
              <div class="u-blog-tag"><?= h($post['category'] ?? $post['tags'][0]) ?></div>
              <?php endif; ?>
              <div class="u-blog-title"><?= h($post['title'] ?? 'Untitled') ?></div>
              <?php if (!empty($post['excerpt'] ?? $post['description'] ?? '')): ?>
              <div class="u-blog-excerpt"><?= h(mb_substr(strip_tags($post['excerpt'] ?? $post['description']), 0, 90)) ?>…</div>
              <?php endif; ?>
              <div class="u-blog-meta">
                <?php if (!empty($post['date'] ?? $post['published_at'] ?? '')): ?>
                <?= u_icon('bi-calendar3') ?>
                <?= date('d M Y', strtotime($post['date'] ?? $post['published_at'])) ?>
                <?php endif; ?>
                <span class="u-blog-read-more">Read more <?= u_icon('bi-arrow-right') ?></span>
              </div>
            </div>
          </a>
          <?php endforeach; ?>
        <?php else: ?>
          <?php include __DIR__ . '/includes/recent_blogs.php'; ?>
        <?php endif; ?>
      </div>

      <!-- Upgrade Banner -->
      <?php if (strtolower($userProfile['plan'] ?? 'free') === 'free'): ?>
      <div class="u-upgrade-banner">
        <div>
          <div class="u-upgrade-kicker">Upgrade Your Plan</div>
          <div class="u-upgrade-title">Unlock all premium content</div>
          <div class="u-upgrade-sub">4K streaming &middot; Unlimited downloads &middot; No ads &middot; Early access</div>
        </div>
        <a href="<?= BASE_URL ?>?upage=subscription" class="u-btn u-btn-red">Upgrade to Premium <?= u_icon('bi-arrow-right') ?></a>
      </div>
      <?php endif; ?>

      <!-- ================================================
           BROWSE ALL PAGE
      ================================================ -->
      <?php elseif ($activePage === 'movies'): ?>

      <div class="u-section-header" style="margin-bottom:20px;">
        <span class="u-section-title"><?= u_icon('bi-collection-play') ?> All Videos</span>
        <span style="font-size:12px;color:var(--muted)"><?= count($featured) ?> videos available</span>
      </div>

      <!-- Search bar inline -->
      <div style="margin-bottom:20px;">
        <input id="browseSearch" class="u-search" style="width:100%;max-width:400px;padding:10px 14px;"
               placeholder="Search by title, category, year, plan…" type="search">
      </div>

      <div class="u-content-row" id="browseGrid" data-feed-target="browse">
        <?php foreach ($featured as $item): ?>
        <?php $thumbUrl = u_media_url($item['thumbnail'] ?? ''); ?>
        <div class="u-video-card browse-card js-open-video" data-title="<?= h(strtolower($item['title'])) ?>" data-category="<?= h(strtolower($item['category'] ?? '')) ?>" data-desc="<?= h(strtolower(substr($item['description'] ?? '', 0, 200))) ?>" data-year="<?= date('Y', strtotime($item['created_at'] ?? 'now')) ?>" data-plan="<?= h(strtolower($item['access_level'] ?? 'free')) ?>" data-views="<?= (int)($item['views'] ?? 0) ?>" data-duration="<?= (int)($item['duration_sec'] ?? 0) ?>" data-video="<?= u_video_payload($item) ?>">
          <?= u_hd_badge($item) ?>
          <?= u_plan_badge($item) ?>
          <div class="u-card-thumb">
            <?php if ($thumbUrl): ?><img src="<?= h($thumbUrl) ?>" alt=""><?php else: ?><?= u_icon('bi-play-fill') ?><?php endif; ?>
            <div class="u-card-play-overlay"><div class="u-card-play-btn"><?= u_icon('bi-play-fill') ?></div></div>
          </div>
          <div class="u-card-body">
            <div class="u-card-title"><?= h($item['title']) ?></div>
            <div class="u-card-meta"><?= h($item['category'] ?? '') ?> &middot; <?= VideoModel::formatDuration((int)($item['duration_sec'] ?? 0)) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($featured)): ?>
        <div class="u-empty"><span class="u-empty-icon"><?= u_icon('bi-collection-play') ?></span>No videos published yet.</div>
        <?php endif; ?>
      </div>
      <script>
      document.getElementById('browseSearch')?.addEventListener('input', function() {
        const q = this.value.trim().toLowerCase();
        const grid = document.getElementById('browseGrid');
        let visible = 0;
        grid.querySelectorAll('.browse-card').forEach(function(c) {
          const fields = [
            c.dataset.title    || '',
            c.dataset.category || '',
            c.dataset.desc     || '',
            c.dataset.year     || '',
            c.dataset.plan     || '',
            c.dataset.views    || '',
            c.dataset.duration || ''
          ];
          const matches = q === '' || fields.some(f => f.toLowerCase().includes(q));
          c.style.display = matches ? '' : 'none';
          if (matches) visible++;
        });
        // Show/hide no-results message
        let noRes = grid.querySelector('.u-search-no-results');
        if (q !== '' && visible === 0) {
          if (!noRes) {
            noRes = document.createElement('div');
            noRes.className = 'u-empty u-search-no-results';
            grid.appendChild(noRes);
          }
          noRes.innerHTML = '<span class="u-empty-icon"><i class="bi bi-search" aria-hidden="true"></i></span>No results for &ldquo;<strong>' + q + '</strong>&rdquo;';
          noRes.style.display = '';
        } else if (noRes) {
          noRes.style.display = 'none';
        }
      });
      </script>

      <!-- ================================================
           TRENDING PAGE
      ================================================ -->
      <?php elseif ($activePage === 'trending'): ?>

      <div class="u-section-header" style="margin-bottom:20px;">
        <span class="u-section-title"><?= u_icon('bi-graph-up-arrow') ?> Trending Now</span>
        <form method="get" class="u-sort-form">
          <input type="hidden" name="module" value="user">
          <input type="hidden" name="page" value="home">
          <input type="hidden" name="upage" value="trending">
          <label for="trendingSort">Sort</label>
          <select id="trendingSort" name="sort" onchange="this.form.submit()">
            <option value="desc" <?= $trendingSort === 'desc' ? 'selected' : '' ?>>Descending</option>
            <option value="asc" <?= $trendingSort === 'asc' ? 'selected' : '' ?>>Ascending</option>
          </select>
        </form>
      </div>

      <div class="u-content-row">
        <?php foreach ($trending as $i => $item): ?>
        <?php $thumbUrl = u_media_url($item['thumbnail'] ?? ''); ?>
        <div class="u-video-card js-open-video" data-title="<?= h(strtolower($item['title'])) ?>" data-category="<?= h(strtolower($item['category'] ?? '')) ?>" data-desc="<?= h(strtolower(substr($item['description'] ?? '', 0, 200))) ?>" data-year="<?= date('Y', strtotime($item['created_at'] ?? 'now')) ?>" data-plan="<?= h(strtolower($item['access_level'] ?? 'free')) ?>" data-views="<?= (int)($item['views'] ?? 0) ?>" data-duration="<?= (int)($item['duration_sec'] ?? 0) ?>" data-video="<?= u_video_payload($item) ?>">
          <?= u_plan_badge_left($item) ?>
          <div class="u-card-badge u-card-rank-badge">#<?= $i+1 ?></div>
          <div class="u-card-thumb">
            <?php if ($thumbUrl): ?><img src="<?= h($thumbUrl) ?>" alt=""><?php else: ?><?= u_icon('bi-play-fill') ?><?php endif; ?>
            <div class="u-card-play-overlay"><div class="u-card-play-btn"><?= u_icon('bi-play-fill') ?></div></div>
          </div>
          <div class="u-card-body">
            <div class="u-card-title"><?= h($item['title']) ?></div>
            <div class="u-card-meta"><?= h($item['category'] ?? '') ?> &middot; <?= num_short($item['views'] ?? 0) ?> views</div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($trending)): ?>
        <div class="u-empty"><span class="u-empty-icon"><?= u_icon('bi-graph-up-arrow') ?></span>No trending videos yet.</div>
        <?php endif; ?>
      </div>

      <!-- ================================================
           CATEGORIES PAGE
      ================================================ -->
      <?php elseif ($activePage === 'categories'): ?>

      <div class="u-section-header" style="margin-bottom:16px;">
        <span class="u-section-title"><?= u_icon('bi-grid') ?> Browse by Category</span>
      </div>

      <div class="u-cat-grid" style="margin-bottom:28px;">
        <a class="u-cat-card <?= $selectedCatId === 0 ? 'active' : '' ?>"
           href="<?= BASE_URL ?>?upage=categories">
          <div class="u-cat-icon"><?= u_icon('bi-grid-3x3-gap') ?></div>
          <div class="u-cat-name">All</div>
          <div class="u-cat-count"><?= count($featured) ?> videos</div>
        </a>
        <?php foreach ($categories as $cat): ?>
        <a class="u-cat-card <?= $selectedCatId === (int)$cat['id'] ? 'active' : '' ?>"
           href="<?= BASE_URL ?>?upage=categories&cat=<?= (int)$cat['id'] ?>">
          <div class="u-cat-icon"><?= u_icon('bi-collection-play') ?></div>
          <div class="u-cat-name"><?= h($cat['name']) ?></div>
          <div class="u-cat-count"><?= (int)$cat['video_count'] ?> videos</div>
        </a>
        <?php endforeach; ?>
      </div>

      <?php $catVideos = $selectedCatId > 0 ? $categoryVideos : $featured; ?>
      <div class="u-section-header" style="margin-bottom:16px;">
        <span class="u-section-title"><?= $selectedCatId > 0 ? h(array_column($categories, null, 'id')[$selectedCatId]['name'] ?? 'Category') : 'All Videos' ?></span>
        <span style="font-size:12px;color:var(--muted)"><?= count($catVideos) ?> videos</span>
      </div>
      <div class="u-content-row">
        <?php foreach ($catVideos as $item): ?>
        <?php $thumbUrl = u_media_url($item['thumbnail'] ?? ''); ?>
        <div class="u-video-card js-open-video" data-title="<?= h(strtolower($item['title'])) ?>" data-category="<?= h(strtolower($item['category'] ?? '')) ?>" data-desc="<?= h(strtolower(substr($item['description'] ?? '', 0, 200))) ?>" data-year="<?= date('Y', strtotime($item['created_at'] ?? 'now')) ?>" data-plan="<?= h(strtolower($item['access_level'] ?? 'free')) ?>" data-views="<?= (int)($item['views'] ?? 0) ?>" data-duration="<?= (int)($item['duration_sec'] ?? 0) ?>" data-video="<?= u_video_payload($item) ?>">
          <?= u_hd_badge($item) ?>
          <?= u_plan_badge($item) ?>
          <div class="u-card-thumb">
            <?php if ($thumbUrl): ?><img src="<?= h($thumbUrl) ?>" alt=""><?php else: ?><?= u_icon('bi-play-fill') ?><?php endif; ?>
            <div class="u-card-play-overlay"><div class="u-card-play-btn"><?= u_icon('bi-play-fill') ?></div></div>
          </div>
          <div class="u-card-body">
            <div class="u-card-title"><?= h($item['title']) ?></div>
            <div class="u-card-meta"><?= h($item['category'] ?? '') ?> &middot; <?= VideoModel::formatDuration((int)($item['duration_sec'] ?? 0)) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($catVideos)): ?>
        <div class="u-empty"><span class="u-empty-icon"><?= u_icon('bi-collection-play') ?></span>No videos in this category yet.</div>
        <?php endif; ?>
      </div>

      <!-- ================================================
           WATCHLIST PAGE
      ================================================ -->
      <?php elseif ($activePage === 'watchlist'): ?>

      <div class="u-section-header" style="margin-bottom:20px;">
        <span class="u-section-title"><?= u_icon('bi-bookmark-check') ?> My Watchlist</span>
        <span style="font-size:12px;color:var(--muted)"><?= count($wishlistItems) ?> saved videos</span>
      </div>

      <div class="u-panel" data-feed-target="wishlist">
        <div class="u-panel-body">
          <?php if (!empty($wishlistItems)): foreach ($wishlistItems as $item): ?>
          <?php $thumbUrl = u_media_url($item['thumbnail'] ?? ''); ?>
          <div class="u-list-row">
            <div class="u-list-thumb">
              <?php if ($thumbUrl): ?><img src="<?= h($thumbUrl) ?>" alt=""><?php else: ?><?= u_icon('bi-play-fill') ?><?php endif; ?>
            </div>
            <div class="u-list-info">
              <div class="u-list-title"><?= h($item['title']) ?></div>
              <div class="u-list-meta"><?= h($item['category'] ?? '') ?> &middot; Added <?= ago($item['created_at'] ?? null) ?></div>
            </div>
            <div class="u-list-action" style="display:flex;gap:8px;">
              <button class="u-btn u-btn-red js-open-video" type="button" style="padding:6px 14px;font-size:12px;" data-video="<?= u_video_payload($item, 'video_id') ?>">
                <?= u_icon('bi-play-fill') ?> Play
              </button>
              <form method="post" action="<?= BASE_URL ?>?action=remove_wishlist" style="margin:0;">
                <input type="hidden" name="video_id" value="<?= (int)$item['video_id'] ?>">
                <button type="submit" class="u-btn u-btn-ghost" style="padding:6px 14px;font-size:12px;">Remove</button>
              </form>
            </div>
          </div>
          <?php endforeach; else: ?>
          <div class="u-empty"><span class="u-empty-icon"><?= u_icon('bi-bookmark') ?></span>Your watchlist is empty. Browse videos and add them.</div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ================================================
           WATCH HISTORY PAGE
      ================================================ -->
      <?php elseif ($activePage === 'history'): ?>

      <div class="u-section-header" style="margin-bottom:20px;">
        <span class="u-section-title"><?= u_icon('bi-clock-history') ?> Watch History</span>
        <span style="display:flex;align-items:center;gap:12px;">
          <span style="font-size:12px;color:var(--muted)"><?= count($historyItems) ?> videos</span>
          <?php if (!empty($historyItems)): ?>
          <form method="post" action="<?= BASE_URL ?>?action=clear_history"
                onsubmit="return confirm('Clear all watch history?');" style="margin:0;">
            <button type="submit" class="u-btn u-btn-ghost" style="padding:5px 12px;font-size:12px;color:var(--red);border-color:var(--red);">
              <?= u_icon('bi-trash') ?> Clear All
            </button>
          </form>
          <?php endif; ?>
        </span>
      </div>

      <div class="u-panel" data-feed-target="history">
        <div class="u-panel-body">
          <?php if (!empty($historyItems)): foreach ($historyItems as $item): ?>
          <?php $thumbUrl = u_media_url($item['thumbnail'] ?? ''); ?>
          <div class="u-list-row">
            <div class="u-list-thumb">
              <?php if ($thumbUrl): ?><img src="<?= h($thumbUrl) ?>" alt=""><?php else: ?><?= u_icon('bi-play-fill') ?><?php endif; ?>
            </div>
            <div class="u-list-info">
              <div class="u-list-title"><?= h($item['title']) ?></div>
              <div class="u-list-meta">
                <?= h($item['category'] ?? '') ?> &middot; <?= ago($item['watched_at'] ?? null) ?>
                &middot; <span style="color:var(--red)"><?= (int)($item['progress_percent'] ?? 0) ?>% watched</span>
              </div>
              <div class="u-progress-bar" style="margin-top:5px;"><div class="u-progress-fill" style="width:<?= (int)($item['progress_percent'] ?? 0) ?>%"></div></div>
            </div>
            <div class="u-list-action">
              <button class="u-btn u-btn-red js-open-video" type="button" style="padding:6px 14px;font-size:12px;" data-video="<?= u_video_payload($item, 'video_id') ?>">
                <?= u_icon('bi-play-fill') ?> Resume
              </button>
            </div>
          </div>
          <?php endforeach; else: ?>
          <div class="u-empty"><span class="u-empty-icon"><?= u_icon('bi-tv') ?></span>No watch history yet. Start streaming.</div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ================================================
           PROFILE PAGE
      ================================================ -->
      <?php elseif ($activePage === 'profile'): ?>

      <!-- ═══════════════════════════════════════════
           PROFILE — VIEW MODE (default)
      ════════════════════════════════════════════ -->
      <div id="profileViewMode">

        <div class="u-profile-card" style="margin-bottom:20px;">
          <!-- Header row: avatar+name on left, Edit button on right -->
          <div class="u-profile-header" style="justify-content:space-between;margin-bottom:20px;">
            <div style="display:flex;align-items:center;gap:16px;">
              <div class="u-profile-avatar-lg" id="profileAvatarDisp"><?= h($userInitials) ?></div>
              <div>
                <div id="profileDispName" style="font-family:'Bebas Neue',sans-serif;font-size:26px;letter-spacing:1.5px;"><?= h($userName) ?></div>
                <div style="font-size:13px;color:var(--muted2);margin-top:2px;"><?= h($userProfile['email'] ?? '') ?></div>
                <div style="margin-top:6px;"><span class="u-pill u-pill-<?= strtolower($userProfile['status'] ?? 'active') === 'active' ? 'green' : 'red' ?>"><?= h(ucfirst($userProfile['status'] ?? 'Active')) ?></span></div>
              </div>
            </div>
            <!-- ✏ Edit Profile button — top-right -->
            <button id="profileEditOpenBtn" type="button" class="u-btn u-btn-ghost"
                    style="display:flex;align-items:center;gap:7px;padding:9px 18px;font-size:13px;border-radius:9px;flex-shrink:0;align-self:flex-start;">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              Edit Profile
            </button>
          </div>

          <!-- Info grid -->
          <div class="u-profile-info-grid">
            <div class="u-info-box">
              <div class="u-info-lbl">Email</div>
              <div class="u-info-val"><?= h($userProfile['email'] ?? '—') ?></div>
            </div>
            <div class="u-info-box">
              <div class="u-info-lbl">Current Plan</div>
              <div class="u-info-val"><?= h($userProfile['plan'] ?? '—') ?></div>
            </div>
            <div class="u-info-box">
              <div class="u-info-lbl">Member Since</div>
              <div class="u-info-val"><?= $userProfile['joined_at'] ? date('M j, Y', strtotime($userProfile['joined_at'])) : '—' ?></div>
            </div>
            <div class="u-info-box">
              <div class="u-info-lbl">Last Active</div>
              <div class="u-info-val"><?= $userProfile['last_seen'] ? ago($userProfile['last_seen']) : 'Just now' ?></div>
            </div>
            <div class="u-info-box">
              <div class="u-info-lbl">Videos Watched</div>
              <div class="u-info-val" data-history-count><?= number_format((int)$historyCount) ?></div>
            </div>
            <div class="u-info-box">
              <div class="u-info-lbl">Watchlist Items</div>
              <div class="u-info-val" data-watchlist-count><?= number_format((int)$wishlistCount) ?></div>
            </div>
          </div>
        </div>

        <!-- Security summary (view mode only) -->
        <div class="u-section-header" style="margin-bottom:16px;">
          <span class="u-section-title"><?= u_icon('bi-shield-lock') ?> Security</span>
        </div>
        <div class="u-panel">
          <div class="u-panel-body">
            <div class="u-profile-info-grid">
              <div class="u-info-box"><div class="u-info-lbl">Password</div><div class="u-info-val">•••••••••• (encrypted)</div></div>
              <div class="u-info-box"><div class="u-info-lbl">Account Status</div><div class="u-info-val"><span class="u-pill u-pill-green">Active</span></div></div>
              <div class="u-info-box"><div class="u-info-lbl">Session</div><div class="u-info-val">Active now</div></div>
            </div>
          </div>
        </div>

        <div class="u-section-header" style="margin:22px 0 16px;">
          <span class="u-section-title"><?= u_icon('bi-exclamation-triangle') ?> Danger Zone</span>
        </div>
        <div class="u-panel">
          <div class="u-panel-body" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
              <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:4px;">Delete account</div>
              <div style="font-size:12px;color:var(--muted2);">This permanently removes your account and related activity.</div>
            </div>
            <form method="post"
                  action="<?= BASE_URL ?>?action=delete_account"
                  onsubmit="return confirm('Delete your account permanently? This cannot be undone.');"
                  style="margin:0;">
              <button type="submit" class="u-btn u-btn-red" style="padding:10px 18px;font-size:13px;">
                <?= u_icon('bi-trash') ?> Delete Account
              </button>
            </form>
          </div>
        </div>

      </div><!-- /#profileViewMode -->


      <!-- ═══════════════════════════════════════════
           PROFILE — EDIT MODE (hidden by default)
      ════════════════════════════════════════════ -->
      <div id="profileEditMode" style="display:none;">

        <!-- Header matching view mode layout -->
        <div class="u-profile-card" style="margin-bottom:20px;">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
            <div style="display:flex;align-items:center;gap:14px;">
              <div class="u-profile-avatar-lg"><?= h($userInitials) ?></div>
              <div>
                <div style="font-family:'Bebas Neue',sans-serif;font-size:22px;letter-spacing:1.5px;color:var(--red);">Edit Profile</div>
                <div style="font-size:12px;color:var(--muted2);margin-top:2px;">Update your name or change your password</div>
              </div>
            </div>
            <!-- Cancel button — top-right -->
            <button id="profileEditCancelBtn" type="button" class="u-btn u-btn-ghost"
                    style="display:flex;align-items:center;gap:7px;padding:9px 18px;font-size:13px;border-radius:9px;flex-shrink:0;align-self:flex-start;">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              Cancel
            </button>
          </div>

          <!-- Alert message -->
          <div id="profileEditMsg" style="display:none;margin-bottom:18px;padding:11px 16px;border-radius:9px;font-size:13px;font-weight:500;"></div>

          <!-- ── Account Details ── -->
          <div style="margin-bottom:22px;">
            <div style="font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--border);">
              Account Details
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px;">
              <div>
                <label style="display:block;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:7px;">Display Name</label>
                <input id="profileName" type="text" value="<?= h($userName) ?>"
                       autocomplete="name"
                       style="width:100%;padding:10px 14px;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:14px;outline:none;transition:border-color .2s;"
                       onfocus="this.style.borderColor='var(--red)'" onblur="this.style.borderColor='var(--border)'">
              </div>
              <div>
                <label style="display:block;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:7px;">Email <span style="color:var(--muted2);font-weight:400;">(read-only)</span></label>
                <input type="email" value="<?= h($userProfile['email'] ?? '') ?>" disabled
                       style="width:100%;padding:10px 14px;background:var(--card2);border:1px solid var(--border);border-radius:8px;color:var(--muted);font-size:14px;cursor:not-allowed;">
              </div>
            </div>
          </div>

          <!-- ── Change Password ── -->
          <div style="margin-bottom:24px;">
            <div style="font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--border);">
              Change Password <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:11px;color:var(--muted2);">— leave blank to keep current password</span>
            </div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px;">
              <div>
                <label style="display:block;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:7px;">Current Password</label>
                <div style="position:relative;">
                  <input id="profileCurrentPw" type="password" placeholder="Enter your current password"
                         autocomplete="current-password"
                         style="width:100%;padding:10px 42px 10px 14px;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:14px;outline:none;transition:border-color .2s;"
                         onfocus="this.style.borderColor='var(--red)'" onblur="this.style.borderColor='var(--border)'">
                  <span onclick="togglePw('profileCurrentPw',this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--muted);font-size:13px;" title="Show/hide"><?= u_icon('bi-eye') ?></span>
                </div>
              </div>
              <div>
                <label style="display:block;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:7px;">New Password</label>
                <div style="position:relative;">
                  <input id="profileNewPw" type="password" placeholder="Min 6 characters"
                         autocomplete="new-password"
                         style="width:100%;padding:10px 42px 10px 14px;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:14px;outline:none;transition:border-color .2s;"
                         onfocus="this.style.borderColor='var(--red)'" onblur="this.style.borderColor='var(--border)'">
                  <span onclick="togglePw('profileNewPw',this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--muted);font-size:13px;" title="Show/hide"><?= u_icon('bi-eye') ?></span>
                </div>
              </div>
              <div>
                <label style="display:block;font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--muted);margin-bottom:7px;">Confirm New Password</label>
                <div style="position:relative;">
                  <input id="profileConfirmPw" type="password" placeholder="Repeat new password"
                         autocomplete="new-password"
                         style="width:100%;padding:10px 42px 10px 14px;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:14px;outline:none;transition:border-color .2s;"
                         onfocus="this.style.borderColor='var(--red)'" onblur="this.style.borderColor='var(--border)'">
                  <span onclick="togglePw('profileConfirmPw',this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--muted);font-size:13px;" title="Show/hide"><?= u_icon('bi-eye') ?></span>
                </div>
              </div>
            </div>
            <!-- Strength bar (visible only when typing new password) -->
            <div id="pwStrengthWrap" style="display:none;margin-top:10px;max-width:300px;">
              <div style="height:4px;border-radius:4px;background:var(--border);overflow:hidden;">
                <div id="pwStrengthBar" style="height:100%;width:0%;border-radius:4px;transition:width .3s,background .3s;"></div>
              </div>
              <div id="pwStrengthLbl" style="font-size:11px;color:var(--muted);margin-top:4px;"></div>
            </div>
          </div>

          <!-- Action row -->
          <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <button id="profileSaveBtn" type="button" class="u-btn u-btn-red" style="padding:11px 32px;font-size:14px;font-weight:700;letter-spacing:.5px;">
              <?= u_icon('bi-check2') ?> Save Changes
            </button>
            <button id="profileEditCancelBtn2" type="button" class="u-btn u-btn-ghost" style="padding:11px 24px;font-size:14px;">
              Cancel
            </button>
          </div>
        </div>

      </div><!-- /#profileEditMode -->

      <!-- ================================================
           SUBSCRIPTION PAGE
      ================================================ -->
      <?php elseif ($activePage === 'subscription'): ?>

      <div data-feed-target="subscription">
      <?php if ($subscription): ?>
      <div class="u-sub-card" style="margin-bottom:20px;">
        <div class="u-sub-header">
          <div>
            <div style="font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--red);margin-bottom:4px;">Current Plan</div>
            <div class="u-sub-plan"><?= h($subscription['plan_name'] ?? 'Free') ?></div>
          </div>
          <span class="u-sub-badge"><?= h(ucfirst($subscription['sub_status'] ?? 'active')) ?></span>
        </div>
        <div class="u-sub-grid">
          <div class="u-sub-item"><div class="u-sub-label">Price</div><div class="u-sub-value"><?= h($subscription['currency'] ?? 'USD') ?> <?= number_format((float)($subscription['price'] ?? 0), 2) ?>/mo</div></div>
          <div class="u-sub-item"><div class="u-sub-label">Started</div><div class="u-sub-value"><?= $subscription['starts_at'] ? date('M j, Y', strtotime($subscription['starts_at'])) : '—' ?></div></div>
          <div class="u-sub-item"><div class="u-sub-label">Expires</div><div class="u-sub-value"><?= $subscription['expires_at'] ? date('M j, Y', strtotime($subscription['expires_at'])) : '—' ?></div></div>
          <div class="u-sub-item"><div class="u-sub-label">Days Left</div><div class="u-sub-value" style="color:var(--<?= (int)($subscription['days_remaining'] ?? 0) < 7 ? 'red' : 'green' ?>)"><?= max(0, (int)($subscription['days_remaining'] ?? 0)) ?> days</div></div>
          <div class="u-sub-item"><div class="u-sub-label">Status</div><div class="u-sub-value"><span class="u-pill u-pill-<?= ($subscription['sub_status'] ?? '') === 'active' ? 'green' : 'red' ?>"><?= h(ucfirst($subscription['sub_status'] ?? '')) ?></span></div></div>
        </div>
      </div>
      <?php else: ?>
      <div class="u-upgrade-banner" style="margin-bottom:20px;">
        <div>
          <div class="u-upgrade-kicker">No Active Plan</div>
          <div class="u-upgrade-title">You are on the Free plan</div>
          <div class="u-upgrade-sub">Upgrade to unlock HD streaming, downloads and no ads.</div>
        </div>
      </div>
      <?php endif; ?>
      </div>

      <!-- Plan options -->
      <div class="u-section-header" style="margin-bottom:16px;"><span class="u-section-title"><?= u_icon('bi-gem') ?> Available Plans</span></div>
      <div class="u-plan-grid">
        <?php foreach ($plans as $plan): ?>
        <?php
          $planFeatures = [
            'Free' => ['Limited monthly streaming', '720p quality', 'Standard support'],
            'Basic' => ['Unlimited streaming', '1080p quality', 'Priority support'],
            'Premium' => ['Unlimited streaming', '4K quality', 'Downloads and no ads'],
          ][$plan['name']] ?? ['Streaming access', 'Admin approval required'];
          $isCurrentPlan = strtolower($userProfile['plan'] ?? '') === strtolower($plan['name']);
          $planPrice = ($plan['currency'] ?? 'USD') . ' ' . number_format((float)$plan['price'], 2);
        ?>
        <div class="u-plan-card <?= $isCurrentPlan ? 'active' : '' ?>">
          <?php if ($isCurrentPlan): ?>
          <div class="u-plan-current">CURRENT PLAN</div>
          <?php endif; ?>
          <div class="u-plan-name"><?= h($plan['name']) ?></div>
          <div class="u-plan-price"><?= h($planPrice) ?><span>/mo</span></div>
          <?php foreach ($planFeatures as $f): ?>
          <div class="u-plan-feature"><?= u_icon('bi-check2') ?> <?= h($f) ?></div>
          <?php endforeach; ?>
          <?php if (!$isCurrentPlan): ?>
          <button type="button" class="u-btn u-btn-red js-plan-open"
                  data-plan-id="<?= (int)$plan['id'] ?>"
                  data-plan-name="<?= h($plan['name']) ?>"
                  data-plan-price="<?= h($planPrice) ?>">
            Get <?= h($plan['name']) ?>
          </button>
          <?php else: ?>
          <div class="u-btn u-btn-ghost active-plan-btn">Active</div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="u-panel u-payment-panel" id="paymentRequestPanel" style="display:none;">
        <div class="u-panel-head">
          <span class="u-panel-title"><?= u_icon('bi-receipt') ?> Payment Request</span>
        </div>
        <div class="u-panel-body">
          <div class="u-payment-summary">
            <div><div class="u-info-lbl">Selected Plan</div><div class="u-info-val" id="paymentPlanName"></div></div>
            <div><div class="u-info-lbl">Amount</div><div class="u-info-val" id="paymentPlanPrice"></div></div>
            <div><div class="u-info-lbl">Approval</div><div class="u-info-val">Admin review required</div></div>
          </div>
          <div class="u-payment-form">
            <input type="hidden" id="paymentPlanId">
            <label>Payment Method
              <select id="paymentMethod">
                <option value="UPI">UPI</option>
                <option value="Card">Card</option>
                <option value="NetBanking">NetBanking</option>
                <option value="Wallet">Wallet</option>
              </select>
            </label>
            <label>Payment Note
              <textarea id="paymentNote" rows="3" placeholder="Transaction ID or note for admin"></textarea>
            </label>
            <div class="u-payment-actions">
              <button type="button" class="u-btn u-btn-red" id="sendPlanRequestBtn"><?= u_icon('bi-send') ?> Send to Admin</button>
              <button type="button" class="u-btn u-btn-ghost" id="cancelPlanRequestBtn">Cancel</button>
              <span id="paymentRequestMsg"></span>
            </div>
          </div>
        </div>
      </div>

      <?php endif; /* end page switcher */ ?>

    </div><!-- /u-content -->

    <!-- ======================================================
         FOOTER
    ====================================================== -->
    <footer class="u-footer">
      <div class="u-footer-inner">
        <a class="u-footer-logo" href="<?= BASE_URL ?>">
          <img src="<?= BASE_URL ?>assets/images/logo1.png" alt="<?= h(APP_NAME) ?>">
        </a>
        <div class="u-footer-links">
          <a href="<?= BASE_URL ?>?upage=home">Home</a>
          <a href="<?= BASE_URL ?>?upage=movies">Browse</a>
          <a href="<?= BASE_URL ?>?upage=trending">Trending</a>
          <a href="<?= BASE_URL ?>?upage=categories">Categories</a>
          
          <!-- <a href="#">Privacy</a>
          <a href="#">Terms</a> -->
          <a href="http://myserver.com/PHP/vs950/blog/home/" target="_blank" rel="noopener">Blog</a>
        </div>
        <div class="u-footer-copy">&copy; <?= date('Y') ?> <?= h(APP_NAME) ?>. All rights reserved.</div>
      </div>
    </footer>

  </div><!-- /u-main -->
</div><!-- /u-shell -->

<style>
/* ── User Footer ───────────────────────────────────────── */
.u-footer {
  background: var(--card, #131313);
  border-top: 1px solid var(--border, #1e1e1e);
  padding: 10px 24px;
  margin-top: 22px;
}
.u-footer-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
  max-width: 1400px;
  margin: 0 auto;
}
.u-footer-logo img {
  height: 53px;
  width: auto;
  object-fit: contain;
  display: block;
  opacity: .8;
}
.u-footer-links {
  display: flex;
  gap: 16px;
  flex-wrap: wrap;
}
.u-footer-links a {
  color: var(--muted2, #888);
  text-decoration: none;
  font-size: 12px;
  transition: color .15s;
}
.u-footer-links a:hover { color: var(--red, #e50914); }
.u-footer-copy {
  font-size: 12px;
  color: var(--muted, #555);
  white-space: nowrap;
}
@media (max-width: 600px) {
  .u-footer-inner { justify-content: center; text-align: center; }
  .u-footer-links { justify-content: center; }
}
</style>

<!-- ======================================================
     VIDEO MODAL
====================================================== -->
<div id="uVideoModal" class="u-modal-overlay" style="display:none;" aria-modal="true" role="dialog">
  <div class="u-modal-box" id="uModalBox">

    <!-- ── VIDEO AREA ── -->
    <div class="u-modal-video" id="uModalVideoWrap" style="position:relative;background:#000;">
      <div id="uModalVideoPlaceholder" style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--muted);font-size:48px;"><?= u_icon('bi-play-circle') ?></div>

      <!-- Custom overlay controls (shown over video) -->
      <div id="uPlayerControls" style="display:none;position:absolute;bottom:0;left:0;right:0;background:linear-gradient(to top,rgba(0,0,0,.85) 0%,transparent 100%);padding:10px 14px 10px;z-index:10;">
        <!-- Progress bar -->
        <div id="uSeekBar" style="width:100%;height:4px;background:rgba(255,255,255,.25);border-radius:4px;cursor:pointer;margin-bottom:10px;position:relative;">
          <div id="uSeekFill" style="height:100%;width:0%;background:var(--red);border-radius:4px;pointer-events:none;"></div>
          <div id="uSeekThumb" style="position:absolute;top:50%;right:auto;width:12px;height:12px;background:#fff;border-radius:50%;transform:translate(-50%,-50%);left:0%;pointer-events:none;box-shadow:0 1px 4px rgba(0,0,0,.5);"></div>
        </div>
        <!-- Control row -->
        <div style="display:flex;align-items:center;gap:10px;">
          <!-- Play/Pause -->
          <button id="uPlayPauseBtn" type="button" style="background:none;border:none;color:#fff;font-size:18px;cursor:pointer;padding:0;line-height:1;width:28px;text-align:center;" title="Play/Pause"><?= u_icon('bi-play-fill') ?></button>
          <!-- Skip back 10s -->
          <button id="uSkipBackBtn" type="button" style="background:none;border:none;color:rgba(255,255,255,.75);font-size:13px;cursor:pointer;padding:0;line-height:1;" title="Back 10s"><?= u_icon('bi-arrow-counterclockwise') ?>10</button>
          <!-- Skip fwd 10s -->
          <button id="uSkipFwdBtn" type="button" style="background:none;border:none;color:rgba(255,255,255,.75);font-size:13px;cursor:pointer;padding:0;line-height:1;" title="Forward 10s">10<?= u_icon('bi-arrow-clockwise') ?></button>
          <!-- Time -->
          <span id="uTimeDisplay" style="font-size:12px;color:rgba(255,255,255,.8);white-space:nowrap;min-width:80px;">0:00 / 0:00</span>
          <!-- Volume -->
          <button id="uMuteBtn" type="button" style="background:none;border:none;color:rgba(255,255,255,.75);font-size:16px;cursor:pointer;padding:0;line-height:1;margin-left:4px;" title="Mute/Unmute"><?= u_icon('bi-volume-up') ?></button>
          <input id="uVolumeSlider" type="range" min="0" max="1" step="0.05" value="1"
                 style="width:64px;accent-color:var(--red);cursor:pointer;">
          <!-- Spacer -->
          <div style="flex:1;"></div>
          <!-- Playback speed -->
          <select id="uSpeedSelect" style="background:rgba(255,255,255,.1);color:#fff;border:none;border-radius:5px;padding:3px 6px;font-size:12px;cursor:pointer;">
            <option value="0.5">0.5×</option>
            <option value="0.75">0.75×</option>
            <option value="1" selected>1×</option>
            <option value="1.25">1.25×</option>
            <option value="1.5">1.5×</option>
            <option value="2">2×</option>
          </select>
          <!-- Fullscreen -->
          <button id="uFullscreenBtn" type="button" style="background:none;border:none;color:rgba(255,255,255,.75);font-size:16px;cursor:pointer;padding:0;line-height:1;" title="Fullscreen"><?= u_icon('bi-fullscreen') ?></button>
        </div>
      </div>
    </div>

    <!-- ── INFO AREA ── -->
    <div class="u-modal-info">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:6px;">
        <div>
          <div class="u-modal-title" id="uModalTitle"></div>
          <div class="u-modal-meta" id="uModalMeta" style="margin-bottom:0;"></div>
        </div>
        <button class="u-modal-close-btn" id="uModalClose" type="button" title="Close"><?= u_icon('bi-x-lg') ?></button>
      </div>
      <div class="u-modal-desc" id="uModalDesc" style="margin-top:10px;"></div>
      <div class="u-modal-actions" id="uModalActions" style="margin-top:12px;">
        <button class="u-btn u-btn-ghost" id="uModalBackBtn" type="button"><?= u_icon('bi-arrow-left') ?> Back</button>
        <button class="u-btn u-btn-ghost" id="uWishlistBtn" type="button"><?= u_icon('bi-bookmark-plus') ?> Add to Watchlist</button>
        <button class="u-btn u-btn-ghost" id="uReviewToggleBtn" type="button"><?= u_icon('bi-star') ?> Rate / Review</button>
        <button class="u-btn u-btn-ghost" id="uReportToggleBtn" type="button"><?= u_icon('bi-flag') ?> Report</button>
      </div>
      <!-- Review form -->
      <div id="uReviewForm" class="u-feedback-box" style="display:none;">
        <div class="u-feedback-title">Your Rating</div>
        <div id="uStarRow" class="u-star-row">
          <?php for ($s = 1; $s <= 5; $s++): ?>
          <span class="u-star" data-v="<?= $s ?>" style="color:var(--muted2);transition:color .15s;"><?= u_icon('bi-star-fill') ?></span>
          <?php endfor; ?>
        </div>
        <textarea id="uReviewComment" class="u-feedback-textarea" rows="5" placeholder="Write your comment..."></textarea>
        <div class="u-feedback-actions">
          <button class="u-btn u-btn-red" id="uReviewSubmitBtn" type="button">Submit Review</button>
          <span id="uReviewMsg" class="u-feedback-msg"></span>
        </div>
      </div>
      <div id="uReportForm" class="u-feedback-box" style="display:none;">
        <div class="u-feedback-title">Report this movie</div>
        <textarea id="uReportReason" class="u-feedback-textarea" rows="5" placeholder="Tell us what is wrong with this movie..."></textarea>
        <div class="u-feedback-actions">
          <button class="u-btn u-btn-red" id="uReportSubmitBtn" type="button">Submit Report</button>
          <span id="uReportMsg" class="u-feedback-msg"></span>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Toast holder -->
<div class="u-toast-holder" id="uToastHolder"></div>

<style>
/* ── Recent Blogs ─────────────────────────────────────── */
.u-blog-row {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 18px;
  margin-bottom: 28px;
}
.u-blog-card {
  background: var(--card-bg, #1a1a1a);
  border: 1px solid var(--border, rgba(255,255,255,.08));
  border-radius: 12px;
  overflow: hidden;
  text-decoration: none;
  color: inherit;
  display: flex;
  flex-direction: column;
  transition: border-color .2s, transform .18s;
}
.u-blog-card:hover {
  border-color: var(--red, #e50914);
  transform: translateY(-3px);
  text-decoration: none;
  color: inherit;
}
.u-blog-thumb {
  width: 100%;
  aspect-ratio: 16/9;
  overflow: hidden;
  background: var(--surface2, #222);
}
.u-blog-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform .3s;
}
.u-blog-card:hover .u-blog-thumb img {
  transform: scale(1.04);
}
.u-blog-thumb-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 36px;
  color: var(--muted, #666);
}
.u-blog-body {
  padding: 14px 16px 16px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  flex: 1;
}
.u-blog-tag {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
  color: var(--red, #e50914);
}
.u-blog-title {
  font-size: 14px;
  font-weight: 600;
  line-height: 1.4;
  color: var(--fg, #fff);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.u-blog-excerpt {
  font-size: 12px;
  color: var(--muted, #888);
  line-height: 1.5;
  flex: 1;
}
.u-blog-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 11px;
  color: var(--muted, #888);
  margin-top: auto;
  padding-top: 8px;
  border-top: 1px solid var(--border, rgba(255,255,255,.06));
}
.u-blog-read-more {
  margin-left: auto;
  color: var(--red, #e50914);
  font-weight: 600;
  font-size: 11px;
  white-space: nowrap;
}
@media (max-width: 600px) {
  .u-blog-row { grid-template-columns: 1fr; }
}
</style>

<script>
const USER_PLAN_LEVEL = (function(p) {
  p = (p || 'free').toLowerCase();
  if (p.includes('premium')) return 'premium';
  if (p.includes('basic'))   return 'basic';
  return 'free';
})(<?= json_encode($userPlanLevel) ?>);
const PLAN_RANK = { free: 0, basic: 1, premium: 2 };
const SUBSCRIPTION_URL = '<?= BASE_URL ?>?upage=subscription';
/* ══════════════════════════════════════════════════
   SIDEBAR TOGGLE  (handled by user.js – no duplicate here)
══════════════════════════════════════════════════ */

/* ══════════════════════════════════════════════════
   NOTIFICATION DROPDOWN
══════════════════════════════════════════════════ */
document.getElementById('uNotifToggle')?.addEventListener('click', function(e) {
  e.stopPropagation();
  document.getElementById('uNotifDropdown').classList.toggle('open');
});
/* click-outside handled by user.js */

/* ══════════════════════════════════════════════════
   GLOBAL SEARCH  (handled by user.js – no duplicate here)
══════════════════════════════════════════════════ */

/* ══════════════════════════════════════════════════
   TOAST
══════════════════════════════════════════════════ */
function uToast(msg, type) {
  const holder = document.getElementById('uToastHolder');
  const toast  = document.createElement('div');
  toast.className = 'u-toast' + (type === 'error' ? ' error' : '');
  toast.textContent = msg;
  holder.appendChild(toast);
  setTimeout(() => toast.remove(), 3200);
}

/* ══════════════════════════════════════════════════
   VIDEO PLAYER
══════════════════════════════════════════════════ */
let currentVideoId  = null;
let progressTimer   = null;
let controlsTimeout = null;
let _video          = null; // reference to the <video> element

/* ── helpers ── */
function fmtTime(sec) {
  if (!isFinite(sec)) return '0:00';
  const h = Math.floor(sec / 3600);
  const m = Math.floor((sec % 3600) / 60);
  const s = Math.floor(sec % 60);
  return h > 0
    ? `${h}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`
    : `${m}:${String(s).padStart(2,'0')}`;
}

function updateSeekUI() {
  if (!_video || !_video.duration) return;
  const pct = (_video.currentTime / _video.duration) * 100;
  document.getElementById('uSeekFill').style.width  = pct + '%';
  document.getElementById('uSeekThumb').style.left  = pct + '%';
  document.getElementById('uTimeDisplay').textContent =
    fmtTime(_video.currentTime) + ' / ' + fmtTime(_video.duration);
}

function updatePlayBtn() {
  const btn = document.getElementById('uPlayPauseBtn');
  if (!btn) return;
  btn.innerHTML = (_video && !_video.paused) ? '<i class="bi bi-pause-fill" aria-hidden="true"></i>' : '<i class="bi bi-play-fill" aria-hidden="true"></i>';
}

function showControls() {
  const ctrl = document.getElementById('uPlayerControls');
  if (ctrl) ctrl.style.opacity = '1';
  clearTimeout(controlsTimeout);
  controlsTimeout = setTimeout(hideControls, 3000);
}
function hideControls() {
  const ctrl = document.getElementById('uPlayerControls');
  if (ctrl && _video && !_video.paused) ctrl.style.opacity = '0';
}

function saveProgress(videoId, pct) {
  const fd = new FormData();
  fd.append('video_id', videoId);
  fd.append('progress', pct);
  fetch('<?= BASE_URL ?>?action=save_progress', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      if (typeof d.count !== 'undefined') {
        document.querySelectorAll('[data-history-count]').forEach(el => el.textContent = Number(d.count).toLocaleString());
      }
    })
    .catch(()=>{});
}

function videoMimeType(url) {
  const clean = String(url || '').split('?')[0].toLowerCase();
  if (clean.endsWith('.webm')) return 'video/webm';
  if (clean.endsWith('.ogg') || clean.endsWith('.ogv')) return 'video/ogg';
  if (clean.endsWith('.mov')) return 'video/quicktime';
  if (clean.endsWith('.m4v')) return 'video/x-m4v';
  return 'video/mp4';
}

/* ── open modal ── */
function recordVideoView(videoId) {
  const fd = new FormData();
  fd.append('video_id', videoId);
  fetch('<?= BASE_URL ?>?action=record_view', { method: 'POST', body: fd }).catch(()=>{});
}

document.addEventListener('click', function(e) {
  const opener = e.target.closest('.js-open-video');
  if (!opener) return;
  e.preventDefault();
  try {
    const data = JSON.parse(opener.dataset.video || '{}');
    const needed   = (data.accessLevel || 'free').toLowerCase();
    const userRank  = PLAN_RANK[USER_PLAN_LEVEL] ?? 0;
    const needRank  = PLAN_RANK[needed] ?? 0;
    console.log('[VS] plan check — user:', USER_PLAN_LEVEL, '('+userRank+')', 'needed:', needed, '('+needRank+')', 'filePath:', data.filePath);
    if (userRank < needRank) {
      uToast('This video requires a ' + needed + ' plan. Please upgrade.', 'error');
      window.location.href = SUBSCRIPTION_URL;
      return;
    }
    if (!data.filePath) {
      uToast('Video file not available.', 'error');
      return;
    }
    openVideoModal(data.id, data.title, data.filePath, data.thumbUrl, data.desc, data.category, data.durationSec);
  } catch (err) {
    uToast('Unable to open this video.', 'error');
  }
});

function openVideoModal(id, title, filePath, thumbUrl, desc, category, durationSec) {
  closeVideoModal(false);
  currentVideoId = id;

  document.getElementById('uModalTitle').textContent = title;
  const durFmt = durationSec > 0 ? fmtTime(durationSec).replace(/^0:/,'') : '';
  document.getElementById('uModalMeta').textContent  = [category, durFmt ? durFmt + ' min' : ''].filter(Boolean).join(' · ');
  document.getElementById('uModalDesc').textContent  = desc || 'No description available.';

  // reset review
  document.getElementById('uReviewForm').style.display   = 'none';
  document.getElementById('uReviewComment').value        = '';
  document.getElementById('uReviewMsg').textContent      = '';
  document.getElementById('uReportForm').style.display   = 'none';
  document.getElementById('uReportReason').value         = '';
  document.getElementById('uReportMsg').textContent      = '';
  selectedRating = 0;
  updateStars(0);

  const wrap = document.getElementById('uModalVideoWrap');

  if (filePath) {
    // Build <video> element
    const vid = document.createElement('video');
    vid.id              = 'uModalVideo';
    vid.style.cssText   = 'width:100%;height:100%;display:block;background:#000;';
    vid.preload         = 'metadata';
    vid.autoplay        = true;
    vid.controls        = false;
    vid.disablePictureInPicture = true;
    vid.setAttribute('controlsList', 'nodownload noplaybackrate');
    vid.playsInline     = true;
    if (thumbUrl) vid.poster = thumbUrl;
    const src = document.createElement('source');
    src.src = filePath;
    src.type = videoMimeType(filePath);
    vid.appendChild(src);
    // Clear placeholder, inject video
    document.getElementById('uModalVideoPlaceholder').style.display = 'none';
    wrap.appendChild(vid);
    _video = vid;
    recordVideoView(id);
    saveProgress(id, 0);

    // Show custom controls
    document.getElementById('uPlayerControls').style.display = 'flex';
    document.getElementById('uPlayerControls').style.flexDirection = 'column';

    // ── wire events ──
    vid.addEventListener('play',       updatePlayBtn);
    vid.addEventListener('pause',      updatePlayBtn);
    vid.addEventListener('error', () => {
      uToast('This video file could not be loaded. Please check the saved video path.', 'error');
    });
    vid.addEventListener('timeupdate', () => {
      updateSeekUI();
      // start periodic progress save
      if (!progressTimer && vid.duration > 0) {
        progressTimer = setInterval(() => {
          saveProgress(id, Math.round((vid.currentTime / vid.duration) * 100));
        }, 10000);
      }
    });
    vid.addEventListener('ended', () => {
      saveProgress(id, 100);
      clearInterval(progressTimer); progressTimer = null;
      updatePlayBtn();
      document.getElementById('uPlayerControls').style.opacity = '1';
    });
    vid.addEventListener('pause',  () => {
      if (vid.duration > 0) saveProgress(id, Math.round((vid.currentTime / vid.duration) * 100));
      updatePlayBtn();
      document.getElementById('uPlayerControls').style.opacity = '1';
      clearTimeout(controlsTimeout);
    });
    vid.addEventListener('volumechange', () => {
      const btn = document.getElementById('uMuteBtn');
      const sl  = document.getElementById('uVolumeSlider');
      if (btn) btn.innerHTML = vid.muted || vid.volume === 0 ? '<i class="bi bi-volume-mute" aria-hidden="true"></i>' : '<i class="bi bi-volume-up" aria-hidden="true"></i>';
      if (sl)  sl.value = vid.muted ? 0 : vid.volume;
    });

    // show controls on mouse move over video area
    wrap.addEventListener('mousemove', showControls);
    wrap.addEventListener('mouseleave', hideControls);
    // click video = play/pause
    vid.addEventListener('click', () => { vid.paused ? vid.play() : vid.pause(); });

    showControls();
    const playPromise = vid.play();
    if (playPromise && typeof playPromise.catch === 'function') {
      playPromise.catch(() => {
        document.getElementById('uPlayerControls').style.opacity = '1';
        updatePlayBtn();
      });
    }

  } else if (thumbUrl) {
    document.getElementById('uModalVideoPlaceholder').innerHTML =
      '<img src="' + thumbUrl + '" alt="' + title.replace(/"/g,'') + '" style="width:100%;height:100%;object-fit:contain;">';
    document.getElementById('uPlayerControls').style.display = 'none';
  } else {
    document.getElementById('uModalVideoPlaceholder').style.display = 'flex';
    document.getElementById('uPlayerControls').style.display = 'none';
  }

  document.getElementById('uVideoModal').style.display = 'flex';
}

/* ── close modal ── */
function closeVideoModal(hide = true) {
  if (_video) {
    if (!_video.paused && _video.duration > 0) {
      saveProgress(currentVideoId, Math.round((_video.currentTime / _video.duration) * 100));
    }
    _video.pause();
    _video.removeAttribute('src');
    _video.load();
    _video = null;
  }
  clearInterval(progressTimer); progressTimer = null;
  clearTimeout(controlsTimeout);

  // restore placeholder, hide controls, remove injected video
  const wrap = document.getElementById('uModalVideoWrap');
  const existVid = document.getElementById('uModalVideo');
  if (existVid) existVid.remove();
  const ph = document.getElementById('uModalVideoPlaceholder');
  if (ph) { ph.style.display = 'flex'; ph.innerHTML = '<i class="bi bi-play-circle" aria-hidden="true"></i>'; }
  document.getElementById('uPlayerControls').style.display = 'none';
  if (hide) document.getElementById('uVideoModal').style.display = 'none';
  currentVideoId = null;
}

/* ── pause on tab/screen hide ── */
document.addEventListener('visibilitychange', () => {
  if (document.hidden && _video && !_video.paused) {
    _video.pause();
    if (currentVideoId && _video.duration > 0) {
      saveProgress(currentVideoId, Math.round((_video.currentTime / _video.duration) * 100));
    }
  }
});

/* ── close triggers ── */
document.getElementById('uModalClose')?.addEventListener('click', closeVideoModal);
document.getElementById('uModalBackBtn')?.addEventListener('click', closeVideoModal);
document.getElementById('uVideoModal')?.addEventListener('click', function(e) {
  if (e.target === this) closeVideoModal();
});
document.addEventListener('keydown', e => {
  const target = e.target;
  if (target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)) return;
  if (target && target.isContentEditable) return;
  if (!_video) return;
  if (e.key === 'Escape')       { closeVideoModal(); return; }
  if (e.key === ' ')            { e.preventDefault(); _video.paused ? _video.play() : _video.pause(); }
  if (e.key === 'ArrowRight')   { _video.currentTime = Math.min(_video.duration, _video.currentTime + 10); }
  if (e.key === 'ArrowLeft')    { _video.currentTime = Math.max(0, _video.currentTime - 10); }
  if (e.key === 'ArrowUp')      { _video.volume = Math.min(1, _video.volume + 0.1); }
  if (e.key === 'ArrowDown')    { _video.volume = Math.max(0, _video.volume - 0.1); }
  if (e.key === 'm' || e.key === 'M') { _video.muted = !_video.muted; }
  if (e.key === 'f' || e.key === 'F') { toggleFullscreen(); }
});

/* ── play / pause button ── */
document.getElementById('uPlayPauseBtn')?.addEventListener('click', () => {
  if (!_video) return;
  _video.paused ? _video.play() : _video.pause();
  showControls();
});

/* ── skip buttons ── */
document.getElementById('uSkipBackBtn')?.addEventListener('click', () => {
  if (_video) { _video.currentTime = Math.max(0, _video.currentTime - 10); showControls(); }
});
document.getElementById('uSkipFwdBtn')?.addEventListener('click', () => {
  if (_video) { _video.currentTime = Math.min(_video.duration, _video.currentTime + 10); showControls(); }
});

/* ── seek bar ── */
(function () {
  const bar = document.getElementById('uSeekBar');
  if (!bar) return;
  let seeking = false;

  function seek(e) {
    if (!_video || !_video.duration) return;
    const rect = bar.getBoundingClientRect();
    const pct  = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
    _video.currentTime = pct * _video.duration;
    updateSeekUI();
  }
  bar.addEventListener('mousedown', e => { seeking = true; seek(e); showControls(); });
  document.addEventListener('mousemove', e => { if (seeking) { seek(e); showControls(); } });
  document.addEventListener('mouseup',   () => { seeking = false; });
  // touch
  bar.addEventListener('touchstart', e => { seeking = true; seek(e.touches[0]); }, { passive: true });
  document.addEventListener('touchmove', e => { if (seeking) seek(e.touches[0]); }, { passive: true });
  document.addEventListener('touchend',  () => { seeking = false; });
})();

/* ── volume ── */
document.getElementById('uVolumeSlider')?.addEventListener('input', function() {
  if (_video) { _video.volume = parseFloat(this.value); _video.muted = _video.volume === 0; }
  showControls();
});
document.getElementById('uMuteBtn')?.addEventListener('click', () => {
  if (!_video) return;
  _video.muted = !_video.muted;
  const sl = document.getElementById('uVolumeSlider');
  if (sl) sl.value = _video.muted ? 0 : _video.volume;
  showControls();
});

/* ── speed ── */
document.getElementById('uSpeedSelect')?.addEventListener('change', function() {
  if (_video) _video.playbackRate = parseFloat(this.value);
  showControls();
});

/* ── fullscreen ── */
function toggleFullscreen() {
  const box = document.getElementById('uModalBox');
  if (!box) return;
  if (!document.fullscreenElement) {
    (box.requestFullscreen || box.webkitRequestFullscreen || box.mozRequestFullScreen).call(box);
  } else {
    (document.exitFullscreen || document.webkitExitFullscreen || document.mozCancelFullScreen).call(document);
  }
}
document.getElementById('uFullscreenBtn')?.addEventListener('click', () => { toggleFullscreen(); showControls(); });
document.addEventListener('fullscreenchange', () => {
  const btn = document.getElementById('uFullscreenBtn');
  if (btn) btn.innerHTML = document.fullscreenElement ? '<i class="bi bi-fullscreen-exit" aria-hidden="true"></i>' : '<i class="bi bi-fullscreen" aria-hidden="true"></i>';
});

/* ── wishlist ── */
document.getElementById('uWishlistBtn')?.addEventListener('click', function() {
  if (!currentVideoId) return;
  fetch('<?= BASE_URL ?>?action=wishlist_toggle&id=' + currentVideoId, { method: 'POST' })
    .then(r => r.json())
    .then(d => {
      uToast(d.message || 'Updated watchlist');
      if (typeof d.count !== 'undefined') {
        document.querySelectorAll('[data-watchlist-count]').forEach(el => el.textContent = Number(d.count).toLocaleString());
      }
    })
    .catch(() => uToast('Added to watchlist'));
});

document.querySelectorAll('.js-plan-open').forEach(btn => {
  btn.addEventListener('click', function() {
    document.getElementById('paymentPlanId').value = this.dataset.planId || '';
    document.getElementById('paymentPlanName').textContent = this.dataset.planName || '';
    document.getElementById('paymentPlanPrice').textContent = this.dataset.planPrice || '';
    document.getElementById('paymentRequestMsg').textContent = '';
    const panel = document.getElementById('paymentRequestPanel');
    panel.style.display = 'block';
    panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  });
});
document.getElementById('cancelPlanRequestBtn')?.addEventListener('click', function() {
  document.getElementById('paymentRequestPanel').style.display = 'none';
});
document.getElementById('sendPlanRequestBtn')?.addEventListener('click', function() {
  const msg = document.getElementById('paymentRequestMsg');
  const fd = new FormData();
  fd.append('plan_id', document.getElementById('paymentPlanId').value);
  fd.append('payment_method', document.getElementById('paymentMethod').value);
  fd.append('payment_note', document.getElementById('paymentNote').value.trim());
  this.disabled = true;
  msg.textContent = 'Sending request...';
  fetch('<?= BASE_URL ?>?action=subscription_request', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      msg.textContent = d.message || 'Request sent.';
      msg.style.color = d.ok ? 'var(--green)' : 'var(--red)';
      if (d.ok) uToast(d.message);
    })
    .catch(() => {
      msg.textContent = 'Could not send request.';
      msg.style.color = 'var(--red)';
    })
    .finally(() => { this.disabled = false; });
});

/* ══════════════════════════════════════════════════
   REVIEW / RATING
══════════════════════════════════════════════════ */
let selectedRating = 0;

function updateStars(val) {
  document.querySelectorAll('.u-star').forEach(s => {
    s.style.color = parseInt(s.dataset.v) <= val ? '#f5c518' : 'var(--muted2)';
  });
}
document.getElementById('uStarRow')?.addEventListener('mouseover', e => {
  const s = e.target.closest('.u-star'); if (s) updateStars(parseInt(s.dataset.v));
});
document.getElementById('uStarRow')?.addEventListener('mouseout', () => updateStars(selectedRating));
document.getElementById('uStarRow')?.addEventListener('click', e => {
  const s = e.target.closest('.u-star');
  if (s) { selectedRating = parseInt(s.dataset.v); updateStars(selectedRating); }
});
/* ── Review toggle: hide report when review opens ── */
document.getElementById('uReviewToggleBtn')?.addEventListener('click', function() {
  const reviewForm = document.getElementById('uReviewForm');
  const reportForm = document.getElementById('uReportForm');
  const isOpen = reviewForm.style.display !== 'none';
  reviewForm.style.display = isOpen ? 'none' : 'block';
  if (!isOpen) { reportForm.style.display = 'none'; } // close report when review opens
});

document.getElementById('uReviewSubmitBtn')?.addEventListener('click', function() {
  if (!currentVideoId) return;
  if (selectedRating < 1) { uToast('Please select a star rating first.', 'error'); return; }
  const fd = new FormData();
  fd.append('video_id', currentVideoId);
  fd.append('rating', selectedRating);
  fd.append('comment', document.getElementById('uReviewComment').value.trim());
  const msg = document.getElementById('uReviewMsg');
  msg.textContent = 'Submitting…';
  this.disabled = true;
  fetch('<?= BASE_URL ?>?action=save_review', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      msg.textContent = d.message || (d.ok ? 'Submitted!' : 'Error');
      msg.style.color = d.ok ? 'var(--green)' : 'var(--red)';
      if (d.ok) {
        uToast(d.message || 'Review submitted!');
        // auto-hide review form after 2 seconds on success
        setTimeout(() => {
          document.getElementById('uReviewForm').style.display = 'none';
          msg.textContent = '';
          document.getElementById('uReviewComment').value = '';
          selectedRating = 0; updateStars(0);
        }, 2000);
      }
    })
    .catch(() => { msg.textContent = 'Error.'; msg.style.color = 'var(--red)'; })
    .finally(() => { this.disabled = false; });
});

/* ── Report toggle: hide review when report opens ── */
document.getElementById('uReportToggleBtn')?.addEventListener('click', function() {
  const reportForm = document.getElementById('uReportForm');
  const reviewForm = document.getElementById('uReviewForm');
  const isOpen = reportForm.style.display !== 'none';
  reportForm.style.display = isOpen ? 'none' : 'block';
  if (!isOpen) { reviewForm.style.display = 'none'; } // close review when report opens
});

document.getElementById('uReportSubmitBtn')?.addEventListener('click', function() {
  if (!currentVideoId) return;
  const reason = document.getElementById('uReportReason').value.trim();
  if (!reason) { uToast('Please add a short report reason.', 'error'); return; }
  const fd = new FormData();
  fd.append('video_id', currentVideoId);
  fd.append('reason', reason);
  const msg = document.getElementById('uReportMsg');
  msg.textContent = 'Submitting...';
  this.disabled = true;
  fetch('<?= BASE_URL ?>?action=save_report', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      msg.textContent = d.message || (d.ok ? 'Report sent.' : 'Error');
      msg.style.color = d.ok ? 'var(--green)' : 'var(--red)';
      if (d.ok) {
        uToast(d.message || 'Report sent.');
        // auto-hide report form after 2 seconds on success
        setTimeout(() => {
          document.getElementById('uReportForm').style.display = 'none';
          msg.textContent = '';
          document.getElementById('uReportReason').value = '';
        }, 2000);
      }
    })
    .catch(() => { msg.textContent = 'Error.'; msg.style.color = 'var(--red)'; })
    .finally(() => { this.disabled = false; });
});

/* ══════════════════════════════════════════════════
   PROFILE TOGGLE
══════════════════════════════════════════════════ */
document.getElementById('profileEditOpenBtn')?.addEventListener('click', function () {
  document.getElementById('profileViewMode').style.display = 'none';
  document.getElementById('profileEditMode').style.display = 'block';
  document.getElementById('profileEditMsg').style.display  = 'none';
});
function closeEditMode() {
  document.getElementById('profileEditMode').style.display = 'none';
  document.getElementById('profileViewMode').style.display = 'block';
  document.getElementById('profileCurrentPw').value = '';
  document.getElementById('profileNewPw').value     = '';
  document.getElementById('profileConfirmPw').value = '';
  document.getElementById('profileEditMsg').style.display  = 'none';
  document.getElementById('pwStrengthWrap').style.display  = 'none';
}
document.getElementById('profileEditCancelBtn')?.addEventListener('click',  closeEditMode);
document.getElementById('profileEditCancelBtn2')?.addEventListener('click', closeEditMode);

function togglePw(inputId, icon) {
  const inp = document.getElementById(inputId);
  if (!inp) return;
  inp.type = inp.type === 'password' ? 'text' : 'password';
  icon.style.opacity = inp.type === 'text' ? '1' : '0.5';
}

document.getElementById('profileNewPw')?.addEventListener('input', function () {
  const val = this.value;
  const wrap = document.getElementById('pwStrengthWrap');
  const bar  = document.getElementById('pwStrengthBar');
  const lbl  = document.getElementById('pwStrengthLbl');
  if (!val) { wrap.style.display = 'none'; return; }
  wrap.style.display = 'block';
  let score = 0;
  if (val.length >= 6)  score++;
  if (val.length >= 10) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  const levels = [
    { w:'20%', bg:'#e50914', txt:'Very weak'   },
    { w:'40%', bg:'#f97316', txt:'Weak'        },
    { w:'60%', bg:'#eab308', txt:'Fair'        },
    { w:'80%', bg:'#22c55e', txt:'Strong'      },
    { w:'100%',bg:'#16a34a', txt:'Very strong' },
  ];
  const lvl = levels[Math.min(score,4)];
  bar.style.width = lvl.w; bar.style.background = lvl.bg;
  lbl.textContent = lvl.txt; lbl.style.color = lvl.bg;
});

document.getElementById('profileSaveBtn')?.addEventListener('click', function () {
  const name      = document.getElementById('profileName')?.value.trim();
  const currentPw = document.getElementById('profileCurrentPw')?.value;
  const newPw     = document.getElementById('profileNewPw')?.value;
  const confirmPw = document.getElementById('profileConfirmPw')?.value;
  if (!name) { showProfileMsg('Display name cannot be empty.', false); return; }
  if (newPw) {
    if (!currentPw) { showProfileMsg('Please enter your current password.', false); return; }
    if (newPw.length < 6) { showProfileMsg('New password must be at least 6 characters.', false); return; }
    if (newPw !== confirmPw) { showProfileMsg('Passwords do not match.', false); return; }
  }
  const fd = new FormData();
  fd.append('name', name);
  fd.append('current_password', currentPw);
  fd.append('new_password', newPw);
  const btn = this; btn.textContent = 'Saving…'; btn.disabled = true;
  fetch('<?= BASE_URL ?>?action=update_profile', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      showProfileMsg(d.message, d.ok);
      if (d.ok) {
        uToast('Profile updated!');
        const dn = document.getElementById('profileDispName');
        if (dn) dn.textContent = d.name;
        document.querySelectorAll('.u-profile-name').forEach(el => el.textContent = d.name);
        document.getElementById('profileCurrentPw').value = '';
        document.getElementById('profileNewPw').value = '';
        document.getElementById('profileConfirmPw').value = '';
        document.getElementById('pwStrengthWrap').style.display = 'none';
        setTimeout(closeEditMode, 1600);
      }
    })
    .catch(() => showProfileMsg('Network error. Please try again.', false))
    .finally(() => { btn.innerHTML = '<i class="bi bi-check2" aria-hidden="true"></i> Save Changes'; btn.disabled = false; });
});
function showProfileMsg(text, ok) {
  const el = document.getElementById('profileEditMsg');
  if (!el) return;
  el.style.display    = 'block';
  el.textContent      = text;
  el.style.background = ok ? 'rgba(34,197,94,.12)' : 'rgba(229,9,20,.12)';
  el.style.color      = ok ? '#22c55e' : '#e50914';
  el.style.border     = '1px solid ' + (ok ? 'rgba(34,197,94,.25)' : 'rgba(229,9,20,.25)');
}
</script>
