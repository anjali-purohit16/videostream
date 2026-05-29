<?php require_once ROOT_PATH . '/app/views/admin/_helpers.php'; ?>

<div class="user-auth-page">

    <!-- Background layers -->
    <div class="user-bg"></div>
    <div class="user-bg-tiles">
        <?php for ($i = 0; $i < 32; $i++): ?>
            <div class="user-bg-tile"></div>
        <?php endfor; ?>
    </div>
    <div class="user-auth-overlay"></div>

    <!-- Top bar — logo only, no admin link -->
    <div class="user-topbar">
        <a class="user-logo" href="<?= BASE_URL ?>">
               <img src="<?= BASE_URL ?>assets/images/logo1.png" alt="<?= htmlspecialchars(APP_NAME) ?> Logo" style="width:100%;height:auto;max-width:200px;object-fit:contain;display:block;margin:0 auto;">
        </a>
    </div>

    <!-- Card -->
    <div class="user-card-container">
        <div class="user-auth-card">

            <div class="user-card-header">
                <div class="user-card-kicker">Member Access</div>
                <h1 class="user-card-title">Welcome back</h1>
                <p class="user-card-subtitle">Sign in to continue watching</p>
            </div>

            <?php if (!empty($flash)): ?>
                <div class="flash-alert <?= h($flash['type']) ?>">
                    <?= $flash['type'] === 'error' ? '&#9888; ' : '&#10003; ' ?>
                    <?= h($flash['message']) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>login/login" id="userLoginForm" novalidate>

                <div class="form-field">
                    <label class="vs-label user-field-label" for="user_email">Email</label>
                    <div class="input-wrapper">
                        <span class="input-icon">@</span>
                        <input
                            class="vs-input"
                            type="email"
                            id="user_email"
                            name="email"
                            placeholder="you@example.com"
                            autocomplete="email"
                            required
                        >
                    </div>
                </div>

                <div class="form-field" style="margin-bottom:24px;">
                    <label class="vs-label user-field-label" for="user_password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">#</span>
                        <input
                            class="vs-input"
                            type="password"
                            id="user_password"
                            name="password"
                            placeholder="Your password"
                            autocomplete="current-password"
                            required
                        >
                        <button type="button" class="toggle-pw" data-target="user_password" aria-label="Show/hide password">&#128065;</button>
                    </div>
                </div>
                <div class="mb-3">
                 <div class="g-recaptcha"
                   data-sitekey="<?= h(defined('RECAPTCHA_SITE_KEY') ? RECAPTCHA_SITE_KEY : '') ?>">
                 </div>
                </div>

                <button class="vs-btn vs-btn-red" type="submit" id="userSubmitBtn">
                    <span id="userBtnText">&#9654;&nbsp; Start Watching</span>
                    <span id="userBtnSpinner" style="display:none;">Signing in...</span>
                </button>

            </form>

            <!-- Only user-facing link: create account -->
            <div class="user-auth-links" style="justify-content:center; margin-top:20px;">
                <a href="<?= BASE_URL ?>register">New here? <strong>Create a free account</strong></a>
            </div>
            

            <div class="user-divider"></div>
            <!-- <div class="user-features">
                <div class="user-feature">
                    <div class="user-feature-icon">&#127916;</div>
                    <div>Movies</div>
                </div>
                <div class="user-feature">
                    <div class="user-feature-icon">&#128250;</div>
                    <div>Series</div>
                </div>
                <div class="user-feature">
                    <div class="user-feature-icon">&#127911;</div>
                    <div>Music</div>
                </div>
                <div class="user-feature">
                    <div class="user-feature-icon">&#127760;</div>
                    <div>Originals</div>
                </div>
            </div> -->

        </div>
    </div>

</div>
