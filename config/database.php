
<?php
define('DB_HOST',    $_ENV['DB_HOST']    ?? 'localhost');
define('DB_PORT',    $_ENV['DB_PORT']    ?? '3306');
define('DB_NAME',    $_ENV['DB_NAME']    ?? 'videostream');
define('DB_USER',    $_ENV['DB_USER']    ?? 'root');
define('DB_PASS',    $_ENV['DB_PASS']    ?? 'root');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');