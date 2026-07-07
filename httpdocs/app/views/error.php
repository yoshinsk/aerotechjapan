<?php
/**
 * httpdocs/app/views/error.php
 * 公開サイトのエラー表示を行います。
 */
?>
<section class="section narrow">
    <p class="eyebrow">Error</p>
    <h1><?= e(t('ページを表示できません', 'Unable to display this page')) ?></h1>
    <p class="muted"><?= e($message ?? t('不明なエラーです。', 'Unknown error.')) ?></p>
    <a class="button" href="<?= e(url('/')) ?>"><?= e(t('トップへ戻る', 'Back to top')) ?></a>
</section>
