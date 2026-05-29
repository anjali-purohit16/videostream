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