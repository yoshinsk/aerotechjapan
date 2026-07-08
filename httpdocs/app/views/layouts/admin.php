<?php
/**
 * httpdocs/app/views/layouts/admin.php
 * 管理画面の共通HTMLとサイドナビゲーションを描画します。
 */
$pageTitle = ($title ?? 'CMS') . ' | AERO TECH CMS';
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('css/site.css')) ?>">
</head>
<body class="admin-body <?= ($path ?? '') === '/admin/login' ? 'admin-login-body' : '' ?>">
<?php if (($path ?? '') !== '/admin/login'): ?>
<aside class="admin-sidebar">
    <a class="admin-logo" href="<?= e(url('/admin')) ?>">AERO TECH CMS</a>
    <nav>
        <a href="<?= e(url('/admin')) ?>">ダッシュボード</a>
        <a href="<?= e(url('/admin/products')) ?>">商品</a>
        <a href="<?= e(url('/admin/categories')) ?>">カテゴリ</a>
        <a href="<?= e(url('/admin/news')) ?>">ニュース</a>
        <a href="<?= e(url('/admin/pages')) ?>">固定ページ</a>
        <a href="<?= e(url('/admin/inquiries')) ?>">問い合わせ</a>
        <a href="<?= e(url('/admin/settings')) ?>">設定</a>
        <a href="<?= e(url('/')) ?>" target="_blank">公開サイト</a>
        <a href="<?= e(url('/admin/logout')) ?>">ログアウト</a>
    </nav>
</aside>
<?php endif; ?>
<main class="admin-main <?= ($path ?? '') === '/admin/login' ? 'login-main' : '' ?>">
    <?= $content ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset_url('js/site.js')) ?>"></script>
</body>
</html>
