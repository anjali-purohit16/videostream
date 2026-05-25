<?php require_once ROOT_PATH . '/app/views/admin/_helpers.php'; ?>

<div class="user-auth-page">
    <div class="user-bg"></div>
    <div class="user-bg-tiles">
        <?php for ($i = 0; $i < 32; $i++): ?>
            <div class="user-bg-tile"></div>
        <?php endfor; ?>
    </div>
    <div class="user-auth-overlay"></div>

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
                <div class="user-card-kicker">Verify Email</div>
                <h1 class="user-card-title">Enter OTP</h1>
                <p class="user-card-subtitle">We sent a code to <?= h($email ?? '') ?></p>
            </div>

            <div class="register-progress">
                <div class="progress-step active"></div>
                <div class="progress-step active"></div>
                <div class="progress-step active"></div>
            </div>

            <?php if (!empty($flash)): ?>
                <div class="flash-alert <?= h($flash['type']) ?>">
                    <?= $flash['type'] === 'error' ? '&#9888; ' : '&#10003; ' ?>
                    <?= h($flash['message']) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>register?action=confirm" id="otpForm" novalidate>
                <div class="form-field">
                    <label class="vs-label user-field-label" for="reg_otp">6-digit OTP</label>
                    <div class="input-wrapper">
                        <span class="input-icon">#</span>
                        <input class="vs-input" type="text" id="reg_otp" name="otp"
                               inputmode="numeric" autocomplete="one-time-code"
                               maxlength="6" pattern="[0-9]{6}" placeholder="123456" required
                               style="letter-spacing:8px;text-align:center;font-weight:700;">
                        <div class="invalid-feedback">Please enter the 6-digit OTP.</div>
                    </div>
                </div>

                <button class="vs-btn vs-btn-red" type="submit" style="margin-top:20px;" id="otpSubmitBtn">
                    <span id="otpBtnText">Verify Account</span>
                    <span id="otpBtnSpinner" style="display:none;">Verifying...</span>
                </button>
            </form>

            <div class="user-auth-links" style="justify-content:center;margin-top:16px;">
                <a href="<?= BASE_URL ?>register">Use a different email</a>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('otpForm');
    var otp = document.getElementById('reg_otp');

    otp.addEventListener('input', function () {
        otp.value = otp.value.replace(/\D/g, '').slice(0, 6);
    });

    form.addEventListener('submit', function (e) {
        if (!/^\d{6}$/.test(otp.value)) {
            e.preventDefault();
            otp.focus();
            return;
        }

        document.getElementById('otpBtnText').style.display = 'none';
        document.getElementById('otpBtnSpinner').style.display = 'inline';
        document.getElementById('otpSubmitBtn').disabled = true;
    });
})();
</script>
