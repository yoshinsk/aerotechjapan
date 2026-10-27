<?php
/**
 * httpdocs/app/views/admin/inquiry_detail.php
 * 問い合わせの本文と連絡先、受信情報を詳細表示します。
 */
?>
<header class="admin-page-head">
    <div>
        <p class="eyebrow">INQUIRY DETAIL</p>
        <h1><?= e($inquiry['name']) ?></h1>
        <p class="admin-help"><time datetime="<?= e($inquiry['created_at']) ?>"><?= e($inquiry['created_at']) ?></time> 受信</p>
    </div>
    <a class="btn btn-outline-light" href="<?= e(url('/admin/inquiries')) ?>">一覧へ戻る</a>
</header>
<section class="admin-panel inquiry-detail">
    <dl class="inquiry-detail-grid">
        <div><dt>商品</dt><dd><?= e($inquiry['product_name'] ?: '商品指定なし') ?></dd></div>
        <div><dt>会社名</dt><dd><?= e($inquiry['company'] ?: '-') ?></dd></div>
        <div><dt>メール</dt><dd><a href="mailto:<?= e($inquiry['email']) ?>"><?= e($inquiry['email']) ?></a></dd></div>
        <div><dt>電話</dt><dd><?= e($inquiry['phone'] ?: '-') ?></dd></div>
        <div><dt>国・地域</dt><dd><?= e($inquiry['country'] ?: '-') ?></dd></div>
    </dl>
    <section class="inquiry-message-full">
        <h2>問い合わせ内容</h2>
        <div><?= e($inquiry['message']) ?></div>
    </section>
    <details class="inquiry-technical-details">
        <summary>受信情報</summary>
        <dl class="inquiry-detail-grid">
            <div><dt>IPアドレス</dt><dd><?= e($inquiry['ip_address'] ?: '-') ?></dd></div>
            <div><dt>ユーザーエージェント</dt><dd><?= e($inquiry['user_agent'] ?: '-') ?></dd></div>
        </dl>
    </details>
    <form method="post" action="<?= e(url('/admin/inquiry-delete')) ?>" onsubmit="return confirm('この問い合わせ履歴を完全に削除します。元に戻せません。よろしいですか？');">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($inquiry['id']) ?>"><button type="submit" class="btn btn-outline-danger">この問い合わせを削除</button>
    </form>
</section>
