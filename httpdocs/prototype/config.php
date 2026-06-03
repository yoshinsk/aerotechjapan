<?php
/**
 * 試作版（プロトタイプ）共通設定
 *
 * このファイルだけ書き換えれば、データソースを「JSONファイル」と
 * 「MariaDB」のどちらかに切り替えられます。
 *
 * - USE_DB = false : data/products.json を読み書き（DB不要ですぐ確認できる）
 * - USE_DB = true  : 下記 DB_* の MariaDB に接続（本番想定）
 */

// MariaDB を使うかどうか。まずは false のまま動作確認できます。
define('USE_DB', false);

// MariaDB 接続情報（USE_DB = true のときに使用）
define('DB_HOST', 'localhost');
define('DB_NAME', 'aerotech');
define('DB_USER', 'aerotech');
define('DB_PASS', 'CHANGE_ME');
define('DB_CHARSET', 'utf8mb4');

// 既存サイトの画像フォルダ（garage-img）への相対パス。
// prototype/ から1つ上がると httpdocs/ なので "../" 。
define('SITE_BASE', '../');

// 簡易管理画面のログインパスワード（試作用。本番では必ず変更し、
// できればセッション＋ハッシュ化された認証に置き換えること）
define('ADMIN_PASSWORD', 'aerotech-demo');
