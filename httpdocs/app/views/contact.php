<?php
/**
 * httpdocs/app/views/contact.php
 * 商品指定に対応した問い合わせフォームを描画します。
 */
$input = $input ?? [];
?>
<section class="section narrow container">
    <p class="eyebrow">Contact</p>
    <h1><?= e(t('お問い合わせ', 'Contact')) ?></h1>
    <?php if ($product): ?>
        <p class="muted"><?= e(t('対象商品', 'Product')) ?>: <?= e($product['name_ja']) ?> / <?= e($product['slug']) ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="error-box"><?= e($error) ?></div>
    <?php endif; ?>
    <form class="form row g-3" method="post" action="<?= e(url('/contact')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" value="<?= e($product['id'] ?? '') ?>">
        <input type="hidden" name="form_started_at" value="<?= e((string)time()) ?>">
        <div class="hp-field">
            <label>Company website <input type="text" name="<?= e(config_value('security.honeypot_field')) ?>" tabindex="-1" autocomplete="off"></label>
        </div>
        <div class="field col-12 col-md-6">
            <label><?= e(t('お名前', 'Name')) ?> *</label>
            <input class="form-control" name="name" required value="<?= e($input['name'] ?? '') ?>">
        </div>
        <div class="field col-12 col-md-6">
            <label><?= e(t('メールアドレス', 'Email')) ?> *</label>
            <input class="form-control" type="email" name="email" required value="<?= e($input['email'] ?? '') ?>">
        </div>
        <div class="field col-12 col-md-6">
            <label><?= e(t('電話番号', 'Phone')) ?></label>
            <input class="form-control" name="phone" value="<?= e($input['phone'] ?? '') ?>">
        </div>
        <div class="field col-12 col-md-6">
            <label><?= e(t('会社名', 'Company')) ?></label>
            <input class="form-control" name="company" value="<?= e($input['company'] ?? '') ?>">
        </div>
        <div class="field col-12">
            <label><?= e(t('国・地域', 'Country / Region')) ?></label>
            <input class="form-control" name="country" value="<?= e($input['country'] ?? '') ?>">
        </div>
        <div class="field col-12">
            <label><?= e(t('お問い合わせ内容', 'Message')) ?> *</label>
            <textarea class="form-control" name="message" rows="8" required><?= e($input['message'] ?? '') ?></textarea>
        </div>
        <div class="col-12">
            <button class="button" type="submit"><?= e(t('送信する', 'Send')) ?></button>
        </div>
    </form>
</section>
