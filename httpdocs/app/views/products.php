<?php
/**
 * httpdocs/app/views/products.php
 * 製品一覧、カテゴリ絞り込み、キーワード検索結果を描画します。
 */
?>
<section class="section narrow container">
    <p class="eyebrow">Products</p>
    <h1><?= e(($category ?? null) ? localized($category, 'name') : t('製品一覧', 'Products')) ?></h1>
    <?php if (!empty($category)): ?>
        <p class="muted"><?= e(localized($category, 'description')) ?></p>
    <?php endif; ?>
    <form class="form row g-2" action="<?= e(url('/products')) ?>" method="get">
        <div class="field">
            <label><?= e(t('キーワード検索', 'Keyword search')) ?></label>
            <input class="form-control" type="search" name="q" value="<?= e($keyword ?? '') ?>" placeholder="<?= e(t('車種・型式・ブランド', 'Model, chassis, brand')) ?>">
        </div>
    </form>
</section>

<section class="section container-fluid">
    <div class="category-strip row row-cols-2 row-cols-lg-4 g-2">
        <?php foreach ($categories as $item): ?>
            <div class="col">
                <a class="category-chip h-100" href="<?= e(url('/category/' . $item['slug'])) ?>">
                    <strong><?= e(localized($item, 'name')) ?></strong>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="section container-fluid">
    <?php if (!$products): ?>
        <p class="muted"><?= e(t('該当する製品がありません。', 'No products found.')) ?></p>
    <?php else: ?>
        <div class="grid row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
            <?php foreach ($products as $product): ?>
                <div class="col"><?php require APP_ROOT . '/views/partials/product_card.php'; ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
