-- httpdocs/database/schema.sql
-- AERO TECH JAPAN CMSのMariaDBスキーマを作成します。

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(120) NOT NULL DEFAULT '',
    role VARCHAR(40) NOT NULL DEFAULT 'admin',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(190) NOT NULL,
    name_ja VARCHAR(190) NOT NULL,
    name_en VARCHAR(190) NOT NULL DEFAULT '',
    description_ja TEXT NULL,
    description_en TEXT NULL,
    logo_path VARCHAR(500) NOT NULL DEFAULT '',
    sort_order INT NOT NULL DEFAULT 100,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id INT UNSIGNED NULL,
    slug VARCHAR(190) NOT NULL,
    name_ja VARCHAR(255) NOT NULL,
    name_en VARCHAR(255) NOT NULL DEFAULT '',
    model_year_ja VARCHAR(255) NOT NULL DEFAULT '',
    model_year_en VARCHAR(255) NOT NULL DEFAULT '',
    summary_ja TEXT NULL,
    summary_en TEXT NULL,
    notes_ja TEXT NULL,
    notes_en TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'published',
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 100,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_slug (slug),
    KEY idx_products_category (category_id),
    KEY idx_products_status (status),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_images (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT UNSIGNED NOT NULL,
    path VARCHAR(500) NOT NULL,
    original_path VARCHAR(500) NOT NULL DEFAULT '',
    large_path VARCHAR(500) NOT NULL DEFAULT '',
    thumb_path VARCHAR(500) NOT NULL DEFAULT '',
    alt_ja VARCHAR(255) NOT NULL DEFAULT '',
    alt_en VARCHAR(255) NOT NULL DEFAULT '',
    source_type VARCHAR(20) NOT NULL DEFAULT 'legacy',
    sort_order INT NOT NULL DEFAULT 100,
    is_main TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_product_images_product (product_id),
    CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_specs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT UNSIGNED NOT NULL,
    label_ja VARCHAR(500) NOT NULL DEFAULT '',
    label_en VARCHAR(500) NOT NULL DEFAULT '',
    value_ja TEXT NULL,
    value_en TEXT NULL,
    sort_order INT NOT NULL DEFAULT 100,
    PRIMARY KEY (id),
    KEY idx_product_specs_product (product_id),
    CONSTRAINT fk_product_specs_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS news_posts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(190) NOT NULL,
    title_ja VARCHAR(255) NOT NULL,
    title_en VARCHAR(255) NOT NULL DEFAULT '',
    body_ja TEXT NULL,
    body_en TEXT NULL,
    image_path VARCHAR(500) NOT NULL DEFAULT '',
    published_at DATE NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_news_slug (slug),
    KEY idx_news_published (published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pages (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug VARCHAR(190) NOT NULL,
    title_ja VARCHAR(255) NOT NULL,
    title_en VARCHAR(255) NOT NULL DEFAULT '',
    body_ja MEDIUMTEXT NULL,
    body_en MEDIUMTEXT NULL,
    meta_description_ja TEXT NULL,
    meta_description_en TEXT NULL,
    sort_order INT NOT NULL DEFAULT 100,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pages_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inquiries (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT UNSIGNED NULL,
    locale VARCHAR(8) NOT NULL DEFAULT 'ja',
    name VARCHAR(190) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(80) NOT NULL DEFAULT '',
    company VARCHAR(190) NOT NULL DEFAULT '',
    country VARCHAR(120) NOT NULL DEFAULT '',
    message TEXT NOT NULL,
    ip_address VARCHAR(64) NOT NULL DEFAULT '',
    user_agent VARCHAR(500) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_inquiries_product (product_id),
    KEY idx_inquiries_created (created_at),
    CONSTRAINT fk_inquiries_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(190) NOT NULL,
    setting_value TEXT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS redirects (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    old_path VARCHAR(500) NOT NULL,
    new_path VARCHAR(500) NOT NULL,
    http_status SMALLINT NOT NULL DEFAULT 301,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_redirects_old_path (old_path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
