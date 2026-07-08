<?php
/**
 * httpdocs/app/views/page.php
 * CMSで編集された固定ページを公開画面へ描画します。
 */
?>
<article class="section narrow container">
    <p class="eyebrow">AERO TECH JAPAN</p>
    <h1><?= e(localized($page, 'title')) ?></h1>
    <div class="rich-content"><?= render_rich_text(localized($page, 'body')) ?></div>
</article>
