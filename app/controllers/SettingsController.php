<?php

class SettingsController extends AdminController
{
    public function index(): void
    {
        $this->adminView('settings', [
            'title' => 'Settings',
            'section' => 'settings',
            'settings' => (new SettingsModel())->getAll(),
            'tab' => $_GET['tab'] ?? 'security',
        ]);
    }

    public function save(): void
    {
        $allowed = [
            'platform_name', 'platform_tagline', 'support_email', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass',
            'storage_provider', 'max_upload_mb', 'allowed_formats', 'razorpay_enabled',
            'stripe_enabled', 'user_registrations', 'email_notifications', '2fa_enabled', 'rate_limiting', 'csrf_protection',
            'session_timeout', 'maintenance_mode',
        ];

        $data = [];
        foreach ($allowed as $key) {
            if (isset($_POST[$key])) {
                $data[$key] = is_array($_POST[$key]) ? '' : trim((string)$_POST[$key]);
            }
        }

        foreach (['razorpay_enabled', 'stripe_enabled', 'user_registrations', 'email_notifications', '2fa_enabled', 'rate_limiting', 'csrf_protection', 'maintenance_mode'] as $toggle) {
            $data[$toggle] = isset($_POST[$toggle]) ? '1' : '0';
        }

        (new SettingsModel())->saveMany($data);
        $this->logAdminAction('Settings Updated', 'Settings', 'Saved ' . ($_POST['tab'] ?? 'settings') . ' settings');
        $this->flash('success', 'Settings saved.');
        $this->redirect(BASE_URL . '?module=admin&page=settings&tab=' . urlencode($_POST['tab'] ?? 'security'));
    }
}
