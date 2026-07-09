<?php
/**
 * httpdocs/app/views/layouts/site.php
 * 公開サイトの共通HTML、ヘッダー、ナビゲーション、フッターを描画します。
 */
$pageTitle = ($title ?? config_value('app.name')) . ' | ' . config_value('app.name');
?>
<!doctype html>
<html lang="<?= e(current_locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e(t('エアロテックジャパン公式サイト。エアロパーツ、ボディキット、OEM製作、イベント情報を掲載しています。', 'Official website of AERO TECH JAPAN. Body kits, aero parts, OEM production, and news.')) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset_url('css/public.css')) ?>">
</head>
<body class="site-body">
<header class="site-header navbar navbar-expand-lg">
    <div class="container-fluid site-header-inner">
        <a class="navbar-brand brand" href="<?= e(url('/')) ?>" aria-label="AERO TECH JAPAN">
            <span class="brand-mark">ATJ</span>
            <span class="brand-text">AERO TECH JAPAN</span>
        </a>
        <button class="navbar-toggler nav-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#siteNav" aria-controls="siteNav" aria-expanded="false" aria-label="<?= e(t('メニュー', 'Menu')) ?>">
            <span></span><span></span><span></span>
        </button>
        <nav id="siteNav" class="collapse navbar-collapse site-nav" data-nav>
            <div class="navbar-nav ms-lg-auto">
                <a class="nav-link" href="<?= e(url('/products')) ?>"><?= e(t('製品', 'Products')) ?></a>
                <a class="nav-link" href="<?= e(url('/price-lists')) ?>"><?= e(t('価格表リスト', 'Price Lists')) ?></a>
                <a class="nav-link" href="<?= e(url('/news')) ?>"><?= e(t('ニュース', 'News')) ?></a>
                <a class="nav-link" href="<?= e(url('/page/about')) ?>"><?= e(t('会社情報', 'About')) ?></a>
                <a class="nav-link" href="<?= e(url('/contact')) ?>"><?= e(t('お問い合わせ', 'Contact')) ?></a>
                <a class="nav-link lang-link" href="?lang=<?= current_locale() === 'en' ? 'ja' : 'en' ?>"><?= current_locale() === 'en' ? 'JP' : 'EN' ?></a>
            </div>
        </nav>
    </div>
</header>

<main>
    <?= $content ?>
</main>

<footer class="site-footer">
    <div>
        <strong>AERO TECH JAPAN CO., LTD.</strong>
        <span><?= e(t('大阪府高槻市下田部町2丁目54番1号', '2-54-1 Shimotanabe-cho, Takatsuki, Osaka, Japan')) ?></span>
    </div>
    <nav>
        <a href="<?= e(url('/pages')) ?>"><?= e(t('サイトマップ', 'Sitemap')) ?></a>
        <a href="<?= e(url('/page/privacy')) ?>"><?= e(t('プライバシーポリシー', 'Privacy Policy')) ?></a>
        <a href="<?= e(url('/admin')) ?>">CMS</a>
    </nav>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset_url('js/site.js')) ?>"></script>
</body>
</html>
