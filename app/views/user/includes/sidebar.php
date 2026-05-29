<!-- ======================================================
     SIDEBAR
====================================================== -->
<aside class="u-sidebar" id="uSidebar">
  <a class="u-logo" href="<?= BASE_URL ?>">
    <img    src="<?= BASE_URL ?>assets/images/logo1.png" alt="<?= h(APP_NAME) ?>" style="width:180px; height:60px;">
  </a>

  <nav class="u-nav">
    <!-- <div class="u-nav-label">Browse</div> -->
    <a class="u-nav-item <?= $activePage === 'home' ? 'active' : '' ?>"
       data-upage="home"
       href="<?= u_page_url('home') ?>">
      <span class="u-nav-icon"><?= u_icon('bi-house-door') ?></span>
      <span class="u-nav-label-text">Home</span>
    </a>
    <a class="u-nav-item <?= $activePage === 'movies' ? 'active' : '' ?>"
       data-upage="movies"
       href="<?= u_page_url('movies') ?>">
      <span class="u-nav-icon"><?= u_icon('bi-collection-play') ?></span>
      <span class="u-nav-label-text">Browse All</span>
    </a>
    <a class="u-nav-item <?= $activePage === 'trending' ? 'active' : '' ?>"
       data-upage="trending"
       href="<?= u_page_url('trending') ?>">
      <span class="u-nav-icon"><?= u_icon('bi-graph-up-arrow') ?></span>
      <span class="u-nav-label-text">Trending</span>
    </a>
    <a class="u-nav-item <?= $activePage === 'categories' ? 'active' : '' ?>"
       data-upage="categories"
       href="<?= u_page_url('categories') ?>">
      <span class="u-nav-icon"><?= u_icon('bi-grid') ?></span>
      <span class="u-nav-label-text">Categories</span>
    </a>

    <div class="u-nav-label">My Library</div>
    <a class="u-nav-item <?= $activePage === 'watchlist' ? 'active' : '' ?>"
       data-upage="watchlist"
       href="<?= u_page_url('watchlist') ?>">
      <span class="u-nav-icon"><?= u_icon('bi-bookmark-check') ?></span>
      <span class="u-nav-label-text">Watchlist</span>
    </a>
    <a class="u-nav-item <?= $activePage === 'history' ? 'active' : '' ?>"
       data-upage="history"
       href="<?= u_page_url('history') ?>">
      <span class="u-nav-icon"><?= u_icon('bi-clock-history') ?></span>
      <span class="u-nav-label-text">Watch History</span>
    </a>

    <div class="u-nav-label">Account</div>
    <a class="u-nav-item <?= $activePage === 'profile' ? 'active' : '' ?>"
       data-upage="profile"
       href="<?= u_page_url('profile') ?>">
      <span class="u-nav-icon"><?= u_icon('bi-person-circle') ?></span>
      <span class="u-nav-label-text">Profile</span>
    </a>
    <a class="u-nav-item <?= $activePage === 'subscription' ? 'active' : '' ?>"
       data-upage="subscription"
       href="<?= u_page_url('subscription') ?>">
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
