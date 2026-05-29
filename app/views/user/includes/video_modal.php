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
        <div class="u-feedback-top">
          <div class="u-review-rating-block">
            <div class="u-feedback-title">Your Rating</div>
            <div id="uStarRow" class="u-star-row">
              <?php for ($s = 1; $s <= 5; $s++): ?>
              <span class="u-star" data-v="<?= $s ?>" style="color:var(--muted2);transition:color .15s;"><?= u_icon('bi-star-fill') ?></span>
              <?php endfor; ?>
            </div>
          </div>
          <div class="u-review-submit-block">
            <button class="u-btn u-btn-red" id="uReviewSubmitBtn" type="button">Submit Review</button>
            <span class="u-review-note">Your review public</span>
          </div>
        </div>
        <textarea id="uReviewComment" class="u-feedback-textarea" rows="5" placeholder="Write your comment..."></textarea>
        <span id="uReviewMsg" class="u-feedback-msg"></span>
      </div>
      <div id="uReportForm" class="u-feedback-box" style="display:none;">
        <div class="u-feedback-top">
          <div class="u-feedback-title">Report this movie</div>
          <button class="u-btn u-btn-red" id="uReportSubmitBtn" type="button">Submit Report</button>
        </div>
        <textarea id="uReportReason" class="u-feedback-textarea" rows="5" placeholder="Tell us what is wrong with this movie..."></textarea>
        <span id="uReportMsg" class="u-feedback-msg"></span>
      </div>
    </div>

  </div>
</div>

<!-- Toast holder -->
<div class="u-toast-holder" id="uToastHolder"></div>