<?php require_once ROOT_PATH . '/app/views/admin/_helpers.php'; ?>

<div class="admin-auth-page">

    <!-- Left branding panel -->
    <div class="admin-left">
        <div class="admin-grid-bg"></div>
        <div class="admin-brand">

            <div class="admin-brand-logo" style="flex-direction:column;align-items:flex-start;gap:10px;margin-bottom:50px;">
                <img src="<?= BASE_URL ?>assets/images/logo1.png" alt="<?= h(APP_NAME) ?>"
                     style="width:auto;height:70px;max-width:220px;object-fit:contain;display:block;">

                <div class="admin-brand-sub" style="font-size:11px;letter-spacing:4px;color:var(--muted);text-transform:uppercase;">Control Center</div>
            </div>

            <h1 class="admin-headline">Manage your<br><em>streaming empire</em><br>from one place.</h1>
            <p class="admin-tagline">Full control over videos, users, subscriptions, revenue analytics, and platform settings — all in one powerful dashboard.</p>

            <div class="admin-stats-row">
                <div class="admin-stat">
                    <span class="admin-stat-num">10+</span>
                    <span class="admin-stat-lbl">Videos</span>
                </div>
                <div class="admin-stat">
                    <span class="admin-stat-num">20+</span>
                    <span class="admin-stat-lbl">Users</span>
                </div>
                <div class="admin-stat">
                    <span class="admin-stat-num">$250</span>
                    <span class="admin-stat-lbl">Revenue</span>
                </div>
            </div>

        </div>
    </div>

    <!-- Right form panel -->
    <div class="admin-right">
        <div class="admin-form-wrap">

            <div class="admin-form-header">
                <div class="admin-form-badge">Restricted Access</div>
                <h2 class="admin-form-title">Administrator Sign In</h2>
                <p class="admin-form-subtitle">Authorised personnel only</p>
            </div>

            <?php if (!empty($flash)): ?>
                <div class="flash-alert <?= h($flash['type']) ?>">
                    <?= $flash['type'] === 'error' ? '&#9888; ' : '&#10003; ' ?>
                    <?= h($flash['message']) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>admin/login/login" id="adminLoginForm" novalidate>

                <div class="form-field">
                    <label class="vs-label admin-field-label" for="admin_email">Email Address</label>
                    <div class="input-wrapper">
                        <span class="input-icon">@</span>
                        <input
                            class="vs-input input-red"
                            type="email"
                            id="admin_email"
                            name="email"
                            placeholder="Enter admin email"
                            autocomplete="off"
                            required
                        >
                    </div>
                    <div id="email-error" style="font-size:12px;color:#ff6b6b;margin-top:4px;display:none;"></div>
                </div>

                <div class="form-field">
                    <label class="vs-label admin-field-label" for="admin_password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">#</span>
                        <input
                            class="vs-input input-red"
                            type="password"
                            id="admin_password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="off"
                            required
                        >
                        <button type="button" class="toggle-pw" data-target="admin_password" aria-label="Show/hide password">&#128065;</button>
                    </div>
                    <div id="pw-error" style="font-size:12px;color:#ff6b6b;margin-top:4px;display:none;"></div>
                </div>
                 <div class="mb-3">
             <div class="g-recaptcha"
             data-sitekey="6LfWXvcsAAAAAGmwFbfbdjwyK_U42IdAKrWG4bCT">
            </div>
            </div>
                <button class="vs-btn vs-btn-red" type="submit" id="adminSubmitBtn">
                    <span id="adminBtnText">Sign In to Dashboard</span>
                    <span id="adminBtnSpinner" style="display:none;">Verifying...</span>
                </button>

                <div class="admin-security-note">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#444" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    All login attempts are recorded and monitored.
                </div>

            </form>

            <!-- No user login link here — admin portal is completely separate -->

        </div>
    </div>

</div>