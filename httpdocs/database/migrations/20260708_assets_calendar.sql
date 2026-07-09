-- httpdocs/database/migrations/20260708_assets_calendar.sql
-- 商品画像派生、ブランドロゴ、価格表PDF、営業日例外設定を追加します。

ALTER TABLE categories
    ADD COLUMN IF NOT EXISTS logo_path VARCHAR(500) NOT NULL DEFAULT '' AFTER description_en;

ALTER TABLE product_images
    ADD COLUMN IF NOT EXISTS original_path VARCHAR(500) NOT NULL DEFAULT '' AFTER path,
    ADD COLUMN IF NOT EXISTS large_path VARCHAR(500) NOT NULL DEFAULT '' AFTER original_path,
    ADD COLUMN IF NOT EXISTS thumb_path VARCHAR(500) NOT NULL DEFAULT '' AFTER large_path;

UPDATE product_images
SET large_path = path
WHERE large_path = '';

UPDATE product_images
SET thumb_path = REPLACE(path, '-large.jpg', '-thumb.jpg')
WHERE thumb_path = ''
  AND path LIKE 'uploads/%-large.jpg';

CREATE TABLE IF NOT EXISTS price_lists (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id INT UNSIGNED NULL,
    title_ja VARCHAR(255) NOT NULL,
    title_en VARCHAR(255) NOT NULL DEFAULT '',
    pdf_path VARCHAR(500) NOT NULL,
    sort_order INT NOT NULL DEFAULT 100,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    published_at DATE NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_price_lists_category (category_id),
    KEY idx_price_lists_active (is_active),
    CONSTRAINT fk_price_lists_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS business_day_exceptions (
    business_date DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'closed',
    note_ja VARCHAR(255) NOT NULL DEFAULT '',
    note_en VARCHAR(255) NOT NULL DEFAULT '',
    event_name_ja VARCHAR(255) NOT NULL DEFAULT '',
    event_name_en VARCHAR(255) NOT NULL DEFAULT '',
    event_url VARCHAR(500) NOT NULL DEFAULT '',
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (business_date),
    KEY idx_business_day_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
