<?php
/**
 * httpdocs/app/views/home.php
 * トップページとしてヒーロー、カテゴリ、注目商品、ニュースを描画します。
 */
$heroProduct = $featuredProducts[0] ?? $latestProducts[0] ?? null;
$heroImage = $heroProduct['main_image'] ?? 'img/news-img/newsimg20240809_1.jpg';
?>
<section class="hero">
    <img class="hero-bg" src="<?= e(media_url($heroImage)) ?>" alt="">
    <div class="hero-content">
        <p class="eyebrow">AERO PARTS / BODY KIT / OEM</p>
        <h1><?= e(localized($home ?? [], 'title', 'AERO TECH JAPAN')) ?></h1>
        <p><?= e(excerpt(localized($home ?? [], 'body', t('エアロパーツ、ボディキット、OEM製作まで。現場で作り込む日本発のカスタムパーツブランドです。', 'Japanese aero parts, body kits, and OEM production built for real vehicles.')), 180)) ?></p>
        <div class="actions">
            <a class="button" href="<?= e(url('/products')) ?>"><?= e(t('製品を見る', 'View products')) ?></a>
            <a class="button secondary" href="<?= e(url('/contact')) ?>"><?= e(t('問い合わせる', 'Contact us')) ?></a>
        </div>
    </div>
</section>

<section class="section container-fluid">
    <div class="section-head row align-items-end g-3">
        <div>
            <p class="eyebrow">Brands</p>
            <h2><?= e(t('カテゴリ', 'Categories')) ?></h2>
        </div>
        <a class="button secondary" href="<?= e(url('/products')) ?>"><?= e(t('すべて表示', 'All products')) ?></a>
    </div>
    <div class="category-strip row row-cols-2 row-cols-lg-4 g-2">
        <?php foreach ($categories as $category): ?>
            <div class="col">
                <a class="category-chip h-100" href="<?= e(url('/category/' . $category['slug'])) ?>">
                    <strong><?= e(localized($category, 'name')) ?></strong>
                    <span><?= e(excerpt(localized($category, 'description'), 55)) ?></span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="section container-fluid">
    <div class="section-head">
        <div>
            <p class="eyebrow">Products</p>
            <h2><?= e(t('注目製品', 'Featured products')) ?></h2>
        </div>
    </div>
    <div class="grid row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
        <?php foreach (($featuredProducts ?: $latestProducts) as $product): ?>
            <div class="col"><?php require APP_ROOT . '/views/partials/product_card.php'; ?></div>
        <?php endforeach; ?>
    </div>
</section>

<section class="section container-fluid">
    <div class="section-head">
        <div>
            <p class="eyebrow">News</p>
            <h2><?= e(t('最新情報', 'Latest news')) ?></h2>
        </div>
        <a class="button secondary" href="<?= e(url('/news')) ?>"><?= e(t('一覧へ', 'View all')) ?></a>
    </div>
    <div class="grid row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
        <?php foreach ($newsPosts as $post): ?>
            <div class="col">
                <article class="news-card card h-100">
                    <?php if (!empty($post['image_path']) && is_image_path($post['image_path'])): ?>
                        <img class="card-img-top" src="<?= e(media_url($post['image_path'])) ?>" alt="<?= e(localized($post, 'title')) ?>" loading="lazy">
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="card-meta"><?= e(date('Y.m.d', strtotime($post['published_at']))) ?></div>
                        <h3 class="card-title"><a href="<?= e(url('/news/' . $post['slug'])) ?>"><?= e(localized($post, 'title')) ?></a></h3>
                        <p class="muted"><?= e(excerpt(localized($post, 'body'), 100)) ?></p>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
</section>
