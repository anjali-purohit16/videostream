<?php require_once ROOT_PATH . '/app/views/admin/_helpers.php'; ?>

<div class="module-header">
    <div>
        <h1>Categories</h1>
        <p>Organise your video library</p>
    </div>
    <a class="btn btn-primary" href="<?= admin_url('categories', ['new' => 1]) ?>">＋ Add Category</a>
</div>

<!-- ── Toast (auto-dismiss 3 s) ─────────────────────────── -->
<?php if ($flash): ?>
<div id="cat-toast" class="cat-toast cat-toast-<?= h($flash['type']) ?>">
    <?= $flash['type'] === 'success' ? '✓' : '⚠' ?>
    <?= h($flash['message']) ?>
</div>
<script>
(function(){
    var t = document.getElementById('cat-toast');
    if (!t) return;
    setTimeout(function(){ t.classList.add('cat-toast-hide'); }, 3000);
    setTimeout(function(){ if (t) t.remove(); }, 3500);
})();
</script>
<?php endif; ?>

<?php if (!empty($_GET['new']) || !empty($editCategory)): ?>
    <?php $category = $editCategory ?? ['id' => '', 'name' => '', 'icon' => 'film', 'status' => 'active']; ?>
    <form class="panel module-form" method="post" action="<?= admin_url('categories', ['action' => 'save']) ?>">
        <input type="hidden" name="id" value="<?= h($category['id']) ?>">
        <div class="form-grid">
            <label>Name<input class="form-control" name="name" value="<?= h($category['name']) ?>" required></label>
            <input type="hidden" name="icon" value="<?= h($category['icon'] ?? 'film') ?>">
            <label>Status
                <select class="form-control" name="status">
                    <option value="active"   <?= $category['status'] === 'active'   ? 'selected' : '' ?>>Active</option>
                    <option value="suspended" <?= $category['status'] !== 'active' ? 'selected' : '' ?>>Suspended</option>
                </select>
            </label>
        </div>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Save Category</button>
            <a class="btn btn-ghost" href="<?= admin_url('categories') ?>">Cancel</a>
        </div>
    </form>
<?php endif; ?>

<section class="stats-grid three">
    <a class="stat-card c-red" href="#category-table">
        <div class="stat-icon red">▣</div>
        <div class="stat-label">Total Categories</div>
        <div class="stat-value"><?= (int)$stats['total'] ?></div>
        <div class="stat-delta up">↑ <?= (int)$stats['added_this_month'] ?> added this month</div>
    </a>
    <a class="stat-card c-green" href="<?= admin_url('videos', ['category' => $stats['most_videos']['name'] ?? '']) ?>">
        <div class="stat-icon green">▥</div>
        <div class="stat-label">Most Videos</div>
        <div class="stat-value"><?= h(strtoupper($stats['most_videos']['name'] ?? 'None')) ?></div>
        <div class="stat-delta"><?= number_format((int)($stats['most_videos']['video_count'] ?? 0)) ?> videos</div>
    </a>
    <a class="stat-card c-amber" href="<?= admin_url('videos', ['category' => $stats['highest_views']['name'] ?? '']) ?>">
        <div class="stat-icon amber">◎</div>
        <div class="stat-label">Highest Views</div>
        <div class="stat-value"><?= h(strtoupper($stats['highest_views']['name'] ?? 'None')) ?></div>
        <div class="stat-delta"><?= num_short($stats['highest_views']['total_views'] ?? 0) ?> views</div>
    </a>
</section>

<!-- Table with fixed height -->
<section class="panel cat-table-panel" id="category-table">
    <div class="table-responsive cat-table-scroll">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Videos</th>
                    <th>Total Views</th>
                    <th>Last Added</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                    <?php $categoryStatus = ($category['status'] ?? '') === 'active' ? 'active' : 'suspended'; ?>
                    <tr data-search-row>
                        <td><strong><?= h($category['name']) ?></strong></td>
                        <td><?= number_format((int)$category['video_count']) ?></td>
                        <td><?= num_short($category['total_views']) ?></td>
                        <td><?= ago($category['last_upload']) ?></td>
                        <td><span class="pill <?= status_class($categoryStatus) ?>"><?= h(ucfirst($categoryStatus)) ?></span></td>
                        <td class="action-cell">
                            <button class="mini-btn" type="button"
                                    data-bs-toggle="modal"
                                    data-bs-target="#categoryModal<?= (int)$category['id'] ?>">View</button>
                            <a class="mini-btn" href="<?= admin_url('categories', ['edit_id' => $category['id']]) ?>">Edit</a>
                            <form method="post"
                                  action="<?= admin_url('categories', ['action' => 'suspend']) ?>"
                                  data-confirm="<?= $categoryStatus === 'active' ? 'Suspend this category from the user panel?' : 'Activate this category again?' ?>">
                                <input type="hidden" name="id" value="<?= (int)$category['id'] ?>">
                                <button class="mini-btn <?= $categoryStatus === 'active' ? 'mini-btn-danger' : '' ?>" type="submit">
                                    <?= $categoryStatus === 'active' ? 'Suspend' : 'Activate' ?>
                                </button>
                            </form>
                            <form method="post"
                                  action="<?= admin_url('categories', ['action' => 'delete']) ?>"
                                  data-confirm="Delete this category? This is only allowed when it has no videos.">
                                <input type="hidden" name="id" value="<?= (int)$category['id'] ?>">
                                <button class="mini-btn mini-btn-danger-outline" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:40px; color:var(--muted);">
                            No categories found.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Category detail modals -->
<?php foreach ($categories as $category): ?>
    <?php $movies = $categoryVideos[(int)$category['id']] ?? []; ?>
    <div class="modal fade admin-modal" id="categoryModal<?= (int)$category['id'] ?>"
         tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title"><?= h($category['name']) ?></h2>
                        <p><?= number_format((int)$category['video_count']) ?> videos in this category</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white"
                            data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if ($movies): ?>
                        <div class="movie-list">
                            <?php foreach ($movies as $movie): ?>
                                <div class="movie-list-row">
                                    <div class="thumb thumb-image">
                                        <?php if (!empty($movie['thumbnail'])): ?>
                                            <img src="<?= h(app_media_url($movie['thumbnail'])) ?>" alt="">
                                        <?php else: ?>
                                            &#9654;
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <strong><?= h($movie['title']) ?></strong>
                                        <span><?= h(VideoModel::formatDuration((int)$movie['duration_sec'])) ?> &middot; <?= num_short($movie['views']) ?> views &middot; <?= h(ucfirst($movie['status'])) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">No movies have been added in this category yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
