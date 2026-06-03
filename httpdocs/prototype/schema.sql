-- ----------------------------------------------------------------------------
-- エアロテックジャパン 商品テーブル（MariaDB / 試作版）
--
-- 文字コードは utf8mb4 で統一（絵文字・機種依存文字も安全に保存可能）。
-- images / specs は構造が可変なため JSON カラムに格納する。
--
-- 利用手順:
--   mysql -u root -p < schema.sql
--   その後 config.php の USE_DB を true、DB_* を環境に合わせて設定する。
-- ----------------------------------------------------------------------------

CREATE DATABASE IF NOT EXISTS aerotech
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE aerotech;

CREATE TABLE IF NOT EXISTS products (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug        VARCHAR(190) NOT NULL,                      -- URL/ファイル識別子（例: GUN125_HILUX_GR_SPORT）
    name        VARCHAR(255) NOT NULL,                      -- 商品名
    model_year  VARCHAR(255) NOT NULL DEFAULT '',           -- 適合年式（例: (2021/10〜)）
    category    VARCHAR(255) NOT NULL DEFAULT '',           -- ブランド/カテゴリ（乱人, DIRect 等）
    image_dir   VARCHAR(255) NOT NULL DEFAULT '',           -- 画像フォルダ（例: garage-img/GUN125_HILUX_GR_SPORT）
    images      JSON         NULL,                          -- 表示画像ファイル名の配列
    images_large JSON        NULL,                          -- 拡大画像ファイル名の配列（images と同じ並び）
    specs       JSON         NULL,                          -- SPEC行 [{label, value}, ...]
    notes       TEXT         NULL,                          -- 補足説明
    sort_order  INT          NOT NULL DEFAULT 0,            -- 並び順
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- シードデータ（試作で使う1商品）
INSERT INTO products (slug, name, model_year, category, image_dir, images, specs, notes)
VALUES (
    'GUN125_HILUX_GR_SPORT',
    'GUN125 HILUX GR SPORT',
    '(2021/10〜)',
    '乱人 / RANDO',
    'garage-img/GUN125_HILUX_GR_SPORT',
    JSON_ARRAY(
        'GUN125_HILUX_GR_SPORT-01.jpg','GUN125_HILUX_GR_SPORT-02.jpg',
        'GUN125_HILUX_GR_SPORT-03.jpg','GUN125_HILUX_GR_SPORT-04.jpg',
        'GUN125_HILUX_GR_SPORT-05.jpg','GUN125_HILUX_GR_SPORT-06.jpg',
        'GUN125_HILUX_GR_SPORT-07.jpg','GUN125_HILUX_GR_SPORT-08.jpg',
        'GUN125_HILUX_GR_SPORT-09.jpg','GUN125_HILUX_GR_SPORT-10.jpg',
        'GUN125_HILUX_GR_SPORT-11.jpg','GUN125_HILUX_GR_SPORT-12.jpg',
        'GUN125_HILUX_GR_SPORT-13.jpg','GUN125_HILUX_GR_SPORT-14.jpg'
    ),
    JSON_ARRAY(
        JSON_OBJECT('label','DEMOCAR Grade','value','Z GR SPORT'),
        JSON_OBJECT('label','FOOTWORK','value','【RS★R】Best★i 車高調')
    ),
    'GUN125 HILUX GR SPORT 専用 BODY KIT。'
)
ON DUPLICATE KEY UPDATE name = VALUES(name);
