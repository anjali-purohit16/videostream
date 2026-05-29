<div class="u-section-header" style="margin-bottom:16px;">
        <span class="u-section-title"><?= u_icon('bi-grid') ?> Browse by Category</span>
      </div>

      <div class="u-cat-grid" style="margin-bottom:28px;">
        <a class="u-cat-card <?= $selectedCatId === 0 ? 'active' : '' ?>"
           href="<?= u_page_url('categories') ?>">
          <div class="u-cat-icon"><?= u_icon('bi-grid-3x3-gap') ?></div>
          <div class="u-cat-name">All</div>
          <div class="u-cat-count"><?= count($featured) ?> videos</div>
        </a>
        <?php foreach ($categories as $cat): ?>
        <a class="u-cat-card <?= $selectedCatId === (int)$cat['id'] ? 'active' : '' ?>"
           href="<?= u_page_url('categories', ['cat' => (int)$cat['id']]) ?>">
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