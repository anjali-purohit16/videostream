<?php require_once ROOT_PATH . '/app/views/admin/_helpers.php'; ?>

<div class="user-auth-page">
    <div class="user-bg"></div>
    <div class="user-bg-tiles">
        <?php for ($i = 0; $i < 32; $i++): ?>
            <div class="user-bg-tile"></div>
        <?php endfor; ?>
    </div>
    <div class="user-auth-overlay"></div>

    <!-- Top bar — logo only -->
    <div class="user-topbar">
        <a class="user-logo" href="<?= BASE_URL ?>">
            <img src="<?= BASE_URL ?>assets/images/logo1.png"
                 alt="<?= htmlspecialchars(APP_NAME) ?> Logo"
                 style="width:100%;height:auto;max-width:200px;object-fit:contain;display:block;margin:0 auto;">
        </a>
    </div>

    <div class="user-card-container">
        <div class="user-auth-card">

            <div class="user-card-header">
                <div class="user-card-kicker">Join <?= h(APP_NAME) ?></div>
                <h1 class="user-card-title">Create your account</h1>
                <p class="user-card-subtitle">Start streaming in seconds</p>
            </div>

            <div class="register-progress">
                <div class="progress-step active"></div>
                <div class="progress-step active"></div>
                <div class="progress-step"></div>
            </div>

            <?php if (!empty($flash)): ?>
                <div class="flash-alert <?= h($flash['type']) ?>">
                    <?= $flash['type'] === 'error' ? '&#9888; ' : '&#10003; ' ?>
                    <?= h($flash['message']) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>register?action=save"
                  id="registerForm" novalidate>

                <!-- Name -->
                <div class="form-field">
                    <label class="vs-label user-field-label" for="reg_name">Full Name</label>
                    <div class="input-wrapper">
                        <span class="input-icon">&#128100;</span>
                        <input class="vs-input" type="text" id="reg_name" name="name"
                               placeholder="Your full name" autocomplete="name" required>
                        <div class="invalid-feedback">Please enter your full name.</div>
                    </div>
                </div>

                <!-- Email -->
                <div class="form-field">
                    <label class="vs-label user-field-label" for="reg_email">Email Address</label>
                    <div class="input-wrapper">
                        <span class="input-icon">@</span>
                        <input class="vs-input" type="email" id="reg_email" name="email"
                               placeholder="you@example.com" autocomplete="email" required>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>
                    <div id="emailHint" style="font-size:12px;margin-top:4px;min-height:16px;"></div>
                </div>

                <!-- Password -->
                <div class="form-field" style="margin-bottom:6px;">
                    <label class="vs-label user-field-label" for="reg_password">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">#</span>
                        <input class="vs-input" type="password" id="reg_password" name="password"
                               placeholder="Min. 8 characters" autocomplete="new-password"
                               minlength="8" required>
                        <button type="button" class="toggle-pw" data-target="reg_password"
                                aria-label="Show/hide password">&#128065;</button>
                        <div class="invalid-feedback">Password must be at least 8 characters.</div>
                    </div>
                </div>

                <!-- Strength meter -->
                <div class="strength-meter">
                    <div class="strength-bar" id="sb1"></div>
                    <div class="strength-bar" id="sb2"></div>
                    <div class="strength-bar" id="sb3"></div>
                    <div class="strength-bar" id="sb4"></div>
                </div>
                <div class="strength-text" id="strengthText"></div>

                <!-- Rules checklist -->
                <ul id="pwRules" style="list-style:none;padding:0;margin:8px 0 16px;font-size:12px;color:#888;">
                    <li id="rule-len"  >&#9675; At least 8 characters</li>
                    <li id="rule-upper">&#9675; One uppercase letter (A–Z)</li>
                    <li id="rule-lower">&#9675; One lowercase letter (a–z)</li>
                    <li id="rule-digit">&#9675; One number (0–9)</li>
                    <li id="rule-spec" >&#9675; One special character (@, #, !, …)</li>
                </ul>

                <!-- reCAPTCHA -->
                <div class="mb-3">
                    <div class="g-recaptcha"
                         data-sitekey="6LfWXvcsAAAAAGmwFbfbdjwyK_U42IdAKrWG4bCT"></div>
                </div>

                <button class="vs-btn vs-btn-red" type="submit" style="margin-top:20px;" id="regSubmitBtn">
                    <span id="regBtnText">Create Account</span>
                    <span id="regBtnSpinner" style="display:none;">Creating account…</span>
                </button>

            </form>

            <div class="user-auth-links" style="justify-content:center;margin-top:16px;">
                <a href="<?= BASE_URL ?>login">Already have an account? <strong>Sign in</strong></a>
            </div>

        </div>
    </div>
</div>

<!-- reCAPTCHA script -->
<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<script>
(function () {
    /* ── Password strength ── */
    var pw   = document.getElementById('reg_password');
    var bars = [sb1, sb2, sb3, sb4];
    var txt  = document.getElementById('strengthText');

    var rules = {
        'rule-len':   function (v) { return v.length >= 8; },
        'rule-upper': function (v) { return /[A-Z]/.test(v); },
        'rule-lower': function (v) { return /[a-z]/.test(v); },
        'rule-digit': function (v) { return /[0-9]/.test(v); },
        'rule-spec':  function (v) { return /[\W_]/.test(v); },
    };

    var levels = [
        { label: '',       color: '' },
        { label: 'Weak',   color: '#e74c3c' },
        { label: 'Fair',   color: '#f39c12' },
        { label: 'Good',   color: '#3498db' },
        { label: 'Strong', color: '#2ecc71' },
    ];

    pw.addEventListener('input', function () {
        var val   = pw.value;
        var score = 0;

        Object.keys(rules).forEach(function (id) {
            var ok = rules[id](val);
            var el = document.getElementById(id);
            el.innerHTML = (ok ? '&#10003; ' : '&#9675; ') + el.textContent.slice(2);
            el.style.color = ok ? '#2ecc71' : '#888';
            if (ok) score++;
        });

        var lvl = levels[Math.min(score, 4)];
        bars.forEach(function (b, i) {
            b.style.background = i < score ? lvl.color : '';
        });
        txt.textContent = val.length ? lvl.label : '';
        txt.style.color = lvl.color;
    });

    /* ── Email domain hint ── */
    var emailInput = document.getElementById('reg_email');
    var emailHint  = document.getElementById('emailHint');
    var freeHosts  = ['gmail.com','yahoo.com','outlook.com','hotmail.com',
                      'icloud.com','protonmail.com','live.com','ymail.com'];

    emailInput.addEventListener('blur', function () {
        var domain = (emailInput.value.split('@')[1] || '').toLowerCase();
        if (!domain) { emailHint.textContent = ''; return; }
        if (freeHosts.indexOf(domain) !== -1) {
            emailHint.innerHTML = '<span style="color:#2ecc71;">&#10003; Looks good!</span>';
        } else {
            emailHint.innerHTML = '<span style="color:#f39c12;">&#9888; Make sure this is a real, working email.</span>';
        }
    });
    emailInput.addEventListener('focus', function () { emailHint.textContent = ''; });

    /* ── Submit guard ── */
    document.getElementById('registerForm').addEventListener('submit', function (e) {
        var val   = pw.value;
        var score = Object.values(rules).filter(function (fn) { return fn(val); }).length;

        if (score < 5) {
            e.preventDefault();
            pw.focus();
            txt.textContent = 'Please meet all password requirements.';
            txt.style.color = '#e74c3c';
            return;
        }

        document.getElementById('regBtnText').style.display    = 'none';
        document.getElementById('regBtnSpinner').style.display = 'inline';
        document.getElementById('regSubmitBtn').disabled       = true;
    });
})();
</script>