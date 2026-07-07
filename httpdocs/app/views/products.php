<?php
/**
 * httpdocs/app/views/products.php
 * 製品一覧、カテゴリ絞り込み、キーワード検索結果を描画します。
 */
?>
<section class="section narrow">
    <p class="eyebrow">Products</p>
    <h1><?= e(($category ?? null) ? localized($category, 'name') : t('製品一覧', 'Products')) ?></h1>
    <?php if (!empty($category)): ?>
        <p class="muted"><?= e(localized($category, 'description')) ?></p>
    <?php endif; ?>
    <form class="form" action="<?= e(url('/products')) ?>" method="get">
        <div class="field">
            <label><?= e(t('キーワード検索', 'Keyword search')) ?></label>
            <input type="search" name="q" value="<?= e($keyword ?? '') ?>" placeholder="<?= e(t('車種・型式・ブランド', 'Model, chassis, brand')) ?>">
        </div>
    </form>
</section>

<section class="section">
    <div class="category-strip">
        <?php foreach ($categories as $item): ?>
            <a class="category-chip" href="<?= e(url('/category/' . $item['slug'])) ?>">
                <strong><?= e(localized($item, 'name')) ?></strong>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="section">
    <?php if (!$products): ?>
        <p class="muted"><?= e(t('該当する製品がありません。', 'No products found.')) ?></p>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($products as $product): ?>
                <?php require APP_ROOT . '/views/partials/product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
