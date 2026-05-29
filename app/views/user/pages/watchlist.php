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