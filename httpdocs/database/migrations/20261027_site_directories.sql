-- 販売代理店一覧と車種別サイトマップの管理用データを追加します。
CREATE TABLE IF NOT EXISTS site_directory_entries (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    directory_type VARCHAR(32) NOT NULL,
    group_name VARCHAR(190) NOT NULL DEFAULT '',
    title VARCHAR(255) NOT NULL,
    detail VARCHAR(255) NOT NULL DEFAULT '',
    product_slug VARCHAR(190) NOT NULL DEFAULT '',
    sort_order INT NOT NULL DEFAULT 100,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_site_directory_type (directory_type, is_active, group_name, sort_order),
    KEY idx_site_directory_product_slug (product_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
