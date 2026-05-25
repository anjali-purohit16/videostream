-- Align an existing VideoStream database to the 3NF schema used by the app.
-- This preserves existing rows and removes legacy duplicate columns.

USE videostream;

SET FOREIGN_KEY_CHECKS = 0;

-- Plans own currency in the new schema.
-- If your MySQL/MariaDB version does not support DROP COLUMN IF EXISTS,
-- run align-new-videostream-schema.php instead; it checks columns first.

-- ALTER TABLE plans ADD COLUMN currency CHAR(3) NOT NULL DEFAULT 'USD' AFTER price;
-- ALTER TABLE payments DROP COLUMN currency;

-- The new schema uses video_categories, not videos.category_ids.
CREATE TABLE IF NOT EXISTS video_categories (
    video_id    INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (video_id, category_id),
    CONSTRAINT fk_vc_video
        FOREIGN KEY (video_id)    REFERENCES videos(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_vc_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Many-to-many: videos ↔ categories';

INSERT IGNORE INTO video_categories (video_id, category_id)
SELECT id, category_id
FROM videos
WHERE category_id IS NOT NULL;

-- ALTER TABLE videos DROP COLUMN category_ids;

-- admin_messages in the pasted schema is a simple inbox/contact table.
-- ALTER TABLE admin_messages DROP COLUMN user_id;
-- ALTER TABLE admin_messages DROP COLUMN plan_id;
-- ALTER TABLE admin_messages DROP COLUMN request_status;
-- ALTER TABLE admin_messages DROP COLUMN request_type;

SET FOREIGN_KEY_CHECKS = 1;
