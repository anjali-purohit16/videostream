<?php require_once ROOT_PATH . '/app/views/admin/_helpers.php'; ?>

<?php
// Form is open when ?new=1 is in the URL
$showForm = !empty($_GET['new']);
?>

<!-- ── Page header ──────────────────────────────────────── -->
<div class="module-header">
    <div>
        <h1>Users</h1>
        <p><?= number_format((int)$totalUsers) ?> registered members</p>
    </div>
    <div class="header-actions">
        <a class="btn btn-ghost"
           href="<?= admin_url('users', array_merge($filters, ['export' => 1])) ?>"
           id="ud-export-btn"
           style="<?= $showForm ? 'display:none;' : '' ?>">⇩ Export</a>
        <button class="btn btn-primary" id="ud-open-btn" type="button"
                style="<?= $showForm ? 'display:none;' : '' ?>">＋ Add User</button>
    </div>
</div>

<!-- ── Toast (auto-dismiss 3 s) ─────────────────────────── -->
<?php if ($flash): ?>
<div id="ud-toast" class="ud-toast ud-toast-<?= h($flash['type']) ?>">
    <?= $flash['type'] === 'success' ? '✓' : '⚠' ?>
    <?= h($flash['message']) ?>
</div>
<script>
(function(){
    var t = document.getElementById('ud-toast');
    if (!t) return;
    setTimeout(function(){ t.classList.add('ud-toast-hide'); }, 3000);
    setTimeout(function(){ if (t) t.remove(); }, 3500);
})();
</script>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════
     ADD USER FORM — visible only when $showForm = true
     Table is hidden at same time (JS + PHP guard)
     ══════════════════════════════════════════════════════ -->
<div id="ud-form-wrap" style="<?= $showForm ? '' : 'display:none;' ?>">
    <div class="ud-form-card panel">

        <!-- Form header -->
        <div class="ud-form-header">
            <div class="ud-form-header-left">
                <button class="ud-back-btn" id="ud-back-btn" type="button" title="Back to users table">
                    ← Back
                </button>
                <div>
                    <h2 class="ud-form-title">Add New User</h2>
                    <p class="ud-form-sub">Create a user account and assign a subscription plan</p>
                </div>
            </div>
        </div>

        <!-- Form body -->
        <form id="ud-form"
              class="ud-form-body"
              method="post"
              action="<?= admin_url('users', ['action' => 'save']) ?>">

            <!-- Row 1: name + email + password -->
            <div class="ud-grid">
                <div class="ud-field ud-col-2">
                    <label class="ud-label" for="ud-name">Full Name <span class="ud-req">*</span></label>
                    <input class="form-control" id="ud-name" name="name"
                           placeholder="e.g. John Smith" required>
                </div>
                <div class="ud-field ud-col-2">
                    <label class="ud-label" for="ud-email">Email Address <span class="ud-req">*</span></label>
                    <input class="form-control" id="ud-email" type="email" name="email"
                           placeholder="user@example.com" autocomplete="off" required>
                </div>
                <div class="ud-field ud-col-2">
                    <label class="ud-label" for="ud-password">Password <span class="ud-req">*</span></label>
                    <div class="ud-pw-wrap">
                        <input class="form-control" id="ud-password" type="password" name="password"
                               placeholder="Min. 6 characters" minlength="6"
                               autocomplete="new-password" required>
                        <button class="ud-pw-toggle" type="button"
                                data-target="ud-password" title="Show/hide password">👁</button>
                    </div>
                </div>
            </div>

            <!-- Row 2: plan + status + subscription checkbox -->
            <div class="ud-grid">
                <div class="ud-field ud-col-2">
                    <label class="ud-label" for="ud-plan">Plan <span class="ud-req">*</span></label>
                    <select class="form-control" id="ud-plan" name="plan_id" required>
                        <option value="">— select plan —</option>
                        <?php foreach (($plans ?? []) as $item): ?>
                            <option value="<?= (int)$item['id'] ?>">
                                <?= h($item['name']) ?> — <?= h($item['currency']) ?> <?= h($item['price']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ud-field ud-col-2">
                    <label class="ud-label" for="ud-status">Status</label>
                    <select class="form-control" id="ud-status" name="status">
                        <?php foreach (['active', 'suspended', 'banned'] as $st): ?>
                            <option value="<?= $st ?>"><?= ucfirst($st) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="ud-field ud-col-2 ud-check-cell">
                    <label class="ud-label">&nbsp;</label>
                    <label class="ud-check-row">
                        <input type="checkbox" name="create_subscription" value="1" checked>
                        <span>Create active subscription for this plan</span>
                    </label>
                </div>
            </div>

            <!-- Actions -->
            <div class="ud-form-actions">
                <button class="btn btn-primary" type="submit" id="ud-save-btn">
                    <span id="ud-save-label">＋ Save User</span>
                    <span id="ud-save-spinner" style="display:none;">Saving…</span>
                </button>
                <button class="btn btn-ghost" type="button" id="ud-cancel-btn">✕ Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════
     TABLE SECTION — hidden when form is open
     ══════════════════════════════════════════════════════ -->
<div id="ud-table-wrap" style="<?= $showForm ? 'display:none;' : '' ?>">

    <!-- Filters -->
    <form class="filters module-filters" method="get">
        <input type="hidden" name="module" value="admin">
        <input type="hidden" name="page"   value="users">
        <input class="filter-input flex-grow-1" name="search"
               value="<?= h($filters['search']) ?>" placeholder=" Search users...">
        <select class="filter-input" name="plan">
            <option value="">All Plans</option>
            <?php foreach (['Premium', 'Basic', 'Free'] as $plan): ?>
                <option value="<?= $plan ?>"
                    <?= $filters['plan'] === $plan ? 'selected' : '' ?>><?= $plan ?></option>
            <?php endforeach; ?>
        </select>
        <select class="filter-input" name="status">
            <option value="">All Status</option>
            <?php foreach (['active', 'suspended', 'banned'] as $status): ?>
                <option value="<?= $status ?>"
                    <?= $filters['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-ghost" type="submit">Search</button>
    </form>

    <!-- Table -->
    <section class="panel ud-table-panel">
        <div class="table-responsive ud-table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Plan</th>
                        <th>Joined</th>
                        <th>Last Active</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr data-search-row>
                            <td>
                                <span class="avatar-mini"><?= h(initials($user['name'])) ?></span>
                                <strong><?= h($user['name']) ?></strong>
                            </td>
                            <td><?= h($user['email']) ?></td>
                            <td>
                                <span class="pill <?= strtolower($user['plan']) === 'premium'
                                    ? 'pill-red' : (strtolower($user['plan']) === 'basic'
                                    ? 'pill-blue' : 'pill-gray') ?>">
                                    <?= h($user['plan']) ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y', strtotime($user['joined_at'])) ?></td>
                            <td><?= ago($user['last_seen']) ?></td>
                            <td>
                                <span class="pill <?= status_class($user['status']) ?>">
                                    <?= h(ucfirst($user['status'])) ?>
                                </span>
                            </td>
                            <td class="action-cell">
                                <button class="mini-btn" type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#userModal<?= (int)$user['id'] ?>">View</button>
                                <form method="post"
                                      action="<?= admin_url('users', ['action' => $user['status'] === 'active' ? 'suspend' : 'activate']) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                                    <button class="mini-btn" type="submit">
                                        <?= $user['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                                    </button>
                                </form>
                                <form method="post"
                                      action="<?= admin_url('users', ['action' => 'delete']) ?>"
                                      data-confirm="Delete this user and all related account data?">
                                    <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
                                    <button class="mini-btn mini-btn-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:40px; color:var(--muted);">
                                No users found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<!-- ══════════════════════════════════════════════════════
     USER DETAIL MODALS
     ══════════════════════════════════════════════════════ -->
<?php foreach ($users as $user): ?>
    <?php
        $details      = $userDetails[(int)$user['id']] ?? [];
        $profile      = $details['profile'] ?? $user;
        $passwordHash = (string)($profile['password'] ?? '');
        $passwordLabel = $passwordHash !== ''
            ? substr($passwordHash, 0, 12) . '...' . substr($passwordHash, -6)
            : 'Not stored';
    ?>
    <div class="modal fade admin-modal" id="userModal<?= (int)$user['id'] ?>"
         tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title"><?= h($profile['name']) ?></h2>
                        <p><?= h($profile['email']) ?> &middot; User ID #<?= (int)$profile['id'] ?></p>
                    </div>
                    <button type="button" class="btn-close btn-close-white"
                            data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="detail-grid">
                        <div class="detail-card"><span>Plan</span><strong><?= h($profile['plan'] ?? 'Unknown') ?></strong></div>
                        <div class="detail-card"><span>Status</span><strong><?= h(ucfirst($profile['status'] ?? 'Unknown')) ?></strong></div>
                        <div class="detail-card"><span>Created Time</span><strong><?= !empty($profile['joined_at']) ? date('M j, Y g:i A', strtotime($profile['joined_at'])) : 'Unknown' ?></strong></div>
                        <div class="detail-card"><span>Last Active</span><strong><?= !empty($profile['last_seen']) ? date('M j, Y g:i A', strtotime($profile['last_seen'])) : 'Never' ?></strong></div>
                        <div class="detail-card detail-card-wide"><span>Password</span><strong>Hashed in database: <?= h($passwordLabel) ?></strong></div>
                    </div>

                    <div class="modal-section">
                        <h3>Subscription Details</h3>
                        <?php if (!empty($details['subscriptions'])): ?>
                            <div class="mini-list">
                                <?php foreach ($details['subscriptions'] as $subscription): ?>
                                    <div class="mini-list-row">
                                        <strong><?= h($subscription['plan_name']) ?> <?= h($subscription['currency']) ?> <?= h($subscription['price']) ?></strong>
                                        <span><?= h(ucfirst($subscription['status'])) ?> &middot; <?= h($subscription['starts_at']) ?> to <?= h($subscription['expires_at']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">No subscription records found.</div>
                        <?php endif; ?>
                    </div>

                    <div class="modal-columns">
                        <div class="modal-section">
                            <h3>Wishlist</h3>
                            <?php if (!empty($details['wishlist'])): ?>
                                <div class="mini-list">
                                    <?php foreach ($details['wishlist'] as $item): ?>
                                        <div class="mini-list-row">
                                            <strong><?= h($item['title']) ?></strong>
                                            <span><?= h($item['category']) ?> &middot; <?= ago($item['created_at']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">No wishlist items yet.</div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-section">
                            <h3>Watch History</h3>
                            <?php if (!empty($details['history'])): ?>
                                <div class="mini-list">
                                    <?php foreach ($details['history'] as $item): ?>
                                        <div class="mini-list-row">
                                            <strong><?= h($item['title']) ?></strong>
                                            <span><?= (int)$item['progress_percent'] ?>% &middot; <?= ago($item['watched_at']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">No watch history yet.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="modal-columns">
                        <div class="modal-section">
                            <h3>Recent Payments</h3>
                            <?php if (!empty($details['payments'])): ?>
                                <div class="mini-list">
                                    <?php foreach ($details['payments'] as $payment): ?>
                                        <div class="mini-list-row">
                                            <strong><?= h($payment['txn_id']) ?></strong>
                                            <span><?= h($payment['currency']) ?> <?= h($payment['amount']) ?> via <?= h($payment['method']) ?> &middot; <?= h(ucfirst($payment['status'])) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">No payment records found.</div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-section">
                            <h3>Reviews</h3>
                            <?php if (!empty($details['reviews'])): ?>
                                <div class="mini-list">
                                    <?php foreach ($details['reviews'] as $review): ?>
                                        <div class="mini-list-row">
                                            <strong><?= h($review['video']) ?> &middot; <?= (int)$review['rating'] ?>/5</strong>
                                            <span><?= h(ucfirst($review['status'])) ?> &middot; <?= h($review['comment'] ?? '') ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">No reviews found.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- ══════════════════════════════════════════════════════
     USER MODULE JS
     ══════════════════════════════════════════════════════ -->
<script>
(function () {
    'use strict';

    var openBtn    = document.getElementById('ud-open-btn');
    var exportBtn  = document.getElementById('ud-export-btn');
    var backBtn    = document.getElementById('ud-back-btn');
    var cancelBtn  = document.getElementById('ud-cancel-btn');
    var formWrap   = document.getElementById('ud-form-wrap');
    var tableWrap  = document.getElementById('ud-table-wrap');
    var form       = document.getElementById('ud-form');
    var saveBtn    = document.getElementById('ud-save-btn');

    /* ── visibility helpers ── */
    function showForm() {
        formWrap.style.display  = '';
        tableWrap.style.display = 'none';
        if (openBtn)   openBtn.style.display   = 'none';
        if (exportBtn) exportBtn.style.display = 'none';
        formWrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
        // Clear form fields on each fresh open
        if (form) form.reset();
    }

    function showTable() {
        formWrap.style.display  = 'none';
        tableWrap.style.display = '';
        if (openBtn)   openBtn.style.display   = '';
        if (exportBtn) exportBtn.style.display = '';
    }

    /* ── button bindings ── */
    if (openBtn)   openBtn.addEventListener('click',   showForm);
    if (backBtn)   backBtn.addEventListener('click',   showTable);
    if (cancelBtn) cancelBtn.addEventListener('click', showTable);

    /* ── password visibility toggle ── */
    document.querySelectorAll('.ud-pw-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.getElementById(btn.dataset.target);
            if (!target) return;
            var isText = target.type === 'text';
            target.type    = isText ? 'password' : 'text';
            btn.textContent = isText ? '👁' : '🙈';
        });
    });

    /* ── submit: spinner, then POST normally ── */
    if (form && saveBtn) {
        form.addEventListener('submit', function () {
            var label   = document.getElementById('ud-save-label');
            var spinner = document.getElementById('ud-save-spinner');
            if (label)   label.style.display   = 'none';
            if (spinner) spinner.style.display  = '';
            saveBtn.disabled = true;
        });
    }

})();
</script>
