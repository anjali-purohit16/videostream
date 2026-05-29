<?php

define('APP_NAME', 'VideoStream');
define('APP_VERSION', '1.0.0');
define('ROOT_PATH', dirname(__DIR__));
define('SESSION_NAME', 'vs_session');

define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@example.com');
define('SMTP_PASS', 'your-smtp-app-password');
define('SMTP_FROM_EMAIL', 'your-email@example.com');
define('EMAIL_NOTIFICATIONS', '1');

define('RECAPTCHA_SITE_KEY', 'your-recaptcha-site-key');
define('RECAPTCHA_SECRET_KEY', 'your-recaptcha-secret-key');

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath = rtrim($scriptDir, '/');

define('BASE_URL', ($basePath === '' ? '' : $basePath) . '/');

ini_set('display_errors', '0');
error_reporting(E_ALL);

date_default_timezone_set('Asia/Kolkata');

return [
    'recaptcha_site_key' => RECAPTCHA_SITE_KEY,
    'recaptcha_secret_key' => RECAPTCHA_SECRET_KEY,
];
