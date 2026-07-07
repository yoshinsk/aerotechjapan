<?php
/**
 * httpdocs/app/views/contact_done.php
 * 問い合わせ送信完了画面を描画します。
 */
?>
<section class="section narrow">
    <p class="eyebrow">Contact</p>
    <h1><?= e(t('送信しました', 'Sent')) ?></h1>
    <p class="muted"><?= e(t('内容を確認の上、担当者よりご連絡いたします。', 'We will review your message and respond as soon as possible.')) ?></p>
    <a class="button" href="<?= e(url('/')) ?>"><?= e(t('トップへ戻る', 'Back to top')) ?></a>
</section>
