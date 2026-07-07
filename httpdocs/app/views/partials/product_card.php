<?php
/**
 * httpdocs/app/views/partials/product_card.php
 * 商品カードを一覧画面・トップ画面で再利用して描画します。
 */
?>
<article class="product-card">
    <a href="<?= e(url('/products/' . $product['slug'])) ?>">
        <img src="<?= e(media_url($product['main_image'] ?? null)) ?>" alt="<?= e(localized($product, 'name')) ?>" loading="lazy">
        <div class="card-body">
            <div class="card-meta"><?= e($product['category_name_' . current_locale()] ?? $product['category_name_ja'] ?? '') ?></div>
            <h3 class="card-title"><?= e(localized($product, 'name')) ?></h3>
            <?php if (localized($product, 'model_year') !== ''): ?>
                <p class="muted"><?= e(localized($product, 'model_year')) ?></p>
            <?php endif; ?>
        </div>
    </a>
</article>
