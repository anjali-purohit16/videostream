<?php

require dirname(__DIR__, 3) . '/config/app.php';
require ROOT_PATH . '/app/models/Database.php';

$pdo = Database::getInstance();

function column_exists(PDO $pdo, string $table, string $column): bool
{
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table) || !preg_match('/^[a-zA-Z0-9_]+$/', $column)) {
        throw new InvalidArgumentException('Invalid table or column name.');
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM {$table} LIKE " . $pdo->quote($column));
    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

function drop_column_if_exists(PDO $pdo, string $table, string $column): void
{
    if (column_exists($pdo, $table, $column)) {
        $pdo->exec("ALTER TABLE {$table} DROP COLUMN {$column}");
    }
}

try {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

    if (!column_exists($pdo, 'plans', 'currency')) {
        $pdo->exec("ALTER TABLE plans ADD COLUMN currency CHAR(3) NOT NULL DEFAULT 'USD' AFTER price");
    }

    drop_column_if_exists($pdo, 'payments', 'currency');

    $pdo->exec("CREATE TABLE IF NOT EXISTS video_categories (
        video_id INT UNSIGNED NOT NULL,
        category_id INT UNSIGNED NOT NULL,
        PRIMARY KEY (video_id, category_id),
        CONSTRAINT fk_vc_video
            FOREIGN KEY (video_id) REFERENCES videos(id)
            ON UPDATE CASCADE ON DELETE CASCADE,
        CONSTRAINT fk_vc_category
            FOREIGN KEY (category_id) REFERENCES categories(id)
            ON UPDATE CASCADE ON DELETE CASCADE
    ) ENGINE=InnoDB COMMENT='Many-to-many: videos categories'");

    $pdo->exec('INSERT IGNORE INTO video_categories (video_id, category_id)
        SELECT id, category_id FROM videos WHERE category_id IS NOT NULL');

    drop_column_if_exists($pdo, 'videos', 'category_ids');
    if (!column_exists($pdo, 'admin_messages', 'user_id')) {
        $pdo->exec('ALTER TABLE admin_messages ADD COLUMN user_id INT UNSIGNED DEFAULT NULL AFTER created_at');
    }
    if (!column_exists($pdo, 'admin_messages', 'plan_id')) {
        $pdo->exec('ALTER TABLE admin_messages ADD COLUMN plan_id INT UNSIGNED DEFAULT NULL AFTER user_id');
    }
    if (!column_exists($pdo, 'admin_messages', 'request_status')) {
        $pdo->exec("ALTER TABLE admin_messages ADD COLUMN request_status ENUM('none','pending','approved','rejected') NOT NULL DEFAULT 'none' AFTER plan_id");
    }
    if (!column_exists($pdo, 'admin_messages', 'request_type')) {
        $pdo->exec("ALTER TABLE admin_messages ADD COLUMN request_type VARCHAR(40) NOT NULL DEFAULT 'general' AFTER request_status");
    }
    if (!column_exists($pdo, 'admin_messages', 'payment_method')) {
        $pdo->exec("ALTER TABLE admin_messages ADD COLUMN payment_method ENUM('UPI','Card','Wallet','NetBanking','Paypal') NOT NULL DEFAULT 'Card' AFTER request_type");
    }
} finally {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
}

echo "VideoStream schema aligned to new 3NF database.\n";
