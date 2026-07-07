<?php
/**
 * httpdocs/app/views/product.php
 * 商品詳細ページとして画像ギャラリー、SPEC、問い合わせ導線を描画します。
 */
$mainImage = $images[0]['path'] ?? null;
?>
<section class="section product-layout">
    <div>
        <img class="gallery-main" data-gallery-main src="<?= e(media_url($mainImage)) ?>" alt="<?= e(localized($product, 'name')) ?>">
        <?php if ($images): ?>
            <div class="gallery-thumbs">
                <?php foreach ($images as $image): ?>
                    <button type="button" data-gallery-thumb data-src="<?= e(media_url($image['path'])) ?>">
                        <img src="<?= e(media_url($image['path'])) ?>" alt="<?= e(localized($image, 'alt', localized($product, 'name'))) ?>" loading="lazy">
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div>
        <p class="eyebrow"><?= e($product['category_name_' . current_locale()] ?? $product['category_name_ja'] ?? '') ?></p>
        <h1><?= e(localized($product, 'name')) ?></h1>
        <?php if (localized($product, 'model_year') !== ''): ?>
            <p class="muted"><?= e(localized($product, 'model_year')) ?></p>
        <?php endif; ?>
        <?php if (localized($product, 'summary') !== ''): ?>
            <p><?= nl2br(e(localized($product, 'summary'))) ?></p>
        <?php endif; ?>
        <div class="actions">
            <a class="button" href="<?= e(url('/contact?product_id=' . $product['id'])) ?>"><?= e(t('この商品について問い合わせる', 'Ask about this product')) ?></a>
            <a class="button secondary" href="<?= e(url('/products')) ?>"><?= e(t('製品一覧へ', 'Back to products')) ?></a>
        </div>

        <?php if ($specs): ?>
            <table class="spec-table">
                <tbody>
                <?php foreach ($specs as $spec): ?>
                    <tr>
                        <th><?= e(localized($spec, 'label')) ?></th>
                        <td><?= nl2br(e(localized($spec, 'value'))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php if (localized($product, 'notes') !== ''): ?>
            <p class="muted"><?= nl2br(e(localized($product, 'notes'))) ?></p>
        <?php endif; ?>
    </div>
</section>
