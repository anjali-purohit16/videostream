<?php require_once ROOT_PATH . '/app/views/admin/_helpers.php'; ?>

<?php
// Determine form visibility: show if ?new=1 OR editing existing
$showForm = !empty($_GET['new']) || !empty($editVideo);
$video    = $editVideo ?? [
    'id'          => '',
    'title'       => '',
    'description' => '',
    'category_id' => '',
    'category_ids' => '',
    'access_level' => 'free',
    'duration_sec'=> 0,
    'thumbnail'   => '',
    'file_path'   => '',
    'status'      => 'draft',
];
$selectedCategoryIds = array_filter(array_map('intval', explode(',', (string)($video['category_ids'] ?? $video['category_id'] ?? ''))));
// Was the page loaded right after a successful save?
$justSaved = !empty($flash) && $flash['type'] === 'success';
$currentThumbUrl = app_media_url($video['thumbnail'] ?? '');
$currentVideoUrl = app_media_url($video['file_path'] ?? '');
$currentThumbName = basename((string)($video['thumbnail'] ?? ''));
$currentVideoName = basename((string)($video['file_path'] ?? ''));
?>

<!-- ── Page header ─────────────────────────────────────── -->
<div class="module-header">
    <div>
        <h1>Videos</h1>
        <p>Manage all platform video content</p>
    </div>
    <button class="btn btn-primary" id="vd-open-btn" type="button"
            style="<?= $showForm ? 'display:none;' : '' ?>">
        ⇧ Upload Video
    </button>
</div>

<!-- ── Toast (auto-dismiss 3 s) ───────────────────────── -->
<?php if ($flash): ?>
<div id="vd-toast" class="vd-toast vd-toast-<?= h($flash['type']) ?>">
    <?= $flash['type'] === 'success' ? '✓' : '⚠' ?>
    <?= h($flash['message']) ?>
</div>
<script>
    (function(){
        var t = document.getElementById('vd-toast');
        if (!t) return;
        setTimeout(function(){ t.classList.add('vd-toast-hide'); }, 3000);
        setTimeout(function(){ if(t) t.remove(); }, 3500);
    })();
</script>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════
     UPLOAD / EDIT FORM
     Visible when showForm=true.  Table hidden at same time.
     ══════════════════════════════════════════════════════ -->
<div id="vd-form-wrap" style="<?= $showForm ? '' : 'display:none;' ?>">
    <div class="vd-form-card panel">
        <!-- Form header with back button -->
        <div class="vd-form-header">
            <div class="vd-form-header-left">
                <button class="vd-back-btn" type="button" id="vd-back-btn" title="Back to table">
                    ← Back
                </button>
                <div>
                    <h2 class="vd-form-title">
                        <?= empty($video['id']) ? 'Upload New Video' : 'Edit Video' ?>
                    </h2>
                    <p class="vd-form-sub">
                        <?= empty($video['id']) ? 'Add a new video to the platform' : 'Update video details' ?>
                    </p>
                </div>
            </div>
        </div>

        <form id="vd-form"
              class="vd-form-body"
              method="post"
              enctype="multipart/form-data"
              action="<?= admin_url('videos', ['action' => 'save']) ?>">
            <input type="hidden" name="id" value="<?= h($video['id'] ?? '') ?>">

            <!-- Grid row 1: title + category + duration -->
            <div class="vd-grid">
                <div class="vd-field vd-col-3">
                    <label class="vd-label" for="vd-title">Video Title <span class="vd-req">*</span></label>
                    <input class="form-control" id="vd-title" name="title"
                           value="<?= h($video['title']) ?>"
                           placeholder="e.g. Inception (2010)"
                           required>
                </div>
                <div class="vd-field">
                    <label class="vd-label" for="vd-cat">Categories <span class="vd-req">*</span></label>
                    <select class="form-control" id="vd-cat" name="category_ids[]" multiple required size="4">
                        <option value="">— select —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>"
                                <?= in_array((int)$cat['id'], $selectedCategoryIds, true) ? 'selected' : '' ?>>
                                <?= h($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="category_id" id="vd-primary-cat" value="<?= h($selectedCategoryIds[0] ?? '') ?>">
                    <small class="vd-help">Ctrl or Cmd lets you pick more than one category.</small>
                </div>
                <div class="vd-field">
                    <label class="vd-label" for="vd-status">Status</label>
                    <select class="form-control" id="vd-status" name="status">
                        <?php foreach (['draft', 'processing', 'published'] as $st): ?>
                            <option value="<?= $st ?>"
                                <?= ($video['status'] ?? '') === $st ? 'selected' : '' ?>>
                                <?= ucfirst($st) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="vd-field">
                    <label class="vd-label" for="vd-access">Plan Access</label>
                    <select class="form-control" id="vd-access" name="access_level">
                        <?php foreach (['free' => 'Free', 'basic' => 'Basic', 'premium' => 'Premium'] as $value => $label): ?>
                            <option value="<?= $value ?>" <?= strtolower($video['access_level'] ?? 'free') === $value ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Grid row 2: duration + status -->
            <div class="vd-grid">
                <div class="vd-field">
                    <label class="vd-label" for="vd-hours">Hours</label>
                    <input class="form-control" id="vd-hours" type="number"
                           min="0" name="hours"
                           value="<?= intdiv((int)$video['duration_sec'], 3600) ?>"
                           placeholder="0">
                </div>
                <div class="vd-field">
                    <label class="vd-label" for="vd-mins">Minutes</label>
                    <input class="form-control" id="vd-mins" type="number"
                           min="0" max="59" name="minutes"
                           value="<?= intdiv((int)$video['duration_sec'] % 3600, 60) ?>"
                           placeholder="0">
                </div>
                <div class="vd-field vd-col-3">
                    <label class="vd-label" for="vd-thumb-url">Thumbnail URL</label>
                    <input class="form-control" id="vd-thumb-url" name="thumbnail"
                           value="<?= h($video['thumbnail'] ?? '') ?>"
                           placeholder="uploads/thumbnails/poster.jpg">
                </div>
            </div>

            <?php if (!empty($video['id']) && ($currentThumbUrl || $currentVideoUrl)): ?>
            <div class="vd-current-media" style="margin-bottom:16px;">
                <div class="vd-current-thumb" id="vd-current-thumb">
                    <?php if ($currentThumbUrl): ?>
                        <img src="<?= h($currentThumbUrl) ?>" alt="<?= h($video['title'] ?? 'Current thumbnail') ?>">
                    <?php else: ?>
                        &#9654;
                    <?php endif; ?>
                </div>
                <div class="vd-current-info">
                    <span>Current uploads</span>
                    <?php if ($currentThumbUrl): ?>
                        <a href="<?= h($currentThumbUrl) ?>" target="_blank" rel="noopener"><?= h($currentThumbName ?: $video['thumbnail']) ?></a>
                    <?php else: ?>
                        <strong>No thumbnail uploaded</strong>
                    <?php endif; ?>
                    <?php if ($currentVideoUrl): ?>
                        <a href="<?= h($currentVideoUrl) ?>" target="_blank" rel="noopener" style="margin-top:8px;"><?= h($currentVideoName ?: $video['file_path']) ?></a>
                    <?php else: ?>
                        <strong style="margin-top:8px;">No video file uploaded</strong>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Grid row 3: file uploads -->
            <div class="vd-grid">
                <div class="vd-field vd-col-2">
                    <label class="vd-label" for="vd-thumb-file">Upload Thumbnail</label>
                    <div class="vd-file-wrap">
                        <label class="vd-file-btn" for="vd-thumb-file">
                            🖼 Choose Image
                            <input class="vd-file-input" id="vd-thumb-file" type="file"
                                   name="thumbnail_file" accept="image/*">
                        </label>
                        <span class="vd-file-name" id="vd-thumb-name"><?= $currentThumbName ? 'Current: ' . h($currentThumbName) : 'No file chosen' ?></span>
                    </div>
                </div>
                <div class="vd-field vd-col-2">
                    <label class="vd-label" for="vd-video-file">Upload Video File</label>
                    <div class="vd-file-wrap">
                        <label class="vd-file-btn" for="vd-video-file">
                            🎬 Choose Video
                            <input class="vd-file-input" id="vd-video-file" type="file"
                                   name="video_file" accept="video/*">
                        </label>
                        <span class="vd-file-name" id="vd-video-name"><?= $currentVideoName ? 'Current: ' . h($currentVideoName) : 'No file chosen' ?></span>
                    </div>
                </div>
                <div class="vd-field vd-col-2">
                    <label class="vd-label" for="vd-fp">Video URL / Path</label>
                    <input class="form-control" id="vd-fp" name="file_path"
                           value="<?= h($video['file_path'] ?? '') ?>"
                           placeholder="uploads/videos/movie.mp4">
                </div>
            </div>

            <!-- Description -->
            <div class="vd-field" style="margin-top:4px;">
                <label class="vd-label" for="vd-desc">Description</label>
                <textarea class="form-control vd-textarea" id="vd-desc"
                          name="description" rows="4"
                          placeholder="Short movie or series description"><?= h($video['description'] ?? '') ?></textarea>
            </div>

            <!-- Actions -->
            <div class="vd-form-actions">
                <button class="btn btn-primary" type="submit" id="vd-save-btn">
                    <span id="vd-save-label">
                        <?= empty($video['id']) ? '⇧ Upload & Save' : '✓ Update Video' ?>
                    </span>
                    <span id="vd-save-spinner" style="display:none;">Saving…</span>
                </button>
                <button class="btn btn-ghost" type="button" id="vd-cancel-btn">✕ Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════
     TABLE SECTION — hidden when form is open
     ══════════════════════════════════════════════════════ -->
<div id="vd-table-wrap" style="<?= $showForm ? 'display:none;' : '' ?>">

    <!-- Filters -->
    <form class="filters module-filters" method="get">
        <input type="hidden" name="module" value="admin">
        <input type="hidden" name="page"   value="videos">
        <input class="filter-input flex-grow-1" name="search"
               value="<?= h($filters['search']) ?>"
               placeholder="  Search videos...">
        <select class="filter-input" name="category">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= h($cat['name']) ?>"
                    <?= $filters['category'] === $cat['name'] ? 'selected' : '' ?>>
                    <?= h($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select class="filter-input" name="status">
            <option value="">All Status</option>
            <?php foreach (['published', 'processing', 'draft'] as $st): ?>
                <option value="<?= $st ?>" <?= $filters['status'] === $st ? 'selected' : '' ?>>
                    <?= ucfirst($st) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-ghost" type="submit">Search</button>
    </form>

    <!-- Table -->
    <section class="panel vd-table-panel">
        <div class="table-responsive vd-table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Thumbnail</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Access</th>
                        <th>Duration</th>
                        <th>Views</th>
                        <th>Uploaded</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($videos as $vid): ?>
                        <tr data-search-row>
                            <td>
                                <span class="thumb thumb-image">
                                    <?php if (!empty($vid['thumbnail'])): ?>
                                        <img src="<?= h(app_media_url($vid['thumbnail'])) ?>" alt="">
                                    <?php else: ?>
                                        &#9654;
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td><strong><?= h($vid['title']) ?></strong></td>
                            <td><?= h($vid['category']) ?></td>
                            <?php $access = strtolower($vid['access_level'] ?? 'free'); ?>
                            <td><span class="pill access-pill access-<?= h($access) ?>"><?= h(ucfirst($access)) ?></span></td>
                            <td><?= h(VideoModel::formatDuration((int)$vid['duration_sec'])) ?></td>
                            <td><?= num_short($vid['views']) ?></td>
                            <td><?= ago($vid['created_at']) ?></td>
                            <td><span class="pill <?= status_class($vid['status']) ?>"><?= h(ucfirst($vid['status'])) ?></span></td>
                            <td class="action-cell">
                                <button class="mini-btn" type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#videoModal<?= (int)$vid['id'] ?>">View</button>
                                <a class="mini-btn" href="<?= admin_url('videos', ['edit_id' => $vid['id']]) ?>">Edit</a>
                                <form method="post"
                                      action="<?= admin_url('videos', ['action' => 'delete']) ?>"
                                      data-confirm="Delete this video?">
                                    <input type="hidden" name="id" value="<?= (int)$vid['id'] ?>">
                                    <button class="mini-btn mini-btn-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($videos)): ?>
                        <tr><td colspan="9" style="text-align:center; padding:40px; color:var(--muted);">No videos found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<!-- ══════════════════════════════════════════════════════
     VIDEO DETAIL MODALS
     ══════════════════════════════════════════════════════ -->
<?php foreach ($videos as $vid): ?>
    <div class="modal fade admin-modal pro-modal" id="videoModal<?= (int)$vid['id'] ?>"
         tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title"><?= h($vid['title']) ?></h2>
                        <p><?= h($vid['category']) ?> &middot;
                           <?= h(ucfirst($vid['access_level'] ?? 'free')) ?> access &middot;
                           <?= h(VideoModel::formatDuration((int)$vid['duration_sec'])) ?> &middot;
                           <?= num_short($vid['views']) ?> views</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white"
                            data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="video-detail-grid">
                        <div class="video-preview">
                            <?php if (!empty($vid['file_path'])): ?>
                                <video controls preload="metadata"
                                       poster="<?= !empty($vid['thumbnail']) ? h(app_media_url($vid['thumbnail'])) : '' ?>">
                                    <source src="<?= h(app_media_url($vid['file_path'])) ?>" type="<?= h(app_video_mime($vid['file_path'])) ?>">
                                </video>
                            <?php elseif (!empty($vid['thumbnail'])): ?>
                                <img src="<?= h(app_media_url($vid['thumbnail'])) ?>" alt="">
                            <?php else: ?>
                                <div class="video-empty-preview">&#9654;</div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-section">
                            <h3>Upload Details</h3>
                            <div class="detail-list">
                                <div><span>Status</span><strong><?= h(ucfirst($vid['status'])) ?></strong></div>
                                <div><span>Plan Access</span><strong><?= h(ucfirst($vid['access_level'] ?? 'free')) ?></strong></div>
                                <div><span>Uploaded</span><strong><?= date('M j, Y g:i A', strtotime($vid['created_at'])) ?></strong></div>
                                <div><span>Thumbnail</span><strong><?= h($vid['thumbnail'] ?: 'Not uploaded') ?></strong></div>
                                <div><span>Video Path</span><strong><?= h($vid['file_path'] ?: 'Not uploaded') ?></strong></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-section mt-3">
                        <h3>Description</h3>
                        <p class="detail-copy"><?= h($vid['description'] ?: 'No description added yet.') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- ══════════════════════════════════════════════════════
     VIDEO MODULE JS
     ══════════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    var openBtn   = document.getElementById('vd-open-btn');
    var backBtn   = document.getElementById('vd-back-btn');
    var cancelBtn = document.getElementById('vd-cancel-btn');
    var formWrap  = document.getElementById('vd-form-wrap');
    var tableWrap = document.getElementById('vd-table-wrap');
    var form      = document.getElementById('vd-form');
    var saveBtn   = document.getElementById('vd-save-btn');

    /* ── show / hide helpers ── */
    function showForm() {
        formWrap.style.display  = '';
        tableWrap.style.display = 'none';
        if (openBtn) openBtn.style.display = 'none';
        // scroll to top of form
        formWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    function showTable() {
        formWrap.style.display  = 'none';
        tableWrap.style.display = '';
        if (openBtn) openBtn.style.display = '';
    }

    /* ── Upload Video button ── */
    if (openBtn) openBtn.addEventListener('click', showForm);

    /* ── Back / Cancel buttons ── */
    if (backBtn)   backBtn.addEventListener('click',   showTable);
    if (cancelBtn) cancelBtn.addEventListener('click', showTable);

    /* ── File input labels ── */
    var thumbInput = document.getElementById('vd-thumb-file');
    var videoInput = document.getElementById('vd-video-file');
    var thumbName  = document.getElementById('vd-thumb-name');
    var videoName  = document.getElementById('vd-video-name');

    if (thumbInput && thumbName) {
        thumbInput.addEventListener('change', function () {
            thumbName.textContent = this.files[0] ? this.files[0].name : 'No file chosen';
            var preview = document.querySelector('#vd-current-thumb img');
            if (preview && this.files[0]) {
                preview.src = URL.createObjectURL(this.files[0]);
                preview.onload = function () { URL.revokeObjectURL(preview.src); };
            }
        });
    }
    if (videoInput && videoName) {
        videoInput.addEventListener('change', function () {
            videoName.textContent = this.files[0] ? this.files[0].name : 'No file chosen';
        });
    }

    /* ── Submit: show spinner, let form POST normally ── */
    if (form && saveBtn) {
        form.addEventListener('submit', function () {
            var catSelect = document.getElementById('vd-cat');
            var primaryCat = document.getElementById('vd-primary-cat');
            if (catSelect && primaryCat && catSelect.selectedOptions.length) {
                primaryCat.value = catSelect.selectedOptions[0].value;
            }
            var label   = document.getElementById('vd-save-label');
            var spinner = document.getElementById('vd-save-spinner');
            if (label)   label.style.display   = 'none';
            if (spinner) spinner.style.display  = '';
            saveBtn.disabled = true;
        });
    }

    /* ── Delete confirm ── */
})();
</script>
