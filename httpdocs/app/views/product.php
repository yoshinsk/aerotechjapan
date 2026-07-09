<?php
/**
 * httpdocs/app/views/product.php
 * 商品詳細ページとして画像ギャラリー、SPEC、問い合わせ導線を描画します。
 */
$mainImage = $images[0] ?? [];
?>
<section class="section product-layout container-fluid row g-4">
    <div class="col-12 col-lg-6 product-media">
        <img class="gallery-main" data-gallery-main src="<?= e(image_variant_url($mainImage, 'large')) ?>" alt="<?= e(localized($product, 'name')) ?>">
        <?php if ($images): ?>
            <div class="gallery-thumbs row row-cols-4 row-cols-md-6 g-2">
                <?php foreach ($images as $image): ?>
                    <div class="col">
                        <button type="button" data-gallery-thumb data-src="<?= e(image_variant_url($image, 'large')) ?>">
                            <img src="<?= e(image_variant_url($image, 'thumb')) ?>" alt="<?= e(localized($image, 'alt', localized($product, 'name'))) ?>" loading="lazy">
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-12 col-lg-6 product-detail">
        <p class="eyebrow"><?= e($product['category_name_' . current_locale()] ?? $product['category_name_ja'] ?? '') ?></p>
        <h1><?= e(localized($product, 'name')) ?></h1>
        <?php if (localized($product, 'model_year') !== ''): ?>
            <p class="muted"><?= e(localized($product, 'model_year')) ?></p>
        <?php endif; ?>
        <?php if (localized($product, 'summary') !== ''): ?>
            <div class="product-summary rich-content"><?= render_rich_text(localized($product, 'summary')) ?></div>
        <?php endif; ?>
        <div class="actions">
            <a class="button" href="<?= e(url('/contact?product_id=' . $product['id'])) ?>"><?= e(t('この商品について問い合わせる', 'Ask about this product')) ?></a>
            <a class="button secondary" href="<?= e(url('/products')) ?>"><?= e(t('製品一覧へ', 'Back to products')) ?></a>
        </div>

        <?php if ($specs): ?>
            <div class="spec-table-wrap">
                <table class="spec-table table table-dark table-hover">
                    <colgroup>
                        <col class="spec-label-col">
                        <col class="spec-value-col">
                    </colgroup>
                    <tbody>
                    <?php foreach ($specs as $spec): ?>
                        <tr>
                            <th><div class="spec-cell-rich"><?= render_rich_text(localized($spec, 'label')) ?></div></th>
                            <td><div class="spec-cell-rich"><?= render_rich_text(localized($spec, 'value')) ?></div></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if (localized($product, 'notes') !== ''): ?>
            <div class="product-notes muted rich-content"><?= render_rich_text(localized($product, 'notes')) ?></div>
        <?php endif; ?>
    </div>
</section>
