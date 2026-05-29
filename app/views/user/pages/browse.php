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
