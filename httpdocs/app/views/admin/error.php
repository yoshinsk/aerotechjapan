<?php
/**
 * httpdocs/app/views/admin/error.php
 * 管理画面のエラー表示を行います。
 */
?>
<section class="admin-panel">
    <h1>エラー</h1>
    <p><?= e($message ?? '不明なエラーです。') ?></p>
    <a class="button" href="<?= e(url('/admin')) ?>">戻る</a>
</section>
