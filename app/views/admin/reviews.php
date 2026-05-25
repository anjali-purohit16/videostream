<?php require_once ROOT_PATH . '/app/views/admin/_helpers.php'; ?>

<div class="module-header">
    <div>
        <h1>Reviews</h1>
        <p>User ratings & feedback</p>
    </div>
    <span class="rating-badge" data-live-review-avg>★ <?= number_format((float)$avgRating, 1) ?> Avg Rating</span>
</div>

<?php if ($flash): ?><div class="alert <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div><?php endif; ?>

<section class="panel review-table-panel">
    <div class="table-responsive review-table-scroll">
        <table class="data-table">
            <thead><tr><th>User</th><th>Video</th><th>Rating</th><th>Comment</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody data-live-review-rows>
                <?php foreach ($reviews as $review): ?>
                    <tr data-search-row>
                        <td><strong><?= h($review['user']) ?></strong></td>
                        <td><?= h($review['video']) ?></td>
                        <td><span class="stars"><?= str_repeat('★', (int)$review['rating']) . str_repeat('☆', 5 - (int)$review['rating']) ?></span></td>
                        <td><?= h($review['comment']) ?></td>
                        <td><?= date('M j', strtotime($review['created_at'])) ?></td>
                        <td><span class="pill <?= status_class($review['status']) ?>"><?= h(ucfirst($review['status'])) ?></span></td>
                        <td class="action-cell">
                            <form method="post" action="<?= admin_url('reviews', ['action' => 'approve']) ?>">
                                <input type="hidden" name="id" value="<?= (int)$review['id'] ?>">
                                <button class="mini-btn <?= $review['status'] === 'approved' ? 'mini-btn-success' : 'mini-btn-success-outline' ?>" type="submit">Approve</button>
                            </form>
                            <form method="post" action="<?= admin_url('reviews', ['action' => 'delete']) ?>">
                                <input type="hidden" name="id" value="<?= (int)$review['id'] ?>">
                                <button class="mini-btn <?= $review['status'] === 'rejected' ? 'mini-btn-danger' : 'mini-btn-danger-outline' ?>" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
