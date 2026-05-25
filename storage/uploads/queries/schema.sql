CREATE DATABASE IF NOT EXISTS videostream
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE videostream;

SET FOREIGN_KEY_CHECKS = 0;
DROP VIEW  IF EXISTS v_subscription_breakdown;
DROP VIEW  IF EXISTS v_revenue_monthly;
DROP VIEW  IF EXISTS v_dashboard_stats;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS admin_messages;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS watch_history;
DROP TABLE IF EXISTS wishlists;
DROP TABLE IF EXISTS video_categories;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS subscriptions;
DROP TABLE IF EXISTS videos;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS plans;
DROP TABLE IF EXISTS admins;
DROP TABLE IF EXISTS settings;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Admin accounts';

CREATE TABLE plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    currency CHAR(3) NOT NULL DEFAULT 'USD',
    duration_days SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Subscription plans';

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    icon VARCHAR(40) DEFAULT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Video categories';

CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(80) NOT NULL UNIQUE,
    val TEXT DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Platform configuration key-value store';

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    status ENUM('active','suspended','banned') NOT NULL DEFAULT 'active',
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_seen DATETIME DEFAULT NULL,
    CONSTRAINT fk_users_plan FOREIGN KEY (plan_id) REFERENCES plans(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='Registered users';

CREATE TABLE videos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    category_id INT UNSIGNED NOT NULL,
    access_level ENUM('free','basic','premium') NOT NULL DEFAULT 'free',
    duration_sec INT UNSIGNED NOT NULL DEFAULT 0,
    thumbnail VARCHAR(255) DEFAULT NULL,
    file_path VARCHAR(255) DEFAULT NULL,
    views BIGINT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('published','processing','draft') NOT NULL DEFAULT 'draft',
    uploaded_by INT UNSIGNED DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_videos_category FOREIGN KEY (category_id) REFERENCES categories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_videos_admin FOREIGN KEY (uploaded_by) REFERENCES admins(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Video content';

CREATE TABLE video_categories (
    video_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (video_id, category_id),
    CONSTRAINT fk_vc_video FOREIGN KEY (video_id) REFERENCES videos(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_vc_category FOREIGN KEY (category_id) REFERENCES categories(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Many-to-many: videos categories';

CREATE TABLE wishlists (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    video_id INT UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wishlist (user_id, video_id),
    CONSTRAINT fk_wl_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_wl_video FOREIGN KEY (video_id) REFERENCES videos(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='User watchlist / bookmarks';

CREATE TABLE watch_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    video_id INT UNSIGNED NOT NULL,
    progress_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
    watched_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_watch (user_id, video_id),
    CONSTRAINT fk_wh_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_wh_video FOREIGN KEY (video_id) REFERENCES videos(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_progress CHECK (progress_percent BETWEEN 0 AND 100)
) ENGINE=InnoDB COMMENT='Per-user video watch progress';

CREATE TABLE subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    starts_at DATE NOT NULL,
    expires_at DATE NOT NULL,
    status ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sub_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_sub_plan FOREIGN KEY (plan_id) REFERENCES plans(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='User subscription periods';

CREATE TABLE payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    txn_id VARCHAR(40) NOT NULL UNIQUE,
    user_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    method ENUM('UPI','Card','Wallet','NetBanking','Paypal') NOT NULL DEFAULT 'Card',
    status ENUM('success','failed','pending','refunded') NOT NULL DEFAULT 'pending',
    paid_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pay_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_pay_plan FOREIGN KEY (plan_id) REFERENCES plans(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB COMMENT='Payment transactions';

CREATE TABLE reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    video_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment TEXT DEFAULT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rev_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_rev_video FOREIGN KEY (video_id) REFERENCES videos(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB COMMENT='User reviews and ratings';

CREATE TABLE reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_code VARCHAR(20) NOT NULL UNIQUE,
    type ENUM('Video','Comment','User') NOT NULL,
    reporter_id INT UNSIGNED NOT NULL,
    ref_video_id INT UNSIGNED DEFAULT NULL,
    ref_user_id INT UNSIGNED DEFAULT NULL,
    content_ref VARCHAR(200) NOT NULL,
    reason VARCHAR(200) NOT NULL,
    status ENUM('pending','resolved','dismissed') NOT NULL DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rep_reporter FOREIGN KEY (reporter_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_rep_video FOREIGN KEY (ref_video_id) REFERENCES videos(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_rep_user FOREIGN KEY (ref_user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Content and user reports';

CREATE TABLE activity_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED DEFAULT NULL,
    actor VARCHAR(100) NOT NULL,
    action VARCHAR(150) NOT NULL,
    module VARCHAR(60) NOT NULL,
    details VARCHAR(255) DEFAULT NULL,
    icon_color ENUM('blue','green','red','amber') NOT NULL DEFAULT 'blue',
    ip_address VARCHAR(45) NOT NULL DEFAULT '127.0.0.1',
    logged_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_admin FOREIGN KEY (admin_id) REFERENCES admins(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Admin audit trail';

CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    target_role ENUM('admin','user') NOT NULL DEFAULT 'admin',
    target_user_id INT UNSIGNED DEFAULT NULL,
    target_admin_id INT UNSIGNED DEFAULT NULL,
    title VARCHAR(150) NOT NULL,
    body VARCHAR(255) NOT NULL,
    link_url VARCHAR(255) DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (target_user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_notif_admin FOREIGN KEY (target_admin_id) REFERENCES admins(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='In-app notifications';

CREATE TABLE admin_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_name VARCHAR(120) NOT NULL,
    sender_email VARCHAR(150) DEFAULT NULL,
    subject VARCHAR(180) NOT NULL,
    body TEXT DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    user_id INT UNSIGNED DEFAULT NULL,
    plan_id INT UNSIGNED DEFAULT NULL,
    payment_id INT UNSIGNED DEFAULT NULL,
    request_status ENUM('none','pending','approved','rejected') NOT NULL DEFAULT 'none',
    request_type VARCHAR(40) NOT NULL DEFAULT 'general',
    payment_method ENUM('UPI','Card','Wallet','NetBanking','Paypal') NOT NULL DEFAULT 'Card',
    CONSTRAINT fk_msg_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_msg_plan FOREIGN KEY (plan_id) REFERENCES plans(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_msg_payment FOREIGN KEY (payment_id) REFERENCES payments(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Contact messages and plan requests from users to admin';

INSERT INTO admins (name, email, password) VALUES
('Admin', 'purohitanjali098@gmail.com',
 '$2y$10$Vt75jIgSc1M97WKuqk9gXekHpvQzctu6rAGQyjKehRTLNti1b0rqa');

INSERT INTO plans (name, price, currency, duration_days) VALUES
('Free',    0.00,  'USD', 30),
('Basic',   9.00,  'USD', 30),
('Premium', 19.00, 'USD', 30);

INSERT INTO settings (setting_key, val) VALUES
('platform_name', 'VideoStream'),
('platform_tagline', 'Stream. Watch. Enjoy.'),
('support_email', 'purohitanjali098@gmail.com'),
('maintenance_mode', '0'),
('user_registrations', '1'),
('email_notifications', '1'),
('max_upload_mb', '2048'),
('allowed_formats', 'mp4,mov,avi,mkv,webm'),
('smtp_host', 'smtp.gmail.com'),
('smtp_port', '587'),
('smtp_user', 'purohitanjali098@gmail.com'),
('smtp_pass', '123456789'),
('storage_provider', 'Local Server'),
('razorpay_enabled', '0'),
('stripe_enabled', '0'),
('premium_price', '19'),
('basic_price', '9'),
('2fa_enabled', '0'),
('rate_limiting', '1'),
('csrf_protection', '1'),
('session_timeout', '60');

CREATE OR REPLACE VIEW v_dashboard_stats AS
SELECT
    (SELECT COUNT(*) FROM videos) AS total_videos,
    (SELECT COUNT(*) FROM videos WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) AS videos_this_week,
    (SELECT COUNT(*) FROM users WHERE status = 'active') AS active_users,
    (SELECT COUNT(*) FROM users WHERE joined_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)) AS users_this_month,
    (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'success' AND YEAR(paid_at) = YEAR(CURDATE()) AND MONTH(paid_at) = MONTH(CURDATE())) AS monthly_revenue,
    (
        SELECT CASE
            WHEN prev.prev_rev = 0 THEN 0
            ELSE ROUND(((curr.curr_rev - prev.prev_rev) / prev.prev_rev) * 100, 1)
        END
        FROM
          (SELECT COALESCE(SUM(amount),0) AS curr_rev FROM payments WHERE status='success' AND YEAR(paid_at)=YEAR(CURDATE()) AND MONTH(paid_at)=MONTH(CURDATE())) curr,
          (SELECT COALESCE(SUM(amount),0) AS prev_rev FROM payments WHERE status='success' AND YEAR(paid_at)=YEAR(DATE_SUB(CURDATE(),INTERVAL 1 MONTH)) AND MONTH(paid_at)=MONTH(DATE_SUB(CURDATE(),INTERVAL 1 MONTH))) prev
    ) AS revenue_growth_pct,
    (SELECT COALESCE(SUM(views), 0) FROM videos) AS total_views;

CREATE OR REPLACE VIEW v_revenue_monthly AS
SELECT
    DATE_FORMAT(pay.paid_at, '%b') AS month_label,
    MONTH(pay.paid_at) AS month_num,
    YEAR(pay.paid_at) AS yr,
    SUM(pay.amount) AS revenue,
    p.currency
FROM payments pay
JOIN plans p ON p.id = pay.plan_id
WHERE pay.status = 'success'
  AND pay.paid_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
GROUP BY YEAR(pay.paid_at), MONTH(pay.paid_at), DATE_FORMAT(pay.paid_at,'%b'), p.currency
ORDER BY yr, month_num;

CREATE OR REPLACE VIEW v_subscription_breakdown AS
SELECT
    p.name AS plan_name,
    COUNT(s.id) AS subscriber_count,
    ROUND(COUNT(s.id) * 100.0 / NULLIF((SELECT COUNT(*) FROM subscriptions WHERE status='active'),0), 1) AS pct
FROM plans p
LEFT JOIN subscriptions s ON s.plan_id = p.id AND s.status = 'active'
GROUP BY p.id, p.name
ORDER BY FIELD(p.name, 'Premium', 'Basic', 'Free');
