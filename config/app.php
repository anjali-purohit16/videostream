<?php

define('APP_NAME', 'VideoStream');
define('APP_VERSION', '1.0.0');
define('ROOT_PATH', dirname(__DIR__));
define('SESSION_NAME', 'vs_session');


define('SMTP_HOST',       $_ENV['SMTP_HOST']       ?? 'smtp.gmail.com');
define('SMTP_PORT',       $_ENV['SMTP_PORT']       ?? 587);
define('SMTP_USER',       $_ENV['SMTP_USER']       ?? '');
define('SMTP_PASS',       $_ENV['SMTP_PASS']       ?? '');
define('SMTP_FROM_EMAIL', $_ENV['SMTP_FROM_EMAIL'] ?? '');
define('EMAIL_NOTIFICATIONS', $_ENV['EMAIL_NOTIFICATIONS'] ?? '1');

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath = rtrim($scriptDir, '/');

define('BASE_URL', ($basePath === '' ? '' : $basePath) . '/');

ini_set('display_errors', '1');
error_reporting(E_ALL);

date_default_timezone_set('Asia/Kolkata');




return [
    'recaptcha_site_key'   => $_ENV['RECAPTCHA_SITE_KEY']   ?? '6LfWXvcsAAAAAGmwFbfbdjwyK_U42IdAKrWG4bCT',
    'recaptcha_secret_key' => $_ENV['RECAPTCHA_SECRET_KEY'] ?? '6LfWXvcsAAAAAAWI_CMXO9zeDY0kWsckLyEnuSRR',
];