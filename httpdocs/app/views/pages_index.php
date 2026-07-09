<?php
/**
 * httpdocs/app/views/pages_index.php
 * CMSで公開中の固定ページを一覧表示し、未ナビゲーションページにも公開導線を作ります。
 */
?>
<section class="section container">
    <div class="section-head">
        <div>
            <p class="eyebrow">AERO TECH JAPAN</p>
            <h1><?= e(t('サイトマップ', 'Sitemap')) ?></h1>
        </div>
    </div>
    <?php if ($pages): ?>
        <div class="page-card-grid row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
            <?php foreach ($pages as $page): ?>
                <?php
                $plainBody = html_entity_decode(strip_tags(localized($page, 'body')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $body = trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $plainBody) ?? '');
                $excerpt = mb_strlen($body) > 120 ? mb_substr($body, 0, 120) . '...' : $body;
                ?>
                <article class="col">
                    <a class="page-card card-link" href="<?= e(public_page_url($page)) ?>">
                        <div class="card-body">
                            <p class="card-meta"><?= e($page['slug']) ?></p>
                            <h2 class="card-title"><?= e(localized($page, 'title')) ?></h2>
                            <?php if ($excerpt !== ''): ?>
                                <p class="muted"><?= e($excerpt) ?></p>
                            <?php endif; ?>
                        </div>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="muted"><?= e(t('公開中の固定ページはありません。', 'No published pages are available.')) ?></p>
    <?php endif; ?>
</section>
