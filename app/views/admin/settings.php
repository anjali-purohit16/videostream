<?php require_once ROOT_PATH . '/app/views/admin/_helpers.php'; ?>
<?php
$tabs = [
    'general' => ['☷', 'General'],
    'email' => ['✉', 'Email / SMTP'],
    'storage' => ['▤', 'Storage'],
    'payments' => ['▣', 'Payments'],
    'security' => ['♢', 'Security'],
];
$tab = array_key_exists($tab, $tabs) ? $tab : 'security';
$get = fn(string $key, string $default = '') => $settings[$key] ?? $default;
?>

<div class="module-header">
    <div>
        <h1>Settings</h1>
        <p>Platform configuration</p>
    </div>
    <button class="btn btn-primary" form="settings-form" type="submit">✓ Save Changes</button>
</div>

<?php if ($flash): ?><div class="alert <?= h($flash['type']) ?>"><?= h($flash['message']) ?></div><?php endif; ?>

<div class="settings-grid">
    <aside class="panel settings-nav">
        <?php foreach ($tabs as $key => [$icon, $label]): ?>
            <a class="settings-nav-item <?= $tab === $key ? 'active' : '' ?>" href="<?= admin_url('settings', ['tab' => $key]) ?>">
                <span><?= $icon ?></span><?= h($label) ?>
            </a>
        <?php endforeach; ?>
    </aside>

    <form id="settings-form" class="panel settings-panel" method="post" action="<?= admin_url('settings', ['action' => 'save']) ?>">
        <input type="hidden" name="tab" value="<?= h($tab) ?>">

        <?php if ($tab === 'general'): ?>
            <label class="form-group"><span class="form-label">Platform Name</span><input class="form-control" name="platform_name" value="<?= h($get('platform_name', APP_NAME)) ?>"></label>
            <label class="form-group"><span class="form-label">Platform Tagline</span><input class="form-control" name="platform_tagline" value="<?= h($get('platform_tagline', 'Stream. Watch. Enjoy.')) ?>"></label>
            <label class="form-group"><span class="form-label">Support Email</span><input class="form-control" name="support_email" value="<?= h($get('support_email')) ?>"></label>
            <div class="toggle-row"><div class="toggle-info"><h4>Maintenance Mode</h4><p>Temporarily disable public access</p></div><label class="toggle"><input type="checkbox" name="maintenance_mode" <?= $get('maintenance_mode') === '1' ? 'checked' : '' ?>><span class="toggle-track"></span></label></div>
            <div class="toggle-row"><div class="toggle-info"><h4>User Registrations</h4><p>Allow new users to register</p></div><label class="toggle"><input type="checkbox" name="user_registrations" <?= $get('user_registrations', '1') === '1' ? 'checked' : '' ?>><span class="toggle-track"></span></label></div>
            <div class="toggle-row"><div class="toggle-info"><h4>Email Notifications</h4><p>Send automated emails on events</p></div><label class="toggle"><input type="checkbox" name="email_notifications" <?= $get('email_notifications', '1') === '1' ? 'checked' : '' ?>><span class="toggle-track"></span></label></div>
        <?php elseif ($tab === 'email'): ?>
            <label class="form-group"><span class="form-label">SMTP Host</span><input class="form-control" name="smtp_host" value="<?= h($get('smtp_host', 'smtp.gmail.com')) ?>"></label>
            <label class="form-group"><span class="form-label">SMTP Port</span><input class="form-control" name="smtp_port" value="<?= h($get('smtp_port', '587')) ?>"></label>
            <label class="form-group"><span class="form-label">SMTP User</span><input class="form-control" name="smtp_user" value="<?= h($get('smtp_user')) ?>"></label>
            <label class="form-group"><span class="form-label">SMTP Password</span><input class="form-control" type="password" name="smtp_pass" value="<?= h($get('smtp_pass')) ?>"></label>
            <button class="btn btn-ghost" type="button" data-toast="Test email queued from SMTP settings.">Send Test Email</button>
        <?php elseif ($tab === 'storage'): ?>
            <label class="form-group"><span class="form-label">Storage Provider</span>
                <select class="form-control" name="storage_provider">
                    <?php foreach (['Local Server', 'Amazon S3', 'Cloudinary'] as $provider): ?>
                        <option value="<?= h($provider) ?>" <?= $get('storage_provider', 'Local Server') === $provider ? 'selected' : '' ?>><?= h($provider) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="form-group"><span class="form-label">Max Upload MB</span><input class="form-control" type="number" name="max_upload_mb" value="<?= h($get('max_upload_mb', '2048')) ?>"></label>
            <label class="form-group"><span class="form-label">Allowed Formats</span><input class="form-control" name="allowed_formats" value="<?= h($get('allowed_formats', 'mp4,mov,avi,mkv,webm')) ?>"></label>
            <div class="storage-meter"><div><span>Disk Used</span><strong>386 GB / 1 TB</strong></div><span class="storage-fill"></span></div>
        <?php elseif ($tab === 'payments'): ?>
            <div class="toggle-row"><div class="toggle-info"><h4>Razorpay Enabled</h4><p>Allow Razorpay checkout</p></div><label class="toggle"><input type="checkbox" name="razorpay_enabled" <?= $get('razorpay_enabled', '1') === '1' ? 'checked' : '' ?>><span class="toggle-track"></span></label></div>
            <div class="toggle-row"><div class="toggle-info"><h4>Stripe Enabled</h4><p>Allow Stripe checkout</p></div><label class="toggle"><input type="checkbox" name="stripe_enabled" <?= $get('stripe_enabled') === '1' ? 'checked' : '' ?>><span class="toggle-track"></span></label></div>
            <div class="sec-divider"></div>
            <label class="form-group"><span class="form-label">Premium Plan Price (₹/month)</span><input class="form-control" name="premium_price" value="<?= h($get('premium_price', '499')) ?>"></label>
            <label class="form-group"><span class="form-label">Basic Plan Price (₹/month)</span><input class="form-control" name="basic_price" value="<?= h($get('basic_price', '199')) ?>"></label>
        <?php else: ?>
            <div class="toggle-row"><div class="toggle-info"><h4>Two-Factor Authentication</h4><p>Require 2FA for admin login</p></div><label class="toggle"><input type="checkbox" name="2fa_enabled" <?= $get('2fa_enabled', '1') === '1' ? 'checked' : '' ?>><span class="toggle-track"></span></label></div>
            <div class="toggle-row"><div class="toggle-info"><h4>Rate Limiting</h4><p>Limit login attempts to 5 per minute</p></div><label class="toggle"><input type="checkbox" name="rate_limiting" <?= $get('rate_limiting', '1') === '1' ? 'checked' : '' ?>><span class="toggle-track"></span></label></div>
            <div class="toggle-row"><div class="toggle-info"><h4>CSRF Protection</h4><p>Enable CSRF token validation on forms</p></div><label class="toggle"><input type="checkbox" name="csrf_protection" <?= $get('csrf_protection', '1') === '1' ? 'checked' : '' ?>><span class="toggle-track"></span></label></div>
            <div class="sec-divider"></div>
            <label class="form-group"><span class="form-label">Session Timeout (minutes)</span><input class="form-control" type="number" name="session_timeout" value="<?= h($get('session_timeout', '60')) ?>"></label>
        <?php endif; ?>
    </form>
</div>
