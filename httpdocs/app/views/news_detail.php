<?php
/**
 * httpdocs/app/views/news_detail.php
 * ニュース詳細を公開画面へ描画します。
 */
?>
<article class="section narrow container">
    <p class="eyebrow"><?= e(date('Y.m.d', strtotime($post['published_at']))) ?></p>
    <h1><?= e(localized($post, 'title')) ?></h1>
    <?php if (!empty($post['image_path']) && is_image_path($post['image_path'])): ?>
        <p><img class="gallery-main" src="<?= e(media_url($post['image_path'])) ?>" alt="<?= e(localized($post, 'title')) ?>"></p>
    <?php elseif (!empty($post['image_path'])): ?>
        <p><a class="button secondary" href="<?= e(media_url($post['image_path'])) ?>" target="_blank" rel="noopener"><?= e(t('資料を開く', 'Open document')) ?></a></p>
    <?php endif; ?>
    <div><?= nl2br(e(localized($post, 'body'))) ?></div>
</article>
