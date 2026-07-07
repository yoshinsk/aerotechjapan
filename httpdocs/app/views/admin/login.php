<?php
/**
 * httpdocs/app/views/admin/login.php
 * 管理画面ログインフォームを描画します。
 */
?>
<section class="admin-panel" style="width:min(420px, 100%);">
    <h1>AERO TECH CMS</h1>
    <?php if ($error): ?>
        <div class="error-box"><?= e($error) ?></div>
    <?php endif; ?>
    <form class="admin-form" method="post" action="<?= e(url('/admin/login')) ?>">
        <?= csrf_field() ?>
        <label>メールアドレス
            <input type="email" name="email" required autocomplete="username">
        </label>
        <label>パスワード
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button class="button" type="submit">ログイン</button>
    </form>
</section>
