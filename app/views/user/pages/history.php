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