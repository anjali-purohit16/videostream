<?php require_once ROOT_PATH . '/app/views/admin/_helpers.php'; ?>
<?php

 //  1. Session and active page state

$userId      = (int)($_SESSION['user_id'] ?? 0);
$userName    = $_SESSION['user_name'] ?? 'Guest';
$userInitials = strtoupper(substr(explode(' ', trim($userName))[0], 0, 1) . substr(explode(' ', trim($userName))[1] ?? '', 0, 1));
$activePage  = $_GET['upage'] ?? 'home';
$greeting    = date('H') < 12 ? 'Morning' : (date('H') < 18 ? 'Afternoon' : 'Evening');


  //  2. Controller data defaults

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

 
// 3. User panel helpers and access state
require ROOT_PATH . '/app/views/user/includes/user_view_helpers.php';
?>

 
<!--  5. User panel shell -->
<div class="u-shell">
  <?php require ROOT_PATH . '/app/views/user/includes/sidebar.php'; ?>

       <!-- MAIN -->
     <div class="u-main">
      <?php require ROOT_PATH . '/app/views/user/includes/topbar.php'; ?>

   <!-- 6. Page content router -->
    <div class="u-content" id="uPageContent">
   <!-- HOME PAGE -->
      <?php if ($activePage === 'home'): ?>

     
     
      <!-- Featured -->
      <div class="u-section-header">
        <span class="u-section-title"><?= u_icon('bi-stars') ?> Featured For You</span>
        <a class="u-section-more" href="<?= u_page_url('movies') ?>">Browse all <?= u_icon('bi-arrow-right') ?></a>
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
        <a class="u-section-more" href="<?= u_page_url('trending') ?>">See all <?= u_icon('bi-arrow-right') ?></a>
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
        <a href="<?= u_page_url('movies') ?>" class="u-stat-card c-red" style="text-decoration:none;cursor:pointer;" title="Browse all videos">
          <div class="u-stat-icon red"><?= u_icon('bi-collection-play') ?></div>
          <div class="u-stat-value"><?= number_format((int)$publishedCount) ?></div>
          <div class="u-stat-label">Videos Available</div>
          <div class="u-stat-sub">Ready to stream <?= u_icon('bi-arrow-right') ?></div>
        </a>
        <a href="<?= u_page_url('history') ?>" class="u-stat-card c-amber" style="text-decoration:none;cursor:pointer;" title="Your watch history">
          <div class="u-stat-icon amber"><?= u_icon('bi-clock-history') ?></div>
          <div class="u-stat-value" data-history-count><?= number_format((int)$historyCount) ?></div>
          <div class="u-stat-label">Watched</div>
          <div class="u-stat-sub">In your history <?= u_icon('bi-arrow-right') ?></div>
        </a>
        <a href="<?= u_page_url('watchlist') ?>" class="u-stat-card c-green" style="text-decoration:none;cursor:pointer;" title="Your watchlist">
          <div class="u-stat-icon green"><?= u_icon('bi-bookmark-check') ?></div>
          <div class="u-stat-value" data-watchlist-count><?= number_format((int)$wishlistCount) ?></div>
          <div class="u-stat-label">Watchlist</div>
          <div class="u-stat-sub">Saved to watch <?= u_icon('bi-arrow-right') ?></div>
        </a>
        <a href="<?= u_page_url('categories') ?>" class="u-stat-card c-blue" style="text-decoration:none;cursor:pointer;" title="Browse by category">
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
            <a class="u-panel-link" href="<?= u_page_url('history') ?>">View all <?= u_icon('bi-arrow-right') ?></a>
          </div>
          <div class="u-panel-body">
            <div class="u-continue-list">
              <?php foreach (array_slice($continueWatching, 0, 2) as $item): ?>
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
        <a href="<?= u_page_url('subscription') ?>" class="u-btn u-btn-red">Upgrade to Premium <?= u_icon('bi-arrow-right') ?></a>
      </div>
      <?php endif; ?>

      <!-- ================================================
           PAGE PARTIALS
      ================================================ -->
      <!-- BROWSE ALL PAGE -->
      <?php elseif ($activePage === 'movies'): ?>
      <?php require ROOT_PATH . '/app/views/user/pages/browse.php'; ?>

      <!-- TRENDING PAGE -->
      <?php elseif ($activePage === 'trending'): ?>
      <?php require ROOT_PATH . '/app/views/user/pages/trending.php'; ?>

      <!-- CATEGORIES PAGE -->
      <?php elseif ($activePage === 'categories'): ?>
      <?php require ROOT_PATH . '/app/views/user/pages/categories.php'; ?>

      <!-- WATCHLIST PAGE -->
      <?php elseif ($activePage === 'watchlist'): ?>
      <?php require ROOT_PATH . '/app/views/user/pages/watchlist.php'; ?>

      <!-- WATCH HISTORY PAGE -->
      <?php elseif ($activePage === 'history'): ?>
      <?php require ROOT_PATH . '/app/views/user/pages/history.php'; ?>

      <!-- PROFILE PAGE -->
      <?php elseif ($activePage === 'profile'): ?>
      <?php require ROOT_PATH . '/app/views/user/pages/profile.php'; ?>

      <!-- SUBSCRIPTION PAGE -->
      <?php elseif ($activePage === 'subscription'): ?>
      <?php require ROOT_PATH . '/app/views/user/pages/subscription.php'; ?>

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
          <a href="<?= u_page_url('home') ?>">Home</a>
          <a href="<?= u_page_url('movies') ?>">Browse</a>
          <a href="<?= u_page_url('trending') ?>">Trending</a>
          <a href="<?= u_page_url('categories') ?>">Categories</a>
          
          <!-- <a href="#">Privacy</a>
          <a href="#">Terms</a> -->
          <a href="http://myserver.com/PHP/vs950/blog/home/" target="_blank" rel="noopener">Blog</a>
        </div>
        <div class="u-footer-copy">&copy; <?= date('Y') ?> <?= h(APP_NAME) ?>. All rights reserved.</div>
      </div>
    </footer>

  </div><!-- /u-main -->
</div><!-- /u-shell -->

<!-- ======================================================
     7. Shared modal and frontend config
====================================================== -->
<?php require ROOT_PATH . '/app/views/user/includes/video_modal.php'; ?>
<?php require ROOT_PATH . '/app/views/user/includes/user_frontend_config.php'; ?>
