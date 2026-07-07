<?php
/**
 * httpdocs/app/views/news.php
 * ニュース一覧を公開画面へ描画します。
 */
?>
<section class="section narrow">
    <p class="eyebrow">News</p>
    <h1><?= e(t('ニュース', 'News')) ?></h1>
</section>
<section class="section">
    <div class="grid">
        <?php foreach ($posts as $post): ?>
            <article class="news-card">
                <?php if (!empty($post['image_path']) && is_image_path($post['image_path'])): ?>
                    <img src="<?= e(media_url($post['image_path'])) ?>" alt="<?= e(localized($post, 'title')) ?>" loading="lazy">
                <?php endif; ?>
                <div class="card-body">
                    <div class="card-meta"><?= e(date('Y.m.d', strtotime($post['published_at']))) ?></div>
                    <h2 class="card-title"><a href="<?= e(url('/news/' . $post['slug'])) ?>"><?= e(localized($post, 'title')) ?></a></h2>
                    <p class="muted"><?= e(excerpt(localized($post, 'body'), 120)) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
